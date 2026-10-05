<?php declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Database.php';

class ContratModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connection();
    }

    public function list(int $agencyId, array $filters = []): array {
        $sql = "SELECT c.*, b.titre as bien_nom, b.reference as bien_reference
                FROM contrat c
                LEFT JOIN bien b ON c.id_bien = b.id_bien
                WHERE c.id_agence = :id_agence AND c.deleted_at IS NULL";
        
        $params = [':id_agence' => $agencyId];

        if (!empty($filters['search'])) {
            $sql .= " AND (c.numero LIKE :search OR b.titre LIKE :search OR b.reference LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['statut'])) {
            $sql .= " AND c.statut = :statut";
            $params[':statut'] = $filters['statut'];
        }

        if (!empty($filters['type_contrat'])) {
            $sql .= " AND c.type_contrat = :type_contrat";
            $params[':type_contrat'] = $filters['type_contrat'];
        }

        $sql .= " ORDER BY c.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $agencyId, int $contractId): ?array {
        $sql = "SELECT c.*, b.titre as bien_nom, b.reference as bien_reference,
                a.rue as adresse_rue, a.code_postal as adresse_cp, a.ville as adresse_ville
                FROM contrat c
                LEFT JOIN bien b ON c.id_bien = b.id_bien
                LEFT JOIN adresse a ON b.id_adresse = a.id_adresse
                WHERE c.id_contrat = :id_contrat AND c.id_agence = :id_agence AND c.deleted_at IS NULL";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_contrat' => $contractId,
            ':id_agence' => $agencyId
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(int $agencyId, array $input): int {
        $sql = "INSERT INTO contrat (
            id_agence, id_bien, numero, type_contrat, date_debut, date_fin, 
            loyer, charges, depot_garantie, frequence_paiement, jour_echeance, 
            statut, indice_revision, date_prochaine_revision, preavis_mois
        ) VALUES (
            :id_agence, :id_bien, :numero, :type_contrat, :date_debut, :date_fin,
            :loyer, :charges, :depot_garantie, :frequence_paiement, :jour_echeance,
            :statut, :indice_revision, :date_prochaine_revision, :preavis_mois
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_agence' => $agencyId,
            ':id_bien' => $input['id_bien'],
            ':numero' => $input['numero'],
            ':type_contrat' => $input['type_contrat'] ?? null,
            ':date_debut' => $input['date_debut'],
            ':date_fin' => $input['date_fin'] ?: null,
            ':loyer' => $input['loyer'],
            ':charges' => $input['charges'] ?: 0,
            ':depot_garantie' => $input['depot_garantie'] ?: 0,
            ':frequence_paiement' => $input['frequence_paiement'] ?? 'MONTHLY',
            ':jour_echeance' => $input['jour_echeance'] ?? 1,
            ':statut' => $input['statut'] ?? 'DRAFT',
            ':indice_revision' => $input['indice_revision'] ?: null,
            ':date_prochaine_revision' => $input['date_prochaine_revision'] ?: null,
            ':preavis_mois' => $input['preavis_mois'] ?: null
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $agencyId, int $contractId, array $input): bool {
        $sql = "UPDATE contrat SET 
            id_bien = :id_bien,
            numero = :numero,
            type_contrat = :type_contrat,
            date_debut = :date_debut,
            date_fin = :date_fin,
            loyer = :loyer,
            charges = :charges,
            depot_garantie = :depot_garantie,
            frequence_paiement = :frequence_paiement,
            jour_echeance = :jour_echeance,
            statut = :statut,
            indice_revision = :indice_revision,
            date_prochaine_revision = :date_prochaine_revision,
            preavis_mois = :preavis_mois,
            updated_at = NOW()
        WHERE id_contrat = :id_contrat AND id_agence = :id_agence AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_bien' => $input['id_bien'],
            ':numero' => $input['numero'],
            ':type_contrat' => $input['type_contrat'] ?? null,
            ':date_debut' => $input['date_debut'],
            ':date_fin' => $input['date_fin'] ?: null,
            ':loyer' => $input['loyer'],
            ':charges' => $input['charges'] ?: 0,
            ':depot_garantie' => $input['depot_garantie'] ?: 0,
            ':frequence_paiement' => $input['frequence_paiement'] ?? 'MONTHLY',
            ':jour_echeance' => $input['jour_echeance'] ?? 1,
            ':statut' => $input['statut'] ?? 'DRAFT',
            ':indice_revision' => $input['indice_revision'] ?: null,
            ':date_prochaine_revision' => $input['date_prochaine_revision'] ?: null,
            ':preavis_mois' => $input['preavis_mois'] ?: null,
            ':id_contrat' => $contractId,
            ':id_agence' => $agencyId
        ]);
    }

    public function terminate(int $agencyId, int $contractId, string $reason): bool {
        $sql = "UPDATE contrat SET 
            statut = 'TERMINATED',
            motif_resiliation = :reason,
            date_resiliation = NOW(),
            updated_at = NOW()
        WHERE id_contrat = :id_contrat AND id_agence = :id_agence AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':reason' => $reason,
            ':id_contrat' => $contractId,
            ':id_agence' => $agencyId
        ]);
    }

    public function tenants(int $agencyId, int $contractId): array {
        if (!$this->find($agencyId, $contractId)) return [];

        $sql = "SELECT l.*, cl.titulaire_principal, cl.date_entree, cl.date_sortie
                FROM contrat_locataire cl
                JOIN locataire l ON cl.id_locataire = l.id_locataire
                WHERE cl.id_contrat = :id_contrat AND l.deleted_at IS NULL";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_contrat' => $contractId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function attachTenant(int $agencyId, int $contractId, array $input): void {
        if (!$this->find($agencyId, $contractId)) return;

        $sql = "INSERT INTO contrat_locataire (id_contrat, id_locataire, titulaire_principal, date_entree, date_sortie) 
                VALUES (:id_contrat, :id_locataire, :titulaire_principal, :date_entree, :date_sortie)
                ON DUPLICATE KEY UPDATE 
                titulaire_principal = :titulaire_principal, 
                date_entree = :date_entree, 
                date_sortie = :date_sortie";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_contrat' => $contractId,
            ':id_locataire' => $input['id_locataire'],
            ':titulaire_principal' => $input['titulaire_principal'] ?? 0,
            ':date_entree' => $input['date_entree'] ?: null,
            ':date_sortie' => $input['date_sortie'] ?: null
        ]);
    }

    public function detachTenant(int $agencyId, int $contractId, int $tenantId): void {
        if (!$this->find($agencyId, $contractId)) return;

        $sql = "DELETE FROM contrat_locataire WHERE id_contrat = :id_contrat AND id_locataire = :id_locataire";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_contrat' => $contractId,
            ':id_locataire' => $tenantId
        ]);
    }

    public function stats(int $agencyId): array {
        $sql = "SELECT statut, COUNT(*) as count FROM contrat WHERE id_agence = :id_agence AND deleted_at IS NULL GROUP BY statut";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_agence' => $agencyId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [
            'total' => 0,
            'active' => 0,
            'draft' => 0,
            'expired' => 0
        ];

        foreach ($rows as $row) {
            $stats['total'] += $row['count'];
            if ($row['statut'] === 'ACTIVE') $stats['active'] += $row['count'];
            if ($row['statut'] === 'DRAFT') $stats['draft'] += $row['count'];
            if ($row['statut'] === 'EXPIRED') $stats['expired'] += $row['count'];
        }

        return $stats;
    }
}
