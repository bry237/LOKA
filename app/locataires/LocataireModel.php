<?php declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Database.php';

class LocataireModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connection();
    }

    public function list(int $agencyId, string $query = '', string $status = ''): array {
        $sql = "SELECT l.*, 
                (SELECT COUNT(*) FROM contrat_locataire cl 
                 JOIN contrat c ON cl.id_contrat = c.id_contrat 
                 WHERE cl.id_locataire = l.id_locataire AND c.deleted_at IS NULL AND c.statut = 'ACTIF') as contrats_actifs 
                FROM locataire l 
                WHERE l.id_agence = :agencyId AND l.deleted_at IS NULL";
        
        $params = [':agencyId' => $agencyId];
        
        if ($status !== '') {
            $sql .= " AND l.statut = :status";
            $params[':status'] = $status;
        }
        
        if ($query !== '') {
            $sql .= " AND (l.nom LIKE :query OR l.prenom LIKE :query OR l.email LIKE :query)";
            $params[':query'] = "%$query%";
        }
        
        $sql .= " ORDER BY l.nom ASC, l.prenom ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $agencyId, int $tenantId): ?array {
        $sql = "SELECT * FROM locataire 
                WHERE id_agence = :agencyId AND id_locataire = :tenantId AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':agencyId' => $agencyId, ':tenantId' => $tenantId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(int $agencyId, array $input): int {
        $sql = "INSERT INTO locataire (
            id_agence, nom, prenom, email, telephone, adresse, code_postal, ville, pays,
            date_naissance, profession, revenu_mensuel, revenu_mensuel_fourchette, statut,
            numero_identite, type_identite, situation_familiale, nombre_occupants,
            garant_nom, garant_telephone, garant_email, notes, created_at, updated_at
        ) VALUES (
            :agencyId, :nom, :prenom, :email, :telephone, :adresse, :code_postal, :ville, :pays,
            :date_naissance, :profession, :revenu_mensuel, :revenu_mensuel_fourchette, :statut,
            :numero_identite, :type_identite, :situation_familiale, :nombre_occupants,
            :garant_nom, :garant_telephone, :garant_email, :notes, NOW(), NOW()
        )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->params($agencyId, $input));
        return (int)$this->db->lastInsertId();
    }

    public function update(int $agencyId, int $tenantId, array $input): bool {
        $sql = "UPDATE locataire SET 
            nom = :nom, prenom = :prenom, email = :email, telephone = :telephone, 
            adresse = :adresse, code_postal = :code_postal, ville = :ville, pays = :pays,
            date_naissance = :date_naissance, profession = :profession, 
            revenu_mensuel = :revenu_mensuel, revenu_mensuel_fourchette = :revenu_mensuel_fourchette, 
            statut = :statut, numero_identite = :numero_identite, type_identite = :type_identite, 
            situation_familiale = :situation_familiale, nombre_occupants = :nombre_occupants,
            garant_nom = :garant_nom, garant_telephone = :garant_telephone, 
            garant_email = :garant_email, notes = :notes, updated_at = NOW()
        WHERE id_agence = :agencyId AND id_locataire = :tenantId AND deleted_at IS NULL";
        
        $params = $this->params($agencyId, $input);
        $params[':tenantId'] = $tenantId;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function archive(int $agencyId, int $tenantId): bool {
        $sql = "UPDATE locataire SET deleted_at = NOW(), statut = 'ARCHIVED' 
                WHERE id_agence = :agencyId AND id_locataire = :tenantId AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':agencyId' => $agencyId, ':tenantId' => $tenantId]);
    }

    public function contracts(int $agencyId, int $tenantId): array {
        $sql = "SELECT c.*, b.nom as bien_nom, b.adresse as bien_adresse 
                FROM contrat c
                JOIN contrat_locataire cl ON c.id_contrat = cl.id_contrat
                JOIN bien b ON c.id_bien = b.id_bien
                WHERE cl.id_locataire = :tenantId AND c.id_agence = :agencyId AND c.deleted_at IS NULL
                ORDER BY c.date_debut DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':tenantId' => $tenantId, ':agencyId' => $agencyId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function stats(int $agencyId): array {
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN statut = 'ACTIVE' THEN 1 ELSE 0 END) as actifs,
                SUM(CASE WHEN statut = 'INACTIVE' THEN 1 ELSE 0 END) as inactifs
                FROM locataire 
                WHERE id_agence = :agencyId AND deleted_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':agencyId' => $agencyId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'total' => (int)($result['total'] ?? 0),
            'actifs' => (int)($result['actifs'] ?? 0),
            'inactifs' => (int)($result['inactifs'] ?? 0)
        ];
    }

    private function params(int $agencyId, array $input): array {
        return [
            ':agencyId' => $agencyId,
            ':nom' => trim($input['nom'] ?? ''),
            ':prenom' => trim($input['prenom'] ?? ''),
            ':email' => $this->nullable($input['email'] ?? null),
            ':telephone' => $this->nullable($input['telephone'] ?? null),
            ':adresse' => $this->nullable($input['adresse'] ?? null),
            ':code_postal' => $this->nullable($input['code_postal'] ?? null),
            ':ville' => $this->nullable($input['ville'] ?? null),
            ':pays' => $this->nullable($input['pays'] ?? 'France'),
            ':date_naissance' => $this->nullable($input['date_naissance'] ?? null),
            ':profession' => $this->nullable($input['profession'] ?? null),
            ':revenu_mensuel' => !empty($input['revenu_mensuel']) ? (float)$input['revenu_mensuel'] : null,
            ':revenu_mensuel_fourchette' => $this->nullable($input['revenu_mensuel_fourchette'] ?? null),
            ':statut' => $input['statut'] ?? 'ACTIVE',
            ':numero_identite' => $this->nullable($input['numero_identite'] ?? null),
            ':type_identite' => $this->nullable($input['type_identite'] ?? null),
            ':situation_familiale' => $this->nullable($input['situation_familiale'] ?? null),
            ':nombre_occupants' => !empty($input['nombre_occupants']) ? (int)$input['nombre_occupants'] : 1,
            ':garant_nom' => $this->nullable($input['garant_nom'] ?? null),
            ':garant_telephone' => $this->nullable($input['garant_telephone'] ?? null),
            ':garant_email' => $this->nullable($input['garant_email'] ?? null),
            ':notes' => $this->nullable($input['notes'] ?? null)
        ];
    }

    private function nullable(?string $value): ?string {
        if ($value === null) return null;
        $val = trim($value);
        return $val === '' ? null : $val;
    }
}
