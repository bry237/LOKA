<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Database.php';

class AgenceModel {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::connection();
    }

    public function findAll(string $search = '', string $status = ''): array {
        $sql = "SELECT * FROM agence WHERE deleted_at IS NULL";
        $params = [];
        if ($search) {
            $sql .= " AND nom LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }
        if ($status) {
            $sql .= " AND statut = :statut";
            $params[':statut'] = $status;
        }
        $sql .= " ORDER BY nom ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM agence WHERE id_agence = :id AND deleted_at IS NULL");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function create(array $data) {
        $sql = "INSERT INTO agence (nom, email, telephone, adresse, ville, code_postal, pays, statut, created_at) 
                VALUES (:nom, :email, :telephone, :adresse, :ville, :code_postal, :pays, :statut, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nom' => $data['nom'],
            ':email' => $data['email'],
            ':telephone' => $data['telephone'] ?? null,
            ':adresse' => $data['adresse'] ?? null,
            ':ville' => $data['ville'] ?? null,
            ':code_postal' => $data['code_postal'] ?? null,
            ':pays' => $data['pays'] ?? null,
            ':statut' => $data['statut'] ?? 'ACTIVE'
        ]);
        return $this->db->lastInsertId();
    }

    public function update($id, array $data) {
        $sql = "UPDATE agence 
                SET nom = :nom, email = :email, telephone = :telephone, 
                    adresse = :adresse, ville = :ville, code_postal = :code_postal, pays = :pays, updated_at = NOW() 
                WHERE id_agence = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nom' => $data['nom'],
            ':email' => $data['email'],
            ':telephone' => $data['telephone'] ?? null,
            ':adresse' => $data['adresse'] ?? null,
            ':ville' => $data['ville'] ?? null,
            ':code_postal' => $data['code_postal'] ?? null,
            ':pays' => $data['pays'] ?? null,
            ':id' => $id
        ]);
    }
    
    public function updateStatus($id, string $statut) {
        $sql = "UPDATE agence SET statut = :statut, updated_at = NOW() WHERE id_agence = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':statut' => $statut, ':id' => $id]);
    }
    
    public function getStats(): array {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN statut = 'ACTIVE' THEN 1 ELSE 0 END) as actives,
                SUM(CASE WHEN statut = 'SUSPENDED' THEN 1 ELSE 0 END) as suspendues
            FROM agence WHERE deleted_at IS NULL
        ");
        return $stmt->fetch();
    }
}
