<?php
declare(strict_types=1);

require_once __DIR__ . '/DashboardModel.php';

final class DashboardController
{
	public static function data(): array
	{
		return [
			'kpis' => DashboardModel::kpis(),
			'evolution' => DashboardModel::evolution(),
			'plans' => DashboardModel::subscriptionBreakdown(),
			'activity' => DashboardModel::recentActivity(6),
			'watch' => DashboardModel::watchAlerts(),
			'agencies' => DashboardModel::agencies(),
		];
	}
}
