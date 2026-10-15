<?php
declare(strict_types=1);

require_once __DIR__ . '/AuditModel.php';

final class AuditController
{
	public static function list(array $query): array
	{
		$filters = [
			'q' => trim((string) ($query['q'] ?? '')),
			'action' => (string) ($query['action'] ?? ''),
			'entite' => (string) ($query['entite'] ?? ''),
			'date_from' => (string) ($query['date_from'] ?? ''),
			'date_to' => (string) ($query['date_to'] ?? ''),
		];
		$page = max(1, (int) ($query['page'] ?? 1));

		return [
			'filters' => $filters,
			'entities' => AuditModel::distinctEntities(),
			'result' => AuditModel::list($filters, $page),
		];
	}

	public static function detail(int $id): ?array
	{
		return AuditModel::detail($id);
	}
}
