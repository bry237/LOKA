<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/ProprietaireController.php';

$controller = new ProprietaireController();
$proprietaire = $controller->getCurrentProprietaire();

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

$moisNoms = [1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'];
$anneeActuelle = (int) date('Y');

$annee = filter_input(INPUT_GET, 'annee', FILTER_VALIDATE_INT);
if ($annee === false || $annee === null || $annee < 2000 || $annee > 2100) {
    $annee = 0;
}

$mois = filter_input(INPUT_GET, 'mois', FILTER_VALIDATE_INT);
if ($mois === false || $mois === null || $mois < 0 || $mois > 12) {
    $mois = 0;
}

$dateDebutSql = null;
$dateFinSql = null;
if ($annee > 0) {
    $dateDebut = sprintf('%04d-01-01', $annee);
    $dateFin = sprintf('%04d-12-31', $annee);
    if ($mois > 0) {
        $dateDebut = sprintf('%04d-%02d-01', $annee, $mois);
        $dateFin = date('Y-m-t', strtotime($dateDebut));
    }
    $dateDebutSql = $dateDebut . ' 00:00:00';
    $dateFinSql = $dateFin . ' 23:59:59';
}

if ($annee === 0) {
    $periodeLabel = 'Toutes les années';
} elseif ($mois > 0) {
    $periodeLabel = $moisNoms[$mois] . ' ' . $annee;
} else {
    $periodeLabel = 'Année ' . $annee;
}

$nombreBiens = count($controller->getMesBiens());
$totalRevenus = $controller->getTotalRevenus();
$revenus = $controller->getRevenusPeriode($dateDebutSql, $dateFinSql);
$historiqueRevenus = $controller->getHistoriqueRevenus($dateDebutSql, $dateFinSql);

$anneeGraphique = $annee > 0 ? $annee : $anneeActuelle;
$revenusMensuelsAnnee = $controller->getRevenusMensuelsAnnee($anneeGraphique);

$graphiqueRevenus = [];
foreach ($moisNoms as $numero => $nomMois) {
    $graphiqueRevenus[$numero] = ['mois' => $nomMois, 'montant' => 0];
}
foreach ($revenusMensuelsAnnee as $ligne) {
    $numeroMois = (int) $ligne['mois'];
    if (isset($graphiqueRevenus[$numeroMois])) {
        $graphiqueRevenus[$numeroMois]['montant'] = (float) $ligne['total'];
    }
}
$graphiqueRevenus = array_values($graphiqueRevenus);

$prenom = (string) ($proprietaire['prenom'] ?? '');
$nom = (string) ($proprietaire['nom'] ?? '');

$pageTitle = 'Mes revenus';
$activeNav = 'Mes revenus';
$sidebarFile = 'sidebar-proprietaire.php';
$greetingTitle = 'Mes revenus';
$greetingSubtitle = 'Consultez les revenus liés à vos biens.';
$profileName = trim($prenom . ' ' . $nom) ?: 'Propriétaire';
$profileRole = 'Propriétaire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'P';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';
$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>'
    . '<script>
      (function () {
        const ctx = document.getElementById("revenusChart").getContext("2d");
        const gradient = ctx.createLinearGradient(0, 0, 0, 240);
        gradient.addColorStop(0, "rgba(15, 139, 141, 0.22)");
        gradient.addColorStop(1, "rgba(15, 139, 141, 0)");
        new Chart(ctx, {
          type: "line",
          data: {
            labels: window.revenusChartLabels || [],
            datasets: [{
              label: "Revenus encaissés",
              data: window.revenusChartDatas || [],
              borderColor: "#0F8B8D",
              backgroundColor: gradient,
              fill: true,
              tension: 0.4,
              borderWidth: 2.4,
              pointRadius: 0,
              pointHoverRadius: 5,
              pointHoverBackgroundColor: "#0F8B8D",
              pointHoverBorderColor: "#fff",
              pointHoverBorderWidth: 2,
            }],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: "index", intersect: false },
            plugins: {
              legend: { display: false },
              tooltip: {
                backgroundColor: "#043F42", titleColor: "#fff", bodyColor: "#DDEDEC",
                borderColor: "#0F8B8D", borderWidth: 1, padding: 10, cornerRadius: 8,
                titleFont: { size: 11 }, bodyFont: { size: 11.5 },
                callbacks: { label: (c) => new Intl.NumberFormat("fr-FR", { style: "currency", currency: "EUR", maximumFractionDigits: 0 }).format(c.raw) },
              },
            },
            scales: {
              x: { grid: { display: false }, ticks: { color: "#8AA0A0", font: { size: 10.5 } } },
              y: { beginAtZero: true, grid: { color: "#EEF3F3" }, ticks: { color: "#8AA0A0", font: { size: 10.5 }, callback: (v) => v + " €" } },
            },
          },
        });
      })();
    </script>';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="mini-stat" style="max-width:260px;margin-bottom:18px;">
      <div class="num"><?= formatMontant($totalRevenus) ?></div>
      <div class="lbl">Total de mes revenus encaissés</div>
    </div>

    <form class="filters-form" method="get" action="revenus.php">
      <select class="filter-select" name="annee">
        <option value="0"<?= $annee === 0 ? ' selected' : '' ?>>Toutes les années</option>
        <?php for ($i = $anneeActuelle - 5; $i <= $anneeActuelle + 1; $i++): ?>
          <option value="<?= $i ?>"<?= $annee === $i ? ' selected' : '' ?>><?= $i ?></option>
        <?php endfor; ?>
      </select>
      <select class="filter-select" name="mois">
        <option value="0"<?= $mois === 0 ? ' selected' : '' ?>>Tous les mois</option>
        <?php foreach ($moisNoms as $numero => $nomMois): ?>
          <option value="<?= $numero ?>"<?= $mois === $numero ? ' selected' : '' ?>><?= e($nomMois) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary">Filtrer</button>
      <a href="revenus.php" class="btn btn-sm">Réinitialiser</a>
    </form>

    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div></div>
        <div class="kpi-value"><?= formatMontant($revenus['encaisses']) ?></div>
        <div class="kpi-label">Revenus encaissés</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon amber"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div></div>
        <div class="kpi-value"><?= formatMontant($revenus['attente']) ?></div>
        <div class="kpi-label">En attente</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div></div>
        <div class="kpi-value"><?= formatMontant($revenus['retard']) ?></div>
        <div class="kpi-label">En retard</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon teal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/></svg></div></div>
        <div class="kpi-value"><?= $nombreBiens ?></div>
        <div class="kpi-label">Biens concernés</div>
      </div>
    </div>

    <div class="panel" style="margin-bottom:18px;">
      <div class="panel-header"><div class="panel-title">Évolution des revenus en <?= (int) $anneeGraphique ?></div></div>
      <div style="padding:10px 18px 18px;height:240px;"><canvas id="revenusChart"></canvas></div>
    </div>

    <div class="panel table-panel">
      <div class="panel-header"><div class="panel-title">Historique — <?= e($periodeLabel) ?></div></div>
      <?php if (empty($historiqueRevenus)): ?>
        <div class="empty-state" style="padding:48px 20px;">
          <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
          <h4>Aucun revenu enregistré</h4>
          <p>Aucun paiement ne correspond à la période sélectionnée.</p>
        </div>
      <?php else: ?>
        <div class="table-scroll">
          <table>
            <thead><tr><th>DATE</th><th>BIEN</th><th>LOCATAIRE</th><th>CONTRAT</th><th>MODE</th><th>MONTANT</th><th>STATUT</th></tr></thead>
            <tbody>
              <?php foreach ($historiqueRevenus as $revenu): ?>
                <?php
                [$label, $tone] = statutPaiementBadge($revenu['statut'] ?? null);
                $nomLocataire = trim(($revenu['locataire_prenom'] ?? '') . ' ' . ($revenu['locataire_nom'] ?? ''));
                ?>
                <tr>
                  <td class="cell-sub"><?= formatDate($revenu['date_paiement'] ?? null) ?></td>
                  <td class="cell-title"><?= e($revenu['reference_bien'] ?? '—') ?><?php if (!empty($revenu['titre_bien'])): ?><div class="cell-sub"><?= e($revenu['titre_bien']) ?></div><?php endif; ?></td>
                  <td class="cell-sub"><?= $nomLocataire !== '' ? e($nomLocataire) : '—' ?></td>
                  <td class="cell-sub"><?= e($revenu['numero_contrat'] ?? '—') ?></td>
                  <td class="cell-sub"><?= e(modePaiementLabel($revenu['mode_paiement'] ?? null)) ?></td>
                  <td class="cell-price"><?= formatMontant($revenu['montant']) ?></td>
                  <td><span class="status-badge <?= $tone ?>"><?= e($label) ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <script>
      window.revenusChartLabels = <?= json_encode(array_column($graphiqueRevenus, 'mois'), JSON_THROW_ON_ERROR) ?>;
      window.revenusChartDatas = <?= json_encode(array_column($graphiqueRevenus, 'montant'), JSON_THROW_ON_ERROR) ?>;
    </script>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
