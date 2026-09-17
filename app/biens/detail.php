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
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= htmlspecialchars($property['titre'], ENT_QUOTES, 'UTF-8') ?></title></head>
<body><main>
<h1><?= htmlspecialchars($property['titre'], ENT_QUOTES, 'UTF-8') ?></h1>
<dl>
<?php foreach (['reference','type_bien','statut','surface','nombre_pieces','etage','loyer','charges','caution','ligne1','code_postal','ville','immeuble_nom'] as $field): ?>
	<dt><?= htmlspecialchars($field, ENT_QUOTES, 'UTF-8') ?></dt>
	<dd><?= htmlspecialchars((string) ($property[$field] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd>
<?php endforeach; ?>
</dl>
<p><?= nl2br(htmlspecialchars((string) ($property['description'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
<?php if (isset($errors['general'])): ?><p role="alert"><?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<h2>Équipements</h2>
<form method="post">
	<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
	<input type="hidden" name="action" value="equipment">
	<?php foreach ($equipmentOptions as $equipment): ?><label><input type="checkbox" name="equipements[]" value="<?= (int) $equipment['id_equipement'] ?>"<?= in_array((int) $equipment['id_equipement'], $equipmentIds, true) ? ' checked' : '' ?>> <?= htmlspecialchars($equipment['nom'], ENT_QUOTES, 'UTF-8') ?></label><?php endforeach; ?>
	<button type="submit">Enregistrer les équipements</button>
</form>
<h2>Photos</h2>
<?php foreach ($photos as $photo): ?><p><img src="/LOKA/<?= htmlspecialchars($photo['chemin_stockage'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($photo['nom_fichier'], ENT_QUOTES, 'UTF-8') ?>" width="180"> <?= $photo['est_principale'] ? 'Photo principale' : '' ?></p><?php endforeach; ?>
<p><a href="photos.php?id=<?= $propertyId ?>">Gérer les photos</a></p>
<p><a href="modifier.php?id=<?= $propertyId ?>">Modifier</a> | <a href="index.php">Retour</a></p>
<?php if ($property['statut'] !== 'ARCHIVED'): ?>
<form method="post" action="supprimer.php">
	<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
	<input type="hidden" name="id" value="<?= $propertyId ?>">
	<button type="submit">Archiver</button>
</form>
<?php endif; ?>
</main></body></html>