<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/QuittanceModel.php';

final class QuittanceController
{
    private QuittanceModel $model;

    public function __construct()
    {
        $this->model = new QuittanceModel();
    }

    /**
     * Récupère le locataire actuellement connecté.
     */
    public function getCurrentLocataire(): array
    {
        $user = Authorization::requireRole('Locataire');

        /*
         * Si l'id du locataire est déjà présent dans la session,
         * on l'utilise directement.
         */
        if (!empty($user['id_locataire'])) {
            return $user;
        }

        /*
         * Sinon, on recherche le locataire à partir
         * de son adresse e-mail.
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
     * Récupère toutes les quittances du locataire connecté.
     */
    public function getMyQuittances(): array
    {
        $locataire = $this->getCurrentLocataire();

        return $this->model->getQuittancesByLocataire(
            (int) $locataire['id_locataire']
        );
    }

    /**
     * Récupère une quittance précise du locataire connecté.
     */
    public function getMyQuittance(int $idQuittance): ?array
    {
        $locataire = $this->getCurrentLocataire();

        return $this->model->getQuittanceByIdForLocataire(
            $idQuittance,
            (int) $locataire['id_locataire']
        );
    }

    /**
     * Récupère le chemin du PDF d'une quittance
     * appartenant au locataire connecté.
     */
    public function getMyQuittancePdfPath(int $idQuittance): ?string
    {
        $locataire = $this->getCurrentLocataire();

        return $this->model->getPdfPathForLocataire(
            $idQuittance,
            (int) $locataire['id_locataire']
        );
    }

    /**
     * Retourne le nombre de quittances du locataire.
     */
    public function getNombreQuittances(): int
    {
        $locataire = $this->getCurrentLocataire();

        return $this->model->countQuittancesByLocataire(
            (int) $locataire['id_locataire']
        );
    }
}