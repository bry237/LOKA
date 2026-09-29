<?php
declare(strict_types=1);

class DocumentValidator
{
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain'
    ];
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB

    public static function validate(array $input, ?array $file = null): array
    {
        $errors = [];

        if (empty($input['id_type_document']) || !is_numeric($input['id_type_document'])) {
            $errors['id_type_document'] = 'Le type de document est requis.';
        }

        if (empty($input['nom'])) {
            $errors['nom'] = 'Le nom du document est requis.';
        } elseif (mb_strlen($input['nom']) > 255) {
            $errors['nom'] = 'Le nom ne peut pas dépasser 255 caractères.';
        }

        $hasEntity = false;
        $entityFields = ['id_bien', 'id_contrat', 'id_proprietaire', 'id_locataire', 'id_intervention'];
        
        foreach ($entityFields as $field) {
            if (!empty($input[$field]) && is_numeric($input[$field])) {
                if ($hasEntity) {
                    $errors['entity'] = 'Un document ne peut être rattaché qu\'à une seule entité.';
                    break;
                }
                $hasEntity = true;
            }
        }

        if (!$hasEntity) {
            $errors['entity'] = 'Le document doit être rattaché à une entité.';
        }

        if ($file !== null) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors['fichier'] = 'Erreur lors du téléchargement du fichier (code: ' . $file['error'] . ').';
            } else {
                if ($file['size'] > self::MAX_FILE_SIZE) {
                    $errors['fichier'] = 'Le fichier ne doit pas dépasser 10 Mo.';
                }
                
                $mimeType = mime_content_type($file['tmp_name']);
                if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
                    $errors['fichier'] = 'Le type de fichier n\'est pas autorisé (pdf, jpeg, png, docx, xlsx, txt).';
                }
            }
        } elseif (!isset($input['id_document'])) {
            $errors['fichier'] = 'Le fichier est requis.';
        }

        return $errors;
    }
}
