<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/MaintenanceModel.php';

final class MaintenanceController
{
    private MaintenanceModel $model;

    public function __construct()
    {
        $this->model = new MaintenanceModel();
    }

    /**
     * Récupère le locataire actuellement connecté.
     */
    public function getCurrentLocataire(): ?array
    {
        $user = Authorization::requireRole('Locataire');

        require_once dirname(__DIR__) . '/locataires/LocataireModel.php';

        $locataireModel = new LocataireModel();

        return $locataireModel->findByUserEmail(
            $user['email']
        );
    }

    /**
     * Récupère le logement actuel du locataire.
     */
    public function getCurrentHousing(): ?array
    {
        $locataire = $this->getCurrentLocataire();

        if (!$locataire) {
            return null;
        }

        require_once dirname(__DIR__) . '/locataires/LocataireModel.php';

        $locataireModel = new LocataireModel();

        return $locataireModel->findCurrentHousing(
            (int) $locataire['id_locataire']
        );
    }

    /**
     * Récupère toutes les demandes du locataire connecté.
     */
    public function getMyRequests(): array
    {
        $locataire = $this->getCurrentLocataire();

        if (!$locataire) {
            return [];
        }

        return $this->model->getByLocataire(
            (int) $locataire['id_locataire']
        );
    }

    /**
     * Récupère une demande appartenant au locataire connecté.
     */
    public function getRequest(int $idIntervention): ?array
    {
        $locataire = $this->getCurrentLocataire();

        if (!$locataire) {
            return null;
        }

        return $this->model->findByIdForLocataire(
            $idIntervention,
            (int) $locataire['id_locataire']
        );
    }

    /**
     * Crée une nouvelle demande de maintenance.
     */
    public function createRequest(
        string $titre,
        string $description,
        string $priorite = 'MEDIUM'
    ): bool {
        $user = Authorization::requireRole('Locataire');

        $locataire = $this->getCurrentLocataire();

        if (!$locataire) {
            return false;
        }

        $housing = $this->getCurrentHousing();

        if (!$housing) {
            return false;
        }

        $titre = trim($titre);
        $description = trim($description);

        if ($titre === '' || $description === '') {
            return false;
        }

        $prioritesAutorisees = [
            'LOW',
            'MEDIUM',
            'HIGH',
            'URGENT'
        ];

        if (!in_array($priorite, $prioritesAutorisees, true)) {
            $priorite = 'MEDIUM';
        }

        $idUtilisateurCreateur = null;

        if (isset($user['id_utilisateur'])) {
            $idUtilisateurCreateur = (int) $user['id_utilisateur'];
        }

        return $this->model->create(
            (int) $locataire['id_agence'],
            (int) $housing['id_bien'],
            (int) $locataire['id_locataire'],
            $idUtilisateurCreateur,
            $titre,
            $description,
            $priorite
        );
    }

    public function getComments(int $idIntervention): array
    {
        $locataire = $this->getCurrentLocataire();

        if (!$locataire) {
            return [];
        }

        $intervention = $this->model->findByIdForLocataire(
            $idIntervention,
            (int) $locataire['id_locataire']
        );

        if (!$intervention) {
            return [];
        }

        return $this->model->getComments($idIntervention);
    }

    /**
 * Modifie une demande appartenant au locataire connecté.
 */
public function updateRequest(
    int $idIntervention,
    string $titre,
    string $description,
    string $priorite
): bool {
    $locataire = $this->getCurrentLocataire();

    if (!$locataire) {
        return false;
    }

    $titre = trim($titre);
    $description = trim($description);

    if ($titre === '' || $description === '') {
        return false;
    }

    $prioritesAutorisees = [
        'LOW',
        'MEDIUM',
        'HIGH',
        'URGENT'
    ];

    if (!in_array($priorite, $prioritesAutorisees, true)) {
        return false;
    }

    return $this->model->updateForLocataire(
        $idIntervention,
        (int) $locataire['id_locataire'],
        $titre,
        $description,
        $priorite
    );
}


/**
 * Supprime une demande appartenant au locataire connecté.
 */
public function deleteRequest(
    int $idIntervention
): bool {
        $locataire = $this->getCurrentLocataire();

        if (!$locataire) {
            return false;
        }

        return $this->model->deleteForLocataire(
            $idIntervention,
            (int) $locataire['id_locataire']
        );
    }
}

