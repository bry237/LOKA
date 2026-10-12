<?php

declare(strict_types=1);

/**
 * Sidebar partagée — Composant de navigation LOKA
 *
 * Utilisation dans chaque vue :
 *   <?php $currentPage = 'biens'; require_once dirname(__DIR__) . '/layouts/sidebar-gestion.php'; ?>
 *
 * Pages disponibles :
 *   dashboard, biens, immeubles, proprietaires, locataires, contrats,
 *   paiements, quittances, maintenance, documents, rapports
 */

$basePath = '/LOKA/app/jordan';
$sidebarUser = $user ?? null;
$sidebarRole = $sidebarUser['role_nom'] ?? '';

$icon = [
	'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>',
	'biens' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>',
	'immeubles' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 9h1M14 9h1M9 13h1M14 13h1M9 17h1M14 17h1"/></svg>',
	'proprietaires' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v6c0 5 3.4 8.8 8 11 4.6-2.2 8-6 8-11V5l-8-3Z"/><path d="m9 12 2 2 4-4"/></svg>',
	'locataires' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
	'contrats' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/></svg>',
	'paiements' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>',
	'quittances' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h12v20l-3-2-3 2-3-2-3 2V2Z"/><path d="M9 7h6M9 11h6"/></svg>',
	'encaissements' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
	'maintenance' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
	'documents' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/></svg>',
	'rapports' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="M7 15l4-5 3 3 5-7"/></svg>',
	'logout' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>',
];

$navigation = [
	[
		'label' => 'Tableau de bord',
		'items' => [
			['key' => 'dashboard', 'icon' => $icon['dashboard'], 'text' => 'Dashboard', 'href' => "$basePath/dashboard/index.php"],
		],
	],
	[
		'label' => 'Patrimoine',
		'items' => [
			['key' => 'biens', 'icon' => $icon['biens'], 'text' => 'Biens', 'href' => "$basePath/biens/index.php"],
			['key' => 'immeubles', 'icon' => $icon['immeubles'], 'text' => 'Immeubles', 'href' => "$basePath/biens/references.php"],
			['key' => 'proprietaires', 'icon' => $icon['proprietaires'], 'text' => 'Propriétaires', 'href' => "$basePath/proprietaires/index.php"],
		],
	],
	[
		'label' => 'Location',
		'items' => [
			['key' => 'locataires', 'icon' => $icon['locataires'], 'text' => 'Locataires', 'href' => "$basePath/locataires/index.php"],
			['key' => 'contrats', 'icon' => $icon['contrats'], 'text' => 'Contrats', 'href' => "$basePath/contrats/index.php"],
		],
	],
	[
		'label' => 'Finances',
		'items' => [
			['key' => 'paiements', 'icon' => $icon['paiements'], 'text' => 'Paiements', 'href' => "$basePath/paiements/index.php"],
			['key' => 'encaissements', 'icon' => $icon['encaissements'], 'text' => 'Encaissements', 'href' => "$basePath/paiements/echeances.php"],
			['key' => 'quittances', 'icon' => $icon['quittances'], 'text' => 'Quittances', 'href' => "$basePath/paiements/quittances.php"],
		],
	],
	[
		'label' => 'Technique',
		'items' => [
			['key' => 'maintenance', 'icon' => $icon['maintenance'], 'text' => 'Maintenance', 'href' => "$basePath/maintenance/index.php"],
		],
	],
	[
		'label' => 'Outils',
		'items' => [
			['key' => 'documents', 'icon' => $icon['documents'], 'text' => 'Documents', 'href' => "$basePath/documents/index.php"],
			['key' => 'rapports', 'icon' => $icon['rapports'], 'text' => 'Rapports', 'href' => "$basePath/rapports/index.php"],
		],
	],
];

$currentPage = $currentPage ?? '';
?>
<aside class="sidebar">
	<a class="brand" href="<?= htmlspecialchars("$basePath/dashboard/index.php", ENT_QUOTES, 'UTF-8') ?>">
		<span class="brand__mark">L</span><span>LOKA</span>
	</a>
	<nav class="nav">
		<?php foreach ($navigation as $group): ?>
			<div class="nav__label"><?= htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8') ?></div>
			<?php foreach ($group['items'] as $item): ?>
				<a class="<?= $currentPage === $item['key'] ? 'is-active' : '' ?>" href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>">
					<span class="nav__icon"><?= $item['icon'] ?></span><span><?= htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8') ?></span>
				</a>
			<?php endforeach; ?>
		<?php endforeach; ?>
	</nav>
	<div class="sidebar__footer">
		<div class="sidebar__user">
			<span class="sidebar__user-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr((string) ($sidebarUser['prenom'] ?? 'U'), 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
			<span class="sidebar__user-text">
				<span class="sidebar__user-name"><?php if ($sidebarUser): ?><?= htmlspecialchars(($sidebarUser['prenom'] ?? '') . ' ' . ($sidebarUser['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?><?php else: ?>Agence immobilière<?php endif; ?></span>
				<span class="sidebar__user-role">Espace gestionnaire</span>
			</span>
		</div>
		<a class="sidebar__logout" href="/LOKA/app/authentification/deconnexion.php">
			<span class="nav__icon"><?= $icon['logout'] ?></span>
			<span>Déconnexion</span>
		</a>
	</div>
</aside>