<?php

declare(strict_types=1);

require_once __DIR__ . '/DocumentController.php';

$controller = new DocumentController();

$locataire = $controller->getCurrentLocataire();
$documents = $controller->getMyDocuments();

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

    return $timestamp === false ? e($date) : date('d/m/Y', $timestamp);
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

$pageTitle = 'Mes documents';
$activeNav = 'Documents';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Mes documents';
$greetingSubtitle = 'Consultez et téléchargez vos documents.';
$profileName = trim($prenom . ' ' . $nom);
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

require dirname(__DIR__, 2) . '/layouts/header.php';
?>
    <div class="page-header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <div>
        <h1>Documents disponibles</h1>
        <p>Retrouvez ici les documents associés à votre compte.</p>
      </div>
      <a href="importer.php" class="btn btn-primary">+ Ajouter un document</a>
    </div>

    <div class="panel table-panel">
      <?php if (empty($documents)): ?>
        <div class="empty-state" style="padding:48px 20px;">
          <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></div>
          <h4>Aucun document disponible</h4>
          <p>Vous n'avez actuellement aucun document associé à votre compte.</p>
        </div>
      <?php else: ?>
        <div class="table-scroll">
          <table>
            <thead><tr><th>Document</th><th>Type</th><th>Taille</th><th>Version</th><th>Date</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($documents as $document): ?>
                <tr>
                  <td>
                    <div class="cell">
                      <div class="cell-avatar"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></div>
                      <div>
                        <div class="cell-title"><?= e($document['nom'] ?? $document['nom_original'] ?? 'Document') ?></div>
                        <?php if (!empty($document['nom_original'])): ?><div class="cell-sub"><?= e($document['nom_original']) ?></div><?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td class="cell-sub"><?= e($document['mime_type'] ?? 'Document') ?></td>
                  <td class="cell-sub"><?= formatFileSize($document['taille_octets'] ?? null) ?></td>
                  <td class="cell-sub">v<?= (int) ($document['version'] ?? 1) ?></td>
                  <td class="cell-sub"><?= dateFr($document['created_at'] ?? null) ?></td>
                  <td>
                    <div style="display:flex;gap:8px;">
                      <a href="detail.php?id=<?= (int) $document['id_document'] ?>" class="btn btn-sm">Détails</a>
                      <a href="telecharger.php?id=<?= (int) $document['id_document'] ?>" class="btn btn-primary btn-sm">Télécharger</a>
                      <a href="supprimer.php?id=<?= (int) $document['id_document'] ?>" class="btn btn-sm btn-danger">Supprimer</a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
<?php
require dirname(__DIR__, 2) . '/layouts/footer.php';
