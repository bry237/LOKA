<?php
declare(strict_types=1);

require_once __DIR__ . '/StatistiqueModel.php';

final class StatistiqueController
{
	public static function data(): array
	{
		return [
			'kpis' => StatistiqueModel::kpis(),
			'revenue' => StatistiqueModel::revenueByMonth(12),
			'userGrowth' => StatistiqueModel::userGrowthByMonth(12),
			'topAgences' => StatistiqueModel::topAgences(10),
			'geographic' => StatistiqueModel::geographicDistribution(8),
		];
	}
}
