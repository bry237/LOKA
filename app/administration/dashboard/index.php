<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
Authorization::requireRole('Administrateur plateforme');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>LOKA — Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
<link rel="stylesheet" href="dashboard.css">
</head>
<body>
<div class="app">
  <div class="sidebar-overlay" id="overlay"></div>

  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="brand-mark">LK</div>
      <div class="brand-name">LOKA</div>
    </div>

    <div class="nav-section-label">VUE D'ENSEMBLE</div>
    <ul class="nav">
      <li class="nav-item"><a class="nav-link active" data-page="Dashboard">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
        Dashboard
      </a></li>
    </ul>

    <div class="nav-section-label">GESTION PLATEFORME</div>
    <ul class="nav">
      <li class="nav-item"><a class="nav-link" data-page="Utilisateurs">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Utilisateurs
      </a></li>
      <li class="nav-item"><a class="nav-link" data-page="Agences">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 9h1M14 9h1M9 13h1M14 13h1M9 17h1M14 17h1"/></svg>
        Agences
      </a></li>
      <li class="nav-item"><a class="nav-link" data-page="Biens">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>
        Biens
      </a></li>
      <li class="nav-item"><a class="nav-link" data-page="Abonnements">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>
        Abonnements
      </a></li>
      <li class="nav-item"><a class="nav-link" data-page="Roles">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v6c0 5 3.4 8.8 8 11 4.6-2.2 8-6 8-11V5l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg>
        Rôles &amp; permissions
      </a></li>
    </ul>

    <div class="nav-section-label">ANALYSE &amp; SÉCURITÉ</div>
    <ul class="nav">
      <li class="nav-item"><a class="nav-link" data-page="Statistiques">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/></svg>
        Statistiques
      </a></li>
      <li class="nav-item"><a class="nav-link" data-page="Journal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M9 7h7M9 11h5"/></svg>
        Journal d'audit
      </a></li>
    </ul>

    <div class="sidebar-bottom">
      <ul class="nav">
        <li class="nav-item"><a class="nav-link" data-page="Parametres">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
          Paramètres
        </a></li>
        <li class="nav-item"><a class="nav-link" id="logoutLink">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
          Déconnexion
        </a></li>
      </ul>
    </div>
  </aside>

  <main class="main">
    <!-- Topbar -->
    <div class="topbar">
      <div style="display:flex;align-items:center;">
        <button class="menu-toggle" id="menuToggle" aria-label="Menu">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="greeting">
          <h1>Bonjour, Admin <span></span></h1>
          <p>Voici un aperçu de l'activité de LOKA.</p>
        </div>
      </div>
      <div class="topbar-actions">
        <div class="search-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" placeholder="Rechercher une agence, un utilisateur…">
        </div>
        <button class="icon-btn" id="notifBtn" aria-label="Notifications">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <span class="dot"></span>
        </button>
        <div class="profile" id="profileBtn">
          <div class="profile-avatar">AD</div>
          <div class="profile-text">
            <div class="profile-name">Admin LOKA</div>
            <div class="profile-role">Administrateur plateforme</div>
          </div>
          <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="#8AA0A0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
      </div>
    </div>

    <!-- Banner -->
    <div class="banner" id="banner">
      <svg class="info" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
      <div>
        <strong>Maquette front-end autonome.</strong> Toutes les données affichées (KPI, graphiques, activité, tableau) sont des données d'exemple, clairement isolées dans un fichier <code>DATA_MOCK</code> en fin de fichier. À l'intégration, remplacez ces blocs par les appels réels à vos tables <em>utilisateur, agence, bien, abonnement, agence_administrateur, journal_audit, …</em> — les points d'injection sont marqués <code>/* TODO API */</code>.
      </div>
      <button class="banner-close" id="bannerClose" aria-label="Fermer">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>

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
            <div class="donut-center"><div class="num">86</div><div class="lbl">agences</div></div>
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
          <div class="select-box" id="statusFilter">
            <span id="statusFilterLabel">Tous les statuts</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </div>
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
  </main>
</div>

<script src="dashboard.js"></script></body>
</html>

