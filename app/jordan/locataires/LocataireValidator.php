<?php declare(strict_types=1);

class LocataireValidator {
    public function validate(array $input): array {
        $errors = [];

        if (empty(trim($input['nom'] ?? ''))) {
            $errors['nom'] = 'Le nom est requis.';
        }

        if (empty(trim($input['prenom'] ?? ''))) {
            $errors['prenom'] = 'Le prénom est requis.';
        }

        if (!empty($input['email']) && !filter_var(trim($input['email']), FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L\'adresse email n\'est pas valide.';
        }

        if (!empty($input['statut']) && !in_array($input['statut'], ['ACTIVE', 'INACTIVE', 'ARCHIVED'])) {
            $errors['statut'] = 'Le statut est invalide.';
        }
        
        if (!empty($input['type_identite']) && !in_array($input['type_identite'], ['CNI', 'PASSEPORT', 'TITRE_SEJOUR', 'AUTRE'])) {
            $errors['type_identite'] = 'Le type d\'identité est invalide.';
        }
        
        if (!empty($input['situation_familiale']) && !in_array($input['situation_familiale'], ['CELIBATAIRE', 'MARIE', 'PACSE', 'DIVORCE', 'VEUF'])) {
            $errors['situation_familiale'] = 'La situation familiale est invalide.';
        }

        return $errors;
    }
}
