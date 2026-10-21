<?php

declare(strict_types=1);

require_once __DIR__ . '/LocataireController.php';

$controller = new LocataireController();

$locataire = $controller->getCurrentLocataire();
$logement = $controller->getCurrentHousing();

if (!$locataire) {
    http_response_code(404);
    exit('Locataire introuvable.');
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function money($value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    return number_format((float) $value, 2, ',', ' ') . ' €';
}

function dateFr(?string $date): string
{
    if (!$date) {
        return '—';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return e($date);
    }

    return date('d/m/Y', $timestamp);
}

$prenom = $locataire['prenom'] ?? '';
$nom = $locataire['nom'] ?? '';

$pageTitle = 'Mon logement';
$activeNav = 'Mon logement';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Mon logement';
$greetingSubtitle = 'Consultez les informations concernant votre logement.';
$profileName = trim($prenom . ' ' . $nom);
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">'
    . '<link rel="stylesheet" href="ops.css?v=' . filemtime(__DIR__ . '/ops.css') . '">';

require dirname(__DIR__, 2) . '/layouts/header.php';
?>
<?php if (!$logement): ?>
    <div class="panel">
      <div class="empty-state" style="padding:48px 20px;">
        <div class="empty-state-icon">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>
        </div>
        <h4>Aucun logement pour le moment</h4>
        <p>Vous n'avez pas encore de logement associé à votre profil. Les informations apparaîtront ici une fois votre contrat actif.</p>
        <a href="recherche.php" class="btn btn-primary" style="margin-top:16px;">Rechercher un logement</a>
      </div>
    </div>
<?php else: ?>
    <div class="panel" style="margin-bottom:18px;">
      <div class="panel-header">
        <div>
          <div class="panel-title"><?= e($logement['titre']) ?></div>
          <p class="panel-sub"><?= e($logement['type_bien']) ?> · Référence <?= e($logement['reference']) ?></p>
        </div>
        <span class="status-badge actif">Logement occupé</span>
      </div>

      <div class="address-row">
        <div class="housing-info-icon">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-6-5.686-6-10a6 6 0 0 1 12 0c0 4.314-6 10-6 10Z"/><circle cx="12" cy="11" r="2.5"/></svg>
        </div>
        <div>
          <strong><?= e($logement['ligne1']) ?></strong>
          <?php if (!empty($logement['ligne2'])): ?><span><?= e($logement['ligne2']) ?></span><?php endif; ?>
          <span><?= e($logement['code_postal']) ?> <?= e($logement['ville']) ?><?php if (!empty($logement['pays'])): ?> · <?= e($logement['pays']) ?><?php endif; ?></span>
        </div>
      </div>

      <div class="mini-stats cols-4" style="padding:0 18px 18px;">
        <div class="mini-stat"><div class="num" style="font-size:14px;"><?= e($logement['type_bien']) ?></div><div class="lbl">Type de bien</div></div>
        <div class="mini-stat"><div class="num"><?= number_format((float) $logement['surface'], 0, ',', ' ') ?> m²</div><div class="lbl">Surface</div></div>
        <div class="mini-stat"><div class="num"><?= e((string) $logement['nombre_pieces']) ?></div><div class="lbl">Pièces</div></div>
        <div class="mini-stat"><div class="num"><?= $logement['etage'] !== null ? e((string) $logement['etage']) : '—' ?></div><div class="lbl">Étage</div></div>
      </div>
    </div>

    <div class="detail-grid" style="margin-bottom:18px;">
      <div class="panel">
        <div class="panel-header"><div class="panel-title">Informations financières</div></div>
        <div class="info-list">
          <div class="info-row"><span class="info-label">Loyer</span><span class="info-value"><?= money($logement['contrat_loyer']) ?></span></div>
          <div class="info-row"><span class="info-label">Charges</span><span class="info-value"><?= money($logement['contrat_charges']) ?></span></div>
          <div class="info-row"><span class="info-label">Total mensuel</span><span class="info-value"><?= money((float) $logement['contrat_loyer'] + (float) $logement['contrat_charges']) ?></span></div>
          <div class="info-row"><span class="info-label">Dépôt de garantie</span><span class="info-value"><?= money($logement['depot_garantie']) ?></span></div>
          <div class="info-row"><span class="info-label">Paiement</span><span class="info-value"><?= e($logement['frequence_paiement']) ?></span></div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header"><div class="panel-title">Informations du contrat</div></div>
        <div class="info-list">
          <div class="info-row"><span class="info-label">Numéro du contrat</span><span class="info-value"><?= e($logement['numero_contrat']) ?></span></div>
          <div class="info-row"><span class="info-label">Date d'entrée</span><span class="info-value"><?= dateFr($logement['date_entree'] ?? $logement['date_debut']) ?></span></div>
          <div class="info-row"><span class="info-label">Date de fin</span><span class="info-value"><?= dateFr($logement['date_fin']) ?></span></div>
          <div class="info-row"><span class="info-label">Jour d'échéance</span><span class="info-value"><?= e((string) $logement['jour_echeance']) ?></span></div>
        </div>
        <div class="form-actions">
          <a href="contrats/detail.php?id=<?= (int) $logement['id_contrat'] ?>" class="btn btn-primary">Consulter mon contrat</a>
        </div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header"><div class="panel-title">Description</div></div>
      <p style="padding:4px 18px 18px;font-size:13px;color:var(--text);line-height:1.6;">
        <?php if (!empty($logement['description'])): ?>
          <?= nl2br(e($logement['description'])) ?>
        <?php else: ?>
          Aucune description n'est disponible pour ce logement.
        <?php endif; ?>
      </p>
    </div>
<?php endif; ?>
<?php
require dirname(__DIR__, 2) . '/layouts/footer.php';
