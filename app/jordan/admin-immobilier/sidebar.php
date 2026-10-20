<?php
declare(strict_types=1);

$currentPage = $currentPage ?? '';
$basePath = '/LOKA/app/jordan/admin-immobilier';
$navActive = static fn (string $page): string => $currentPage === $page ? ' is-active' : '';
?>
<aside class="sidebar admin-sidebar">
    <a class="brand" href="<?= htmlspecialchars("$basePath/dashboard/index.php", ENT_QUOTES, 'UTF-8') ?>">
        <span class="brand__mark">L</span><span>LOKA Admin</span>
    </a>
    <nav class="nav">
        <div class="nav__label">Administration</div>
        <a class="<?= $navActive('dashboard') ?>" href="<?= htmlspecialchars("$basePath/dashboard/index.php", ENT_QUOTES, 'UTF-8') ?>">
            <span class="nav__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg></span>
            <span>Tableau de bord admin</span>
        </a>
        <a class="<?= $navActive('agences') ?>" href="<?= htmlspecialchars("$basePath/agences/index.php", ENT_QUOTES, 'UTF-8') ?>">
            <span class="nav__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 9h1M14 9h1M9 13h1M14 13h1M9 17h1M14 17h1"/></svg></span>
            <span>Agences</span>
        </a>
        <a class="<?= $navActive('utilisateurs') ?>" href="<?= htmlspecialchars("$basePath/utilisateurs/index.php", ENT_QUOTES, 'UTF-8') ?>">
            <span class="nav__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
            <span>Utilisateurs</span>
        </a>

        <div class="nav__label">Configuration</div>
        <a class="<?= $navActive('roles') ?>" href="<?= htmlspecialchars("$basePath/roles/index.php", ENT_QUOTES, 'UTF-8') ?>">
            <span class="nav__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v6c0 5 3.4 8.8 8 11 4.6-2.2 8-6 8-11V5l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg></span>
            <span>Rôles &amp; permissions</span>
        </a>
        <a class="<?= $navActive('abonnements') ?>" href="<?= htmlspecialchars("$basePath/abonnements/index.php", ENT_QUOTES, 'UTF-8') ?>">
            <span class="nav__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></span>
            <span>Abonnements</span>
        </a>
        <a class="<?= $navActive('parametres') ?>" href="<?= htmlspecialchars("$basePath/parametres/index.php", ENT_QUOTES, 'UTF-8') ?>">
            <span class="nav__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg></span>
            <span>Paramètres</span>
        </a>

        <div class="nav__label">Audit</div>
        <a class="<?= $navActive('audit') ?>" href="<?= htmlspecialchars("$basePath/audit/index.php", ENT_QUOTES, 'UTF-8') ?>">
            <span class="nav__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M9 7h7M9 11h5"/></svg></span>
            <span>Journal d'audit</span>
        </a>
    </nav>
    <div class="sidebar__footer">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
            <a href="/LOKA/app/jordan/dashboard/index.php" style="color:inherit;">&larr; Retour au portail</a>
            <?php require dirname(__DIR__, 3) . '/layouts/notif-widget.php'; ?>
        </div>
        <a href="/LOKA/app/authentification/deconnexion.php" style="color:inherit;display:block;margin-top:10px;">Déconnexion</a>
    </div>
</aside>
