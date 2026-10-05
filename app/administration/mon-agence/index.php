<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';

$currentUser = Authorization::requireRole('Administrateur agence');

$pageTitle = 'Mon agence';
$activeNav = 'Dashboard';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = "L'essentiel de l'activité de votre agence, en un coup d'œil.";
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <!-- KPI -->
    <div class="kpi-grid" id="kpiGrid"></div>

    <div class="dashboard-grid">
      <!-- Colonne principale -->
      <div class="dashboard-main">
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title">État du parc immobilier</div>
          </div>
          <div id="portfolioBlock"></div>
        </div>

        <div class="panel">
          <div class="panel-header">
            <div class="panel-title">Contrats à surveiller</div>
          </div>
          <p class="panel-sub">Contrats actifs arrivant à échéance sous 30 jours</p>
          <div class="watch-list" id="contractsWatchList"></div>
        </div>

        <div class="panel">
          <div class="panel-header">
            <div class="panel-title">Activité récente</div>
            <span class="panel-link disabled" title="Bientôt disponible">Voir tout</span>
          </div>
          <p class="panel-sub">Les derniers événements marquants de votre agence</p>
          <div class="activity-list" id="activityList"></div>
        </div>
      </div>

      <!-- Rail latéral -->
      <div class="dashboard-rail">
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title">À traiter aujourd'hui</div>
          </div>
          <div class="watch-list" id="todayTasksList"></div>
        </div>

        <div class="panel subscription-card" id="subscriptionCard"></div>

        <div class="panel">
          <div class="panel-header">
            <div class="panel-title">Actions rapides</div>
          </div>
          <div class="quick-actions" id="quickActions"></div>
        </div>
      </div>
    </div>
<?php
$extraScripts = '<script src="dashboard.js"></script>';
require dirname(__DIR__, 3) . '/layouts/footer.php';
