<?php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/ProprietaireController.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);
$ownerId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($agencyId < 1 || !$ownerId) {
	http_response_code(400);
	exit('Paramètres invalides.');
}
$owner = ProprietaireModel::find($agencyId, $ownerId);
if (!$owner) {
	http_response_code(404);
	exit('Propriétaire introuvable.');
}
$errors = [];
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		$errors['general'] = 'Votre session a expiré. Rechargez la page.';
	} elseif (($_POST['action'] ?? '') === 'attach') {
		try {
			[$errors] = ProprietaireController::attach($agencyId, $ownerId, $_POST);
			if ($errors === []) {
				$success = 'Le bien a été associé.';
			}
		} catch (Throwable $exception) {
			error_log($exception->getMessage());
			$errors['general'] = $exception->getMessage();
		}
	} elseif (($_POST['action'] ?? '') === 'detach') {
		try {
			ProprietaireModel::detachProperty($agencyId, $ownerId, (int) $_POST['id_bien']);
			$success = 'L\'association a été retirée.';
		} catch (Throwable $exception) {
			error_log($exception->getMessage());
			$errors['general'] = 'Impossible de retirer cette association.';
		}
	}
}
$properties = ProprietaireModel::properties($agencyId, $ownerId);
$available = ProprietaireModel::availableProperties($agencyId, $ownerId);
$csrfToken = Auth::csrfToken();
$projetLabels = ['rent' => 'Location', 'sell' => 'Vente', 'rent-sell' => 'Location et vente'];
?>
<!doctype html>
<html lang="fr">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= htmlspecialchars($owner['prenom'] . ' ' . $owner['nom'], ENT_QUOTES, 'UTF-8') ?> | LOKA</title>
	<link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>

<body>
	<div class="app-shell">
		<?php $currentPage = 'proprietaires';
		require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
		<div class="content">
			<header class="topbar">
				<div>
					<div class="topbar__eyebrow">Patrimoine · Propriétaires</div>
					<div class="topbar__title">Fiche détaillée</div>
				</div>
				<a class="button button--ghost" href="index.php">Retour à la liste</a>
			</header>
			<main class="page">
				<div class="page__header">
					<div>
						<h1 class="page__title"><?= htmlspecialchars($owner['prenom'] . ' ' . $owner['nom'], ENT_QUOTES, 'UTF-8') ?></h1>
						<p class="page__subtitle"><?= htmlspecialchars((string) ($owner['email'] ?? 'Aucun e-mail'), ENT_QUOTES, 'UTF-8') ?></p>
					</div>
					<div class="actions">
						<a class="button button--secondary" href="modifier.php?id=<?= $ownerId ?>">Modifier</a>
						<form method="post" action="supprimer.php" style="display:inline">
							<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
							<input type="hidden" name="id" value="<?= $ownerId ?>">
							<button class="button--danger" type="submit">Archiver</button>
						</form>
					</div>
				</div>

				<?php if ($success): ?>
					<div class="alert alert--success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
				<?php endif; ?>
				<?php if (isset($errors['general'])): ?>
					<div class="alert alert--error"><?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?></div>
				<?php endif; ?>

				<div class="detail-grid">
					<section class="card">
						<div class="card__header">
							<h2 class="card__title">Informations du propriétaire</h2>
						</div>
						<div class="card__body">
							<dl class="detail-list">
								<?php foreach (['email' => 'E-mail', 'telephone' => 'Téléphone', 'adresse' => 'Adresse', 'code_postal' => 'Code postal', 'ville' => 'Ville', 'pays' => 'Pays', 'date_naissance' => 'Date de naissance'] as $field => $label): ?>
									<div>
										<dt><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></dt>
										<dd><?= htmlspecialchars((string) ($owner[$field] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>
									</div>
								<?php endforeach; ?>
								<div>
									<dt>Projet</dt>
									<dd><?= htmlspecialchars($projetLabels[$owner['projet'] ?? ''] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
								</div>
							</dl>
						</div>
					</section>

					<section class="card">
						<div class="card__header">
							<h2 class="card__title">Associer un bien</h2>
						</div>
						<div class="card__body">
							<form method="post">
								<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
								<input type="hidden" name="action" value="attach">
								<label>Bien
									<select name="id_bien" required>
										<option value="">Sélectionner un bien</option>
										<?php foreach ($available as $property): ?>
											<option value="<?= (int) $property['id_bien'] ?>"><?= htmlspecialchars($property['reference'] . ' — ' . $property['titre'], ENT_QUOTES, 'UTF-8') ?></option>
										<?php endforeach; ?>
									</select>
								</label>
								<label>Quote-part (%)<input type="number" name="quote_part" min="0.01" max="100" step="0.01" value="100"></label>
								<label>Date de début<input type="date" name="date_debut" required value="<?= date('Y-m-d') ?>"></label>
								<button type="submit" style="margin-top:12px;">Associer le bien</button>
							</form>
						</div>
					</section>
				</div>

				<!-- Biens associés -->
				<section class="card" style="margin-top:20px">
					<div class="card__header">
						<h2 class="card__title">Biens associés</h2>
						<span class="cell-muted"><?= count($properties) ?> bien(s)</span>
					</div>
					<div class="table-wrap">
						<table>
							<thead>
								<tr>
									<th>Bien</th>
									<th>Statut</th>
									<th>Surface</th>
									<th>Loyer</th>
									<th>Quote-part</th>
									<th>Depuis</th>
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
										<td><span class="badge badge--<?= $property['statut'] === 'AVAILABLE' ? 'available' : ($property['statut'] === 'OCCUPIED' ? 'occupied' : 'created') ?>"><?= htmlspecialchars($property['statut'], ENT_QUOTES, 'UTF-8') ?></span></td>
										<td><?= htmlspecialchars((string) $property['surface'], ENT_QUOTES, 'UTF-8') ?> m²</td>
										<td><?= number_format((float) $property['loyer'], 2, ',', ' ') ?> €</td>
										<td><?= htmlspecialchars((string) $property['quote_part'], ENT_QUOTES, 'UTF-8') ?> %</td>
										<td><?= htmlspecialchars($property['date_debut'], ENT_QUOTES, 'UTF-8') ?></td>
										<td>
											<form method="post" style="display:inline">
												<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
												<input type="hidden" name="action" value="detach">
												<input type="hidden" name="id_bien" value="<?= (int) $property['id_bien'] ?>">
												<button class="button--danger" type="submit" style="min-height:32px;font-size:12px;">Retirer</button>
											</form>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php if ($properties === []): ?>
						<div class="empty">Aucun bien associé.</div>
					<?php endif; ?>
				</section>
			</main>
		</div>
	</div>
</body>

</html>