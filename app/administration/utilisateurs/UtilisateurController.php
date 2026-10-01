<?php
declare(strict_types=1);

class UtilisateurController {
    public function validate(array $data, bool $isUpdate = false): array {
        $errors = [];
        if (empty($data['nom'])) {
            $errors['nom'] = "Le nom est requis.";
        }
        if (empty($data['prenom'])) {
            $errors['prenom'] = "Le prénom est requis.";
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Email invalide.";
        }
        if (!$isUpdate && empty($data['mot_de_passe'])) {
            $errors['mot_de_passe'] = "Le mot de passe est requis.";
        }
        if (empty($data['id_role'])) {
            $errors['id_role'] = "Le rôle est requis.";
        }
        return $errors;
    }
}
