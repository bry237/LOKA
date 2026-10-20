<?php

declare(strict_types=1);

require_once __DIR__ . '/ProprietaireController.php';

$controller = new ProprietaireController();
$user = $controller->getUser();
$proprietaire = $controller->getCurrentProprietaire();

if (!$proprietaire) {
    http_response_code(403);
    exit('Aucun dossier propriétaire n’est associé à ce compte.');
}

$stats = $controller->getStats();
$derniersPaiements = $controller->getDerniersPaiements(5);
$mesBiens = $controller->getMesBiens();
$revenusMensuels = $controller->getRevenusMensuels();

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatDate(?string $date): string
{
    if (empty($date)) {
        return 'N/A';
    }
    $timestamp = strtotime($date);

    return $timestamp === false ? 'N/A' : date('d/m/Y', $timestamp);
}

function paiementStatutBadge(string $statut): array
{
    return match ($statut) {
        'PAID' => ['Payé', 'actif'],
        'PENDING' => ['En attente', 'attente'],
        'LATE' => ['En retard', 'suspendu'],
        'CANCELLED' => ['Annulé', 'inactif'],
        'REFUNDED' => ['Remboursé', 'inactif'],
        default => [$statut, 'inactif'],
    };
}

function bienStatutBadge(string $statut): array
{
    return match ($statut) {
        'OCCUPIED' => ['Loué', 'actif'],
        'AVAILABLE' => ['Disponible', 'attente'],
        'MAINTENANCE' => ['En travaux', 'suspendu'],
        'ARCHIVED' => ['Archivé', 'inactif'],
        default => ['Créé', 'inactif'],
    };
}

$nomsMois = [1 => 'Jan', 2 => 'Fév', 3 => 'Mar', 4 => 'Avr', 5 => 'Mai', 6 => 'Juin', 7 => 'Juil', 8 => 'Août', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc'];
$chartLabels = [];
$chartDatas = [];
foreach ($revenusMensuels as $ligne) {
    $chartLabels[] = $nomsMois[(int) $ligne['mois']] ?? '';
    $chartDatas[] = (float) $ligne['total'];
}

$prenom = (string) ($proprietaire['prenom'] ?? $user['prenom'] ?? '');
$nom = (string) ($proprietaire['nom'] ?? $user['nom'] ?? '');

$pageTitle = 'Dashboard Propriétaire';
$activeNav = 'Dashboard';
$sidebarFile = 'sidebar-proprietaire.php';
$greetingTitle = 'Bonjour, ' . trim($prenom . ' ' . $nom);
$greetingSubtitle = 'Bienvenue sur votre espace propriétaire.';
$profileName = trim($prenom . ' ' . $nom) ?: 'Propriétaire';
$profileRole = 'Propriétaire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'P';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';
$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>'
    . '<script>
      (function () {
        const ctx = document.getElementById("revenueChart").getContext("2d");
        const gradient = ctx.createLinearGradient(0, 0, 0, 190);
        gradient.addColorStop(0, "rgba(15, 139, 141, 0.22)");
        gradient.addColorStop(1, "rgba(15, 139, 141, 0)");
        new Chart(ctx, {
          type: "line",
          data: {
            labels: window.revenueChartLabels || [],
            datasets: [{
              label: "Revenus",
              data: window.revenueChartDatas || [],
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

require dirname(__DIR__, 2) . '/layouts/header.php';
?>
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon teal"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 9h1M14 9h1M9 13h1M14 13h1M9 17h1M14 17h1"/></svg></div></div>
        <div class="kpi-value"><?= $stats['total_biens'] ?></div>
        <div class="kpi-label">Mes biens</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon green"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div></div>
        <div class="kpi-value"><?= number_format($stats['revenus'], 0, ',', ' ') ?> €</div>
        <div class="kpi-label">Revenus encaissés</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon blue"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></div></div>
        <div class="kpi-value"><?= $stats['contrats_actifs'] ?></div>
        <div class="kpi-label">Contrats actifs</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon violet"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></div></div>
        <div class="kpi-value"><?= $stats['total_paiements'] ?></div>
        <div class="kpi-label">Paiements</div>
      </div>
      <?php if ($stats['echeances_en_retard'] > 0): ?>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon red"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div></div>
        <div class="kpi-value"><?= $stats['echeances_en_retard'] ?></div>
        <div class="kpi-label">Échéances en retard</div>
      </div>
      <?php endif; ?>
    </div>

    <section class="detail-grid" style="margin-top:18px;">
      <div class="panel">
        <div class="panel-header"><div class="panel-title">Revenus mensuels</div></div>
        <div style="padding:10px 16px 16px;height:190px;"><canvas id="revenueChart"></canvas></div>
      </div>

      <div class="panel table-panel">
        <div class="panel-header"><div class="panel-title">Derniers paiements</div></div>
        <?php if (empty($derniersPaiements)): ?>
          <div class="empty-state" style="padding:40px 20px;">
            <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></div>
            <h4>Aucun paiement enregistré</h4>
          </div>
        <?php else: ?>
          <div class="table-scroll">
            <table>
              <thead><tr><th>BIEN</th><th>DATE</th><th>MONTANT</th><th>STATUT</th></tr></thead>
              <tbody>
                <?php foreach ($derniersPaiements as $paiement): ?>
                  <?php [$label, $tone] = paiementStatutBadge((string) $paiement['statut']); ?>
                  <tr>
                    <td class="cell-title"><?= e($paiement['titre']) ?></td>
                    <td class="cell-sub"><?= formatDate($paiement['date_paiement'] ?? null) ?></td>
                    <td class="cell-sub"><?= number_format((float) $paiement['montant'], 2, ',', ' ') ?> €</td>
                    <td><span class="status-badge <?= $tone ?>"><?= e($label) ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <section class="panel" style="margin-top:18px;">
      <div class="panel-header">
        <div class="panel-title">Mes biens</div>
        <a class="panel-link" href="biens.php">Voir tout</a>
      </div>
      <?php if (empty($mesBiens)): ?>
        <div class="empty-state" style="padding:40px 20px;">
          <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/></svg></div>
          <h4>Aucun bien enregistré</h4>
        </div>
      <?php else: ?>
        <div class="table-scroll">
          <table>
            <thead><tr><th>BIEN</th><th>TYPE</th><th>VILLE</th><th>LOYER</th><th>STATUT</th></tr></thead>
            <tbody>
              <?php foreach ($mesBiens as $bien): ?>
                <?php [$label, $tone] = bienStatutBadge((string) $bien['statut']); ?>
                <tr>
                  <td class="cell-title"><a href="detail.php?id=<?= (int) $bien['id_bien'] ?>"><?= e($bien['titre']) ?></a></td>
                  <td class="cell-sub"><?= e($bien['type_bien'] ?? '—') ?></td>
                  <td class="cell-sub"><?= e($bien['ville'] ?? '—') ?></td>
                  <td class="cell-sub"><?= number_format((float) $bien['loyer'], 0, ',', ' ') ?> €</td>
                  <td><span class="status-badge <?= $tone ?>"><?= e($label) ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <script>
      window.revenueChartLabels = <?= json_encode($chartLabels, JSON_THROW_ON_ERROR) ?>;
      window.revenueChartDatas = <?= json_encode($chartDatas, JSON_THROW_ON_ERROR) ?>;
    </script>
<?php
require dirname(__DIR__, 2) . '/layouts/footer.php';
