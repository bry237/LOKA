<?php
declare(strict_types=1);

require_once __DIR__ . '/MonAgenceModel.php';

final class MonAgenceController
{
	public static function data(int $idAgence): array
	{
		return [
			'agency' => MonAgenceModel::agency($idAgence),
			'subscription' => MonAgenceModel::subscription($idAgence),
			'kpis' => MonAgenceModel::kpis($idAgence),
			'activity' => MonAgenceModel::recentActivity($idAgence, 6),
		];
	}
}
