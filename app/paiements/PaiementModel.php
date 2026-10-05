<?php declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Database.php';

class PaiementModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connection();
    }

    public function list(int $agencyId, array $filters = []): array {
        $sql = "SELECT p.*, e.periode_debut, e.periode_fin, e.montant_total as echeance_montant, 
                       c.reference as contrat_reference, b.titre as bien_nom,
                       l.nom as locataire_nom, l.prenom as locataire_prenom
                FROM paiement p
                JOIN contrat c ON p.id_contrat = c.id_contrat
                JOIN bien b ON c.id_bien = b.id_bien
                JOIN locataire l ON p.id_locataire = l.id_locataire
                LEFT JOIN echeance e ON p.id_echeance = e.id_echeance
                WHERE c.id_agence = :agency_id";
        
        $params = [':agency_id' => $agencyId];

        if (!empty($filters['statut'])) {
            $sql .= " AND p.statut = :statut";
            $params[':statut'] = $filters['statut'];
        }
        if (!empty($filters['mode_paiement'])) {
            $sql .= " AND p.mode_paiement = :mode_paiement";
            $params[':mode_paiement'] = $filters['mode_paiement'];
        }
        if (!empty($filters['date_debut'])) {
            $sql .= " AND p.date_paiement >= :date_debut";
            $params[':date_debut'] = $filters['date_debut'];
        }
        if (!empty($filters['date_fin'])) {
            $sql .= " AND p.date_paiement <= :date_fin";
            $params[':date_fin'] = $filters['date_fin'];
        }

        $sql .= " ORDER BY p.date_paiement DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function find(int $agencyId, int $paymentId): ?array {
        $sql = "SELECT p.*, e.periode_debut, e.periode_fin, e.montant_total as echeance_montant, 
                       c.reference as contrat_reference, b.titre as bien_nom,
                       l.nom as locataire_nom, l.prenom as locataire_prenom, l.email as locataire_email
                FROM paiement p
                JOIN contrat c ON p.id_contrat = c.id_contrat
                JOIN bien b ON c.id_bien = b.id_bien
                JOIN locataire l ON p.id_locataire = l.id_locataire
                LEFT JOIN echeance e ON p.id_echeance = e.id_echeance
                WHERE p.id_paiement = :id_paiement AND c.id_agence = :agency_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_paiement' => $paymentId,
            ':agency_id' => $agencyId
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(int $agencyId, array $input): int {
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO paiement (id_echeance, id_contrat, id_locataire, montant, date_paiement, mode_paiement, reference_transaction, statut, commentaire)
                    VALUES (:id_echeance, :id_contrat, :id_locataire, :montant, :date_paiement, :mode_paiement, :reference_transaction, :statut, :commentaire)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_echeance' => !empty($input['id_echeance']) ? $input['id_echeance'] : null,
                ':id_contrat' => $input['id_contrat'],
                ':id_locataire' => $input['id_locataire'],
                ':montant' => $input['montant'],
                ':date_paiement' => $input['date_paiement'],
                ':mode_paiement' => $input['mode_paiement'],
                ':reference_transaction' => $input['reference_transaction'] ?? null,
                ':statut' => $input['statut'] ?? 'PAID',
                ':commentaire' => $input['commentaire'] ?? null
            ]);
            
            $paymentId = (int)$this->db->lastInsertId();

            if (!empty($input['id_echeance'])) {
                $this->updateEcheanceStatus((int)$input['id_echeance']);
            }

            $this->db->commit();
            return $paymentId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function update(int $agencyId, int $paymentId, array $input): bool {
        try {
            $this->db->beginTransaction();

            $oldPayment = $this->find($agencyId, $paymentId);
            if (!$oldPayment) {
                return false;
            }

            $sql = "UPDATE paiement 
                    SET montant = :montant, date_paiement = :date_paiement, 
                        mode_paiement = :mode_paiement, reference_transaction = :reference_transaction, 
                        statut = :statut, commentaire = :commentaire, updated_at = CURRENT_TIMESTAMP
                    WHERE id_paiement = :id_paiement";
            
            $stmt = $this->db->prepare($sql);
            $success = $stmt->execute([
                ':montant' => $input['montant'],
                ':date_paiement' => $input['date_paiement'],
                ':mode_paiement' => $input['mode_paiement'],
                ':reference_transaction' => $input['reference_transaction'] ?? null,
                ':statut' => $input['statut'],
                ':commentaire' => $input['commentaire'] ?? null,
                ':id_paiement' => $paymentId
            ]);

            if ($oldPayment['id_echeance']) {
                $this->updateEcheanceStatus((int)$oldPayment['id_echeance']);
            }

            $this->db->commit();
            return $success;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function overdueList(int $agencyId): array {
        $sql = "SELECT e.*, c.reference as contrat_reference, b.titre as bien_nom,
                       l.nom as locataire_nom, l.prenom as locataire_prenom, l.telephone as locataire_telephone, l.email as locataire_email
                FROM echeance e
                JOIN contrat c ON e.id_contrat = c.id_contrat
                JOIN bien b ON c.id_bien = b.id_bien
                JOIN contrat_locataire cl ON c.id_contrat = cl.id_contrat
                JOIN locataire l ON cl.id_locataire = l.id_locataire
                WHERE c.id_agence = :agency_id 
                  AND e.statut IN ('PENDING', 'PARTIAL', 'LATE') 
                  AND e.date_echeance < CURRENT_DATE";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':agency_id' => $agencyId]);
        return $stmt->fetchAll();
    }

    public function stats(int $agencyId): array {
        $stats = [
            'collected_month' => 0.0,
            'overdue_count' => 0,
            'overdue_amount' => 0.0,
            'pending_echeances' => 0
        ];

        // Collected this month
        $sql1 = "SELECT SUM(p.montant) as total 
                 FROM paiement p
                 JOIN contrat c ON p.id_contrat = c.id_contrat
                 WHERE c.id_agence = :agency_id 
                   AND p.statut = 'PAID'
                   AND MONTH(p.date_paiement) = MONTH(CURRENT_DATE)
                   AND YEAR(p.date_paiement) = YEAR(CURRENT_DATE)";
        $stmt1 = $this->db->prepare($sql1);
        $stmt1->execute([':agency_id' => $agencyId]);
        $res1 = $stmt1->fetch();
        if ($res1 && $res1['total']) {
            $stats['collected_month'] = (float)$res1['total'];
        }

        // Overdue stats
        $sql2 = "SELECT COUNT(DISTINCT e.id_echeance) as count, SUM(e.montant_total) as total
                 FROM echeance e
                 JOIN contrat c ON e.id_contrat = c.id_contrat
                 WHERE c.id_agence = :agency_id 
                   AND e.statut IN ('PENDING', 'PARTIAL', 'LATE') 
                   AND e.date_echeance < CURRENT_DATE";
        $stmt2 = $this->db->prepare($sql2);
        $stmt2->execute([':agency_id' => $agencyId]);
        $res2 = $stmt2->fetch();
        if ($res2) {
            $stats['overdue_count'] = (int)$res2['count'];
            $stats['overdue_amount'] = (float)$res2['total'];
        }

        // Pending echeances
        $sql3 = "SELECT COUNT(DISTINCT e.id_echeance) as count
                 FROM echeance e
                 JOIN contrat c ON e.id_contrat = c.id_contrat
                 WHERE c.id_agence = :agency_id 
                   AND e.statut IN ('PENDING', 'PARTIAL')";
        $stmt3 = $this->db->prepare($sql3);
        $stmt3->execute([':agency_id' => $agencyId]);
        $res3 = $stmt3->fetch();
        if ($res3) {
            $stats['pending_echeances'] = (int)$res3['count'];
        }

        return $stats;
    }

    public function scheduleList(int $agencyId, array $filters = []): array {
        $sql = "SELECT e.*, c.reference as contrat_reference, b.titre as bien_nom,
                       l.id_locataire, l.nom as locataire_nom, l.prenom as locataire_prenom
                FROM echeance e
                JOIN contrat c ON e.id_contrat = c.id_contrat
                JOIN bien b ON c.id_bien = b.id_bien
                JOIN contrat_locataire cl ON c.id_contrat = cl.id_contrat
                JOIN locataire l ON cl.id_locataire = l.id_locataire
                WHERE c.id_agence = :agency_id";
        
        $params = [':agency_id' => $agencyId];
        
        if (!empty($filters['statut'])) {
            $sql .= " AND e.statut = :statut";
            $params[':statut'] = $filters['statut'];
        }

        $sql .= " ORDER BY e.date_echeance ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function updateEcheanceStatus(int $echeanceId): void {
        $sql = "SELECT e.montant_total, COALESCE(SUM(p.montant), 0) as total_paye, e.date_echeance
                FROM echeance e
                LEFT JOIN paiement p ON e.id_echeance = p.id_echeance AND p.statut = 'PAID'
                WHERE e.id_echeance = :id_echeance
                GROUP BY e.id_echeance, e.montant_total, e.date_echeance";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id_echeance' => $echeanceId]);
        $row = $stmt->fetch();

        if (!$row) return;

        $montantTotal = (float)$row['montant_total'];
        $totalPaye = (float)$row['total_paye'];
        $dateEcheance = $row['date_echeance'];
        
        $statut = 'PENDING';
        if ($totalPaye >= $montantTotal) {
            $statut = 'PAID';
        } elseif ($totalPaye > 0) {
            $statut = 'PARTIAL';
        } elseif (strtotime($dateEcheance) < time()) {
            $statut = 'LATE';
        }

        $updateSql = "UPDATE echeance SET statut = :statut WHERE id_echeance = :id_echeance";
        $upStmt = $this->db->prepare($updateSql);
        $upStmt->execute([':statut' => $statut, ':id_echeance' => $echeanceId]);
    }
}
