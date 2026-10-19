<?php

declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/ProprietaireModel.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);
if ($agencyId < 1) {
	http_response_code(400);
	exit('Aucune agence associee a ce compte.');
}
$owners = ProprietaireModel::list($agencyId, (string) ($_GET['q'] ?? ''));
$isAjax = ($_GET['ajax'] ?? '') === '1';

$renderResults = function () use ($owners): void {
	?>
	<section class="card">
		<div class="card__header">
			<div>
				<h2 class="card__title">Répertoire des propriétaires</h2>
				<div class="cell-muted"><span class="results-count"><?= count($owners) ?> résultat(s)</span></div>
			</div>
		</div>
		<div class="table-wrap">
			<table>
				<thead>
					<tr>
						<th>Propriétaire</th>
						<th>Contact</th>
						<th>Ville</th>
						<th>Biens</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($owners as $owner): ?>
						<tr>
							<td>
								<div class="cell-title"><?= htmlspecialchars($owner['prenom'] . ' ' . $owner['nom'], ENT_QUOTES, 'UTF-8') ?></div>
								<div class="cell-muted">#<?= (int) $owner['id_proprietaire'] ?></div>
							</td>
							<td><?= htmlspecialchars((string) ($owner['email'] ?? 'Non renseigné'), ENT_QUOTES, 'UTF-8') ?></td>
							<td><?= htmlspecialchars((string) ($owner['ville'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
							<td><span class="badge badge--created"><?= (int) $owner['biens_count'] ?> bien(s)</span></td>
							<td><a class="button button--ghost" href="detail.php?id=<?= (int) $owner['id_proprietaire'] ?>">Voir</a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php if ($owners === []): ?>
			<div class="empty">
				<div class="empty__icon"><svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="14.5" r="3.5"/><path d="m10 12 8-8M15.5 3.5l3 3M18.5 6.5l3 3"/></svg></div>
				<strong>Aucun propriétaire trouvé</strong>
				<div>Ajoutez votre premier propriétaire ou modifiez votre recherche.</div>
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
	<title>Propriétaires | LOKA</title>
	<link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>

<body>
	<div class="app-shell">
		<?php $currentPage = 'proprietaires';
		require_once dirname(__DIR__) . '/layouts/sidebar-gestion.php'; ?>
		<div class="content">
			<header class="topbar">
				<div>
					<div class="topbar__eyebrow">Patrimoine</div>
					<div class="topbar__title">Propriétaires</div>
				</div>
				<div class="user-chip">
					<span class="avatar"><?= strtoupper(substr((string) ($user['prenom'] ?? 'U'), 0, 1)) ?></span>
					<span><?= htmlspecialchars((string) ($user['prenom'] ?? 'Utilisateur'), ENT_QUOTES, 'UTF-8') ?></span>
				</div>
			</header>
			<main class="page">
				<div class="page__header">
					<div>
						<h1 class="page__title">Propriétaires</h1>
						<p class="page__subtitle">Gérez les propriétaires rattachés à votre agence.</p>
					</div>
					<a class="button button--primary" href="creer.php" data-modal>＋ Ajouter un propriétaire</a>
				</div>

				<div class="filters-card">
					<form class="filters-form" method="get" data-live-search data-live-target="#ownersResults">
						<label class="live-search">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
							<input name="q" placeholder="Nom, prénom ou e-mail" value="<?= htmlspecialchars((string) ($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
							<span class="live-search-spinner"></span>
						</label>
						<div class="form-actions" style="margin:0;padding:0;border:none;">
							<button type="submit" class="btn btn-secondary">Rechercher</button>
						</div>
					</form>
				</div>

				<div id="ownersResults">
					<?php $renderResults(); ?>
				</div>
			</main>
		</div>
	</div>
	<?php require __DIR__ . '/../layouts/modal-shell.php'; ?>
	<script src="/LOKA/app/jordan/layouts/live-search.js"></script>
</body>

</html>
