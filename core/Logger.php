<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

final class Logger
{
	public static function audit(
		string $action,
		string $entite,
		?int $idEntite = null,
		?int $idUtilisateur = null,
		?int $idAgence = null,
		?array $ancienneValeur = null,
		?array $nouvelleValeur = null
	): void {
		$stmt = Database::connection()->prepare(
			'INSERT INTO journal_audit (id_agence, id_utilisateur, action, entite, id_entite, ancienne_valeur, nouvelle_valeur, adresse_ip, user_agent)
			 VALUES (:id_agence, :id_utilisateur, :action, :entite, :id_entite, :ancienne_valeur, :nouvelle_valeur, :adresse_ip, :user_agent)'
		);
		$stmt->execute([
			'id_agence' => $idAgence,
			'id_utilisateur' => $idUtilisateur,
			'action' => $action,
			'entite' => $entite,
			'id_entite' => $idEntite,
			'ancienne_valeur' => $ancienneValeur !== null ? json_encode($ancienneValeur, JSON_UNESCAPED_UNICODE) : null,
			'nouvelle_valeur' => $nouvelleValeur !== null ? json_encode($nouvelleValeur, JSON_UNESCAPED_UNICODE) : null,
			'adresse_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
			'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
		]);
	}
}
