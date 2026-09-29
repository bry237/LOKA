<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Database.php';

class DocumentModel
{
    public static function list(int $agencyId, array $filters = []): array
    {
        $db = Database::connection();
        
        $sql = "SELECT d.*, td.nom as type_nom,
                b.nom as bien_nom, c.numero as contrat_numero, 
                p.nom as proprietaire_nom, p.prenom as proprietaire_prenom,
                l.nom as locataire_nom, l.prenom as locataire_prenom,
                i.titre as intervention_titre
                FROM document d
                JOIN type_document td ON d.id_type_document = td.id_type_document
                LEFT JOIN bien b ON d.id_bien = b.id_bien AND b.id_agence = :agency_id1
                LEFT JOIN contrat c ON d.id_contrat = c.id_contrat AND c.id_agence = :agency_id2
                LEFT JOIN proprietaire p ON d.id_proprietaire = p.id_proprietaire AND p.id_agence = :agency_id3
                LEFT JOIN locataire l ON d.id_locataire = l.id_locataire AND l.id_agence = :agency_id4
                LEFT JOIN intervention i ON d.id_intervention = i.id_intervention AND i.id_agence = :agency_id5
                WHERE (d.id_bien IS NOT NULL AND b.id_bien IS NOT NULL
                   OR d.id_contrat IS NOT NULL AND c.id_contrat IS NOT NULL
                   OR d.id_proprietaire IS NOT NULL AND p.id_proprietaire IS NOT NULL
                   OR d.id_locataire IS NOT NULL AND l.id_locataire IS NOT NULL
                   OR d.id_intervention IS NOT NULL AND i.id_intervention IS NOT NULL)";

        $params = [
            ':agency_id1' => $agencyId,
            ':agency_id2' => $agencyId,
            ':agency_id3' => $agencyId,
            ':agency_id4' => $agencyId,
            ':agency_id5' => $agencyId
        ];

        if (!empty($filters['id_type_document'])) {
            $sql .= " AND d.id_type_document = :type_id";
            $params[':type_id'] = $filters['id_type_document'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND d.nom LIKE :search";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['entity_type'])) {
            switch ($filters['entity_type']) {
                case 'bien':
                    $sql .= " AND d.id_bien IS NOT NULL";
                    break;
                case 'contrat':
                    $sql .= " AND d.id_contrat IS NOT NULL";
                    break;
                case 'proprietaire':
                    $sql .= " AND d.id_proprietaire IS NOT NULL";
                    break;
                case 'locataire':
                    $sql .= " AND d.id_locataire IS NOT NULL";
                    break;
                case 'intervention':
                    $sql .= " AND d.id_intervention IS NOT NULL";
                    break;
            }
        }

        $sql .= " ORDER BY d.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $agencyId, int $documentId): ?array
    {
        $db = Database::connection();
        
        $sql = "SELECT d.*, td.nom as type_nom,
                b.nom as bien_nom, c.numero as contrat_numero, 
                p.nom as proprietaire_nom, p.prenom as proprietaire_prenom,
                l.nom as locataire_nom, l.prenom as locataire_prenom,
                i.titre as intervention_titre
                FROM document d
                JOIN type_document td ON d.id_type_document = td.id_type_document
                LEFT JOIN bien b ON d.id_bien = b.id_bien AND b.id_agence = :agency_id1
                LEFT JOIN contrat c ON d.id_contrat = c.id_contrat AND c.id_agence = :agency_id2
                LEFT JOIN proprietaire p ON d.id_proprietaire = p.id_proprietaire AND p.id_agence = :agency_id3
                LEFT JOIN locataire l ON d.id_locataire = l.id_locataire AND l.id_agence = :agency_id4
                LEFT JOIN intervention i ON d.id_intervention = i.id_intervention AND i.id_agence = :agency_id5
                WHERE d.id_document = :id_document
                AND (d.id_bien IS NOT NULL AND b.id_bien IS NOT NULL
                   OR d.id_contrat IS NOT NULL AND c.id_contrat IS NOT NULL
                   OR d.id_proprietaire IS NOT NULL AND p.id_proprietaire IS NOT NULL
                   OR d.id_locataire IS NOT NULL AND l.id_locataire IS NOT NULL
                   OR d.id_intervention IS NOT NULL AND i.id_intervention IS NOT NULL)";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':agency_id1' => $agencyId,
            ':agency_id2' => $agencyId,
            ':agency_id3' => $agencyId,
            ':agency_id4' => $agencyId,
            ':agency_id5' => $agencyId,
            ':id_document' => $documentId
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public static function create(array $input): int
    {
        $db = Database::connection();
        
        $sql = "INSERT INTO document (
                    id_type_document, id_bien, id_contrat, id_proprietaire, 
                    id_locataire, id_intervention, nom, nom_original, 
                    mime_type, taille_octets, chemin_stockage, version, 
                    created_at, updated_at
                ) VALUES (
                    :id_type_document, :id_bien, :id_contrat, :id_proprietaire,
                    :id_locataire, :id_intervention, :nom, :nom_original,
                    :mime_type, :taille_octets, :chemin_stockage, 1,
                    NOW(), NOW()
                )";
                
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id_type_document' => $input['id_type_document'],
            ':id_bien' => $input['id_bien'] ?? null,
            ':id_contrat' => $input['id_contrat'] ?? null,
            ':id_proprietaire' => $input['id_proprietaire'] ?? null,
            ':id_locataire' => $input['id_locataire'] ?? null,
            ':id_intervention' => $input['id_intervention'] ?? null,
            ':nom' => $input['nom'],
            ':nom_original' => $input['nom_original'],
            ':mime_type' => $input['mime_type'],
            ':taille_octets' => $input['taille_octets'],
            ':chemin_stockage' => $input['chemin_stockage']
        ]);
        
        return (int)$db->lastInsertId();
    }

