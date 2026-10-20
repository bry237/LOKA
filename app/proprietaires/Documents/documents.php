<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/ProprietaireController.php';

$controller = new ProprietaireController();
$proprietaire = $controller->getCurrentProprietaire();

$filtres = [
    'recherche' => trim((string) ($_GET['recherche'] ?? '')),
    'id_type_document' => (int) ($_GET['id_type_document'] ?? 0),
    'id_bien' => (int) ($_GET['id_bien'] ?? 0),
    'id_contrat' => (int) ($_GET['id_contrat'] ?? 0),
];

$stats = $controller->getDocumentsStats();
$types = $controller->getTypesDocuments();
$biens = $controller->getMesBiens();
$contrats = $controller->getContrats();
$documents = $controller->getDocuments($filtres);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatDate(?string $date): string
{
    if (empty($date)) {
        return '—';
    }
    $timestamp = strtotime($date);

    return $timestamp === false ? '—' : date('d/m/Y', $timestamp);
}

function formatTaille(?int $bytes): string
{
    if ($bytes === null || $bytes < 0) {
        return '—';
    }
    if ($bytes < 1024) {
        return $bytes . ' o';
    }
    $units = ['Ko', 'Mo', 'Go'];
    $i = min((int) floor(log($bytes, 1024)) - 1, count($units) - 1);

    return number_format($bytes / (1024 ** ($i + 1)), 1, ',', ' ') . ' ' . $units[$i];
}

$prenom = (string) ($proprietaire['prenom'] ?? '');
$nom = (string) ($proprietaire['nom'] ?? '');

$pageTitle = 'Mes documents';
$activeNav = 'Documents';
$sidebarFile = 'sidebar-proprietaire.php';
$greetingTitle = 'Mes documents';
$greetingSubtitle = 'Les documents associés à vos biens, contrats et locations.';
$profileName = trim($prenom . ' ' . $nom) ?: 'Propriétaire';
$profileRole = 'Propriétaire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'P';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon teal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg></div></div>
        <div class="kpi-value"><?= $stats['total'] ?></div>
        <div class="kpi-label">Total documents</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/></svg></div></div>
        <div class="kpi-value"><?= $stats['biens'] ?></div>
        <div class="kpi-label">Liés aux biens</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon violet"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></div></div>
        <div class="kpi-value"><?= $stats['contrats'] ?></div>
        <div class="kpi-label">Liés aux contrats</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div></div>
        <div class="kpi-value"><?= $stats['locataires'] ?></div>
        <div class="kpi-label">Liés aux locataires</div>
      </div>
    </div>

    <form class="filters-form" method="get" action="documents.php">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="recherche" placeholder="Nom, bien, contrat, locataire…" value="<?= e($filtres['recherche']) ?>" autocomplete="off">
      </div>
      <select class="filter-select" name="id_type_document">
        <option value="0">Tous les types</option>
        <?php foreach ($types as $type): ?>
          <option value="<?= (int) $type['id_type_document'] ?>"<?= $filtres['id_type_document'] === (int) $type['id_type_document'] ? ' selected' : '' ?>><?= e($type['nom']) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="filter-select" name="id_bien">
        <option value="0">Tous les biens</option>
        <?php foreach ($biens as $bien): ?>
          <option value="<?= (int) $bien['id_bien'] ?>"<?= $filtres['id_bien'] === (int) $bien['id_bien'] ? ' selected' : '' ?>><?= e($bien['reference'] ?? $bien['titre']) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="filter-select" name="id_contrat">
        <option value="0">Tous les contrats</option>
        <?php foreach ($contrats as $contrat): ?>
          <option value="<?= (int) $contrat['id_contrat'] ?>"<?= $filtres['id_contrat'] === (int) $contrat['id_contrat'] ? ' selected' : '' ?>><?= e($contrat['numero']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary">Filtrer</button>
      <a href="documents.php" class="btn btn-sm">Réinitialiser</a>
    </form>

    <div class="panel table-panel">
      <div class="panel-header"><div class="panel-title">Documents</div><span style="font-size:12px;color:var(--text);"><?= count($documents) ?> document<?= count($documents) > 1 ? 's' : '' ?> trouvé<?= count($documents) > 1 ? 's' : '' ?></span></div>
      <?php if (empty($documents)): ?>
        <div class="empty-state" style="padding:48px 20px;">
          <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg></div>
          <h4>Aucun document trouvé</h4>
          <p>Aucun document ne correspond aux critères sélectionnés.</p>
        </div>
      <?php else: ?>
        <div class="table-scroll">
          <table>
            <thead><tr><th>DOCUMENT</th><th>TYPE</th><th>BIEN</th><th>CONTRAT</th><th>LOCATAIRE</th><th>TAILLE</th><th>DATE</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($documents as $document): ?>
                <?php
                $hasFile = trim((string) ($document['chemin_stockage'] ?? '')) !== '';
                $nomLocataire = trim(($document['locataire_prenom'] ?? '') . ' ' . ($document['locataire_nom'] ?? ''));
                ?>
                <tr>
                  <td class="cell-title"><?= e($document['nom'] ?: $document['nom_original'] ?: 'Document sans nom') ?></td>
                  <td class="cell-sub"><?= e($document['type_document_nom'] ?? 'Autre') ?></td>
                  <td class="cell-sub"><?= e($document['reference_bien'] ?? '—') ?></td>
                  <td class="cell-sub"><?= e($document['numero_contrat'] ?? '—') ?></td>
                  <td class="cell-sub"><?= $nomLocataire !== '' ? e($nomLocataire) : '—' ?></td>
                  <td class="cell-sub"><?= formatTaille($document['taille_octets'] !== null ? (int) $document['taille_octets'] : null) ?></td>
                  <td class="cell-sub"><?= formatDate($document['created_at'] ?? null) ?></td>
                  <td>
                    <?php if ($hasFile): ?>
                      <a class="consult-link" href="telechargerDocument.php?id=<?= (int) $document['id_document'] ?>&action=telecharger">
                        Télécharger
                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M4 19h16"/></svg>
                      </a>
                    <?php else: ?>
                      <span class="cell-sub">Aucun fichier</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
