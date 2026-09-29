<?php
declare(strict_types=1);

require_once __DIR__ . '/RapportModel.php';

class RapportController {
    private RapportModel $model;

    public function __construct() {
        $this->model = new RapportModel();
    }
    
    public function getRevenueReport(int $agencyId, ?string $dateFrom, ?string $dateTo): array {
        return $this->model->revenueReport($agencyId, $dateFrom, $dateTo);
    }
    
    public function getOccupancyReport(int $agencyId): array {
        return $this->model->occupancyReport($agencyId);
    }
    
    public function getOverdueReport(int $agencyId): array {
        return $this->model->overdueReport($agencyId);
    }
    
    public function getMaintenanceReport(int $agencyId, ?string $dateFrom, ?string $dateTo): array {
        return $this->model->maintenanceReport($agencyId, $dateFrom, $dateTo);
    }
    
    public function getPropertyPerformance(int $agencyId): array {
        return $this->model->propertyPerformance($agencyId);
    }
    
    public function getTenantReport(int $agencyId): array {
        return $this->model->tenantReport($agencyId);
    }
}
