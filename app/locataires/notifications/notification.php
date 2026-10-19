<?php

declare(strict_types=1);

require_once __DIR__ . '/NotificationController.php';
require_once dirname(__DIR__, 3) . '/core/Authorization.php';

$controller = new NotificationController();

$user = Authorization::requireRole('Locataire');

$notifications = $controller->getMyNotifications();
$unreadCount = $controller->getUnreadCount();

$prenom = (string) ($user['prenom'] ?? '');
$nom = (string) ($user['nom'] ?? '');

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function dateFr(?string $date): string
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime($date);

    return $timestamp === false ? e($date) : date('d/m/Y à H:i', $timestamp);
}

function statutLabel(string $statut): string
{
    return match ($statut) {
        'READ' => 'Lue',
        'SENT' => 'Envoyée',
        'PENDING' => 'En attente',
        'FAILED' => 'Échec',
        default => $statut,
    };
}

function statutTone(string $statut): string
{
    return match ($statut) {
        'READ' => 'inactif',
        'SENT' => 'actif',
        'PENDING' => 'attente',
        'FAILED' => 'suspendu',
        default => 'inactif',
    };
}

$pageTitle = 'Notifications';
$activeNav = 'Notifications';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Notifications';
$greetingSubtitle = 'Consultez les messages et informations qui vous sont destinés.';
$profileName = trim($prenom . ' ' . $nom) ?: 'Locataire';
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'L';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if ($unreadCount > 0): ?>
      <div class="feedback" style="background:#E3F4F4;color:var(--dark);border:1px solid #C7E5E5;">
        <?= $unreadCount ?> notification<?= $unreadCount > 1 ? 's' : '' ?> non lue<?= $unreadCount > 1 ? 's' : '' ?>
      </div>
    <?php endif; ?>

    <?php if (empty($notifications)): ?>
      <div class="panel">
        <div class="empty-state" style="padding:48px 20px;">
          <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>
          <h4>Aucune notification</h4>
          <p>Vous n'avez actuellement aucune notification.</p>
        </div>
      </div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:12px;">
        <?php foreach ($notifications as $notification): ?>
          <?php
          $isUnread = ($notification['statut'] ?? '') !== 'READ';
          $statut = (string) ($notification['statut'] ?? '');
          ?>
          <div class="panel" style="padding:18px;<?= $isUnread ? 'border-left:3px solid var(--primary);' : '' ?>">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:14px;">
              <div style="display:flex;align-items:center;gap:8px;">
                <?php if ($isUnread): ?><span style="width:7px;height:7px;border-radius:50%;background:var(--primary);flex-shrink:0;"></span><?php endif; ?>
                <strong style="font-size:14px;color:var(--heading);"><?= e($notification['titre'] ?? 'Notification') ?></strong>
              </div>
              <span class="status-badge <?= statutTone($statut) ?>"><?= e(statutLabel($statut)) ?></span>
            </div>
            <p style="margin:10px 0 0;font-size:13px;color:var(--text);line-height:1.5;"><?= nl2br(e($notification['message'] ?? '')) ?></p>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:12px;padding-top:12px;border-top:1px solid var(--border);">
              <span style="font-size:11.5px;color:#8AA0A0;"><?= dateFr($notification['created_at'] ?? null) ?></span>
              <?php if ($isUnread): ?>
                <a href="NotificationLire.php?id=<?= (int) $notification['id_notification'] ?>" class="btn btn-primary btn-sm">Lire la notification</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
