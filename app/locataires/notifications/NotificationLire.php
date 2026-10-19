<?php

declare(strict_types=1);

require_once __DIR__ . '/NotificationController.php';
require_once dirname(__DIR__, 3) . '/core/Authorization.php';

$controller = new NotificationController();
$user = Authorization::requireRole('Locataire');

$idNotification = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idNotification || $idNotification <= 0) {
    http_response_code(400);
    exit('Notification invalide.');
}

$notification = $controller->getNotification($idNotification);

if (!$notification) {
    http_response_code(404);
    exit('Notification introuvable.');
}

if (($notification['statut'] ?? '') !== 'READ') {
    $controller->markAsRead($idNotification);
    $notification = $controller->getNotification($idNotification);

    if (!$notification) {
        http_response_code(404);
        exit('Notification introuvable.');
    }
}

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

$prenom = (string) ($user['prenom'] ?? '');
$nom = (string) ($user['nom'] ?? '');

$pageTitle = $notification['titre'] ?? 'Notification';
$activeNav = 'Notifications';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Notification';
$greetingSubtitle = 'Consultez les détails de cette notification.';
$profileName = trim($prenom . ' ' . $nom) ?: 'Locataire';
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'L';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/locataires/ops.css?v=' . filemtime(dirname(__DIR__) . '/ops.css') . '">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <a class="detail-back" href="notification.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour aux notifications
    </a>

    <div class="panel">
      <div class="panel-header">
        <div style="display:flex;align-items:center;gap:10px;">
          <div class="quick-link-icon" style="margin:0;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>
          <div class="panel-title"><?= e($notification['titre'] ?? 'Notification') ?></div>
        </div>
        <span class="status-badge inactif">Lue</span>
      </div>

      <p style="padding:0 18px;font-size:14px;color:var(--text);line-height:1.6;"><?= nl2br(e($notification['message'] ?? '')) ?></p>

      <div class="info-list">
        <div class="info-row"><span class="info-label">Date de création</span><span class="info-value"><?= dateFr($notification['created_at'] ?? null) ?></span></div>
        <div class="info-row"><span class="info-label">Date de lecture</span><span class="info-value"><?= dateFr($notification['date_lecture'] ?? null) ?></span></div>
        <div class="info-row"><span class="info-label">Type</span><span class="info-value"><?= e($notification['type'] ?? 'INTERNAL') ?></span></div>
      </div>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
