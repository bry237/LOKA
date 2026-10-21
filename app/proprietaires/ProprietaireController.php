<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/ProprietaireModel.php';

final class ProprietaireController
{
    private ProprietaireModel $model;
    private array $user;
    private ?array $proprietaire = null;

    public function __construct()
    {
        $this->user = Authorization::requireRole('Proprietaire');
        $this->model = new ProprietaireModel();
    }

    public function getCurrentProprietaire(): ?array
    {
        if ($this->proprietaire === null) {
            $this->proprietaire = $this->model->findByUserId((int) $this->user['id_utilisateur']);
        }

        return $this->proprietaire;
    }

    public function getUser(): array
    {
        return $this->user;
    }

    public function idProprietaire(): int
    {
        $proprietaire = $this->getCurrentProprietaire();
        if (!$proprietaire) {
            http_response_code(403);
            exit('Aucun dossier propriétaire n’est associé à ce compte.');
        }

        return (int) $proprietaire['id_proprietaire'];
    }

    public function getStats(): array
    {
        return $this->model->getStats($this->idProprietaire());
    }

    public function getDerniersPaiements(int $limit = 5): array
    {
        return $this->model->getDerniersPaiements($this->idProprietaire(), $limit);
    }

    public function getMesBiens(): array
    {
        return $this->model->getMesBiens($this->idProprietaire());
    }

    public function getRevenusMensuels(): array
    {
        return $this->model->getRevenusMensuels($this->idProprietaire());
    }

    public function getDossierVerification(): array
    {
        return $this->model->getDossierVerification($this->idProprietaire());
    }

    public function getInterventions(): array
    {
        return $this->model->getInterventions($this->idProprietaire());
    }

    public function getBiensPourSignalement(): array
    {
        return $this->model->getBiensPourSignalement($this->idProprietaire());
    }

    public function getContrats(): array
    {
        return $this->model->getContrats($this->idProprietaire());
    }

    public function getContratDetail(int $idContrat): ?array
    {
        return $this->model->getContratDetail($idContrat, $this->idProprietaire());
    }

    public function getQuittancesByContrat(int $idContrat): array
    {
        return $this->model->getQuittancesByContrat($idContrat, $this->idProprietaire());
    }

    public function getQuittances(): array
    {
        return $this->model->getQuittances($this->idProprietaire());
    }

    public function getDonneesQuittance(int $idPaiement): ?array
    {
        return $this->model->getDonneesQuittance($idPaiement, $this->idProprietaire());
    }

    public function signalerIncident(int $idBien, string $titre, string $description, string $priorite): int
    {
        return $this->model->creerSignalement(
            $this->idProprietaire(),
            (int) $this->user['id_utilisateur'],
            $idBien,
            $titre,
            $description,
            $priorite
        );
    }

    public function getLocataires(): array
    {
        return $this->model->getLocataires($this->idProprietaire());
    }

    public function getLocataireDetail(int $idLocataire): ?array
    {
        return $this->model->getLocataireDetail($idLocataire, $this->idProprietaire());
    }

    public function getContratsLocataire(int $idLocataire): array
    {
        return $this->model->getContratsLocataire($idLocataire, $this->idProprietaire());
    }

    public function getPaiements(): array
    {
        return $this->model->getPaiements($this->idProprietaire());
    }

    public function getTypesDocuments(): array
    {
        return $this->model->getTypesDocuments();
    }

    public function getDocumentsStats(): array
    {
        return $this->model->getDocumentsStats($this->idProprietaire());
    }

    public function getDocuments(array $filtres = []): array
    {
        return $this->model->getDocuments($this->idProprietaire(), $filtres);
    }

    public function getLocatairesDeMesBiens(): array
    {
        return $this->model->getLocatairesDeMesBiens($this->idProprietaire());
    }

    /**
     * @param array{id_type_document:string, id_bien:string, id_contrat:string, id_locataire:string} $input
     * @return array{0: array<string,string>, 1: ?int} [erreurs, id_document créé]
     */
    public function creerDocument(array $input, ?array $file): array
    {
        require_once dirname(__DIR__, 2) . '/core/UploadHelper.php';

        $errors = [];
        $idTypeDocument = (int) ($input['id_type_document'] ?? 0);
        if ($idTypeDocument < 1) {
            $errors['id_type_document'] = 'Choisissez un type de document.';
        }

        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors['fichier'] = 'Sélectionnez un fichier.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['fichier'] = 'Le téléversement du fichier a échoué.';
        } elseif ((int) $file['size'] > 10 * 1024 * 1024) {
            $errors['fichier'] = 'Le fichier ne doit pas dépasser 10 Mo.';
        } elseif ((string) $file['tmp_name'] === '' || !is_file((string) $file['tmp_name'])) {
            $errors['fichier'] = 'Le fichier envoyé est invalide.';
        }

