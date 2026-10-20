<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/ProprietaireController.php';

$controller = new ProprietaireController();
$proprietaire = $controller->getCurrentProprietaire();

try {
    $quittances = $controller->getQuittances();
} catch (Throwable $e) {
    http_response_code(500);
    exit('Une erreur est survenue lors du chargement de vos quittances.');
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatDate(?string $date): string
{
    if (empty($date)) {
        return '—';
    }
    $timestamp = strtotime($date);

    return $timestamp === false ? '—' : date('d/m/Y', $timestamp);
}

function formatMontant($montant): string
{
    return number_format((float) $montant, 2, ',', ' ') . ' €';
}

$prenom = (string) ($proprietaire['prenom'] ?? '');
$nom = (string) ($proprietaire['nom'] ?? '');

$pageTitle = 'Mes quittances';
$activeNav = 'Quittances';
$sidebarFile = 'sidebar-proprietaire.php';
$greetingTitle = 'Mes quittances';
$greetingSubtitle = 'Consultez et téléchargez les quittances de loyer de vos biens.';
$profileName = trim($prenom . ' ' . $nom) ?: 'Propriétaire';
$profileRole = 'Propriétaire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'P';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="mini-stat" style="max-width:220px;margin-bottom:18px;">
      <div class="num"><?= count($quittances) ?></div>
      <div class="lbl">Nombre de quittances</div>
    </div>

    <div class="panel table-panel">
      <div class="panel-header"><div class="panel-title">Mes quittances</div></div>
      <?php if (empty($quittances)): ?>
        <div class="empty-state" style="padding:48px 20px;">
          <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M9 7h7M9 11h5M9 15h3"/></svg></div>
          <h4>Aucune quittance disponible</h4>
          <p>Les quittances apparaîtront ici dès qu'un loyer aura été encaissé sur l'un de vos biens.</p>
        </div>
      <?php else: ?>
        <div class="document-list">
          <?php foreach ($quittances as $quittance): ?>
            <div class="document-item">
              <div style="display:flex;gap:14px;min-width:0;">
                <div class="cell-avatar"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M9 7h7M9 11h5M9 15h3"/></svg></div>
                <div class="document-main">
                  <div class="document-title"><?= e($quittance['numero'] ?? 'Quittance') ?></div>
                  <div class="document-sub"><?= e($quittance['bien_titre'] ?? '') ?></div>
                  <div class="document-sub">Période : <strong style="color:var(--heading);"><?= formatDate($quittance['periode_debut'] ?? null) ?></strong> au <strong style="color:var(--heading);"><?= formatDate($quittance['periode_fin'] ?? null) ?></strong></div>
                  <div class="document-sub">Montant : <strong style="color:var(--heading);"><?= formatMontant($quittance['montant'] ?? 0) ?></strong> &nbsp;·&nbsp; Émise le : <strong style="color:var(--heading);"><?= formatDate($quittance['date_emission'] ?? null) ?></strong></div>
                </div>
              </div>
              <div class="document-actions">
                <a href="quittance-telecharger.php?id=<?= (int) ($quittance['id_paiement'] ?? 0) ?>" class="btn btn-primary btn-sm">
                  <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:3px;"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M4 19h16"/></svg>
                  Télécharger
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
