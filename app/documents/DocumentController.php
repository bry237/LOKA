<?php
declare(strict_types=1);

require_once __DIR__ . '/DocumentModel.php';
require_once __DIR__ . '/DocumentValidator.php';

class DocumentController
{
    private const UPLOAD_DIR = __DIR__ . '/../../public/uploads/documents/';

    public static function create(int $agencyId, array $post, array $files): array
    {
        $file = $files['fichier'] ?? null;
        $errors = DocumentValidator::validate($post, $file);

        if (!empty($errors)) {
            return [$errors, null];
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uniqueName = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = self::UPLOAD_DIR . $uniqueName;
        $relativePath = 'public/uploads/documents/' . $uniqueName;

        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0777, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $errors['fichier'] = 'Erreur lors de l\'enregistrement du fichier.';
            return [$errors, null];
        }

        $input = [
            'id_type_document' => (int)$post['id_type_document'],
            'id_bien' => !empty($post['id_bien']) ? (int)$post['id_bien'] : null,
            'id_contrat' => !empty($post['id_contrat']) ? (int)$post['id_contrat'] : null,
            'id_proprietaire' => !empty($post['id_proprietaire']) ? (int)$post['id_proprietaire'] : null,
            'id_locataire' => !empty($post['id_locataire']) ? (int)$post['id_locataire'] : null,
            'id_intervention' => !empty($post['id_intervention']) ? (int)$post['id_intervention'] : null,
            'nom' => trim($post['nom']),
            'nom_original' => $file['name'],
            'mime_type' => mime_content_type($destination),
            'taille_octets' => filesize($destination),
            'chemin_stockage' => $relativePath
        ];

        try {
            $id = DocumentModel::create($input);
            return [[], $id];
        } catch (Exception $e) {
            // Delete file if DB insert fails
            if (file_exists($destination)) {
                unlink($destination);
            }
            $errors['general'] = 'Une erreur est survenue lors de l\'enregistrement en base de données.';
            return [$errors, null];
        }
    }

    public static function delete(int $agencyId, int $documentId): bool
    {
        $path = DocumentModel::delete($agencyId, $documentId);
        
        if ($path) {
            $fullPath = dirname(__DIR__, 2) . '/' . $path;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            return true;
        }
        
        return false;
    }
}
