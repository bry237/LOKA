<?php
declare(strict_types=1);

/**
 * Sidebar de navigation admin, partagée entre les pages "administration plateforme".
 * Variable attendue : $activeNav (string) — doit correspondre à l'un des data-page ci-dessous.
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
      <li class="nav-item"><a class="nav-link<?= $navActive('Dashboard') ?>" href="../dashboard/index.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
        Dashboard
      </a></li>
    </ul>

    <div class="nav-section-label">GESTION PLATEFORME</div>
    <ul class="nav">
      <li class="nav-item"><a class="nav-link<?= $navActive('Utilisateurs') ?>" href="../utilisateurs/index.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Utilisateurs
      </a></li>
      <li class="nav-item"><a class="nav-link<?= $navActive('Agences') ?>" href="../agences/index.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 9h1M14 9h1M9 13h1M14 13h1M9 17h1M14 17h1"/></svg>
        Agences
      </a></li>
      <li class="nav-item"><a class="nav-link<?= $navActive('Biens') ?>" data-page="Biens">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>
        Biens
      </a></li>
      <li class="nav-item"><a class="nav-link<?= $navActive('Abonnements') ?>" href="../abonnements/index.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>
        Abonnements
      </a></li>
      <li class="nav-item"><a class="nav-link<?= $navActive('Roles') ?>" data-page="Roles">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v6c0 5 3.4 8.8 8 11 4.6-2.2 8-6 8-11V5l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg>
        Rôles &amp; permissions
      </a></li>
    </ul>

    <div class="nav-section-label">ANALYSE &amp; SÉCURITÉ</div>
    <ul class="nav">
      <li class="nav-item"><a class="nav-link<?= $navActive('Statistiques') ?>" data-page="Statistiques">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/></svg>
        Statistiques
      </a></li>
      <li class="nav-item"><a class="nav-link<?= $navActive('Journal') ?>" data-page="Journal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M9 7h7M9 11h5"/></svg>
        Journal d'audit
      </a></li>
    </ul>

    <div class="sidebar-bottom">
      <ul class="nav">
        <li class="nav-item"><a class="nav-link<?= $navActive('Parametres') ?>" data-page="Parametres">
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
