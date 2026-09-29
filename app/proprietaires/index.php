<?php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/ProprietaireModel.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);
if ($agencyId < 1) {
	http_response_code(400);
	exit('Aucune agence associee a ce compte.');
}
$owners = ProprietaireModel::list($agencyId, (string) ($_GET['q'] ?? ''));
?>
<!doctype html>
<html lang="fr">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Propriétaires | LOKA</title>
	<link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>

<body>
	<div class="app-shell">
		<?php $currentPage = 'proprietaires';
		require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
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
					<a class="button button--primary" href="ajouter.php">＋ Ajouter un propriétaire</a>
				</div>

				<section class="card">
					<div class="card__header">
						<div>
							<h2 class="card__title">Répertoire des propriétaires</h2>
							<div class="cell-muted"><?= count($owners) ?> résultat(s)</div>
						</div>
					</div>
					<form class="toolbar" method="get">
						<label>Rechercher<input name="q" placeholder="Nom, prénom ou e-mail" value="<?= htmlspecialchars((string) ($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
						<button type="submit">Rechercher</button>
					</form>
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
							<div class="empty__icon">♙</div>
							<strong>Aucun propriétaire trouvé</strong>
							<div>Ajoutez votre premier propriétaire ou modifiez votre recherche.</div>
						</div>
					<?php endif; ?>
				</section>
			</main>
		</div>
	</div>
</body>

</html>