<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/BienModel.php';
require_once __DIR__ . '/BienController.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);
$propertyId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($agencyId < 1 || !$propertyId) { http_response_code(400); exit('Paramètres invalides.'); }
$property = BienModel::find($agencyId, $propertyId);
if (!$property) { http_response_code(404); exit('Bien introuvable.'); }
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
$photos = BienModel::photos($agencyId, $propertyId);
?>
<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= htmlspecialchars($property['titre'], ENT_QUOTES, 'UTF-8') ?> | LOKA</title><link rel="stylesheet" href="bien.css"></head>
<body><div class="app-shell"><aside class="sidebar"><a class="brand" href="index.php"><span class="brand__mark">L</span><span>LOKA</span></a><nav class="nav"><div class="nav__label">Gestion</div><a class="is-active" href="index.php"><span class="nav__icon">⌂</span><span>Biens</span></a><a href="references.php"><span class="nav__icon">◈</span><span>Référentiels</span></a><a href="#"><span class="nav__icon">♙</span><span>Propriétaires</span></a><a href="#"><span class="nav__icon">▣</span><span>Contrats</span></a><div class="nav__label">Suivi</div><a href="#"><span class="nav__icon">€</span><span>Paiements</span></a><a href="#"><span class="nav__icon">⚒</span><span>Maintenance</span></a></nav><div class="sidebar__footer">Espace gestionnaire</div></aside><div class="content"><header class="topbar"><div><div class="topbar__eyebrow">Portefeuille immobilier</div><div class="topbar__title">Fiche du bien</div></div><a class="button button--ghost" href="index.php">Retour à la liste</a></header><main class="page">
<div class="page__header"><div><h1 class="page__title"><?= htmlspecialchars($property['titre'], ENT_QUOTES, 'UTF-8') ?></h1><p class="page__subtitle"><?= htmlspecialchars($property['reference'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($property['ville'], ENT_QUOTES, 'UTF-8') ?></p></div><div class="actions"><a class="button button--secondary" href="photos.php?id=<?= $propertyId ?>">Gérer les photos</a><a class="button button--primary" href="modifier.php?id=<?= $propertyId ?>">Modifier</a></div></div>
<div class="detail-grid"><section class="card"><div class="card__header"><h2 class="card__title">Informations du bien</h2><span class="badge badge--<?= $property['statut'] === 'AVAILABLE' ? 'available' : ($property['statut'] === 'OCCUPIED' ? 'occupied' : 'created') ?>"><?= htmlspecialchars($property['statut'], ENT_QUOTES, 'UTF-8') ?></span></div><div class="card__body"><dl class="detail-list">
<?php foreach (['reference','type_bien','statut','surface','nombre_pieces','etage','loyer','charges','caution','ligne1','code_postal','ville','immeuble_nom'] as $field): ?>
	<div><dt><?= htmlspecialchars(str_replace('_', ' ', $field), ENT_QUOTES, 'UTF-8') ?></dt><dd><?= htmlspecialchars((string) ($property[$field] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd></div>
<?php endforeach; ?>
</dl><p><?= nl2br(htmlspecialchars((string) ($property['description'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p></div></section>
<aside class="card"><div class="card__header"><h2 class="card__title">Équipements</h2></div><div class="card__body"><form method="post">
<?php if (isset($errors['general'])): ?><p role="alert"><?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
	<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
	<input type="hidden" name="action" value="equipment">
	<div class="check-grid"><?php foreach ($equipmentOptions as $equipment): ?><label><input type="checkbox" name="equipements[]" value="<?= (int) $equipment['id_equipement'] ?>"<?= in_array((int) $equipment['id_equipement'], $equipmentIds, true) ? ' checked' : '' ?>> <?= htmlspecialchars($equipment['nom'], ENT_QUOTES, 'UTF-8') ?></label><?php endforeach; ?></div>
	<button type="submit">Enregistrer les équipements</button>
</form></div></aside></div>
<section class="card" style="margin-top:20px"><div class="card__header"><h2 class="card__title">Galerie photos</h2><a href="photos.php?id=<?= $propertyId ?>">Gérer la galerie</a></div><div class="card__body"><div class="photo-grid"><?php foreach (array_slice($photos, 0, 4) as $photo): ?><div class="photo"><img src="/LOKA/<?= htmlspecialchars($photo['chemin_stockage'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($photo['nom_fichier'], ENT_QUOTES, 'UTF-8') ?>"><div class="photo__body"><?= $photo['est_principale'] ? '<span class="badge badge--available">Photo principale</span>' : '' ?></div></div><?php endforeach; ?><?php if ($photos === []): ?><div class="empty">Aucune photo ajoutée pour le moment.</div><?php endif; ?></div></div></section>
<?php if ($property['statut'] !== 'ARCHIVED'): ?>
<form method="post" action="supprimer.php" style="margin-top:20px">
	<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
	<input type="hidden" name="id" value="<?= $propertyId ?>">
	<button type="submit">Archiver</button>
</form>
<?php endif; ?>
</main></div></div></body></html>