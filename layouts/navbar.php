<?php
declare(strict_types=1);

/**
 * Topbar admin, partagée entre les pages "administration plateforme".
 * Variables attendues : $greetingTitle, $greetingSubtitle, $profileName, $profileRole, $profileInitials (strings, échappées ici).
 */
require_once dirname(__DIR__) . '/core/Auth.php';

$greetingTitle ??= 'Bonjour';
$greetingSubtitle ??= '';
$profileName ??= '';
$profileRole ??= '';
$profileInitials ??= '';
// La recherche globale (search.php) n'interroge que les agences/utilisateurs de toute la plateforme :
// réservée à l'Administrateur plateforme, donc masquée sur les pages "admin agence" (sidebar-agence.php).
$showGlobalSearch = ($sidebarFile ?? 'sidebar.php') !== 'sidebar-agence.php';
// Jeton CSRF pour les actions JS de la topbar (marquer une notification comme lue) : certaines
// pages hôtes (le Dashboard, par exemple) n'en génèrent pas elles-mêmes pour leurs propres
// formulaires, donc la topbar porte le sien plutôt que de dépendre d'une variable $csrfToken
// que la page courante n'aurait peut-être pas définie.
$navbarCsrfToken = Auth::csrfToken();
?>
    <div class="topbar">
      <div style="display:flex;align-items:center;">
        <button class="menu-toggle" id="menuToggle" aria-label="Menu">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="greeting">
          <h1><?= htmlspecialchars($greetingTitle, ENT_QUOTES, 'UTF-8') ?> <span></span></h1>
          <p><?= htmlspecialchars($greetingSubtitle, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
      </div>
      <div class="topbar-actions">
        <?php if ($showGlobalSearch): ?>
        <div class="search-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" id="globalSearchInput" placeholder="Rechercher une agence, un utilisateur…" autocomplete="off">
          <div class="search-results" id="globalSearchResults" hidden></div>
        </div>
        <?php endif; ?>
        <div class="notif-wrap" data-csrf="<?= htmlspecialchars($navbarCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <button class="icon-btn" id="notifBtn" aria-label="Notifications">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <span class="dot" id="notifDot" hidden></span>
          </button>
          <div class="notif-panel" id="notifPanel" hidden>
            <div class="notif-panel-header">
              <span>Notifications</span>
              <button type="button" class="panel-link" id="notifMarkAllBtn" style="background:none;border:none;cursor:pointer;">Tout marquer comme lu</button>
            </div>
            <div class="notif-list" id="notifList"><p class="empty-note">Chargement…</p></div>
          </div>
        </div>
        <div class="profile-wrap">
          <div class="profile" id="profileBtn">
            <div class="profile-avatar"><?= htmlspecialchars($profileInitials, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="profile-text">
              <div class="profile-name"><?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8') ?></div>
              <div class="profile-role"><?= htmlspecialchars($profileRole, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="#8AA0A0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </div>
          <div class="profile-menu" id="profileMenu" hidden>
            <a class="profile-menu-item" href="/LOKA/app/authentification/deconnexion.php">Déconnexion</a>
          </div>
        </div>
      </div>
    </div>
