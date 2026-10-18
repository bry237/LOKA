<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/PaiementModel.php';
require_once __DIR__ . '/StripePaiementClient.php';

final class PaiementController
{
    private PaiementModel $model;

    public function __construct()
    {
        $this->model = new PaiementModel();
    }


    /**
     * Récupère le locataire actuellement connecté.
     */
    public function getCurrentLocataire(): array
    {
        $user = Authorization::requireRole('Locataire');

        /*
         * Si l'identifiant du locataire est déjà disponible
         * dans les données utilisateur, on l'utilise.
         */
        if (!empty($user['id_locataire'])) {
            return $user;
        }

        /*
         * Sinon, on recherche le locataire grâce
         * à son adresse e-mail.
         */
        $email = (string) ($user['email'] ?? '');

        if ($email === '') {
            throw new RuntimeException(
                'Adresse e-mail du locataire introuvable.'
            );
        }

        $locataire = $this->model->findLocataireByEmail($email);

        if ($locataire === null) {
            throw new RuntimeException(
                'Impossible de retrouver le locataire connecté.'
            );
        }

        return $locataire;
    }


    /**
     * Récupère toutes les échéances du locataire.
     */
    public function getMyEcheances(): array
    {
        $locataire = $this->getCurrentLocataire();

        return $this->model->getEcheancesByLocataire(
            (int) $locataire['id_locataire']
        );
    }


    /**
     * Récupère une échéance précise.
     */
    public function getMyEcheance(int $idEcheance): ?array
    {
        $locataire = $this->getCurrentLocataire();

        return $this->model->getEcheanceByIdForLocataire(
            $idEcheance,
            (int) $locataire['id_locataire']
        );
    }


    /**
     * Récupère l'historique des paiements.
     */
    public function getMyPaiements(): array
    {
        $locataire = $this->getCurrentLocataire();

        return $this->model->getPaiementsByLocataire(
            (int) $locataire['id_locataire']
        );
    }


    /**
     * Récupère un paiement précis.
     */
    public function getMyPaiement(int $idPaiement): ?array
    {
        $locataire = $this->getCurrentLocataire();

        return $this->model->getPaiementByIdForLocataire(
            $idPaiement,
            (int) $locataire['id_locataire']
        );
    }


    /**
     * Récupère le montant restant pour une échéance.
     */
    public function getMontantRestant(int $idEcheance): float
    {
        $locataire = $this->getCurrentLocataire();

        return $this->model->getMontantRestant(
            $idEcheance,
            (int) $locataire['id_locataire']
        );
    }


    /**
     * Nombre de paiements effectués.
     */
    public function getNombrePaiements(): int
    {
        $locataire = $this->getCurrentLocataire();

        return $this->model->countPaiementsByLocataire(
            (int) $locataire['id_locataire']
        );
    }


    /**
     * Total des paiements effectués.
     */
    public function getTotalPaye(): float
    {
        $locataire = $this->getCurrentLocataire();

        return $this->model->getTotalPayeByLocataire(
            (int) $locataire['id_locataire']
        );
    }


        /* ------------------------------------------------------------------
     * Paiement par carte via Stripe Checkout
     * ------------------------------------------------------------------ */

    /** Montant minimum accepté par Stripe pour un paiement en euros. */
    private const MONTANT_MINIMUM = 0.50;

    /**
     * Démarre un paiement Stripe Checkout pour le montant restant d'une échéance.
     *
     * @param string $successUrl URL de retour après paiement ({CHECKOUT_SESSION_ID} est
     *                           remplacé par Stripe)
     * @param string $cancelUrlBase URL de retour si le locataire abandonne ; l'identifiant
     *                              du paiement y est ajouté (paramètre p)
     * @return array{0: ?string, 1: ?string} [message d'erreur, URL de redirection Stripe]
     */
    public function startCheckout(int $idEcheance, string $successUrl, string $cancelUrlBase): array
    {
        $locataire = $this->getCurrentLocataire();
        $idLocataire = (int) $locataire['id_locataire'];

        $echeance = $this->model->getEcheanceByIdForLocataire($idEcheance, $idLocataire);
        if ($echeance === null) {
            return ['Échéance introuvable.', null];
        }

        if (in_array($echeance['statut'], ['PAID', 'CANCELLED'], true)) {
            return ['Cette échéance ne peut plus être payée.', null];
        }

        // Le montant est recalculé côté serveur : on ne fait jamais confiance au navigateur.
        $montant = $this->model->getMontantRestant($idEcheance, $idLocataire);
        if ($montant < self::MONTANT_MINIMUM) {
            return ['Aucun montant à payer pour cette échéance.', null];
        }

        // Une seule session ouverte à la fois : on expire les précédentes pour éviter un double paiement.
        foreach ($this->model->getPaiementsStripePending($idEcheance, $idLocataire) as $ancien) {
            $reference = (string) ($ancien['reference_transaction'] ?? '');
            if (str_starts_with($reference, 'cs_')) {
                StripePaiementClient::expireCheckoutSession($reference);
            }
            $this->model->annulerPaiement((int) $ancien['id_paiement'], 'Remplacé par une nouvelle tentative de paiement');
        }

        $idPaiement = $this->model->createPaiementCartePending(
            $idEcheance,
            (int) $echeance['id_contrat'],
            $idLocataire,
            $montant
        );

        $periode = date('m/Y', strtotime((string) $echeance['periode_debut']));
        $description = sprintf(
            'Loyer %s - %s (contrat %s)',
            $periode,
            (string) $echeance['titre_bien'],
            (string) $echeance['numero_contrat']
        );

        try {
            $session = StripePaiementClient::createCheckoutSession(
                (int) round($montant * 100),
                $description,
                (string) ($locataire['email'] ?? ''),
                $successUrl,
                $cancelUrlBase . (str_contains($cancelUrlBase, '?') ? '&' : '?') . 'p=' . $idPaiement,
                [
                    'id_paiement' => $idPaiement,
                    'id_echeance' => $idEcheance,
                    'id_locataire' => $idLocataire,
                ],
                'loka-paiement-' . $idPaiement
            );

            $this->model->setSessionStripe($idPaiement, $session['id']);

            return [null, $session['url']];
        } catch (Throwable $exception) {
            $this->model->annulerPaiement($idPaiement, 'Échec de création de la session Stripe');

            return ['Le paiement n’a pas pu être initié : ' . $exception->getMessage(), null];
        }
    }

