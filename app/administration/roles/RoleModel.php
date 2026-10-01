<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Database.php';

class RoleModel {
    private \PDO $db;
    public function __construct() { $this->db = Database::connection(); }
    public function findAll(): array { return $this->db->query("SELECT * FROM role")->fetchAll(); }
}
