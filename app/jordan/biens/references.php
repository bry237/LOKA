<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
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
	<title>Référentiels immobiliers | LOKA</title>
	<link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>
<body>
	<div class="app-shell">
		<?php $currentPage = 'immeubles';
		require_once dirname(__DIR__) . '/layouts/sidebar-gestion.php'; ?>
		<div class="content">
			<header class="topbar">
				<div>
					<div class="topbar__eyebrow">Biens immobiliers</div>
					<div class="topbar__title">Référentiels</div>
				</div>
				<a class="button button--ghost" href="index.php">Retour aux biens</a>
			</header>
			<main class="page">
				<div class="page__header">
					<div>
						<h1 class="page__title">Référentiels immobiliers</h1>
						<p class="page__subtitle">Immeubles et équipements disponibles pour votre agence.</p>
					</div>
				</div>

				<?php if ($success !== null): ?>
					<div class="alert alert--success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
				<?php endif; ?>
				<?php if (isset($errors['general'])): ?>
					<div class="alert alert--error"><?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?></div>
				<?php endif; ?>
				<?php foreach ($errors as $field => $error): ?>
					<?php if ($field !== 'general'): ?><div class="alert alert--error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
				<?php endforeach; ?>

				<div class="stats-row">
					<div class="stat-card">
						<h3>Types de biens</h3>
						<span class="stat-value"><?= count($references['types']) ?></span>
					</div>
					<div class="stat-card">
						<h3>Équipements</h3>
						<span class="stat-value"><?= count($references['equipements']) ?></span>
					</div>
					<div class="stat-card">
						<h3>Adresses</h3>
						<span class="stat-value"><?= count($references['adresses']) ?></span>
					</div>
					<div class="stat-card">
						<h3>Immeubles</h3>
						<span class="stat-value"><?= count($references['immeubles']) ?></span>
					</div>
				</div>

				<div class="detail-grid detail-grid--even">
					<section class="card">
						<div class="card__header"><h2 class="card__title">Ajouter un immeuble</h2></div>
						<div class="card__body">
							<form method="post">
								<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
								<input type="hidden" name="action" value="create_building">
								<div class="form-grid">
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
									<label>Nombre d'étages <input type="number" name="nombre_etages" min="0" value="0"></label>
									<label class="full">Description <textarea name="description"></textarea></label>
								</div>
								<div class="form-actions">
									<button class="button--primary" type="submit">Ajouter l'immeuble</button>
								</div>
							</form>
						</div>
					</section>

					<section class="card">
						<div class="card__header"><h2 class="card__title">Ajouter un équipement</h2></div>
						<div class="card__body">
							<form method="post">
								<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
								<input type="hidden" name="action" value="create_equipment">
								<div class="form-grid">
									<label>Nom <input name="nom" maxlength="150" required></label>
									<label class="full">Description <textarea name="description"></textarea></label>
								</div>
								<div class="form-actions">
									<button class="button--primary" type="submit">Ajouter l'équipement</button>
								</div>
							</form>
						</div>
					</section>
				</div>
			</main>
		</div>
	</div>
</body>
</html>
