<?php

declare(strict_types=1);

final class DocumentValidator
{
    /**
     * Taille maximale : 10 Mo
     */
    private const MAX_SIZE = 10 * 1024 * 1024;

    /**
     * Types MIME autorisés.
     */
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',

        'image/jpeg',
        'image/png',

        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',

        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

        'text/plain'
    ];


    /**
     * Vérifie le fichier envoyé.
     *
     * @return array{
     *     valid: bool,
     *     errors: array<int, string>
     * }
     */
    public static function validate(array $file): array
    {
        $errors = [];


        /* ==============================================
           Vérification de l'upload
           ============================================== */

        if (!isset($file['error'])) {
            $errors[] = 'Aucun fichier n’a été envoyé.';

            return [
                'valid' => false,
                'errors' => $errors
            ];
        }


        if ($file['error'] !== UPLOAD_ERR_OK) {

            switch ($file['error']) {

                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = 'Le fichier est trop volumineux.';
                    break;

                case UPLOAD_ERR_PARTIAL:
                    $errors[] = 'Le téléchargement du fichier est incomplet.';
                    break;

                case UPLOAD_ERR_NO_FILE:
                    $errors[] = 'Veuillez sélectionner un fichier.';
                    break;

                default:
                    $errors[] = 'Une erreur est survenue lors du téléchargement.';
                    break;
            }

            return [
                'valid' => false,
                'errors' => $errors
            ];
        }


        /* ==============================================
           Taille
           ============================================== */

        $size = (int) ($file['size'] ?? 0);

        if ($size <= 0) {
            $errors[] = 'Le fichier est vide.';
        }

        if ($size > self::MAX_SIZE) {
            $errors[] = 'Le fichier ne doit pas dépasser 10 Mo.';
        }


        /* ==============================================
           Nom
           ============================================== */

        $originalName = trim(
            (string) ($file['name'] ?? '')
        );

        if ($originalName === '') {
            $errors[] = 'Le nom du fichier est invalide.';
        }


        /* ==============================================
           Vérification MIME réelle
           ============================================== */

        if (
            !isset($file['tmp_name'])
            || !is_uploaded_file($file['tmp_name'])
        ) {
            $errors[] = 'Le fichier envoyé est invalide.';
        } else {

            $finfo = new finfo(FILEINFO_MIME_TYPE);

            $mimeType = $finfo->file(
                $file['tmp_name']
            );

            if (
                $mimeType === false
                || !in_array(
                    $mimeType,
                    self::ALLOWED_MIME_TYPES,
                    true
                )
            ) {
                $errors[] =
                    'Ce type de fichier n’est pas autorisé.';
            }
        }


        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }


    /**
     * Retourne la taille maximale autorisée.
     */
    public static function getMaxSize(): int
    {
        return self::MAX_SIZE;
    }


    /**
     * Retourne les MIME autorisés.
     */
    public static function getAllowedMimeTypes(): array
    {
        return self::ALLOWED_MIME_TYPES;
    }
}