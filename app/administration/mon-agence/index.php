<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';

$currentUser = Authorization::requireRole('Administrateur agence');

$pageTitle = 'Mon agence';
$activeNav = 'Dashboard';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = "Voici un aperçu de l'activité de votre agence.";
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <!-- Agence & abonnement -->
    <div class="agency-row">
      <div class="panel agency-card" id="agencyCard"></div>
      <div class="panel subscription-card" id="subscriptionCard"></div>
    </div>

    <!-- KPI -->
    <div class="kpi-grid" id="kpiGrid"></div>

    <!-- Activité récente -->
    <div class="panel">
      <div class="panel-header">
        <div class="panel-title">Activité récente</div>
      </div>
      <p style="font-size:11.5px;color:#8AA0A0;margin:0 18px 6px;">Dernières entrées du journal d'audit de votre agence</p>
      <div class="activity-list" id="activityList"></div>
    </div>
<?php
$extraScripts = '<script src="dashboard.js"></script>';
require dirname(__DIR__, 3) . '/layouts/footer.php';
