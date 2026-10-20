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
}
