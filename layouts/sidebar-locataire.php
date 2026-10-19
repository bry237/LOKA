<?php
declare(strict_types=1);

/**
 * Sidebar de navigation pour l'espace "locataire" — même socle visuel que
 * sidebar.php / sidebar-agence.php (admin.css), adapté aux pages du locataire
 * sous app/locataires/*, app/maintenance/* et app/documents/*.
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
      <li class="nav-item"><a class="nav-link<?= $navActive('Accueil') ?>" href="/LOKA/app/locataires/index.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
        <span class="nav-label">Accueil</span>
      </a></li>
    </ul>

    <div class="nav-section-label">MON ESPACE</div>
    <ul class="nav">
      <li class="nav-item"><a class="nav-link<?= $navActive('Mon profil') ?>" href="/LOKA/app/locataires/detail.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg>
        <span class="nav-label">Mon profil</span>
      </a></li>
      <li class="nav-item"><a class="nav-link<?= $navActive('Mon logement') ?>" href="/LOKA/app/locataires/logement.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>
        <span class="nav-label">Mon logement</span>
      </a></li>
      <li class="nav-item"><a class="nav-link<?= $navActive('Recherche') ?>" href="/LOKA/app/locataires/recherche.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <span class="nav-label">Recherche</span>
      </a></li>
    </ul>

    <div class="nav-section-label">LOCATION</div>
    <ul class="nav">
      <li class="nav-item"><a class="nav-link<?= $navActive('Mon contrat') ?>" href="/LOKA/app/locataires/contrats/index.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>
        <span class="nav-label">Mon contrat</span>
      </a></li>
      <li class="nav-item"><a class="nav-link<?= $navActive('Mes paiements') ?>" href="/LOKA/app/locataires/paiements/paiement.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        <span class="nav-label">Mes paiements</span>
      </a></li>
      <li class="nav-item"><a class="nav-link<?= $navActive('Quittances') ?>" href="/LOKA/app/locataires/quittances/index.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M9 7h7M9 11h5M9 15h3"/></svg>
        <span class="nav-label">Quittances</span>
      </a></li>
    </ul>

    <div class="nav-section-label">SUPPORT</div>
    <ul class="nav">
      <li class="nav-item"><a class="nav-link<?= $navActive('Maintenance') ?>" href="/LOKA/app/maintenance/index.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z"/></svg>
        <span class="nav-label">Maintenance</span>
      </a></li>
      <li class="nav-item"><a class="nav-link<?= $navActive('Documents') ?>" href="/LOKA/app/documents/index.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>
        <span class="nav-label">Documents</span>
      </a></li>
      <li class="nav-item"><a class="nav-link<?= $navActive('Notifications') ?>" href="/LOKA/app/locataires/notifications/notification.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <span class="nav-label">Notifications</span>
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
