<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Database.php';

final class DashboardModel
{
	private const RANGES = ['7j', '30j', '3m', '1a'];
	private const MONTH_LABELS = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
	private const PLAN_COLORS = ['Gratuit' => '#B7C6C6', 'Pro' => '#0F8B8D', 'Max' => '#075E63'];

	public static function kpis(): array
	{
		$db = Database::connection();
		return [
			self::countKpi($db, 'utilisateur', 'Utilisateurs', 'users', 'teal'),
			self::countKpi($db, 'agence', 'Agences', 'building', 'dark'),
			self::countKpi($db, 'bien', 'Biens', 'home', 'amber'),
			self::subscriptionsKpi($db),
		];
	}

	public static function evolution(): array
	{
		$db = Database::connection();
		$series = [
			'users' => self::cumulativeSeries($db, 'utilisateur'),
			'agences' => self::cumulativeSeries($db, 'agence'),
			'biens' => self::cumulativeSeries($db, 'bien'),
		];

		$result = [];
		foreach (self::RANGES as $range) {
			$buckets = self::buckets($range);
			$result[$range] = [
				'labels' => array_map(static fn (array $b): string => $b['label'], $buckets),
				'users' => array_map(static fn (array $b): int => self::cumulativeAt($series['users'], $b['end']), $buckets),
				'agences' => array_map(static fn (array $b): int => self::cumulativeAt($series['agences'], $b['end']), $buckets),
				'biens' => array_map(static fn (array $b): int => self::cumulativeAt($series['biens'], $b['end']), $buckets),
			];
		}
		return $result;
	}

	public static function subscriptionBreakdown(): array
	{
		$db = Database::connection();
		$rows = $db->query(
			"SELECT ab.nom AS label, COUNT(*) AS value
			 FROM agence_abonnement aa
			 INNER JOIN abonnement ab ON ab.id_abonnement = aa.id_abonnement
			 WHERE aa.statut = 'ACTIVE'
			 GROUP BY ab.nom
			 ORDER BY ab.id_abonnement"
		)->fetchAll();

		$total = array_sum(array_column($rows, 'value'));

		return array_map(static function (array $row) use ($total): array {
			$value = (int) $row['value'];
			return [
				'label' => $row['label'],
				'value' => $value,
				'pct' => $total > 0 ? round($value / $total * 100) . '%' : '0%',
				'color' => self::PLAN_COLORS[$row['label']] ?? '#B7C6C6',
			];
		}, $rows);
	}

	public static function recentActivity(int $limit = 6): array
	{
		$db = Database::connection();
		$stmt = $db->prepare(
			"SELECT ja.action, ja.entite, ja.date_action, u.nom, u.prenom, a.nom AS agence_nom
			 FROM journal_audit ja
			 LEFT JOIN utilisateur u ON u.id_utilisateur = ja.id_utilisateur
			 LEFT JOIN agence a ON a.id_agence = ja.id_agence
			 ORDER BY ja.date_action DESC
			 LIMIT :limit"
		);
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->execute();

		return array_map(static function (array $row): array {
			[$icon, $tone, $title] = self::activityPresentation($row['action'], $row['entite']);
			$who = trim(($row['prenom'] ?? '') . ' ' . ($row['nom'] ?? ''));
			$sub = $row['agence_nom'] ?? ($who !== '' ? $who : 'Système');
			return [
				'icon' => $icon,
				'tone' => $tone,
				'title' => $title,
				'sub' => $sub,
				'time' => self::relativeTime(new DateTimeImmutable($row['date_action'])),
			];
		}, $stmt->fetchAll());
	}

