<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

/**
 * Lecture/accusé de réception des notifications d'un utilisateur (table `notification`,
 * alimentée aujourd'hui par SmsSender::send() pour chaque SMS envoyé — invitation d'équipe,
 * activation de compte/agence...). Un utilisateur qui n'a reçu aucun SMS n'a simplement rien
 * à voir ici, ce qui est le comportement attendu plutôt qu'un contenu fabriqué.
 */
final class NotificationModel
{
	public static function listForUser(int $idUtilisateur, int $limit = 15): array
	{
		$stmt = Database::connection()->prepare(
			'SELECT id_notification, titre, message, type, statut, date_envoi, date_lecture, created_at
			 FROM notification WHERE id_utilisateur = :id
			 ORDER BY created_at DESC, id_notification DESC LIMIT :limit'
		);
		$stmt->bindValue(':id', $idUtilisateur, PDO::PARAM_INT);
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
		$stmt->execute();
		return $stmt->fetchAll();
	}

	public static function unreadCount(int $idUtilisateur): int
	{
		$stmt = Database::connection()->prepare(
			"SELECT COUNT(*) FROM notification WHERE id_utilisateur = :id AND statut != 'READ'"
		);
		$stmt->execute(['id' => $idUtilisateur]);
		return (int) $stmt->fetchColumn();
	}

	public static function markAsRead(int $idUtilisateur, int $idNotification): void
	{
		$stmt = Database::connection()->prepare(
			"UPDATE notification SET statut = 'READ', date_lecture = NOW()
			 WHERE id_notification = :id_notification AND id_utilisateur = :id_utilisateur AND statut != 'READ'"
		);
		$stmt->execute(['id_notification' => $idNotification, 'id_utilisateur' => $idUtilisateur]);
	}

	public static function markAllAsRead(int $idUtilisateur): void
	{
		$stmt = Database::connection()->prepare(
			"UPDATE notification SET statut = 'READ', date_lecture = NOW()
			 WHERE id_utilisateur = :id AND statut != 'READ'"
		);
		$stmt->execute(['id' => $idUtilisateur]);
	}
}
