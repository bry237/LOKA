<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/BienController.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);
if ($agencyId < 1) {
	http_response_code(400);
	exit('Aucune agence n’est associée à ce compte.');
}

$errors = [];
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		$errors['general'] = 'Votre session a expiré. Rechargez la page.';
	} else {
		try {
			[$errors, $createdId] = match ($_POST['action'] ?? '') {
				'create_building' => BienController::createBuilding($agencyId, $_POST),
				'create_equipment' => BienController::createEquipment($_POST),
				default => [['general' => 'Action inconnue.'], null],
			};
			if ($errors === []) {
				$success = 'Le référentiel a été mis à jour.';
			}
		} catch (Throwable $exception) {
			error_log($exception->getMessage());
			$errors['general'] = 'Impossible d’enregistrer ce référentiel pour le moment.';
		}
	}
}

$references = BienController::references($agencyId);
$csrfToken = Auth::csrfToken();
?>
<!doctype html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="bien.css">
	<title>Référentiels immobiliers</title>
</head>
<body>
	<main class="page">
		<h1>Référentiels immobiliers</h1>
		<?php if ($success !== null): ?><p role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
		<?php if (isset($errors['general'])): ?><p role="alert"><?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
		<?php foreach ($errors as $field => $error): ?>
			<?php if ($field !== 'general'): ?><p role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
		<?php endforeach; ?>

		<section aria-labelledby="building-title">
			<h2 id="building-title">Ajouter un immeuble</h2>
			<form method="post">
				<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
				<input type="hidden" name="action" value="create_building">
				<label>Nom <input name="nom" maxlength="150"></label>
				<label>Adresse existante
					<select name="id_adresse">
						<option value="">Créer une adresse</option>
						<?php foreach ($references['adresses'] as $address): ?>
							<option value="<?= (int) $address['id_adresse'] ?>">
								<?= htmlspecialchars($address['ligne1'] . ', ' . $address['ville'], ENT_QUOTES, 'UTF-8') ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>Ligne 1 <input name="ligne1" maxlength="255"></label>
				<label>Ligne 2 <input name="ligne2" maxlength="255"></label>
				<label>Code postal <input name="code_postal" maxlength="20"></label>
				<label>Ville <input name="ville" maxlength="100"></label>
				<label>Région <input name="region" maxlength="100"></label>
				<label>Pays <input name="pays" value="France" maxlength="100"></label>
				<label>Nombre d’étages <input type="number" name="nombre_etages" min="0" value="0"></label>
				<label>Description <textarea name="description"></textarea></label>
				<button type="submit">Ajouter l’immeuble</button>
			</form>
		</section>

		<section aria-labelledby="equipment-title">
			<h2 id="equipment-title">Ajouter un équipement</h2>
			<form method="post">
				<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
				<input type="hidden" name="action" value="create_equipment">
				<label>Nom <input name="nom" maxlength="150" required></label>
				<label>Description <textarea name="description"></textarea></label>
				<button type="submit">Ajouter l’équipement</button>
			</form>
		</section>

		<section aria-labelledby="existing-title">
			<h2 id="existing-title">Données disponibles pour cette agence</h2>
			<p><?= count($references['types']) ?> types, <?= count($references['equipements']) ?> équipements,
				<?= count($references['adresses']) ?> adresses et <?= count($references['immeubles']) ?> immeubles.</p>
		</section>
	</main>
</body>
</html>
