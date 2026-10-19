<?php

declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/NotificationModel.php';

final class NotificationController
{
    private NotificationModel $model;

    public function __construct()
    {
        $this->model = new NotificationModel();
    }

    /**
     * Récupère l'utilisateur connecté.
     */
    private function getCurrentUser(): array
    {
        return Authorization::requireRole('Locataire');
    }

    /**
     * Récupère toutes les notifications du locataire connecté.
     */
    public function getMyNotifications(): array
    {
        $user = $this->getCurrentUser();

        $idUtilisateur = (int) ($user['id_utilisateur'] ?? 0);

        if ($idUtilisateur <= 0) {
            return [];
        }

        return $this->model->getByUtilisateur($idUtilisateur);
    }

    /**
     * Récupère une notification précise du locataire connecté.
     */
    public function getNotification(int $idNotification): ?array
    {
        $user = $this->getCurrentUser();

        $idUtilisateur = (int) ($user['id_utilisateur'] ?? 0);

        if ($idUtilisateur <= 0) {
            return null;
        }

        return $this->model->findByIdForUtilisateur(
            $idNotification,
            $idUtilisateur
        );
    }

    /**
     * Marque une notification comme lue.
     */
    public function markAsRead(int $idNotification): bool
    {
        $user = $this->getCurrentUser();

        $idUtilisateur = (int) ($user['id_utilisateur'] ?? 0);

        if ($idUtilisateur <= 0) {
            return false;
        }

        return $this->model->markAsRead(
            $idNotification,
            $idUtilisateur
        );
    }

    /**
     * Compte les notifications non lues.
     */
    public function getUnreadCount(): int
    {
        $user = $this->getCurrentUser();

        $idUtilisateur = (int) ($user['id_utilisateur'] ?? 0);

        if ($idUtilisateur <= 0) {
            return 0;
        }

        return $this->model->countUnread($idUtilisateur);
    }
}