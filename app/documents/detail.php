<?php

declare(strict_types=1);

require_once __DIR__ . '/DocumentController.php';

$controller = new DocumentController();

$idDocument = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idDocument) {
    http_response_code(400);
    exit('Document invalide.');
}

$document = $controller->getDocument((int) $idDocument);

if (!$document) {
    http_response_code(404);
    exit('Document introuvable ou accès refusé.');
}

$locataire = $controller->getCurrentLocataire();

if (!$locataire) {
    http_response_code(404);
    exit('Locataire introuvable.');
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

function formatFileSize($bytes): string
{
    if ($bytes === null || $bytes === '') {
        return '—';
    }

    $bytes = (int) $bytes;

    if ($bytes < 1024) {
        return $bytes . ' o';
    }

    if ($bytes < 1024 * 1024) {
        return number_format($bytes / 1024, 1, ',', ' ') . ' Ko';
    }

    if ($bytes < 1024 * 1024 * 1024) {
        return number_format($bytes / (1024 * 1024), 1, ',', ' ') . ' Mo';
    }

    return number_format($bytes / (1024 * 1024 * 1024), 1, ',', ' ') . ' Go';
}

$prenom = e($locataire['prenom'] ?? '');
$nom = e($locataire['nom'] ?? '');
$docName = $document['nom'] ?? $document['nom_original'] ?? 'Document';

$pageTitle = $docName;
$activeNav = 'Documents';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Détail du document';
$greetingSubtitle = 'Consultez les informations de votre document.';
$profileName = trim($prenom . ' ' . $nom);
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));

require dirname(__DIR__, 2) . '/layouts/header.php';
?>
    <a class="detail-back" href="index.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour aux documents
    </a>

    <div class="detail-header">
      <div class="detail-identity">
        <div class="detail-avatar"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></div>
        <div>
          <h1 class="detail-name"><?= e($docName) ?></h1>
          <p class="detail-sub">Informations du document</p>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="info-list">
        <div class="info-row"><span class="info-label">Nom</span><span class="info-value"><?= e($document['nom'] ?? '—') ?></span></div>
        <div class="info-row"><span class="info-label">Nom original</span><span class="info-value"><?= e($document['nom_original'] ?? '—') ?></span></div>
        <div class="info-row"><span class="info-label">Type de fichier</span><span class="info-value"><?= e($document['mime_type'] ?? '—') ?></span></div>
        <div class="info-row"><span class="info-label">Taille</span><span class="info-value"><?= formatFileSize($document['taille_octets'] ?? null) ?></span></div>
        <div class="info-row"><span class="info-label">Version</span><span class="info-value">v<?= (int) ($document['version'] ?? 1) ?></span></div>
        <div class="info-row"><span class="info-label">Date d'ajout</span><span class="info-value"><?= dateFr($document['created_at'] ?? null) ?></span></div>
        <div class="info-row"><span class="info-label">Dernière modification</span><span class="info-value"><?= dateFr($document['updated_at'] ?? null) ?></span></div>
      </div>
      <div class="form-actions">
        <a href="telecharger.php?id=<?= (int) $document['id_document'] ?>" class="btn btn-primary">Télécharger le document</a>
        <a href="supprimer.php?id=<?= (int) $document['id_document'] ?>" class="btn btn-danger">Supprimer</a>
      </div>
    </div>
<?php
require dirname(__DIR__, 2) . '/layouts/footer.php';
