<?php

declare(strict_types=1);

require_once __DIR__ . '/PaiementController.php';

$controller = new PaiementController();

try {
    $locataire = $controller->getCurrentLocataire();
    $echeances = $controller->getMyEcheances();
    $paiements = $controller->getMyPaiements();
    $totalPaye = $controller->getTotalPaye();
} catch (Throwable $e) {
    http_response_code(500);
    die('Une erreur est survenue lors du chargement de vos paiements : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

$prenom = trim((string) ($locataire['prenom'] ?? ''));
$nom = trim((string) ($locataire['nom'] ?? ''));
$nomComplet = trim($prenom . ' ' . $nom) ?: 'Locataire';

$totalARegler = 0.0;
$prochaineEcheance = null;

foreach ($echeances as $echeance) {
    $statut = strtoupper((string) ($echeance['statut'] ?? ''));

    if ($statut !== 'PAID') {
        $montantTotal = (float) ($echeance['montant_total'] ?? 0);

        try {
            $montantRestant = $controller->getMontantRestant((int) $echeance['id_echeance']);
        } catch (Throwable $e) {
            $montantRestant = $montantTotal;
        }

        $totalARegler += max(0, $montantRestant);

        $dateEcheance = $echeance['date_echeance'] ?? null;
        if ($dateEcheance !== null) {
            if ($prochaineEcheance === null || strtotime((string) $dateEcheance) < strtotime((string) $prochaineEcheance['date_echeance'])) {
                $prochaineEcheance = $echeance;
            }
        }
    }
}

function formatMoney(float $montant): string
{
    return number_format($montant, 2, ',', ' ') . ' €';
}

function formatDateFr(?string $date): string
{
    if ($date === null || $date === '') {
        return '-';
    }

    $timestamp = strtotime($date);

    return $timestamp === false ? '-' : date('d/m/Y', $timestamp);
}

function getStatutLabel(string $statut): string
{
    return match (strtoupper($statut)) {
        'PAID' => 'Payée',
        'PENDING' => 'À payer',
        'LATE' => 'En retard',
        'CANCELLED' => 'Annulée',
        'REFUNDED' => 'Remboursée',
        default => ucfirst(strtolower($statut)),
    };
}

function getStatutTone(string $statut): string
{
    return match (strtoupper($statut)) {
        'PAID' => 'actif',
        'LATE' => 'suspendu',
        'CANCELLED' => 'inactif',
        default => 'attente',
    };
}

$prochaineDate = '-';
$prochaineMontant = 0.0;

if ($prochaineEcheance !== null) {
    $prochaineDate = formatDateFr((string) ($prochaineEcheance['date_echeance'] ?? ''));
    $prochaineMontant = (float) ($prochaineEcheance['montant_total'] ?? 0);
}

$pageTitle = 'Mes paiements';
$activeNav = 'Mes paiements';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Bonjour, ' . $prenom;
$greetingSubtitle = 'Consultez vos échéances, vos paiements et l\'historique de vos règlements.';
$profileName = $nomComplet;
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'PN';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">'
    . '<link rel="stylesheet" href="/LOKA/app/locataires/ops.css?v=' . filemtime(dirname(__DIR__) . '/ops.css') . '">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="kpi-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:22px;">
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div></div>
        <div class="kpi-value"><?= formatMoney($totalARegler) ?></div>
        <div class="kpi-label">À payer</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2.5"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></div></div>
        <div class="kpi-value"><?= htmlspecialchars($prochaineDate, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="kpi-label">Prochaine échéance</div>
        <div class="kpi-sub"><?= $prochaineEcheance !== null ? formatMoney($prochaineMontant) : 'Aucune échéance' ?></div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div></div>
        <div class="kpi-value"><?= formatMoney($totalPaye) ?></div>
        <div class="kpi-label">Total payé</div>
      </div>
    </div>

    <div class="panel table-panel" style="margin-bottom:18px;">
      <div class="panel-header"><div class="panel-title">Échéances de paiement</div></div>
      <?php if (empty($echeances)): ?>
        <div class="empty-state" style="padding:40px 20px;">
          <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
          <h4>Aucune échéance</h4>
          <p>Vous n'avez actuellement aucune échéance de paiement.</p>
        </div>
      <?php else: ?>
        <div class="document-list">
          <?php foreach ($echeances as $echeance): ?>
            <?php
            $idEcheance = (int) $echeance['id_echeance'];
            $statut = strtoupper((string) ($echeance['statut'] ?? 'PENDING'));
            $montant = (float) ($echeance['montant_total'] ?? 0);

            try {
                $montantRestant = $controller->getMontantRestant($idEcheance);
            } catch (Throwable $e) {
                $montantRestant = $montant;
            }
            ?>
            <div class="document-item">
              <div style="display:flex;gap:14px;min-width:0;">
                <div class="cell-avatar">€</div>
                <div class="document-main">
                  <div class="document-title">Échéance de loyer <span class="status-badge <?= getStatutTone($statut) ?>"><?= htmlspecialchars(getStatutLabel($statut), ENT_QUOTES, 'UTF-8') ?></span></div>
                  <div class="document-sub">Du <?= formatDateFr($echeance['periode_debut'] ?? null) ?> au <?= formatDateFr($echeance['periode_fin'] ?? null) ?></div>
                  <div class="document-sub">Échéance : <strong style="color:var(--heading);"><?= formatDateFr($echeance['date_echeance'] ?? null) ?></strong> &nbsp;·&nbsp; Montant : <strong style="color:var(--heading);"><?= formatMoney($montant) ?></strong><?php if ($statut !== 'PAID'): ?> &nbsp;·&nbsp; Restant : <strong style="color:var(--danger);"><?= formatMoney(max(0, $montantRestant)) ?></strong><?php endif; ?></div>
                </div>
              </div>
              <div class="document-actions">
                <a href="detail.php?id=<?= $idEcheance ?>" class="btn btn-sm">Voir le détail</a>
                <?php if ($statut !== 'PAID'): ?><a href="payer.php?id=<?= $idEcheance ?>" class="btn btn-primary btn-sm">Payer</a><?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="panel housing-banner">
      <div>
        <p class="eyebrow">Historique</p>
        <h2>Mes paiements effectués</h2>
        <p>Consultez l'ensemble de vos règlements et leur statut.</p>
      </div>
      <a href="historique.php" class="btn btn-primary">Voir mon historique</a>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
