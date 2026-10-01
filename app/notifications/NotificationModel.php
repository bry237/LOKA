<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Database.php';

class NotificationModel
{
    public static function listForUser(int $userId, string $status = ''): array
    {
        $db = Database::connection();
        $query = "SELECT * FROM notification WHERE id_utilisateur = :id_utilisateur";
        $params = [':id_utilisateur' => $userId];
        if ($status !== '') {
            $query .= " AND statut = :statut";
            $params[':statut'] = $status;
        }
        $query .= " ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $userId, int $notificationId): ?array
    {
        $db = Database::connection();
        $stmt = $db->prepare("SELECT * FROM notification WHERE id_notification = :id_notification AND id_utilisateur = :id_utilisateur");
        $stmt->execute([
            ':id_notification' => $notificationId,
            ':id_utilisateur' => $userId
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public static function markAsRead(int $userId, int $notificationId): bool
    {
        $db = Database::connection();
        $stmt = $db->prepare("UPDATE notification SET statut = 'READ', date_lecture = NOW() WHERE id_notification = :id_notification AND id_utilisateur = :id_utilisateur AND statut != 'READ'");
        return $stmt->execute([
            ':id_notification' => $notificationId,
            ':id_utilisateur' => $userId
        ]);
    }

    public static function markAllAsRead(int $userId): int
    {
        $db = Database::connection();
        $stmt = $db->prepare("UPDATE notification SET statut = 'READ', date_lecture = NOW() WHERE id_utilisateur = :id_utilisateur AND statut != 'READ'");
        $stmt->execute([':id_utilisateur' => $userId]);
        return $stmt->rowCount();
    }

    public static function delete(int $userId, int $notificationId): bool
    {
        $db = Database::connection();
        $stmt = $db->prepare("DELETE FROM notification WHERE id_notification = :id_notification AND id_utilisateur = :id_utilisateur");
        return $stmt->execute([
            ':id_notification' => $notificationId,
            ':id_utilisateur' => $userId
        ]);
    }

    public static function unreadCount(int $userId): int
    {
        $db = Database::connection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM notification WHERE id_utilisateur = :id_utilisateur AND statut != 'READ'");
        $stmt->execute([':id_utilisateur' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function create(int $userId, string $titre, string $message, string $type = 'INTERNAL'): int
    {
        $db = Database::connection();
        $stmt = $db->prepare("INSERT INTO notification (id_utilisateur, titre, message, type, statut, date_envoi, created_at) VALUES (:id_utilisateur, :titre, :message, :type, 'PENDING', NOW(), NOW())");
        $stmt->execute([
            ':id_utilisateur' => $userId,
            ':titre' => $titre,
            ':message' => $message,
            ':type' => $type
        ]);
        return (int) $db->lastInsertId();
    }
}
