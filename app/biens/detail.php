<?php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/BienModel.php';
require_once __DIR__ . '/BienController.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);
$propertyId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($agencyId < 1 || !$propertyId) {
	http_response_code(400);
	exit('Paramètres invalides.');
}
$property = BienModel::find($agencyId, $propertyId);
if (!$property) {
	http_response_code(404);
	exit('Bien introuvable.');
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		$errors['general'] = 'Votre session a expiré. Rechargez la page.';
	} elseif (($_POST['action'] ?? '') === 'equipment') {
		try {
			[$errors] = BienController::updateEquipment($agencyId, $propertyId, $_POST);
		} catch (Throwable $exception) {
			error_log($exception->getMessage());
			$errors['general'] = 'Impossible de modifier les équipements.';
		}
	}
}
$equipmentIds = array_map('intval', array_column(BienModel::equipmentAssignments($agencyId, $propertyId), 'id_equipement'));
$equipmentOptions = BienModel::equipments();
$owners = BienModel::owners($agencyId, $propertyId);
$photos = BienModel::photos($agencyId, $propertyId);
$dpeLabels = ['A' => 'dpe-a', 'B' => 'dpe-b', 'C' => 'dpe-c', 'D' => 'dpe-d', 'E' => 'dpe-e', 'F' => 'dpe-f', 'G' => 'dpe-g'];
?>
<!doctype html>
<html lang="fr">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= htmlspecialchars($property['titre'], ENT_QUOTES, 'UTF-8') ?> | LOKA</title>
	<link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>

