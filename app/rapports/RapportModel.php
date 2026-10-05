<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Database.php';

class RapportModel {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::connection();
    }

    public function revenueReport(int $agencyId, ?string $dateFrom = null, ?string $dateTo = null): array {
        $sql = "SELECT DATE_FORMAT(p.date_paiement, '%Y-%m') as mois, 
                       SUM(p.montant) as total_encaisse, 
                       COUNT(p.id) as nb_paiements
                FROM paiement p
                JOIN echeance e ON p.id_echeance = e.id
                JOIN contrat c ON e.id_contrat = c.id
                WHERE c.id_agence = :agence_id AND p.deleted_at IS NULL";
        
        $params = [':agence_id' => $agencyId];

        if ($dateFrom) {
            $sql .= " AND p.date_paiement >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND p.date_paiement <= :date_to";
            $params[':date_to'] = $dateTo;
        }

        $sql .= " GROUP BY mois ORDER BY mois DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function occupancyReport(int $agencyId): array {
        $sql = "SELECT statut, COUNT(id) as count
                FROM bien
                WHERE id_agence = :agence_id AND deleted_at IS NULL
                GROUP BY statut";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':agence_id' => $agencyId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function overdueReport(int $agencyId): array {
        $sql = "SELECT b.titre as bien_nom,
                       CONCAT(l.prenom, ' ', l.nom) as locataire_nom,
                       c.id as contrat_ref,
                       e.montant_total - COALESCE((SELECT SUM(montant) FROM paiement WHERE id_echeance = e.id AND deleted_at IS NULL), 0) as montant_du,
                       DATEDIFF(CURRENT_DATE, e.date_debut) as retard_jours
                FROM echeance e
                JOIN contrat c ON e.id_contrat = c.id
                JOIN bien b ON c.id_bien = b.id
                JOIN contrat_locataire cl ON c.id = cl.id_contrat
                JOIN locataire l ON cl.id_locataire = l.id
                WHERE c.id_agence = :agence_id
                  AND e.deleted_at IS NULL
                  AND c.deleted_at IS NULL
                  AND (e.montant_total - COALESCE((SELECT SUM(montant) FROM paiement WHERE id_echeance = e.id AND deleted_at IS NULL), 0)) > 0
                  AND e.date_debut < CURRENT_DATE
                ORDER BY retard_jours DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':agence_id' => $agencyId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function maintenanceReport(int $agencyId, ?string $dateFrom = null, ?string $dateTo = null): array {
        $sql = "SELECT statut, priorite,
                       COUNT(id) as count,
                       SUM(cout) as total_cout,
                       AVG(DATEDIFF(COALESCE(updated_at, created_at), created_at)) as avg_resolution_days
                FROM intervention
                WHERE id_agence = :agence_id AND deleted_at IS NULL";
        
        $params = [':agence_id' => $agencyId];
        
        if ($dateFrom) {
            $sql .= " AND created_at >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND created_at <= :date_to";
            $params[':date_to'] = $dateTo;
        }
        
        $sql .= " GROUP BY statut, priorite";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function propertyPerformance(int $agencyId): array {
        $sql = "SELECT b.titre,
                       b.statut,
                       COALESCE(SUM(p.montant), 0) as total_revenu
                FROM bien b
                LEFT JOIN contrat c ON b.id = c.id_bien AND c.deleted_at IS NULL
                LEFT JOIN echeance e ON c.id = e.id_contrat AND e.deleted_at IS NULL
                LEFT JOIN paiement p ON e.id = p.id_echeance AND p.deleted_at IS NULL
                WHERE b.id_agence = :agence_id AND b.deleted_at IS NULL
                GROUP BY b.id
                ORDER BY total_revenu DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':agence_id' => $agencyId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function tenantReport(int $agencyId): array {
        $sql = "SELECT l.id, CONCAT(l.prenom, ' ', l.nom) as locataire,
                       COUNT(e.id) as total_echeances,
                       SUM(CASE WHEN (SELECT SUM(montant) FROM paiement WHERE id_echeance = e.id AND deleted_at IS NULL) >= e.montant_total THEN 1 ELSE 0 END) as payees
                FROM locataire l
                JOIN contrat_locataire cl ON l.id = cl.id_locataire
                JOIN contrat c ON cl.id_contrat = c.id
                JOIN echeance e ON c.id = e.id_contrat
                WHERE l.id_agence = :agence_id AND l.deleted_at IS NULL AND e.deleted_at IS NULL
                GROUP BY l.id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':agence_id' => $agencyId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