    public static function delete(int $agencyId, int $documentId): ?string
    {
        $db = Database::connection();
        
        $doc = self::find($agencyId, $documentId);
        if (!$doc) {
            return null;
        }

        $sql = "DELETE FROM document WHERE id_document = :id_document";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id_document' => $documentId]);

        return $doc['chemin_stockage'];
    }

    public static function types(): array
    {
        $db = Database::connection();
        $stmt = $db->query("SELECT * FROM type_document ORDER BY nom");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function forEntity(int $agencyId, string $entityType, int $entityId): array
    {
        $filters = ['entity_type' => $entityType];
        
        $list = self::list($agencyId, $filters);
        
        $filtered = [];
        $idField = "id_$entityType";
        
        foreach ($list as $doc) {
            if ($doc[$idField] == $entityId) {
                $filtered[] = $doc;
            }
        }
        
        return $filtered;
    }

    public static function stats(int $agencyId): array
    {
        $db = Database::connection();
        
        $sql = "SELECT td.nom, COUNT(d.id_document) as count
                FROM document d
                JOIN type_document td ON d.id_type_document = td.id_type_document
                LEFT JOIN bien b ON d.id_bien = b.id_bien AND b.id_agence = :agency_id1
                LEFT JOIN contrat c ON d.id_contrat = c.id_contrat AND c.id_agence = :agency_id2
                LEFT JOIN proprietaire p ON d.id_proprietaire = p.id_proprietaire AND p.id_agence = :agency_id3
                LEFT JOIN locataire l ON d.id_locataire = l.id_locataire AND l.id_agence = :agency_id4
                LEFT JOIN intervention i ON d.id_intervention = i.id_intervention AND i.id_agence = :agency_id5
                WHERE (d.id_bien IS NOT NULL AND b.id_bien IS NOT NULL
                   OR d.id_contrat IS NOT NULL AND c.id_contrat IS NOT NULL
                   OR d.id_proprietaire IS NOT NULL AND p.id_proprietaire IS NOT NULL
                   OR d.id_locataire IS NOT NULL AND l.id_locataire IS NOT NULL
                   OR d.id_intervention IS NOT NULL AND i.id_intervention IS NOT NULL)
                GROUP BY td.id_type_document, td.nom
                ORDER BY count DESC";
                
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':agency_id1' => $agencyId,
            ':agency_id2' => $agencyId,
            ':agency_id3' => $agencyId,
            ':agency_id4' => $agencyId,
            ':agency_id5' => $agencyId
        ]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
