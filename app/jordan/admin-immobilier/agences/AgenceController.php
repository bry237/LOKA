<?php
declare(strict_types=1);

class AgenceController {
    public function validate(array $data): array {
        $errors = [];
        if (empty($data['nom'])) {
            $errors['nom'] = "Le nom est requis.";
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Email invalide.";
        }
        return $errors;
    }
}
