<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Model.php';

final class NotificationModel extends Model
{
    /**
     * Récupère toutes les notifications d'un utilisateur.
     */
    public function getByUtilisateur(int $idUtilisateur): array
    {
        $sql = "
            SELECT
                id_notification,
                id_utilisateur,
                titre,
                message,
                type,
                statut,
                date_envoi,
                date_lecture,
                created_at
            FROM notification
            WHERE id_utilisateur = :id_utilisateur
            ORDER BY created_at DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_utilisateur' => $idUtilisateur
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère une notification appartenant à un utilisateur.
     */
    public function findByIdForUtilisateur(
        int $idNotification,
        int $idUtilisateur
    ): ?array {
        $sql = "
            SELECT
                id_notification,
                id_utilisateur,
                titre,
                message,
                type,
                statut,
                date_envoi,
                date_lecture,
                created_at
            FROM notification
            WHERE id_notification = :id_notification
              AND id_utilisateur = :id_utilisateur
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_notification' => $idNotification,
            'id_utilisateur' => $idUtilisateur
        ]);

        $notification = $stmt->fetch(PDO::FETCH_ASSOC);

        return $notification ?: null;
    }

    /**
     * Marque une notification comme lue.
     */
    public function markAsRead(
        int $idNotification,
        int $idUtilisateur
    ): bool {
        $sql = "
            UPDATE notification
            SET
                statut = 'READ',
                date_lecture = NOW()
            WHERE id_notification = :id_notification
              AND id_utilisateur = :id_utilisateur
              AND statut <> 'READ'
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id_notification' => $idNotification,
            'id_utilisateur' => $idUtilisateur
        ]);
    }

    /**
     * Compte les notifications non lues.
     */
    public function countUnread(int $idUtilisateur): int
    {
        $sql = "
            SELECT COUNT(*)
            FROM notification
            WHERE id_utilisateur = :id_utilisateur
              AND statut <> 'READ'
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'id_utilisateur' => $idUtilisateur
        ]);

        return (int) $stmt->fetchColumn();
    }
}