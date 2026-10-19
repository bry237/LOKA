<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/DocumentModel.php';

final class DocumentController
{
    private DocumentModel $model;

    public function __construct()
    {
        $this->model = new DocumentModel();
    }

    public function getCurrentLocataire(): ?array
    {
        $user = Authorization::requireRole('Locataire');

        require_once dirname(__DIR__) . '/locataires/LocataireModel.php';

        $locataireModel = new LocataireModel();

        return $locataireModel->findByUserEmail(
            $user['email']
        );
    }

    public function getMyDocuments(): array
    {
        $locataire = $this->getCurrentLocataire();

        if (!$locataire) {
            return [];
        }

        return $this->model->getByLocataire(
            (int) $locataire['id_locataire']
        );
    }

    public function getDocument(int $idDocument): ?array
    {
        $locataire = $this->getCurrentLocataire();

        if (!$locataire) {
            return null;
        }

        return $this->model->findByIdForLocataire(
            $idDocument,
            (int) $locataire['id_locataire']
        );
    }

    public function deleteDocument(int $idDocument): ?array
    {
        $locataire = $this->getCurrentLocataire();

        if (!$locataire) {
            return null;
        }

        return $this->model->deleteForLocataire(
            $idDocument,
            (int) $locataire['id_locataire']
        );
    }
}