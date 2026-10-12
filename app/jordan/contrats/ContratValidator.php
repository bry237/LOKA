<?php declare(strict_types=1);

class ContratValidator {
    public function validate(array $input): array {
        $errors = [];

        if (empty($input['numero'])) {
            $errors['numero'] = 'Le numéro de contrat est requis.';
        }

        if (empty($input['id_bien'])) {
            $errors['id_bien'] = 'Le bien est requis.';
        } elseif (!is_numeric($input['id_bien'])) {
            $errors['id_bien'] = 'ID du bien invalide.';
        }

        if (empty($input['date_debut'])) {
            $errors['date_debut'] = 'La date de début est requise.';
        }

        if (empty($input['loyer']) && !is_numeric($input['loyer'])) {
            $errors['loyer'] = 'Le montant du loyer est requis.';
        } elseif (!is_numeric($input['loyer']) || $input['loyer'] < 0) {
            $errors['loyer'] = 'Le loyer doit être un nombre positif.';
        }

        if (!empty($input['charges']) && (!is_numeric($input['charges']) || $input['charges'] < 0)) {
            $errors['charges'] = 'Les charges doivent être un nombre positif.';
        }

        if (!empty($input['depot_garantie']) && (!is_numeric($input['depot_garantie']) || $input['depot_garantie'] < 0)) {
            $errors['depot_garantie'] = 'Le dépôt de garantie doit être un nombre positif.';
        }

        if (!empty($input['frequence_paiement']) && !in_array($input['frequence_paiement'], ['MONTHLY', 'QUARTERLY', 'YEARLY'])) {
            $errors['frequence_paiement'] = 'Fréquence de paiement invalide.';
        }

        if (!empty($input['statut']) && !in_array($input['statut'], ['DRAFT', 'ACTIVE', 'EXPIRED', 'RENEWED', 'TERMINATED', 'ARCHIVED'])) {
            $errors['statut'] = 'Statut invalide.';
        }
        
        if (!empty($input['type_contrat']) && !in_array($input['type_contrat'], ['HABITATION_VIDE', 'HABITATION_MEUBLE', 'COMMERCIAL', 'PROFESSIONNEL', 'SAISONNIER'])) {
            $errors['type_contrat'] = 'Type de contrat invalide.';
        }

        return $errors;
    }

    public function validateTenantAttachment(array $input): array {
        $errors = [];
        if (empty($input['id_locataire'])) {
            $errors['id_locataire'] = 'Le locataire est requis.';
        } elseif (!is_numeric($input['id_locataire'])) {
            $errors['id_locataire'] = 'ID locataire invalide.';
        }
        return $errors;
    }
}
