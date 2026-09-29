<?php declare(strict_types=1);

class MaintenanceValidator
{
    public function validate(array $data): array
    {
        $errors = [];

        if (empty($data['titre'])) {
            $errors['titre'] = "Le titre est requis.";
        } elseif (strlen($data['titre']) > 255) {
            $errors['titre'] = "Le titre ne doit pas dépasser 255 caractères.";
        }

        if (empty($data['priorite'])) {
            $errors['priorite'] = "La priorité est requise.";
        } elseif (!in_array($data['priorite'], ['LOW', 'MEDIUM', 'HIGH', 'URGENT'])) {
            $errors['priorite'] = "La priorité est invalide.";
        }

        if (!empty($data['cout_estime']) && !is_numeric($data['cout_estime'])) {
            $errors['cout_estime'] = "Le coût estimé doit être un nombre.";
        }

        if (empty($data['id_bien'])) {
            $errors['id_bien'] = "Le bien est requis.";
        }

        return $errors;
    }

    public function validateClose(array $data): array
    {
        $errors = [];

        if (!empty($data['cout_final']) && !is_numeric($data['cout_final'])) {
            $errors['cout_final'] = "Le coût final doit être un nombre.";
        }

        if (empty($data['compte_rendu'])) {
            $errors['compte_rendu'] = "Le compte rendu est requis pour clôturer l'intervention.";
        }

        return $errors;
    }
}
