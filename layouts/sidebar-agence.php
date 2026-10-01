<?php
declare(strict_types=1);

/**
 * Sidebar de navigation pour l'espace "admin agence" (scope limité à l'agence de l'utilisateur).
 * Variable attendue : $activeNav (string) — doit correspondre à l'un des libellés ci-dessous.
 * Se charge de son identité d'agence et de ses badges elle-même (indépendante de la page hôte),
 * pour rester correcte sur n'importe quelle page qui l'inclut.
 */
require_once __DIR__ . '/../core/Auth.php';
require_once dirname(__DIR__) . '/app/administration/mon-agence/MonAgenceModel.php';

$activeNav ??= '';
$navActive = static fn (string $page): string => $activeNav === $page ? ' active' : '';

$sidebarUser = Auth::user();
$sidebarAgence = $sidebarUser && $sidebarUser['id_agence'] !== null
	? MonAgenceModel::agency((int) $sidebarUser['id_agence'])
	: null;
$sidebarBadges = $sidebarUser && $sidebarUser['id_agence'] !== null
	? MonAgenceModel::navBadges((int) $sidebarUser['id_agence'])
	: ['contrats' => 0, 'interventions' => 0];

$agenceStatutLabel = ['ACTIVE' => 'Actif', 'PENDING' => 'En attente', 'SUSPENDED' => 'Suspendu'];
$agenceStatutTone = ['ACTIVE' => 'actif', 'PENDING' => 'attente', 'SUSPENDED' => 'suspendu'];

/** Rendu d'une entrée de nav pas encore livrée (module en cours de construction par l'équipe). */
$navSoon = static function (string $label, string $svg) use ($navActive): string {
	return '<li class="nav-item"><span class="nav-link disabled' . $navActive($label) . '">'
		. $svg . '<span class="nav-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>'
		. '<span class="nav-soon">Bientôt</span></span></li>';
};

/** Rendu d'une entrée de nav pas encore livrée, avec un badge de comptage réel. */
$navSoonBadge = static function (string $label, string $svg, int $count, string $badgeTone) use ($navActive): string {
	$badge = $count > 0 ? '<span class="nav-badge ' . $badgeTone . '">' . $count . '</span>' : '<span class="nav-soon">Bientôt</span>';
	return '<li class="nav-item"><span class="nav-link disabled' . $navActive($label) . '">'
		. $svg . '<span class="nav-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>'
		. $badge . '</span></li>';
};
?>
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="brand-mark">LK</div>
      <div class="brand-name">LOKA</div>
    </div>

    <?php if ($sidebarAgence): ?>
    <div class="agency-identity">
      <div class="agency-identity-top">
        <div class="agency-identity-name" title="<?= htmlspecialchars($sidebarAgence['nom'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($sidebarAgence['nom'], ENT_QUOTES, 'UTF-8') ?></div>
        <span class="agency-identity-dot <?= $agenceStatutTone[$sidebarAgence['statut']] ?? 'attente' ?>" title="<?= htmlspecialchars($agenceStatutLabel[$sidebarAgence['statut']] ?? $sidebarAgence['statut'], ENT_QUOTES, 'UTF-8') ?>"></span>
      </div>
      <div class="agency-identity-sub"><?= htmlspecialchars($sidebarAgence['ville'] ?: 'Ville non renseignée', ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <?php endif; ?>

    <div class="nav-section-label">VUE D'ENSEMBLE</div>
    <ul class="nav">
      <li class="nav-item"><a class="nav-link<?= $navActive('Dashboard') ?>" href="../mon-agence/index.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
        <span class="nav-label">Dashboard</span>
      </a></li>
    </ul>

    <div class="nav-section-label">IMMOBILIER</div>
    <ul class="nav">
      <?= $navSoon('Biens', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>') ?>
      <?= $navSoon('Locataires', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg>') ?>
      <?= $navSoon('Propriétaires', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M5 21v-2a5 5 0 0 1 5-5h4a5 5 0 0 1 5 5v2"/><path d="M9 3.5a3 3 0 1 1 0 5.9"/></svg>') ?>
      <?= $navSoonBadge('Contrats', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>', $sidebarBadges['contrats'], 'amber') ?>
    </ul>

    <div class="nav-section-label">FINANCES</div>
    <ul class="nav">
      <?= $navSoon('Paiements', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>') ?>
      <?= $navSoon('Quittances', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path d="M9 7h7M9 11h5M9 15h3"/></svg>') ?>
    </ul>

    <div class="nav-section-label">MAINTENANCE</div>
    <ul class="nav">
      <?= $navSoonBadge('Interventions', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z"/></svg>', $sidebarBadges['interventions'], 'violet') ?>
    </ul>

    <div class="nav-section-label">ORGANISATION</div>
    <ul class="nav">
      <li class="nav-item"><a class="nav-link<?= $navActive('Équipe') ?>" href="../mon-agence/equipe.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span class="nav-label">Équipe</span>
      </a></li>
      <?= $navSoon('Documents', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>') ?>
    </ul>

    <div class="sidebar-bottom">
      <div class="nav-section-label" style="padding-top:0;">AGENCE</div>
      <ul class="nav">
        <?= $navSoon('Paramètres', '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/></svg>') ?>
        <li class="nav-item"><a class="nav-link<?= $navActive('Abonnement') ?>" href="../mon-agence/abonnement.php">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>
          <span class="nav-label">Abonnement</span>
        </a></li>
        <li class="nav-item"><a class="nav-link" id="logoutLink">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
          <span class="nav-label">Déconnexion</span>
        </a></li>
      </ul>
    </div>
  </aside>
