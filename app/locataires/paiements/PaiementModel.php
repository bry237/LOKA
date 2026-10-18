<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Model.php';

final class PaiementModel extends Model
{
    /**
     * Récupère toutes les échéances du locataire.
     */
    public function getEcheancesByLocataire(int $idLocataire): array
    {
        $sql = "
            SELECT
                e.id_echeance,
                e.id_contrat,
                e.periode_debut,
                e.periode_fin,
                e.montant_loyer,
                e.montant_charges,
                e.montant_total,
                e.date_echeance,
                e.statut,

                c.numero AS numero_contrat,
                c.loyer AS loyer_contrat,
                c.charges AS charges_contrat,

                b.id_bien,
                b.reference AS reference_bien,
                b.titre AS titre_bien

            FROM echeance e

            INNER JOIN contrat c
                ON c.id_contrat = e.id_contrat

            INNER JOIN contrat_locataire cl
                ON cl.id_contrat = c.id_contrat

            INNER JOIN bien b
                ON b.id_bien = c.id_bien

            WHERE cl.id_locataire = :id_locataire

            ORDER BY
                e.date_echeance DESC,
                e.id_echeance DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_locataire' => $idLocataire
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Récupère une échéance précise
     * uniquement si elle appartient au locataire connecté.
     */
    public function getEcheanceByIdForLocataire(
        int $idEcheance,
        int $idLocataire
    ): ?array {
        $sql = "
            SELECT
                e.id_echeance,
                e.id_contrat,
                e.periode_debut,
                e.periode_fin,
                e.montant_loyer,
                e.montant_charges,
                e.montant_total,
                e.date_echeance,
                e.statut,

                c.numero AS numero_contrat,
                c.loyer AS loyer_contrat,
                c.charges AS charges_contrat,

                b.id_bien,
                b.reference AS reference_bien,
                b.titre AS titre_bien

            FROM echeance e

            INNER JOIN contrat c
                ON c.id_contrat = e.id_contrat

            INNER JOIN contrat_locataire cl
                ON cl.id_contrat = c.id_contrat

            INNER JOIN bien b
                ON b.id_bien = c.id_bien

            WHERE
                e.id_echeance = :id_echeance
                AND cl.id_locataire = :id_locataire

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_echeance' => $idEcheance,
            ':id_locataire' => $idLocataire
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result !== false ? $result : null;
    }


    /**
     * Récupère tous les paiements du locataire.
     */
    public function getPaiementsByLocataire(int $idLocataire): array
    {
        $sql = "
            SELECT
                p.id_paiement,
                p.id_echeance,
                p.id_contrat,
                p.id_locataire,
                p.montant,
                p.date_paiement,
                p.mode_paiement,
                p.reference_transaction,
                p.statut,
                p.commentaire,
                p.created_at,
                p.updated_at,

                e.periode_debut,
                e.periode_fin,
                e.date_echeance,
                e.montant_total AS montant_echeance,

                c.numero AS numero_contrat,

                b.reference AS reference_bien,
                b.titre AS titre_bien

            FROM paiement p

            INNER JOIN echeance e
                ON e.id_echeance = p.id_echeance

            INNER JOIN contrat c
                ON c.id_contrat = p.id_contrat

            INNER JOIN bien b
                ON b.id_bien = c.id_bien

            WHERE p.id_locataire = :id_locataire

            ORDER BY
                p.date_paiement DESC,
                p.id_paiement DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_locataire' => $idLocataire
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Récupère un paiement précis
     * uniquement s'il appartient au locataire connecté.
     */
    public function getPaiementByIdForLocataire(
        int $idPaiement,
        int $idLocataire
    ): ?array {
        $sql = "
            SELECT
                p.id_paiement,
                p.id_echeance,
                p.id_contrat,
                p.id_locataire,
                p.montant,
                p.date_paiement,
                p.mode_paiement,
                p.reference_transaction,
                p.statut,
                p.commentaire,
                p.created_at,
                p.updated_at,

                e.periode_debut,
                e.periode_fin,
                e.date_echeance,
                e.montant_total AS montant_echeance,

                c.numero AS numero_contrat,

                b.reference AS reference_bien,
                b.titre AS titre_bien

            FROM paiement p

            INNER JOIN echeance e
                ON e.id_echeance = p.id_echeance

            INNER JOIN contrat c
                ON c.id_contrat = p.id_contrat

            INNER JOIN bien b
                ON b.id_bien = c.id_bien

            WHERE
                p.id_paiement = :id_paiement
                AND p.id_locataire = :id_locataire

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_paiement' => $idPaiement,
            ':id_locataire' => $idLocataire
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result !== false ? $result : null;
    }


    /**
     * Calcule le montant déjà payé pour une échéance.
     *
     * Seuls les paiements PAID sont considérés comme réellement encaissés.
     */
    public function getMontantPayePourEcheance(
        int $idEcheance,
        int $idLocataire
    ): float {
        $sql = "
            SELECT
                COALESCE(SUM(p.montant), 0) AS montant_paye

            FROM paiement p

            WHERE
                p.id_echeance = :id_echeance
                AND p.id_locataire = :id_locataire
                AND p.statut = 'PAID'
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_echeance' => $idEcheance,
            ':id_locataire' => $idLocataire
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (float) ($result['montant_paye'] ?? 0);
    }


    /**
     * Calcule le montant restant à payer pour une échéance.
     */
    public function getMontantRestant(
        int $idEcheance,
        int $idLocataire
    ): float {
        $echeance = $this->getEcheanceByIdForLocataire(
            $idEcheance,
            $idLocataire
        );

        if ($echeance === null) {
            return 0.0;
        }

        $montantTotal = (float) $echeance['montant_total'];

        $montantPaye = $this->getMontantPayePourEcheance(
            $idEcheance,
            $idLocataire
        );

        $restant = $montantTotal - $montantPaye;

        return max(0.0, $restant);
    }


    /**
     * Retourne le nombre de paiements réellement effectués.
     */
    public function countPaiementsByLocataire(int $idLocataire): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM paiement
            WHERE
                id_locataire = :id_locataire
                AND statut = 'PAID'
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_locataire' => $idLocataire
        ]);

