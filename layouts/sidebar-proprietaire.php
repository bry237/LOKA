<?php
declare(strict_types=1);

/**
 * Sidebar de navigation pour l'espace "propriétaire" — même socle visuel que
 * sidebar.php / sidebar-agence.php / sidebar-locataire.php (admin.css).
 * Variable attendue : $activeNav (string).
 */
$activeNav ??= '';
$navActive = static fn (string $page): string => $activeNav === $page ? ' active' : '';
?>
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="brand-mark">LK</div>
      <div class="brand-name">LOKA</div>
    </div>

    <div class="nav-section-label">VUE D'ENSEMBLE</div>
    <ul class="nav">
      <li class="nav-item"><a class="nav-link<?= $navActive('Dashboard') ?>" href="/LOKA/app/proprietaires/index.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
        <span class="nav-label">Dashboard</span>
      </a></li>
    </ul>

    <div class="nav-section-label">PATRIMOINE</div>
    <ul class="nav">
      <li class="nav-item"><a class="nav-link<?= $navActive('Mes biens') ?>" href="/LOKA/app/proprietaires/biens.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 9h1M14 9h1M9 13h1M14 13h1M9 17h1M14 17h1"/></svg>
        <span class="nav-label">Mes biens</span>
      </a></li>
      <li class="nav-item"><a class="nav-link<?= $navActive('Mes locataires') ?>" href="/LOKA/app/proprietaires/Locataires/index.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span class="nav-label">Mes locataires</span>
      </a></li>
    </ul>

    <div class="nav-section-label">LOCATION</div>
    <ul class="nav">
      <li class="nav-item"><a class="nav-link<?= $navActive('Mes contrats') ?>" href="/LOKA/app/proprietaires/Contrat/contrat.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>
        <span class="nav-label">Mes contrats</span>
      </a></li>
      <li class="nav-item"><a class="nav-link<?= $navActive('Mes revenus') ?>" href="/LOKA/app/proprietaires/Revenus/revenus.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        <span class="nav-label">Mes revenus</span>
      </a></li>
      <li class="nav-item"><a class="nav-link<?= $navActive('Paiements') ?>" href="/LOKA/app/proprietaires/Paiements/index.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>
        <span class="nav-label">Paiements</span>
      </a></li>
      <li class="nav-item"><a class="nav-link<?= $navActive('Quittances') ?>" href="/LOKA/app/proprietaires/Quittances/quittances.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M9 7h7M9 11h5M9 15h3"/></svg>
        <span class="nav-label">Quittances</span>
      </a></li>
    </ul>

    <div class="nav-section-label">SUPPORT</div>
    <ul class="nav">
      <li class="nav-item"><a class="nav-link<?= $navActive('Documents') ?>" href="/LOKA/app/proprietaires/Documents/documents.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>
        <span class="nav-label">Documents</span>
      </a></li>
      <li class="nav-item"><a class="nav-link<?= $navActive('Vérification') ?>" href="/LOKA/app/proprietaires/Verification/index.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v6c0 5 3.4 8.8 8 11 4.6-2.2 8-6 8-11V5l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg>
        <span class="nav-label">Vérification &amp; incidents</span>
      </a></li>
    </ul>

    <div class="nav-section-label">AGENCE</div>
    <ul class="nav">
      <li class="nav-item"><a class="nav-link<?= $navActive('Paramètres') ?>" href="/LOKA/app/proprietaires/Parametres/parametres.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>
        <span class="nav-label">Paramètres</span>
      </a></li>
    </ul>

    <div class="sidebar-bottom">
      <ul class="nav">
        <li class="nav-item"><a class="nav-link" id="logoutLink">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
          <span class="nav-label">Déconnexion</span>
        </a></li>
      </ul>
    </div>
  </aside>