        $allowedMimes = [
            'application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        ];
        $mime = null;
        if (empty($errors) && $file !== null) {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
            if (!isset($allowedMimes[$mime])) {
                $errors['fichier'] = 'Format non autorisé. Utilisez PDF, JPG, PNG, DOC(X) ou XLS(X).';
            }
        }

        if (!empty($errors)) {
            return [$errors, null];
        }

        $directory = dirname(__DIR__, 2) . '/storage/documents';
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            return [['general' => 'Le dossier de stockage des documents est indisponible.'], null];
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $allowedMimes[$mime];
        $destination = $directory . '/' . $storedName;
        if (!UploadHelper::store($file, $destination)) {
            return [['general' => 'Impossible d’enregistrer le fichier envoyé.'], null];
        }

        try {
            $idDocument = $this->model->creerDocument($this->idProprietaire(), [
                'id_type_document' => $idTypeDocument,
                'id_bien' => !empty($input['id_bien']) ? (int) $input['id_bien'] : null,
                'id_contrat' => !empty($input['id_contrat']) ? (int) $input['id_contrat'] : null,
                'id_locataire' => !empty($input['id_locataire']) ? (int) $input['id_locataire'] : null,
                'nom' => basename((string) $file['name']),
                'nom_original' => basename((string) $file['name']),
                'mime_type' => (string) $mime,
                'taille_octets' => (int) $file['size'],
                'chemin_stockage' => 'storage/documents/' . $storedName,
            ]);

            return [[], $idDocument];
        } catch (\Throwable $e) {
            if (is_file($destination)) {
                unlink($destination);
            }

            return [['general' => $e->getMessage()], null];
        }
    }

    public function getTotalRevenus(): float
    {
        return $this->model->getTotalRevenus($this->idProprietaire());
    }

    public function getRevenusPeriode(?string $dateDebutSql, ?string $dateFinSql): array
    {
        return $this->model->getRevenusPeriode($this->idProprietaire(), $dateDebutSql, $dateFinSql);
    }

    public function getHistoriqueRevenus(?string $dateDebutSql, ?string $dateFinSql): array
    {
        return $this->model->getHistoriqueRevenus($this->idProprietaire(), $dateDebutSql, $dateFinSql);
    }

    public function getRevenusMensuelsAnnee(int $annee): array
    {
        return $this->model->getRevenusMensuelsAnnee($this->idProprietaire(), $annee);
    }

    public function updateProfil(array $data): ?string
    {
        $erreur = $this->model->updateProfil($this->idProprietaire(), (int) $this->user['id_utilisateur'], $data);
        if ($erreur === null) {
            $this->proprietaire = null;
        }

        return $erreur;
    }

    public function updateMotDePasse(string $ancien, string $nouveau, string $confirmation): ?string
    {
        return $this->model->updateMotDePasse((int) $this->user['id_utilisateur'], $ancien, $nouveau, $confirmation);
    }

    public function getMesBiensPourContrat(): array
    {
        return $this->model->getMesBiensPourContrat($this->idProprietaire());
    }

    public function getLocatairesDeLAgence(): array
    {
        $proprietaire = $this->getCurrentProprietaire();
        $idAgence = (int) ($proprietaire['id_agence'] ?? 0);
        if (!$idAgence) {
            return [];
        }

        return $this->model->getLocatairesDeLAgence($idAgence);
    }

    /** @return array{0: array<string,string>, 1: ?int} [erreurs, id_contrat créé] */
    public function creerContrat(array $data): array
    {
        $errors = [];

        $idBien = (int) ($data['id_bien'] ?? 0);
        $idLocataire = (int) ($data['id_locataire'] ?? 0);
        $dateDebut = trim((string) ($data['date_debut'] ?? ''));
        $dateFin = trim((string) ($data['date_fin'] ?? ''));
        $loyer = (float) ($data['loyer'] ?? 0);
        $charges = (float) ($data['charges'] ?? 0);
        $depot = (float) ($data['depot_garantie'] ?? 0);
        $frequence = (string) ($data['frequence_paiement'] ?? 'MONTHLY');
        $jourEcheance = (int) ($data['jour_echeance'] ?? 5);
        $typeContrat = (string) ($data['type_contrat'] ?? 'HABITATION_VIDE');

        if ($idBien < 1) {
            $errors['id_bien'] = 'Choisissez un bien.';
        }
        if ($idLocataire < 1) {
            $errors['id_locataire'] = 'Choisissez un locataire.';
        }
        if ($dateDebut === '' || \DateTime::createFromFormat('Y-m-d', $dateDebut) === false) {
            $errors['date_debut'] = 'La date de début est invalide.';
        }
        if ($dateFin !== '' && \DateTime::createFromFormat('Y-m-d', $dateFin) === false) {
            $errors['date_fin'] = 'La date de fin est invalide.';
        }
        if ($loyer <= 0) {
            $errors['loyer'] = 'Le loyer doit être supérieur à 0.';
        }
        if (!in_array($frequence, ['MONTHLY', 'QUARTERLY', 'YEARLY'], true)) {
            $errors['frequence_paiement'] = 'Fréquence de paiement invalide.';
        }
        if ($jourEcheance < 1 || $jourEcheance > 28) {
            $errors['jour_echeance'] = 'Le jour d’échéance doit être entre 1 et 28.';
        }
        if (!in_array($typeContrat, ['HABITATION_VIDE', 'HABITATION_MEUBLE', 'COMMERCIAL', 'PROFESSIONNEL', 'SAISONNIER'], true)) {
            $errors['type_contrat'] = 'Type de contrat invalide.';
        }

        if (!empty($errors)) {
            return [$errors, null];
        }

        try {
            $idContrat = $this->model->creerContrat($this->idProprietaire(), [
                'id_bien' => $idBien,
                'id_locataire' => $idLocataire,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin !== '' ? $dateFin : null,
                'loyer' => $loyer,
                'charges' => $charges,
                'depot_garantie' => $depot,
                'frequence_paiement' => $frequence,
                'jour_echeance' => $jourEcheance,
                'type_contrat' => $typeContrat,
            ]);

            return [[], $idContrat];
        } catch (\Throwable $e) {
            return [['general' => $e->getMessage()], null];
        }
    }
}