<body>
	<div class="app-shell">
		<?php $currentPage = 'biens';
		require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
		<div class="content">
			<header class="topbar">
				<div>
					<div class="topbar__eyebrow">Portefeuille immobilier</div>
					<div class="topbar__title">Fiche du bien</div>
				</div>
				<a class="button button--ghost" href="index.php">Retour à la liste</a>
			</header>
			<main class="page">
				<div class="page__header">
					<div>
						<h1 class="page__title"><?= htmlspecialchars($property['titre'], ENT_QUOTES, 'UTF-8') ?></h1>
						<p class="page__subtitle"><?= htmlspecialchars($property['reference'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($property['ville'], ENT_QUOTES, 'UTF-8') ?></p>
					</div>
					<div class="actions">
						<a class="button button--secondary" href="photos.php?id=<?= $propertyId ?>">Gérer les photos</a>
						<a class="button button--primary" href="modifier.php?id=<?= $propertyId ?>">Modifier</a>
					</div>
				</div>

				<!-- Sous-onglets de la fiche bien -->
				<nav class="tab-nav" style="display:flex;gap:0;margin-bottom:24px;border-bottom:2px solid var(--border);">
					<a class="tab-link tab-link--active" href="detail.php?id=<?= $propertyId ?>" style="padding:12px 20px;font-weight:700;color:var(--primary-dark);border-bottom:2px solid var(--primary);margin-bottom:-2px;font-size:14px;">Informations</a>
					<a class="tab-link" href="photos.php?id=<?= $propertyId ?>" style="padding:12px 20px;font-weight:600;color:var(--gray);font-size:14px;">Photos</a>
					<a class="tab-link" href="historique.php?id=<?= $propertyId ?>" style="padding:12px 20px;font-weight:600;color:var(--gray);font-size:14px;">Historique</a>
				</nav>

				<div class="detail-grid">
					<section class="card">
						<div class="card__header">
							<h2 class="card__title">Informations du bien</h2>
							<span class="badge badge--<?= $property['statut'] === 'AVAILABLE' ? 'available' : ($property['statut'] === 'OCCUPIED' ? 'occupied' : ($property['statut'] === 'MAINTENANCE' ? 'maintenance' : 'created')) ?>"><?= htmlspecialchars($property['statut'], ENT_QUOTES, 'UTF-8') ?></span>
						</div>
						<div class="card__body">
							<dl class="detail-list">
								<?php foreach (['reference' => 'Référence', 'type_bien' => 'Type de bien', 'statut' => 'Statut', 'surface' => 'Surface (m²)', 'nombre_pieces' => 'Nombre de pièces', 'etage' => 'Étage', 'loyer' => 'Loyer (€)', 'charges' => 'Charges (€)', 'caution' => 'Caution (€)', 'ligne1' => 'Adresse', 'code_postal' => 'Code postal', 'ville' => 'Ville', 'immeuble_nom' => 'Immeuble'] as $field => $label): ?>
									<div>
										<dt><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></dt>
										<dd><?= htmlspecialchars((string) ($property[$field] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>
									</div>
								<?php endforeach; ?>
								<?php if (!empty($property['meuble'])): ?>
									<div>
										<dt>Meublé</dt>
										<dd><?= $property['meuble'] ? 'Oui' : 'Non' ?></dd>
									</div>
								<?php endif; ?>
								<?php if (!empty($property['dpe_classe'])): ?>
									<div>
										<dt>DPE</dt>
										<dd><span class="badge badge--created"><?= htmlspecialchars($property['dpe_classe'], ENT_QUOTES, 'UTF-8') ?></span></dd>
									</div>
								<?php endif; ?>
								<?php if (!empty($property['ges_classe'])): ?>
									<div>
										<dt>GES</dt>
										<dd><span class="badge badge--created"><?= htmlspecialchars($property['ges_classe'], ENT_QUOTES, 'UTF-8') ?></span></dd>
									</div>
								<?php endif; ?>
								<?php if (!empty($property['date_disponibilite'])): ?>
									<div>
										<dt>Date de disponibilité</dt>
										<dd><?= htmlspecialchars($property['date_disponibilite'], ENT_QUOTES, 'UTF-8') ?></dd>
									</div>
								<?php endif; ?>
							</dl>
							<?php if (!empty($property['description'])): ?>
								<p style="margin-top:16px;"><?= nl2br(htmlspecialchars((string) $property['description'], ENT_QUOTES, 'UTF-8')) ?></p>
							<?php endif; ?>
						</div>
					</section>

					<aside class="card">
						<div class="card__header">
							<h2 class="card__title">Équipements</h2>
						</div>
						<div class="card__body">
							<form method="post">
								<?php if (isset($errors['general'])): ?>
									<div class="alert alert--error"><?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?></div>
								<?php endif; ?>
								<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
								<input type="hidden" name="action" value="equipment">
								<div class="check-grid">
									<?php foreach ($equipmentOptions as $equipment): ?>
										<label>
											<input type="checkbox" name="equipements[]" value="<?= (int) $equipment['id_equipement'] ?>" <?= in_array((int) $equipment['id_equipement'], $equipmentIds, true) ? ' checked' : '' ?>>
											<?= htmlspecialchars($equipment['nom'], ENT_QUOTES, 'UTF-8') ?>
										</label>
									<?php endforeach; ?>
								</div>
								<button type="submit" style="margin-top:16px;">Enregistrer les équipements</button>
							</form>
						</div>
					</aside>
				</div>

				<!-- Galerie photos -->
				<section class="card" style="margin-top:20px">
					<div class="card__header">
						<h2 class="card__title">Galerie photos</h2>
						<a href="photos.php?id=<?= $propertyId ?>">Gérer la galerie</a>
					</div>
					<div class="card__body">
						<div class="photo-grid">
							<?php foreach (array_slice($photos, 0, 4) as $photo): ?>
								<div class="photo">
									<img src="/LOKA/<?= htmlspecialchars($photo['chemin_stockage'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($photo['nom_fichier'], ENT_QUOTES, 'UTF-8') ?>">
									<div class="photo__body">
										<?= $photo['est_principale'] ? '<span class="badge badge--available">Photo principale</span>' : '' ?>
									</div>
								</div>
							<?php endforeach; ?>
							<?php if ($photos === []): ?>
								<div class="empty">Aucune photo ajoutée pour le moment.</div>
							<?php endif; ?>
						</div>
					</div>
				</section>

				<!-- Propriétaires associés -->
				<section class="card" style="margin-top:20px">
					<div class="card__header">
						<h2 class="card__title">Propriétaires associés</h2>
						<span class="cell-muted"><?= count($owners) ?> propriétaire(s)</span>
					</div>
					<div class="card__body">
						<?php if ($owners === []): ?>
							<div class="empty">Aucun propriétaire associé.</div>
						<?php else: ?>
							<div class="table-wrap">
								<table>
									<thead>
										<tr>
											<th>Propriétaire</th>
											<th>E-mail</th>
											<th>Quote-part</th>
											<th>Date de début</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($owners as $owner): ?>
											<tr>
												<td class="cell-title"><?= htmlspecialchars($owner['prenom'] . ' ' . $owner['nom'], ENT_QUOTES, 'UTF-8') ?></td>
												<td><?= htmlspecialchars((string) ($owner['email'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
												<td><?= htmlspecialchars((string) $owner['quote_part'], ENT_QUOTES, 'UTF-8') ?> %</td>
												<td><?= htmlspecialchars($owner['date_debut'], ENT_QUOTES, 'UTF-8') ?></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						<?php endif; ?>
					</div>
				</section>

				<?php if ($property['statut'] !== 'ARCHIVED'): ?>
					<form method="post" action="supprimer.php" style="margin-top:20px">
						<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
						<input type="hidden" name="id" value="<?= $propertyId ?>">
						<button type="submit" class="button--danger">Archiver ce bien</button>
					</form>
				<?php endif; ?>
			</main>
		</div>
	</div>
</body>

</html>