    /**
     * Confirme une session Checkout directement auprès de Stripe au retour du locataire,
     * en secours du webhook (en local, Stripe ne peut pas joindre localhost sans `stripe listen`).
     * Idempotent : le même traitement sert au webhook, quel que soit l'ordre d'arrivée.
     *
     * @return 'confirmed'|'already'|'pending'|'unknown'
     */
    public function confirmCheckoutSession(string $sessionId): string
    {
        $locataire = $this->getCurrentLocataire();

        $paiement = $this->model->findPaiementBySession($sessionId);

        // Un locataire ne peut confirmer que ses propres paiements.
        if ($paiement === null || (int) $paiement['id_locataire'] !== (int) $locataire['id_locataire']) {
            return 'unknown';
        }

        if ($paiement['statut'] === 'PAID') {
            return 'already';
        }

        try {
            $session = StripePaiementClient::retrieveCheckoutSession($sessionId);
        } catch (Throwable $exception) {
            return 'pending';
        }

        if (($session['payment_status'] ?? '') !== 'paid') {
            return 'pending';
        }

        return $this->enregistrerPaiementEncaisse($paiement, $session) ? 'confirmed' : 'pending';
    }

    /**
     * Abandon par le locataire (retour via cancel_url) : annule son paiement en attente
     * et expire la session Stripe pour qu'elle ne puisse plus être payée.
     */
    public function cancelCheckout(int $idPaiement): void
    {
        $locataire = $this->getCurrentLocataire();

        $paiement = $this->model->getPaiementByIdForLocataire($idPaiement, (int) $locataire['id_locataire']);
        if ($paiement === null || $paiement['statut'] !== 'PENDING' || $paiement['mode_paiement'] !== 'CARD') {
            return;
        }

        $reference = (string) ($paiement['reference_transaction'] ?? '');
        if (str_starts_with($reference, 'cs_')) {
            StripePaiementClient::expireCheckoutSession($reference);
        }

        $this->model->annulerPaiement($idPaiement, 'Paiement abandonné par le locataire');
    }

    /**
     * Traite un événement webhook Stripe déjà authentifié (signature vérifiée par webhook.php).
     * Idempotent : Stripe réessaie tant qu'il ne reçoit pas un 2xx.
     */
    public function handleWebhookEvent(array $event): void
    {
        $type = (string) ($event['type'] ?? '');
        $session = $event['data']['object'] ?? [];
        $sessionId = (string) ($session['id'] ?? '');

        if ($sessionId === '' || empty($session['metadata']['id_paiement'])) {
            // Événement d'un autre flux (ex. abonnements d'agence) : on l'acquitte sans rien faire.
            return;
        }

        $paiement = $this->model->findPaiementBySession($sessionId);
        if ($paiement === null) {
            return;
        }

        switch ($type) {
            case 'checkout.session.completed':
            case 'checkout.session.async_payment_succeeded':
                // Avec un moyen de paiement différé, "completed" arrive avant l'encaissement
                // réel : on n'enregistre que si Stripe confirme payment_status = paid.
                if (($session['payment_status'] ?? '') === 'paid') {
                    $this->enregistrerPaiementEncaisse($paiement, $session);
                }
                break;

            case 'checkout.session.expired':
            case 'checkout.session.async_payment_failed':
                $this->model->annulerPaiement((int) $paiement['id_paiement'], 'Paiement non abouti (' . $type . ')');
                break;
        }
    }

    /**
     * Vérifie la cohérence entre la session Stripe et notre paiement, puis l'encaisse.
     */
    private function enregistrerPaiementEncaisse(array $paiement, array $session): bool
    {
        $attendu = (int) round(((float) $paiement['montant']) * 100);

        if (
            (int) ($session['amount_total'] ?? -1) !== $attendu
            || strtolower((string) ($session['currency'] ?? '')) !== 'eur'
            || (int) ($session['metadata']['id_paiement'] ?? 0) !== (int) $paiement['id_paiement']
        ) {
            throw new RuntimeException(
                'Session Stripe incohérente avec le paiement #' . (int) $paiement['id_paiement']
            );
        }

        $paymentIntent = (string) ($session['payment_intent'] ?? '');

        return $this->model->finaliserPaiementStripe((int) $paiement['id_paiement'], $paymentIntent);
    }
}