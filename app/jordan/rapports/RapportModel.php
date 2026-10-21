<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Database.php';

class RapportModel {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::connection();
    }

    public function revenueReport(int $agencyId, ?string $dateFrom = null, ?string $dateTo = null): array {
        $sql = "SELECT DATE_FORMAT(p.date_paiement, '%Y-%m') as mois,
                       SUM(p.montant) as total_encaisse,
                       COUNT(p.id_paiement) as nb_paiements
                FROM paiement p
                JOIN echeance e ON p.id_echeance = e.id_echeance
                JOIN contrat c ON e.id_contrat = c.id_contrat
                WHERE c.id_agence = :agence_id
                  AND p.statut = 'PAID'
                  AND p.date_paiement IS NOT NULL";
        
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
        $sql = "SELECT statut, COUNT(id_bien) as count
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
                       c.numero as contrat_ref,
                       e.montant_total - COALESCE((SELECT SUM(montant) FROM paiement WHERE id_echeance = e.id_echeance), 0) as montant_du,
                       DATEDIFF(CURRENT_DATE, e.date_echeance) as retard_jours
                FROM echeance e
                JOIN contrat c ON e.id_contrat = c.id_contrat
                JOIN bien b ON c.id_bien = b.id_bien
                JOIN contrat_locataire cl ON c.id_contrat = cl.id_contrat
                JOIN locataire l ON cl.id_locataire = l.id_locataire
                WHERE c.id_agence = :agence_id
                  AND c.deleted_at IS NULL
                  AND (e.montant_total - COALESCE((SELECT SUM(montant) FROM paiement WHERE id_echeance = e.id_echeance), 0)) > 0
                  AND e.date_echeance < CURRENT_DATE
                ORDER BY retard_jours DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':agence_id' => $agencyId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function maintenanceReport(int $agencyId, ?string $dateFrom = null, ?string $dateTo = null): array {
        $sql = "SELECT statut, priorite,
                       COUNT(id_intervention) as count,
                       SUM(COALESCE(cout_final, cout_estime)) as total_cout,
                       AVG(DATEDIFF(COALESCE(updated_at, created_at), created_at)) as avg_resolution_days
                FROM intervention
                WHERE id_agence = :agence_id";
        
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
        $sql = "SELECT b.titre as bien_nom,
                       b.statut,
                       COALESCE(SUM(p.montant), 0) as total_revenu
                FROM bien b
                LEFT JOIN contrat c ON b.id_bien = c.id_bien AND c.deleted_at IS NULL
                LEFT JOIN echeance e ON c.id_contrat = e.id_contrat
                LEFT JOIN paiement p ON e.id_echeance = p.id_echeance
                WHERE b.id_agence = :agence_id AND b.deleted_at IS NULL
                GROUP BY b.id_bien
                ORDER BY total_revenu DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':agence_id' => $agencyId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function tenantReport(int $agencyId): array {
        $sql = "SELECT l.id_locataire, CONCAT(l.prenom, ' ', l.nom) as locataire,
                       COUNT(e.id_echeance) as total_echeances,
                       SUM(CASE WHEN (SELECT SUM(montant) FROM paiement WHERE id_echeance = e.id_echeance) >= e.montant_total THEN 1 ELSE 0 END) as payees
                FROM locataire l
                JOIN contrat_locataire cl ON l.id_locataire = cl.id_locataire
                JOIN contrat c ON cl.id_contrat = c.id_contrat
                JOIN echeance e ON c.id_contrat = e.id_contrat
                WHERE l.id_agence = :agence_id AND l.deleted_at IS NULL
                GROUP BY l.id_locataire";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':agence_id' => $agencyId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
