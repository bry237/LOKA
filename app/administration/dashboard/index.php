<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$pageTitle = 'Dashboard';
$activeNav = 'Dashboard';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = "L'essentiel de l'activité de la plateforme, en un coup d'œil.";
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.1/chart.umd.min.js"></script>'
	. "\n" . '<link rel="stylesheet" href="dashboard.css">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <!-- KPI -->
    <div class="kpi-grid" id="kpiGrid"></div>

    <!-- Charts -->
    <div class="charts-row">
      <div class="panel">
        <div class="panel-header">
          <div class="panel-title">Évolution de la plateforme</div>
          <div class="range-tabs" id="rangeTabs">
            <div class="range-tab" data-range="7j">7j</div>
            <div class="range-tab active" data-range="30j">30j</div>
            <div class="range-tab" data-range="3m">3 mois</div>
            <div class="range-tab" data-range="1a">1 an</div>
          </div>
        </div>
        <div class="chart-wrap"><canvas id="evolutionChart" height="230"></canvas></div>
        <div class="chart-legend">
          <span><i class="dot-legend" style="background:#0F8B8D"></i>Utilisateurs</span>
          <span><i class="dot-legend" style="background:#075E63"></i>Agences</span>
          <span><i class="dot-legend" style="background:#F59E0B"></i>Biens</span>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <div class="panel-title">Répartition des abonnements</div>
          <div class="panel-link">Par formule active</div>
        </div>
        <div class="donut-wrap">
          <div class="donut-canvas-box">
            <canvas id="donutChart"></canvas>
            <div class="donut-center"><div class="num" id="donutTotal">–</div><div class="lbl">agences</div></div>
          </div>
          <div class="donut-legend" id="donutLegend"></div>
        </div>
      </div>
    </div>

    <!-- Lists -->
    <div class="lists-row">
      <div class="panel">
        <div class="panel-header">
          <div class="panel-title">Activité récente</div>
          <div class="panel-link" id="voirAudit">Voir tout l'audit</div>
        </div>
        <p style="font-size:11.5px;color:#8AA0A0;margin:0 18px 6px;">Dernières entrées du journal d'audit</p>
        <div class="activity-list" id="activityList"></div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <div class="panel-title">À surveiller</div>
        </div>
        <p style="font-size:11.5px;color:#8AA0A0;margin:0 18px 6px;">Nécessite une action de votre part</p>
        <div class="watch-list" id="watchList"></div>
      </div>
    </div>

    <!-- Table -->
    <div class="panel table-panel">
      <div class="table-toolbar">
        <div class="panel-title">Agences récentes</div>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
          <div class="search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="tableSearch" placeholder="Rechercher une agence…">
          </div>
          <select class="filter-select" id="statusFilter">
            <option value="all">Tous les statuts</option>
            <option value="actif">Actif</option>
            <option value="attente">En attente</option>
            <option value="suspendu">Suspendu</option>
          </select>
        </div>
      </div>
      <p style="font-size:11.5px;color:#8AA0A0;margin:0 18px 10px;">Dernières agences inscrites sur la plateforme</p>
      <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th>AGENCE</th>
              <th>ADMINISTRATION</th>
              <th>ABONNEMENT</th>
              <th>DATE D'INSCRIPTION</th>
              <th>STATUT</th>
              <th></th>
            </tr>
          </thead>
          <tbody id="tableBody"></tbody>
        </table>
      </div>
      <div class="table-footer">
        <div id="footerCount">Affichage 1-6 sur 32 agences</div>
        <div class="pagination" id="pagination"></div>
      </div>
    </div>
<?php
$extraScripts = '<script src="dashboard.js"></script>';
require dirname(__DIR__, 3) . '/layouts/footer.php';
