<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Database.php';

class UtilisateurModel {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::connection();
    }

    public function findAll(string $search = '', string $role = '', string $statut = '', string $agence = ''): array {
        $sql = "SELECT u.*, r.nom as role_nom, a.nom as agence_nom 
                FROM utilisateur u 
                LEFT JOIN role r ON u.id_role = r.id_role 
                LEFT JOIN agence a ON u.id_agence = a.id_agence 
                WHERE u.deleted_at IS NULL";
        $params = [];
        
        if ($search) {
            $sql .= " AND (u.nom LIKE :search OR u.email LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        if ($role) {
            $sql .= " AND u.id_role = :role";
            $params[':role'] = $role;
        }
        if ($statut) {
            $sql .= " AND u.statut = :statut";
            $params[':statut'] = $statut;
        }
        if ($agence) {
            $sql .= " AND u.id_agence = :agence";
            $params[':agence'] = $agence;
        }
        
        $sql .= " ORDER BY u.nom ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("
            SELECT u.*, r.nom as role_nom, a.nom as agence_nom 
            FROM utilisateur u 
            LEFT JOIN role r ON u.id_role = r.id_role 
            LEFT JOIN agence a ON u.id_agence = a.id_agence 
            WHERE u.id_utilisateur = :id AND u.deleted_at IS NULL
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function create(array $data) {
        $sql = "INSERT INTO utilisateur (id_agence, id_role, nom, prenom, email, mot_de_passe, telephone, statut, created_at) 
                VALUES (:id_agence, :id_role, :nom, :prenom, :email, :mot_de_passe, :telephone, :statut, NOW())";
        $stmt = $this->db->prepare($sql);
        $hash = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        $stmt->execute([
            ':id_agence' => !empty($data['id_agence']) ? $data['id_agence'] : null,
            ':id_role' => !empty($data['id_role']) ? $data['id_role'] : null,
            ':nom' => $data['nom'],
            ':prenom' => $data['prenom'],
            ':email' => $data['email'],
            ':mot_de_passe' => $hash,
            ':telephone' => $data['telephone'] ?? null,
            ':statut' => $data['statut'] ?? 'ACTIVE'
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, array $data) {
        $sql = "UPDATE utilisateur 
                SET id_agence = :id_agence, id_role = :id_role, nom = :nom, prenom = :prenom, 
                    email = :email, telephone = :telephone, updated_at = NOW() 
                WHERE id_utilisateur = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id_agence' => !empty($data['id_agence']) ? $data['id_agence'] : null,
            ':id_role' => !empty($data['id_role']) ? $data['id_role'] : null,
            ':nom' => $data['nom'],
            ':prenom' => $data['prenom'],
            ':email' => $data['email'],
            ':telephone' => $data['telephone'] ?? null,
            ':id' => $id
        ]);
    }
    
    public function updateStatus($id, string $statut) {
        $sql = "UPDATE utilisateur SET statut = :statut, updated_at = NOW() WHERE id_utilisateur = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':statut' => $statut, ':id' => $id]);
    }
    
    public function getStats(): array {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN statut = 'ACTIVE' THEN 1 ELSE 0 END) as actifs
            FROM utilisateur WHERE deleted_at IS NULL
        ");
        return $stmt->fetch();
    }
}
