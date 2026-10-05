<?php declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Database.php';

class MaintenanceModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function list(int $agencyId, array $filters = []): array
    {
        $sql = "SELECT i.*, b.titre as bien_nom, b.reference as bien_reference, l.nom as locataire_nom, l.prenom as locataire_prenom, u_assigne.nom as assigne_nom, u_assigne.prenom as assigne_prenom
                FROM intervention i
                LEFT JOIN bien b ON i.id_bien = b.id_bien
                LEFT JOIN locataire l ON i.id_locataire = l.id_locataire
                LEFT JOIN utilisateur u_assigne ON i.id_utilisateur_assigne = u_assigne.id_utilisateur
                WHERE i.id_agence = :id_agence";
        
        $params = [':id_agence' => $agencyId];

        if (!empty($filters['search'])) {
            $sql .= " AND i.titre LIKE :search";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['priorite'])) {
            $sql .= " AND i.priorite = :priorite";
            $params[':priorite'] = $filters['priorite'];
        }

        if (!empty($filters['statut'])) {
            $sql .= " AND i.statut = :statut";
            $params[':statut'] = $filters['statut'];
        }

        $sql .= " ORDER BY i.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $agencyId, int $interventionId): ?array
    {
        $sql = "SELECT i.*, 
                b.titre as bien_nom, b.reference as bien_reference, 
                l.nom as locataire_nom, l.prenom as locataire_prenom, 
                u_createur.nom as createur_nom, u_createur.prenom as createur_prenom,
                u_assigne.nom as assigne_nom, u_assigne.prenom as assigne_prenom
                FROM intervention i
                LEFT JOIN bien b ON i.id_bien = b.id_bien
                LEFT JOIN locataire l ON i.id_locataire = l.id_locataire
                LEFT JOIN utilisateur u_createur ON i.id_utilisateur_createur = u_createur.id_utilisateur
                LEFT JOIN utilisateur u_assigne ON i.id_utilisateur_assigne = u_assigne.id_utilisateur
                WHERE i.id_intervention = :id_intervention AND i.id_agence = :id_agence";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_intervention' => $interventionId, ':id_agence' => $agencyId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(int $agencyId, array $input): int
    {
        $sql = "INSERT INTO intervention (id_agence, id_bien, id_locataire, id_utilisateur_createur, titre, description, priorite, cout_estime, statut, created_at, updated_at) 
                VALUES (:id_agence, :id_bien, :id_locataire, :id_utilisateur_createur, :titre, :description, :priorite, :cout_estime, 'OPEN', NOW(), NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_agence' => $agencyId,
            ':id_bien' => $input['id_bien'] ?: null,
            ':id_locataire' => $input['id_locataire'] ?: null,
            ':id_utilisateur_createur' => $input['id_utilisateur_createur'],
            ':titre' => $input['titre'],
            ':description' => $input['description'] ?? null,
            ':priorite' => $input['priorite'],
            ':cout_estime' => $input['cout_estime'] ?: null
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $agencyId, int $interventionId, array $input): bool
    {
        $sql = "UPDATE intervention SET 
                id_bien = :id_bien, 
                id_locataire = :id_locataire, 
                titre = :titre, 
                description = :description, 
                priorite = :priorite, 
                cout_estime = :cout_estime,
                updated_at = NOW() 
                WHERE id_intervention = :id_intervention AND id_agence = :id_agence";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_bien' => $input['id_bien'] ?: null,
            ':id_locataire' => $input['id_locataire'] ?: null,
            ':titre' => $input['titre'],
            ':description' => $input['description'] ?? null,
            ':priorite' => $input['priorite'],
            ':cout_estime' => $input['cout_estime'] ?: null,
            ':id_intervention' => $interventionId,
            ':id_agence' => $agencyId
        ]);
    }

    public function updateStatus(int $agencyId, int $interventionId, string $status): bool
    {
        $sql = "UPDATE intervention SET statut = :statut, updated_at = NOW() WHERE id_intervention = :id_intervention AND id_agence = :id_agence";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':statut' => $status,
            ':id_intervention' => $interventionId,
            ':id_agence' => $agencyId
        ]);
    }

    public function assign(int $agencyId, int $interventionId, int $userId): bool
    {
        $sql = "UPDATE intervention SET id_utilisateur_assigne = :id_utilisateur_assigne, statut = 'ASSIGNED', updated_at = NOW() WHERE id_intervention = :id_intervention AND id_agence = :id_agence";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_utilisateur_assigne' => $userId,
            ':id_intervention' => $interventionId,
            ':id_agence' => $agencyId
        ]);
    }

    public function close(int $agencyId, int $interventionId, array $input): bool
    {
        $sql = "UPDATE intervention SET statut = 'CLOSED', cout_final = :cout_final, compte_rendu = :compte_rendu, date_fin = NOW(), updated_at = NOW() WHERE id_intervention = :id_intervention AND id_agence = :id_agence";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':cout_final' => $input['cout_final'] ?: null,
            ':compte_rendu' => $input['compte_rendu'] ?? null,
            ':id_intervention' => $interventionId,
            ':id_agence' => $agencyId
        ]);
    }

    public function comments(int $agencyId, int $interventionId): array
    {
        $sql = "SELECT c.*, u.nom as utilisateur_nom, u.prenom as utilisateur_prenom 
                FROM commentaire_intervention c 
                JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
                JOIN intervention i ON c.id_intervention = i.id_intervention
                WHERE c.id_intervention = :id_intervention AND i.id_agence = :id_agence
                ORDER BY c.created_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_intervention' => $interventionId, ':id_agence' => $agencyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addComment(int $agencyId, int $interventionId, int $userId, string $content): int
    {
        if (!$this->find($agencyId, $interventionId)) {
            throw new Exception("Intervention introuvable ou non autorisée.");
        }

        $sql = "INSERT INTO commentaire_intervention (id_intervention, id_utilisateur, contenu, created_at) VALUES (:id_intervention, :id_utilisateur, :contenu, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_intervention' => $interventionId,
            ':id_utilisateur' => $userId,
            ':contenu' => $content
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function stats(int $agencyId): array
    {
        $sql = "SELECT 
                SUM(CASE WHEN statut IN ('OPEN', 'ASSIGNED') THEN 1 ELSE 0 END) as ouvertes,
                SUM(CASE WHEN statut = 'IN_PROGRESS' THEN 1 ELSE 0 END) as en_cours,
                SUM(CASE WHEN priorite = 'URGENT' AND statut NOT IN ('RESOLVED', 'CLOSED', 'CANCELLED') THEN 1 ELSE 0 END) as urgentes,
                SUM(CASE WHEN statut = 'RESOLVED' AND MONTH(updated_at) = MONTH(CURRENT_DATE()) AND YEAR(updated_at) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) as resolues_ce_mois
                FROM intervention 
                WHERE id_agence = :id_agence";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_agence' => $agencyId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'ouvertes' => (int)($result['ouvertes'] ?? 0),
            'en_cours' => (int)($result['en_cours'] ?? 0),
            'urgentes' => (int)($result['urgentes'] ?? 0),
            'resolues_ce_mois' => (int)($result['resolues_ce_mois'] ?? 0)
        ];
    }
}
