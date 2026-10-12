<?php
declare(strict_types=1);

require_once __DIR__ . '/NotificationModel.php';

class NotificationController
{
    public static function index(int $userId, string $status = ''): array
    {
        $notifications = NotificationModel::listForUser($userId, $status);
        $unreadCount = NotificationModel::unreadCount($userId);
        return [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount
        ];
    }
    
    public static function markAsRead(int $userId, int $notificationId): bool
    {
        return NotificationModel::markAsRead($userId, $notificationId);
    }

    public static function markAllAsRead(int $userId): int
    {
        return NotificationModel::markAllAsRead($userId);
    }
    
    public static function delete(int $userId, int $notificationId): bool
    {
        return NotificationModel::delete($userId, $notificationId);
    }
}
