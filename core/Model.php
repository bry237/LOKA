<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }
}