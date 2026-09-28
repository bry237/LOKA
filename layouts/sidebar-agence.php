<?php
declare(strict_types=1);

/**
 * Sidebar de navigation pour l'espace "admin agence" (scope limité à l'agence de l'utilisateur).
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
      <li class="nav-item"><a class="nav-link<?= $navActive('Dashboard') ?>" href="../mon-agence/index.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
        Dashboard
      </a></li>
    </ul>

    <div class="sidebar-bottom">
      <ul class="nav">
        <li class="nav-item"><a class="nav-link" id="logoutLink">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
          Déconnexion
        </a></li>
      </ul>
    </div>
  </aside>
