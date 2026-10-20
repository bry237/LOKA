<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/ProprietaireController.php';

$controller = new ProprietaireController();
$proprietaire = $controller->getCurrentProprietaire();
$paiements = $controller->getPaiements();

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

function statutPaiementBadge(?string $statut): array
{
    return match ($statut) {
        'PAID' => ['Payé', 'actif'],
        'PENDING' => ['En attente', 'attente'],
        'LATE' => ['En retard', 'suspendu'],
        'CANCELLED' => ['Annulé', 'inactif'],
        'REFUNDED' => ['Remboursé', 'inactif'],
        default => [$statut ?: 'Non renseigné', 'inactif'],
    };
}

function modePaiementLabel(?string $mode): string
{
    return match ($mode) {
        'CASH' => 'Espèces',
        'BANK_TRANSFER' => 'Virement',
        'CARD' => 'Carte',
        'CHEQUE' => 'Chèque',
        'DIRECT_DEBIT' => 'Prélèvement',
        default => $mode ?: '—',
    };
}

$statutFiltre = (string) ($_GET['statut'] ?? '');
if ($statutFiltre !== '') {
    $paiements = array_values(array_filter($paiements, static fn (array $p): bool => ($p['statut'] ?? null) === $statutFiltre));
}

$totalPaye = 0.0;
$totalAttente = 0.0;
$totalRetard = 0.0;
$nombrePaye = 0;
$nombreAttente = 0;
$nombreRetard = 0;

foreach ($paiements as $paiement) {
    $montant = (float) $paiement['montant'];
    switch ($paiement['statut'] ?? null) {
        case 'PAID':
            $totalPaye += $montant;
            $nombrePaye++;
            break;
        case 'PENDING':
            $totalAttente += $montant;
            $nombreAttente++;
            break;
        case 'LATE':
            $totalRetard += $montant;
            $nombreRetard++;
            break;
    }
}

$statutLabels = ['PENDING' => 'En attente', 'PAID' => 'Payé', 'LATE' => 'En retard', 'CANCELLED' => 'Annulé', 'REFUNDED' => 'Remboursé'];

$prenom = (string) ($proprietaire['prenom'] ?? '');
$nom = (string) ($proprietaire['nom'] ?? '');

$pageTitle = 'Paiements';
$activeNav = 'Paiements';
$sidebarFile = 'sidebar-proprietaire.php';
$greetingTitle = 'Paiements';
$greetingSubtitle = 'Consultez les paiements liés à vos contrats.';
$profileName = trim($prenom . ' ' . $nom) ?: 'Propriétaire';
$profileRole = 'Propriétaire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'P';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></div></div>
        <div class="kpi-value"><?= count($paiements) ?></div>
        <div class="kpi-label">Paiements</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div></div>
        <div class="kpi-value"><?= formatMontant($totalPaye) ?></div>
        <div class="kpi-label"><?= $nombrePaye ?> payé(s)</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div></div>
        <div class="kpi-value"><?= formatMontant($totalAttente) ?></div>
        <div class="kpi-label"><?= $nombreAttente ?> en attente</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div></div>
        <div class="kpi-value"><?= formatMontant($totalRetard) ?></div>
        <div class="kpi-label"><?= $nombreRetard ?> en retard</div>
      </div>
    </div>

    <form class="filters-form" method="get" action="index.php">
      <select class="filter-select" name="statut" onchange="this.form.submit()">
        <option value="">Tous les statuts</option>
        <?php foreach ($statutLabels as $key => $label): ?>
          <option value="<?= e($key) ?>"<?= $statutFiltre === $key ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </form>

    <div class="panel table-panel">
      <div class="panel-header"><div class="panel-title">Historique des paiements</div></div>
      <?php if (empty($paiements)): ?>
        <div class="empty-state" style="padding:48px 20px;">
          <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></div>
          <h4>Aucun paiement</h4>
          <p>Aucun paiement n'est actuellement associé à vos contrats.</p>
        </div>
      <?php else: ?>
        <div class="table-scroll">
          <table>
            <thead><tr><th>CONTRAT</th><th>LOCATAIRE</th><th>PÉRIODE</th><th>MONTANT</th><th>DATE PAIEMENT</th><th>MODE</th><th>STATUT</th></tr></thead>
            <tbody>
              <?php foreach ($paiements as $paiement): ?>
                <?php
                [$label, $tone] = statutPaiementBadge($paiement['statut'] ?? null);
                $nomLocataire = trim(($paiement['locataire_prenom'] ?? '') . ' ' . ($paiement['locataire_nom'] ?? ''));
                ?>
                <tr>
                  <td class="cell-title"><?= e($paiement['numero_contrat'] ?? '—') ?><div class="cell-sub"><?= e($paiement['reference_bien'] ?? '') ?><?= !empty($paiement['titre_bien']) ? ' — ' . e($paiement['titre_bien']) : '' ?></div></td>
                  <td class="cell-sub"><?= $nomLocataire !== '' ? e($nomLocataire) : 'Non renseigné' ?></td>
                  <td class="cell-sub">
                    <?= formatDate($paiement['date_echeance'] ?? null) ?>
                    <?php if (!empty($paiement['periode_debut']) && !empty($paiement['periode_fin'])): ?>
                      <div class="cell-sub"><?= formatDate($paiement['periode_debut']) ?> → <?= formatDate($paiement['periode_fin']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="cell-price"><?= formatMontant($paiement['montant']) ?></td>
                  <td class="cell-sub"><?= formatDate($paiement['date_paiement'] ?? null) ?></td>
                  <td class="cell-sub"><?= e(modePaiementLabel($paiement['mode_paiement'] ?? null)) ?></td>
                  <td><span class="status-badge <?= $tone ?>"><?= e($label) ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
