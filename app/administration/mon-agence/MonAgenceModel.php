<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Database.php';

final class MonAgenceModel
{
	public static function agency(int $idAgence): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT id_agence, nom, email, telephone, adresse, ville, code_postal, pays, statut, created_at
			 FROM agence
			 WHERE id_agence = :id_agence AND deleted_at IS NULL
			 LIMIT 1'
		);
		$stmt->execute(['id_agence' => $idAgence]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function subscription(int $idAgence): ?array
	{
		$stmt = Database::connection()->prepare(
			'SELECT ab.nom, ab.prix_mensuel, ab.limite_utilisateurs, ab.limite_biens, aa.date_debut, aa.date_fin
			 FROM agence_abonnement aa
			 INNER JOIN abonnement ab ON ab.id_abonnement = aa.id_abonnement
			 WHERE aa.id_agence = :id_agence AND aa.statut = \'ACTIVE\'
			 ORDER BY aa.date_debut DESC
			 LIMIT 1'
		);
		$stmt->execute(['id_agence' => $idAgence]);
		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function kpis(int $idAgence): array
	{
		$db = Database::connection();
		return [
			self::countKpi($db, 'utilisateur', $idAgence, 'Utilisateurs', 'users', 'teal'),
			self::countKpi($db, 'bien', $idAgence, 'Biens', 'home', 'amber'),
			self::activeContractsKpi($db, $idAgence),
		];
	}

	public static function recentActivity(int $idAgence, int $limit = 6): array
	{
		$db = Database::connection();
		$stmt = $db->prepare(
			"SELECT ja.action, ja.entite, ja.date_action, ja.nouvelle_valeur, u.nom, u.prenom
			 FROM journal_audit ja
			 LEFT JOIN utilisateur u ON u.id_utilisateur = ja.id_utilisateur
			 WHERE ja.id_agence = :id_agence
			 ORDER BY ja.date_action DESC
			 LIMIT :limit"
		);
		$stmt->bindValue(':id_agence', $idAgence, PDO::PARAM_INT);
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->execute();

		return array_map(static function (array $row): array {
			$nouvelleValeur = $row['nouvelle_valeur'] ? json_decode((string) $row['nouvelle_valeur'], true) : null;
			[$icon, $tone, $title] = self::activityPresentation($row['action'], $row['entite'], is_array($nouvelleValeur) ? $nouvelleValeur : []);
			$who = trim(($row['prenom'] ?? '') . ' ' . ($row['nom'] ?? ''));
			return [
				'icon' => $icon,
				'tone' => $tone,
				'title' => $title,
				'sub' => $who !== '' ? $who : 'Système',
				'time' => self::relativeTime(new DateTimeImmutable($row['date_action'])),
			];
		}, $stmt->fetchAll());
	}

	private static function countKpi(PDO $db, string $table, int $idAgence, string $label, string $icon, string $tone): array
	{
		$stmt = $db->prepare("SELECT COUNT(*) FROM {$table} WHERE id_agence = :id_agence AND deleted_at IS NULL");
		$stmt->execute(['id_agence' => $idAgence]);
		return [
			'label' => $label,
			'value' => number_format((int) $stmt->fetchColumn(), 0, ',', ' '),
			'icon' => $icon,
			'tone' => $tone,
		];
	}

	private static function activeContractsKpi(PDO $db, int $idAgence): array
	{
		$stmt = $db->prepare(
			"SELECT COUNT(*) FROM contrat WHERE id_agence = :id_agence AND statut = 'ACTIVE' AND deleted_at IS NULL"
		);
		$stmt->execute(['id_agence' => $idAgence]);
		return [
			'label' => 'Contrats actifs',
			'value' => number_format((int) $stmt->fetchColumn(), 0, ',', ' '),
			'icon' => 'card',
			'tone' => 'violet',
		];
	}

	private const ENTITY_LABELS = [
		'agence' => 'une agence',
		'utilisateur' => 'un utilisateur',
		'bien' => 'un bien',
		'contrat' => 'un contrat',
		'paiement' => 'un paiement',
		'abonnement' => 'un abonnement',
	];

	/** @param array<string,mixed> $nouvelleValeur */
	private static function activityPresentation(string $action, string $entite, array $nouvelleValeur = []): array
	{
		if ($action === 'UPDATE' && isset($nouvelleValeur['statut']) && $entite === 'utilisateur') {
			return match ($nouvelleValeur['statut']) {
				'ACTIVE' => ['check', 'teal', 'Compte utilisateur activé'],
				'INACTIVE' => ['x', 'red', 'Compte utilisateur désactivé'],
				'LOCKED' => ['shield', 'gray', 'Compte utilisateur verrouillé'],
				default => ['edit', 'amber', 'Statut du compte modifié'],
			};
		}

		$label = self::ENTITY_LABELS[$entite] ?? 'un élément';

		return match (true) {
			$action === 'CREATE' && $entite === 'agence' => ['plus', 'teal', 'Agence créée'],
			$action === 'CREATE' && $entite === 'utilisateur' => ['user', 'blue', 'Nouvel utilisateur ajouté'],
			$action === 'CREATE' => ['plus', 'teal', "Création d’{$label}"],
			$action === 'UPDATE' && $entite === 'agence' => ['edit', 'amber', 'Informations d’agence modifiées'],
			$action === 'UPDATE' && $entite === 'utilisateur' => ['edit', 'amber', 'Profil utilisateur modifié'],
			$action === 'UPDATE' => ['edit', 'amber', "Modification d’{$label}"],
			$action === 'DELETE' => ['x', 'red', "Suppression d’{$label}"],
			$action === 'LOGIN' => ['shield', 'gray', 'Connexion'],
			$action === 'LOGOUT' => ['shield', 'gray', 'Déconnexion'],
			default => ['shield', 'gray', "Action sur {$label}"],
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
