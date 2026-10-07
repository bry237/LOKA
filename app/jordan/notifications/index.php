<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once __DIR__ . '/NotificationController.php';

$user = Auth::requireLogin();
$userId = (int) $user['id_utilisateur'];

$statusFilter = $_GET['status'] ?? '';
$data = NotificationController::index($userId, $statusFilter);
$notifications = $data['notifications'];
$unreadCount = $data['unreadCount'];
$totalCount = count(NotificationModel::listForUser($userId));
$readCount = count(NotificationModel::listForUser($userId, 'READ'));

$currentPage = 'dashboard';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Centre de notifications | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php require_once dirname(__DIR__) . '/layouts/sidebar-gestion.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <span class="eyebrow">Notifications</span>
                <h1 class="page-title">Centre de notifications</h1>
            </div>
            <div class="topbar-right">
                <form action="lire.php" method="POST" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="mark_all">
                    <button type="submit" class="btn btn-secondary">Tout marquer comme lu</button>
                </form>
            </div>
        </header>
        <main class="page">
            <div class="summary-row">
                <div><strong>Total:</strong> <?= $totalCount ?></div>
                <div><strong>Non lues:</strong> <?= $unreadCount ?></div>
                <div><strong>Lues:</strong> <?= $readCount ?></div>
            </div>
            <div class="filters">
                <a href="index.php" class="btn <?= $statusFilter === '' ? 'btn-primary' : 'btn-outline' ?>">Toutes</a>
                <a href="index.php?status=PENDING" class="btn <?= $statusFilter === 'PENDING' ? 'btn-primary' : 'btn-outline' ?>">Non lues</a>
                <a href="index.php?status=READ" class="btn <?= $statusFilter === 'READ' ? 'btn-primary' : 'btn-outline' ?>">Lues</a>
            </div>
            
            <div class="notifications-list">
                <?php if (empty($notifications)): ?>
                    <p>Aucune notification trouvée.</p>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notification-card <?= $notif['statut'] !== 'READ' ? 'unread' : '' ?>">
                            <div class="notification-content">
                                <h4><?= htmlspecialchars($notif['titre'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($notif['statut'] !== 'READ'): ?>
                                        <span class="badge unread">Non lue</span>
                                    <?php else: ?>
                                        <span class="badge read">Lue</span>
                                    <?php endif; ?>
                                </h4>
                                <p><?= htmlspecialchars($notif['message'], ENT_QUOTES, 'UTF-8') ?></p>
                                <span class="notification-meta"><?= htmlspecialchars($notif['created_at'], ENT_QUOTES, 'UTF-8') ?> - Type: <?= htmlspecialchars($notif['type'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="notification-actions">
                                <?php if ($notif['statut'] !== 'READ'): ?>
                                    <form action="lire.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="id_notification" value="<?= (int)$notif['id_notification'] ?>">
                                        <button type="submit" class="btn btn-sm btn-secondary">Marquer lue</button>
                                    </form>
                                <?php endif; ?>
                                <form action="supprimer.php" method="POST" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette notification ?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="id_notification" value="<?= (int)$notif['id_notification'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
</body>
</html>