	public static function watchAlerts(): array
	{
		$db = Database::connection();
		$alerts = [];

		$pending = (int) $db->query(
			"SELECT COUNT(*) FROM agence WHERE statut = 'PENDING' AND created_at <= NOW() - INTERVAL 48 HOUR"
		)->fetchColumn();
		if ($pending > 0) {
			$alerts[] = [
				'icon' => '⏳', 'tone' => 'amber',
				'title' => $pending . ' agence' . ($pending > 1 ? 's' : '') . " en attente d'activation",
				'sub' => 'Inscrites depuis plus de 48h',
			];
		}

		$deactivated = (int) $db->query(
			"SELECT COUNT(*) FROM utilisateur WHERE statut IN ('INACTIVE','LOCKED') AND updated_at <= NOW() - INTERVAL 7 DAY"
		)->fetchColumn();
		if ($deactivated > 0) {
			$alerts[] = [
				'icon' => '⛔', 'tone' => 'red',
				'title' => $deactivated . ' compte' . ($deactivated > 1 ? 's' : '') . ' récemment désactivés',
				'sub' => 'Sans réactivation depuis 7 jours',
			];
		}

		$expiring = (int) $db->query(
			"SELECT COUNT(*) FROM agence_abonnement WHERE statut = 'ACTIVE' AND date_fin IS NOT NULL AND date_fin BETWEEN CURDATE() AND CURDATE() + INTERVAL 7 DAY"
		)->fetchColumn();
		if ($expiring > 0) {
			$alerts[] = [
				'icon' => '⏰', 'tone' => 'blue',
				'title' => $expiring . ' abonnement' . ($expiring > 1 ? 's' : '') . ' arrivent à expiration',
				'sub' => "D'ici 7 jours",
			];
		}

		if (!$alerts) {
			$alerts[] = [
				'icon' => '✓', 'tone' => 'green',
				'title' => 'Aucune alerte système en cours',
				'sub' => 'Dernière vérification : à l’instant',
			];
		}

		return $alerts;
	}

	public static function agencies(): array
	{
		$db = Database::connection();
		$rows = $db->query(
			"SELECT a.nom, a.ville, a.statut, a.created_at,
					(SELECT CONCAT(u.prenom, ' ', u.nom) FROM utilisateur u
					 WHERE u.id_agence = a.id_agence AND u.id_role = 2 AND u.deleted_at IS NULL
					 ORDER BY u.id_utilisateur LIMIT 1) AS admin_nom,
					(SELECT ab.nom FROM agence_abonnement aa
					 INNER JOIN abonnement ab ON ab.id_abonnement = aa.id_abonnement
					 WHERE aa.id_agence = a.id_agence AND aa.statut = 'ACTIVE'
					 ORDER BY aa.date_debut DESC LIMIT 1) AS plan_nom
			 FROM agence a
			 WHERE a.deleted_at IS NULL
			 ORDER BY a.created_at DESC"
		)->fetchAll();

		$statusMap = ['ACTIVE' => 'actif', 'PENDING' => 'attente', 'SUSPENDED' => 'suspendu'];

		return array_map(static function (array $row) use ($statusMap): array {
			return [
				'name' => $row['nom'],
				'type' => $row['ville'] ?? '',
				'admin' => $row['admin_nom'] ?? '—',
				'plan' => $row['plan_nom'] ?? 'Gratuit',
				'date' => (new DateTimeImmutable($row['created_at']))->format('d/m/Y'),
				'status' => $statusMap[$row['statut']] ?? 'attente',
			];
		}, $rows);
	}

	private static function countKpi(PDO $db, string $table, string $label, string $icon, string $tone): array
	{
		$current = (int) $db->query("SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL")->fetchColumn();
		$previous = (int) $db->query(
			"SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL AND created_at <= NOW() - INTERVAL 30 DAY"
		)->fetchColumn();
		return self::formatKpi($label, $icon, $tone, $current, $previous);
	}

	private static function subscriptionsKpi(PDO $db): array
	{
		$current = (int) $db->query("SELECT COUNT(*) FROM agence_abonnement WHERE statut = 'ACTIVE'")->fetchColumn();
		$previous = (int) $db->query(
			"SELECT COUNT(*) FROM agence_abonnement WHERE statut = 'ACTIVE' AND created_at <= NOW() - INTERVAL 30 DAY"
		)->fetchColumn();
		return self::formatKpi('Abonnements actifs', 'card', 'violet', $current, $previous);
	}

