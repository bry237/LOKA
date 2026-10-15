<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Database.php';

final class ParametreModel
{
	public static function get(string $cle, ?string $default = null): ?string
	{
		$stmt = Database::connection()->prepare('SELECT valeur FROM parametre_plateforme WHERE cle = :cle LIMIT 1');
		$stmt->execute(['cle' => $cle]);
		$value = $stmt->fetchColumn();
		return $value === false ? $default : (string) $value;
	}

	/** @param string[] $cles @return array<string,?string> */
	public static function getMany(array $cles): array
	{
		if (!$cles) {
			return [];
		}
		$placeholders = implode(',', array_fill(0, count($cles), '?'));
		$stmt = Database::connection()->prepare("SELECT cle, valeur FROM parametre_plateforme WHERE cle IN ({$placeholders})");
		$stmt->execute(array_values($cles));
		$found = array_column($stmt->fetchAll(), 'valeur', 'cle');
		$result = [];
		foreach ($cles as $cle) {
			$result[$cle] = $found[$cle] ?? null;
		}
		return $result;
	}

	public static function set(string $cle, ?string $valeur): void
	{
		$stmt = Database::connection()->prepare(
			'INSERT INTO parametre_plateforme (cle, valeur) VALUES (:cle, :valeur)
			 ON DUPLICATE KEY UPDATE valeur = :valeur2'
		);
		$stmt->execute(['cle' => $cle, 'valeur' => $valeur, 'valeur2' => $valeur]);
	}
}
