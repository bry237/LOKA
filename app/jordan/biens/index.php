<?php

declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/BienController.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);
if ($agencyId < 1) {
	http_response_code(400);
	exit('Aucune agence associee a ce compte.');
}
$references = BienController::references($agencyId);
$properties = BienModel::list($agencyId, $_GET);
$statusLabels = ['CREATED' => 'Brouillon', 'AVAILABLE' => 'Disponible', 'OCCUPIED' => 'Loué', 'MAINTENANCE' => 'Maintenance', 'ARCHIVED' => 'Archivé'];
$statusClasses = ['CREATED' => 'created', 'AVAILABLE' => 'available', 'OCCUPIED' => 'occupied', 'MAINTENANCE' => 'maintenance', 'ARCHIVED' => 'archived'];

$isAjax = ($_GET['ajax'] ?? '') === '1';

$renderResults = function () use ($properties, $statusLabels, $statusClasses, $references, $agencyId): void {
	?>
	<div class="stats">
		<div class="card stat">
			<div class="stat__top"><span>Total des biens</span><span class="stat__icon">⌂</span></div>
			<div class="stat__value"><?= count($properties) ?></div>
		</div>
		<div class="card stat">
			<div class="stat__top"><span>Disponibles</span><span class="stat__icon">✓</span></div>
			<div class="stat__value"><?= count(array_filter($properties, static fn(array $p): bool => $p['statut'] === 'AVAILABLE')) ?></div>
		</div>
		<div class="card stat">
			<div class="stat__top"><span>Occupés</span><span class="stat__icon">▣</span></div>
			<div class="stat__value"><?= count(array_filter($properties, static fn(array $p): bool => $p['statut'] === 'OCCUPIED')) ?></div>
		</div>
		<div class="card stat">
			<div class="stat__top"><span>Surface totale</span><span class="stat__icon">↗</span></div>
			<div class="stat__value"><?= number_format(array_sum(array_map(static fn(array $p): float => (float) $p['surface'], $properties)), 0, ',', ' ') ?> m²</div>
		</div>
	</div>
	<section class="card">
		<div class="card__header">
			<div>
				<h2 class="card__title">Portefeuille immobilier</h2>
				<div class="cell-muted"><span class="results-count"><?= count($properties) ?> résultat(s)</span> dans votre agence</div>
			</div>
		</div>
		<div class="table-wrap">
			<table>
				<thead>
					<tr>
						<th>Bien</th>
						<th>Type</th>
						<th>Localisation</th>
						<th>Surface</th>
						<th>Loyer</th>
						<th>Statut</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($properties as $property): ?>
						<tr>
							<td>
								<div class="cell-title"><?= htmlspecialchars($property['titre'], ENT_QUOTES, 'UTF-8') ?></div>
								<div class="cell-muted"><?= htmlspecialchars($property['reference'], ENT_QUOTES, 'UTF-8') ?></div>
							</td>
							<td><?= htmlspecialchars($property['type_bien'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars($property['ville'], ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars((string) $property['surface'], ENT_QUOTES, 'UTF-8') ?> m²</td>
							<td><?= number_format((float) $property['loyer'], 2, ',', ' ') ?> €</td>
							<td><span class="badge badge--<?= $statusClasses[$property['statut']] ?? 'created' ?>"><?= $statusLabels[$property['statut']] ?? $property['statut'] ?></span></td>
							<td><a class="button button--ghost" href="detail.php?id=<?= (int) $property['id_bien'] ?>">Voir</a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php if ($properties === []): ?>
			<div class="empty">
				<div class="empty__icon">⌂</div>
				<strong>Aucun bien trouvé</strong>
				<div>Modifiez vos filtres ou ajoutez votre premier bien.</div>
			</div>
		<?php endif; ?>
	</section>
	<?php
};

if ($isAjax) {
	$renderResults();
	exit;
}
?>
<!doctype html>
<html lang="fr">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Biens | LOKA</title>
	<link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>

<body>
	<div class="app-shell">
		<?php $currentPage = 'biens';
		require_once dirname(__DIR__) . '/layouts/sidebar-gestion.php'; ?>
		<div class="content">
			<header class="topbar">
				<div>
					<div class="topbar__eyebrow">Espace de travail</div>
					<div class="topbar__title">Gestion immobilière</div>
				</div>
				<div class="user-chip">
					<span class="avatar"><?= strtoupper(substr((string) ($user['prenom'] ?? 'U'), 0, 1)) ?></span>
					<span><?= htmlspecialchars((string) ($user['prenom'] ?? 'Utilisateur'), ENT_QUOTES, 'UTF-8') ?></span>
				</div>
			</header>
			<main class="page">
				<div class="page__header">
					<div>
						<h1 class="page__title">Biens immobiliers</h1>
						<p class="page__subtitle">Pilotez votre portefeuille et gardez une vue claire sur vos biens.</p>
					</div>
					<div class="actions">
						<a class="button button--secondary" href="references.php">Référentiels</a>
						<a class="button button--primary" href="ajouter.php" data-modal>＋ Ajouter un bien</a>
					</div>
				</div>

				<div class="filters-card">
					<form class="filters-form" method="get" data-live-search data-live-target="#biensResults">
						<label class="live-search">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
							<input name="q" placeholder="Référence, titre ou ville" value="<?= htmlspecialchars((string) ($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
							<span class="live-search-spinner"></span>
						</label>
						<div class="form-group">
							<label>Statut</label>
							<select name="statut">
								<option value="">Tous sauf archivés</option>
								<?php foreach ($statusLabels as $key => $label): ?>
									<option value="<?= $key ?>" <?= ($_GET['statut'] ?? '') === $key ? ' selected' : '' ?>><?= $label ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="form-group">
							<label>Type</label>
							<select name="id_type_bien">
								<option value="">Tous les types</option>
								<?php foreach ($references['types'] as $type): ?>
									<option value="<?= (int) $type['id_type_bien'] ?>" <?= (string) ($_GET['id_type_bien'] ?? '') === (string) $type['id_type_bien'] ? ' selected' : '' ?>><?= htmlspecialchars($type['nom'], ENT_QUOTES, 'UTF-8') ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="form-group">
							<label>Loyer max</label>
							<input type="number" step="0.01" min="0" name="loyer_max" placeholder="€" value="<?= htmlspecialchars((string) ($_GET['loyer_max'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
						</div>
						<div class="form-group">
							<label>Surface min</label>
							<input type="number" step="0.01" min="0" name="surface_min" placeholder="m²" value="<?= htmlspecialchars((string) ($_GET['surface_min'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
						</div>
						<div class="form-actions" style="margin:0;padding:0;border:none;">
							<button type="submit" class="btn btn-secondary">Filtrer</button>
						</div>
					</form>
				</div>

				<div id="biensResults">
					<?php $renderResults(); ?>
				</div>
			</main>
		</div>
	</div>
	<?php require __DIR__ . '/../layouts/modal-shell.php'; ?>
	<script src="/LOKA/app/jordan/layouts/live-search.js"></script>
</body>

</html>
