<?php declare(strict_types=1);

class PaiementValidator {
    public function validate(array $data): array {
        $errors = [];

        if (empty($data['id_contrat'])) {
            $errors['id_contrat'] = 'Le contrat est requis.';
        }
        
        if (empty($data['id_locataire'])) {
            $errors['id_locataire'] = 'Le locataire est requis.';
        }

        if (empty($data['montant']) || !is_numeric($data['montant']) || (float)$data['montant'] <= 0) {
            $errors['montant'] = 'Le montant doit être un nombre positif.';
        }

        if (empty($data['date_paiement'])) {
            $errors['date_paiement'] = 'La date de paiement est requise.';
        }

        $validModes = ['CASH', 'BANK_TRANSFER', 'CARD', 'CHEQUE', 'DIRECT_DEBIT', 'OTHER'];
        if (empty($data['mode_paiement']) || !in_array($data['mode_paiement'], $validModes)) {
            $errors['mode_paiement'] = 'Le mode de paiement est invalide.';
        }

        return $errors;
    }
}