	private static function formatKpi(string $label, string $icon, string $tone, int $current, int $previous): array
	{
		$change = $previous > 0 ? round(($current - $previous) / $previous * 100, 1) : ($current > 0 ? 100.0 : 0.0);
		return [
			'label' => $label,
			'value' => number_format($current, 0, ',', ' '),
			'change' => ($change >= 0 ? '+' : '') . $change . '%',
			'up' => $change >= 0,
			'sub' => 'vs période précédente',
			'icon' => $icon,
			'tone' => $tone,
		];
	}

	/** @return array<int, array{date: string, cumulative: int}> */
	private static function cumulativeSeries(PDO $db, string $table): array
	{
		$rows = $db->query(
			"SELECT DATE(created_at) AS d, COUNT(*) AS c FROM {$table} WHERE deleted_at IS NULL GROUP BY DATE(created_at) ORDER BY d"
		)->fetchAll();

		$cumulative = 0;
		$series = [];
		foreach ($rows as $row) {
			$cumulative += (int) $row['c'];
			$series[] = ['date' => $row['d'], 'cumulative' => $cumulative];
		}
		return $series;
	}

	private static function cumulativeAt(array $series, DateTimeImmutable $at): int
	{
		$atStr = $at->format('Y-m-d');
		$result = 0;
		foreach ($series as $point) {
			if ($point['date'] > $atStr) {
				break;
			}
			$result = $point['cumulative'];
		}
		return $result;
	}

	/** @return array<int, array{label: string, end: DateTimeImmutable}> */
	private static function buckets(string $range): array
	{
		$today = new DateTimeImmutable('today');
		return match ($range) {
			'7j' => self::dailyBuckets($today, 7),
			'3m' => self::weeklyBuckets($today, 12),
			'1a' => self::monthlyBuckets($today, 12),
			default => self::dailyBuckets($today, 30),
		};
	}

	private static function dailyBuckets(DateTimeImmutable $end, int $count): array
	{
		$buckets = [];
		for ($i = $count - 1; $i >= 0; $i--) {
			$date = $end->modify("-{$i} days");
			$buckets[] = ['label' => $date->format('d/m'), 'end' => $date];
		}
		return $buckets;
	}

	private static function weeklyBuckets(DateTimeImmutable $end, int $count): array
	{
		$buckets = [];
		for ($i = $count - 1; $i >= 0; $i--) {
			$date = $end->modify("-{$i} weeks");
			$buckets[] = ['label' => $date->format('d/m'), 'end' => $date];
		}
		return $buckets;
	}

	private static function monthlyBuckets(DateTimeImmutable $end, int $count): array
	{
		$buckets = [];
		for ($i = $count - 1; $i >= 0; $i--) {
			$date = $end->modify("-{$i} months");
			$buckets[] = ['label' => self::MONTH_LABELS[(int) $date->format('n') - 1], 'end' => $date];
		}
		return $buckets;
	}

	private static function activityPresentation(string $action, string $entite): array
	{
		return match ($action) {
			'CREATE' => ['plus', 'teal', "Création — {$entite}"],
			'UPDATE' => ['edit', 'amber', "Modification — {$entite}"],
			'DELETE' => ['x', 'red', "Suppression — {$entite}"],
			'LOGIN' => ['user', 'blue', 'Connexion'],
			'LOGOUT' => ['user', 'gray', 'Déconnexion'],
			default => ['shield', 'gray', "Action — {$entite}"],
		};
	}

	private static function relativeTime(DateTimeImmutable $date): string
	{
		$diff = (new DateTimeImmutable())->getTimestamp() - $date->getTimestamp();
		if ($diff < 60) return 'À l’instant';
		if ($diff < 3600) return 'Il y a ' . intdiv($diff, 60) . ' min';
		if ($diff < 86400) return 'Il y a ' . intdiv($diff, 3600) . 'h';
		if ($diff < 172800) return 'Hier · ' . $date->format('H:i');
		return $date->format('d/m/Y');
	}
}
