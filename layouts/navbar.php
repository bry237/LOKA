<?php
declare(strict_types=1);

/**
 * Topbar admin, partagée entre les pages "administration plateforme".
 * Variables attendues : $greetingTitle, $greetingSubtitle, $profileName, $profileRole, $profileInitials (strings, échappées ici).
 */
$greetingTitle ??= 'Bonjour';
$greetingSubtitle ??= '';
$profileName ??= '';
$profileRole ??= '';
$profileInitials ??= '';
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
        <div class="search-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" placeholder="Rechercher une agence, un utilisateur…">
        </div>
        <button class="icon-btn" id="notifBtn" aria-label="Notifications">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <span class="dot"></span>
        </button>
        <div class="profile" id="profileBtn">
          <div class="profile-avatar"><?= htmlspecialchars($profileInitials, ENT_QUOTES, 'UTF-8') ?></div>
          <div class="profile-text">
            <div class="profile-name"><?= htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="profile-role"><?= htmlspecialchars($profileRole, ENT_QUOTES, 'UTF-8') ?></div>
          </div>
          <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="#8AA0A0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
      </div>
    </div>
