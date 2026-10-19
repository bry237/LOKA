<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Model.php';

final class DocumentModel extends Model
{
    public function getByLocataire(int $idLocataire): array
    {
        $sql = "
            SELECT
                d.id_document,
                d.id_type_document,
                d.id_bien,
                d.id_contrat,
                d.id_proprietaire,
                d.id_locataire,
                d.id_intervention,
                d.nom,
                d.nom_original,
                d.mime_type,
                d.taille_octets,
                d.chemin_stockage,
                d.version,
                d.created_at,
                d.updated_at
            FROM document d
            WHERE d.id_locataire = :id_locataire
            ORDER BY d.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_locataire' => $idLocataire
        ]);

        return $stmt->fetchAll();
    }

    public function findByIdForLocataire(
        int $idDocument,
        int $idLocataire
    ): ?array {
        $sql = "
            SELECT
                d.id_document,
                d.id_type_document,
                d.id_bien,
                d.id_contrat,
                d.id_proprietaire,
                d.id_locataire,
                d.id_intervention,
                d.nom,
                d.nom_original,
                d.mime_type,
                d.taille_octets,
                d.chemin_stockage,
                d.version,
                d.created_at,
                d.updated_at
            FROM document d
            WHERE d.id_document = :id_document
              AND d.id_locataire = :id_locataire
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_document' => $idDocument,
            'id_locataire' => $idLocataire
        ]);

        $document = $stmt->fetch();

        return $document ?: null;
    }

    public function deleteForLocataire(
    int $idDocument,
    int $idLocataire
): ?array {
        $document = $this->findByIdForLocataire(
            $idDocument,
            $idLocataire
        );

        if (!$document) {
            return null;
        }

        $sql = "
            DELETE FROM document
            WHERE id_document = :id_document
            AND id_locataire = :id_locataire
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_document' => $idDocument,
            'id_locataire' => $idLocataire
        ]);

        return $document;
    }
}