        return (int) $stmt->fetchColumn();
    }


    /**
     * Retourne le montant total réellement payé.
     */
    public function getTotalPayeByLocataire(int $idLocataire): float
    {
        $sql = "
            SELECT
                COALESCE(SUM(montant), 0)
            FROM paiement
            WHERE
                id_locataire = :id_locataire
                AND statut = 'PAID'
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id_locataire' => $idLocataire
        ]);

        return (float) $stmt->fetchColumn();
    }


        /* ------------------------------------------------------------------
     * Paiement par carte via Stripe Checkout
     *
     * La table paiement n'a pas de colonne dédiée à Stripe : l'identifiant de la
     * session Checkout (cs_...) est stocké dans reference_transaction, ce qui
     * permet de retrouver le paiement depuis le webhook ou le retour de Stripe.
     * L'identifiant du PaymentIntent est ajouté au commentaire une fois payé.
     * ------------------------------------------------------------------ */

    /**
     * Crée un paiement CARD en attente (PENDING) pour une échéance.
     * Le montant vient toujours du serveur (montant restant), jamais du navigateur.
     */
    public function createPaiementCartePending(
        int $idEcheance,
        int $idContrat,
        int $idLocataire,
        float $montant
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO paiement
                (id_echeance, id_contrat, id_locataire, montant, mode_paiement, statut, commentaire)
            VALUES
                (:id_echeance, :id_contrat, :id_locataire, :montant, 'CARD', 'PENDING', 'Paiement par carte (Stripe)')
        ");

        $stmt->execute([
            ':id_echeance' => $idEcheance,
            ':id_contrat' => $idContrat,
            ':id_locataire' => $idLocataire,
            ':montant' => number_format($montant, 2, '.', ''),
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Associe la session Checkout Stripe à un paiement.
     */
    public function setSessionStripe(int $idPaiement, string $sessionId): void
    {
        $stmt = $this->db->prepare("
            UPDATE paiement
            SET reference_transaction = :session_id
            WHERE id_paiement = :id_paiement
        ");

        $stmt->execute([
            ':session_id' => $sessionId,
            ':id_paiement' => $idPaiement,
        ]);
    }

    /**
     * Paiements Stripe encore en attente pour une échéance (sessions Checkout ouvertes).
     */
    public function getPaiementsStripePending(int $idEcheance, int $idLocataire): array
    {
        $stmt = $this->db->prepare("
            SELECT id_paiement, reference_transaction
            FROM paiement
            WHERE id_echeance = :id_echeance
              AND id_locataire = :id_locataire
              AND mode_paiement = 'CARD'
              AND statut = 'PENDING'
        ");

        $stmt->execute([
            ':id_echeance' => $idEcheance,
            ':id_locataire' => $idLocataire,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retrouve un paiement à partir de l'identifiant de session Checkout.
     */
    public function findPaiementBySession(string $sessionId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM paiement
            WHERE reference_transaction = :session_id
              AND mode_paiement = 'CARD'
            LIMIT 1
        ");

        $stmt->execute([':session_id' => $sessionId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result !== false ? $result : null;
    }

    /**
     * Passe un paiement en CANCELLED (session expirée, abandonnée ou refusée).
     * Ne touche jamais un paiement déjà encaissé.
     */
    public function annulerPaiement(int $idPaiement, string $motif): void
    {
        $stmt = $this->db->prepare("
            UPDATE paiement
            SET statut = 'CANCELLED',
                commentaire = :motif
            WHERE id_paiement = :id_paiement
              AND statut = 'PENDING'
        ");

        $stmt->execute([
            ':motif' => $motif,
            ':id_paiement' => $idPaiement,
        ]);
    }

    /**
     * Marque un paiement Stripe comme encaissé, met à jour le statut de l'échéance
     * et émet la quittance. Idempotent : peut être appelé par le webhook ET par le
     * retour de Stripe, quel que soit l'ordre d'arrivée, sans double comptabilisation.
     *
     * @return bool true si le paiement vient d'être finalisé, false s'il l'était déjà.
     */
    public function finaliserPaiementStripe(int $idPaiement, string $paymentIntentId): bool
    {
        $this->db->beginTransaction();

        try {
            // Verrou sur la ligne : empêche le webhook et le retour de finaliser en parallèle.
            $stmt = $this->db->prepare("
                SELECT id_paiement, id_echeance, statut
                FROM paiement
                WHERE id_paiement = :id_paiement
                FOR UPDATE
            ");
            $stmt->execute([':id_paiement' => $idPaiement]);
            $paiement = $stmt->fetch(PDO::FETCH_ASSOC);

            // Un paiement CANCELLED peut quand même être encaissé (le locataire a payé
            // sur une session ouverte avant son annulation) : l'argent est bien reçu.
            if (!$paiement || !in_array($paiement['statut'], ['PENDING', 'CANCELLED'], true)) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare("
                UPDATE paiement
                SET statut = 'PAID',
                    date_paiement = NOW(),
                    commentaire = :commentaire
                WHERE id_paiement = :id_paiement
            ");
            $stmt->execute([
                ':commentaire' => 'Paiement par carte (Stripe) ' . $paymentIntentId,
                ':id_paiement' => $idPaiement,
            ]);

            $this->recalculerStatutEcheance((int) $paiement['id_echeance']);
            $this->creerQuittance($idPaiement);

            $this->db->commit();

            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    /**
     * Recalcule le statut d'une échéance d'après la somme des paiements PAID.
     */
    private function recalculerStatutEcheance(int $idEcheance): void
    {
        $stmt = $this->db->prepare("
            SELECT e.montant_total,
                   COALESCE((
                       SELECT SUM(p.montant)
                       FROM paiement p
                       WHERE p.id_echeance = e.id_echeance
                         AND p.statut = 'PAID'
                   ), 0) AS montant_paye
            FROM echeance e
            WHERE e.id_echeance = :id_echeance
        ");
        $stmt->execute([':id_echeance' => $idEcheance]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return;
        }

        $paye = (float) $row['montant_paye'];
        $total = (float) $row['montant_total'];

        if ($paye >= $total) {
            $statut = 'PAID';
        } elseif ($paye > 0) {
            $statut = 'PARTIAL';
        } else {
            return;
        }

        $stmt = $this->db->prepare("
            UPDATE echeance
            SET statut = :statut
            WHERE id_echeance = :id_echeance
              AND statut <> 'CANCELLED'
        ");
        $stmt->execute([
            ':statut' => $statut,
            ':id_echeance' => $idEcheance,
        ]);
    }

    /**
     * Émet la quittance d'un paiement encaissé (une seule par paiement).
     */
    private function creerQuittance(int $idPaiement): void
    {
        $stmt = $this->db->prepare("SELECT 1 FROM quittance WHERE id_paiement = :id_paiement LIMIT 1");
        $stmt->execute([':id_paiement' => $idPaiement]);
        if ($stmt->fetchColumn()) {
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO quittance (id_paiement, numero, periode_debut, periode_fin)
            SELECT p.id_paiement,
                   CONCAT('Q-', YEAR(NOW()), '-', LPAD(p.id_paiement, 6, '0')),
                   e.periode_debut,
                   e.periode_fin
            FROM paiement p
            INNER JOIN echeance e ON e.id_echeance = p.id_echeance
            WHERE p.id_paiement = :id_paiement
        ");
        $stmt->execute([':id_paiement' => $idPaiement]);
    }


    /**
 * Récupère le locataire à partir de l'adresse e-mail.
 */
public function findLocataireByEmail(string $email): ?array
    {
        $sql = "
            SELECT *
            FROM locataire
            WHERE LOWER(email) = LOWER(:email)
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':email' => $email
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result !== false ? $result : null;
    }
}