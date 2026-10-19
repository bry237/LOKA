<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$pageTitle = 'Statistiques';
$activeNav = 'Statistiques';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Analyses approfondies de la performance de la plateforme.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.1/chart.umd.min.js"></script>'
	. "\n" . '<link rel="stylesheet" href="../dashboard/dashboard.css">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="kpi-grid" id="kpiGrid"></div>

    <div class="charts-row">
      <div class="panel">
        <div class="panel-header">
          <div class="panel-title">Revenus encaissés</div>
          <div class="panel-link">12 derniers mois</div>
        </div>
        <div class="chart-wrap"><canvas id="revenueChart" height="230"></canvas></div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <div class="panel-title">Nouveaux utilisateurs</div>
          <div class="panel-link">12 derniers mois</div>
        </div>
        <div class="chart-wrap"><canvas id="growthChart" height="230"></canvas></div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header">
        <div class="panel-title">Répartition géographique du parc</div>
        <div class="panel-link">Biens par ville</div>
      </div>
      <div class="chart-wrap" style="height:260px;"><canvas id="geoChart"></canvas></div>
    </div>

    <div class="panel table-panel">
      <div class="panel-header">
        <div class="panel-title">Classement des agences</div>
        <div class="panel-link">Par revenu encaissé</div>
      </div>
      <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th>AGENCE</th>
              <th>VILLE</th>
              <th>PLAN</th>
              <th>BIENS</th>
              <th>UTILISATEURS</th>
              <th>REVENU</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="topAgencesBody"></tbody>
        </table>
      </div>
    </div>
<?php
$extraScripts = '<script src="statistiques.js?v=' . filemtime(__DIR__ . '/statistiques.js') . '"></script>';
require dirname(__DIR__, 3) . '/layouts/footer.php';
