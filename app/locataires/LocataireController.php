<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/LocataireModel.php';

final class LocataireController
{
    private LocataireModel $model;

    public function __construct()
    {
        $this->model = new LocataireModel();
    }

    public function getCurrentLocataire(): ?array
    {
        $user = Authorization::requireRole('Locataire');

        return $this->model->findByUserEmail(
            $user['email']
        );
    }

    public function updateCurrentLocataire(array $data): bool
    {
        $user = Authorization::requireRole('Locataire');

        return $this->model->updateByEmail(
            $user['email'],
            $data
        );
    }

    public function getCurrentHousing(): ?array
    {
        $locataire = $this->getCurrentLocataire();

        if (!$locataire) {
            return null;
        }

        return $this->model->findCurrentHousing(
            (int) $locataire['id_locataire']
        );
    }

    public function searchHousing(array $filters = []): array
    {
        Authorization::requireRole('Locataire');

        return $this->model->searchAvailableHousing($filters);
    }

    public function getHousingDetail(int $idBien): ?array
    {
        Authorization::requireRole('Locataire');

        $housing = $this->model->findAvailableHousingDetail($idBien);
        if (!$housing) {
            return null;
        }

        $housing['images'] = $this->model->findHousingImages($idBien);

        return $housing;
    }

    public function getDonneesQuittance(int $idPaiement): ?array
{
    $locataire = $this->getCurrentLocataire();

    if (!$locataire) {
        return null;
    }

    $donnees = $this->model->findDonneesQuittance(
        $idPaiement,
        (int) $locataire['id_locataire']
    );

    if (!$donnees) {
        return null;
    }

    $donnees['numero_quittance'] = sprintf(
        'QUI-%s-%06d',
        date('Y', strtotime((string) $donnees['date_paiement'])),
        $idPaiement
    );
    $donnees['date_emission'] = date('Y-m-d H:i:s');

    return $donnees;
}
}