<?php

declare(strict_types=1);

/**
 * Sidebar partagée — Composant de navigation LOKA
 *
 * Utilisation dans chaque vue :
 *   <?php $currentPage = 'biens'; require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
 *
 * Pages disponibles :
 *   dashboard, biens, immeubles, proprietaires, locataires, contrats,
 *   paiements, quittances, maintenance, documents, rapports
 */

$basePath = '/LOKA/app';
$sidebarUser = $user ?? null;
$sidebarRole = $sidebarUser['role_nom'] ?? '';

$navigation = [
	[
		'label' => 'Tableau de bord',
		'items' => [
			['key' => 'dashboard', 'icon' => '📊', 'text' => 'Dashboard', 'href' => "$basePath/dashboard/index.php"],
		],
	],
	[
		'label' => 'Patrimoine',
		'items' => [
			['key' => 'biens', 'icon' => '⌂', 'text' => 'Biens', 'href' => "$basePath/biens/index.php"],
			['key' => 'immeubles', 'icon' => '🏢', 'text' => 'Immeubles', 'href' => "$basePath/biens/references.php"],
			['key' => 'proprietaires', 'icon' => '♙', 'text' => 'Propriétaires', 'href' => "$basePath/proprietaires/index.php"],
		],
	],
	[
		'label' => 'Location',
		'items' => [
			['key' => 'locataires', 'icon' => '👤', 'text' => 'Locataires', 'href' => "$basePath/locataires/index.php"],
			['key' => 'contrats', 'icon' => '▣', 'text' => 'Contrats', 'href' => "$basePath/contrats/index.php"],
		],
	],
	[
		'label' => 'Finances',
		'items' => [
			['key' => 'paiements', 'icon' => '€', 'text' => 'Paiements', 'href' => "$basePath/paiements/index.php"],
			['key' => 'quittances', 'icon' => '🧾', 'text' => 'Quittances', 'href' => "$basePath/paiements/quittances.php"],
		],
	],
	[
		'label' => 'Technique',
		'items' => [
			['key' => 'maintenance', 'icon' => '⚒', 'text' => 'Maintenance', 'href' => "$basePath/maintenance/index.php"],
		],
	],
	[
		'label' => 'Outils',
		'items' => [
			['key' => 'documents', 'icon' => '📁', 'text' => 'Documents', 'href' => "$basePath/documents/index.php"],
			['key' => 'rapports', 'icon' => '📈', 'text' => 'Rapports', 'href' => "$basePath/rapports/index.php"],
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
		Espace gestionnaire<br>
		<?php if ($sidebarUser): ?>
			<?= htmlspecialchars(($sidebarUser['prenom'] ?? '') . ' ' . ($sidebarUser['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
		<?php else: ?>
			Agence immobilière
		<?php endif; ?>
	</div>
</aside>