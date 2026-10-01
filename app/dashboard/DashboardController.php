<?php

declare(strict_types=1);

require_once __DIR__ . '/DashboardModel.php';

final class DashboardController
{
	public static function allStats(int $agencyId): array
	{
		return [
			'properties' => DashboardModel::propertyStats($agencyId),
			'occupancyRate' => DashboardModel::occupancyRate($agencyId),
			'contracts' => DashboardModel::contractStats($agencyId),
			'tenantCount' => DashboardModel::tenantCount($agencyId),
			'ownerCount' => DashboardModel::ownerCount($agencyId),
			'overdue' => DashboardModel::overduePayments($agencyId),
			'monthlyRevenue' => DashboardModel::monthlyRevenue($agencyId),
			'openInterventions' => DashboardModel::openInterventions($agencyId),
		];
	}
}
