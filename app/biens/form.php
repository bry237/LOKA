<?php $property = $property ?? $_POST;
$action = $action ?? 'ajouter.php'; ?>
<!doctype html>
<html lang="fr">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= htmlspecialchars($title ?? 'Bien', ENT_QUOTES, 'UTF-8') ?> | LOKA</title>
	<link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>

<body>
	<div class="app-shell">
		<?php $currentPage = 'biens';
		require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
		<div class="content">
			<header class="topbar">
				<div>
					<div class="topbar__eyebrow">Biens immobiliers</div>
					<div class="topbar__title"><?= isset($property['id_bien']) ? 'Modifier la fiche' : 'Créer une nouvelle fiche' ?></div>
				</div>
				<a class="button button--ghost" href="index.php">Retour à la liste</a>
			</header>
			<main class="page">
				<div class="page__header">
					<div>
						<h1 class="page__title"><?= htmlspecialchars($title ?? 'Bien', ENT_QUOTES, 'UTF-8') ?></h1>
						<p class="page__subtitle">Renseignez les informations essentielles de votre bien.</p>
					</div>
				</div>

				<section class="card">
					<div class="card__body">
						<?php if (isset($errors['general'])): ?>
							<div class="alert alert--error"><?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?></div>
						<?php endif; ?>
						<?php foreach ($errors as $field => $error): if ($field !== 'general'): ?>
								<div class="alert alert--error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
						<?php endif;
						endforeach; ?>

						<form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>">
							<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
							<?php if (isset($property['id_bien'])): ?>
								<input type="hidden" name="id" value="<?= (int) $property['id_bien'] ?>">
							<?php endif; ?>

							<h3 style="margin:0 0 16px;color:var(--heading);">Identification</h3>
							<div class="form-grid">
								<label>Référence <input name="reference" maxlength="100" placeholder="Ex. LOKA-APT-001" required value="<?= htmlspecialchars((string) ($property['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
								<label>Titre <input name="titre" maxlength="200" placeholder="Ex. Appartement lumineux centre-ville" required value="<?= htmlspecialchars((string) ($property['titre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
								<label>Type
									<select name="id_type_bien" required>
										<?php foreach ($references['types'] as $type): ?>
											<option value="<?= (int) $type['id_type_bien'] ?>" <?= (string) ($property['id_type_bien'] ?? '') === (string) $type['id_type_bien'] ? ' selected' : '' ?>><?= htmlspecialchars($type['nom'], ENT_QUOTES, 'UTF-8') ?></option>
										<?php endforeach; ?>
									</select>
								</label>
								<label>Statut
									<select name="statut">
										<?php foreach (['CREATED' => 'Brouillon', 'AVAILABLE' => 'Disponible', 'OCCUPIED' => 'Loué', 'MAINTENANCE' => 'Maintenance'] as $status => $label): ?>
											<option value="<?= $status ?>" <?= ($property['statut'] ?? 'CREATED') === $status ? ' selected' : '' ?>><?= $label ?></option>
										<?php endforeach; ?>
									</select>
								</label>
							</div>

							<h3 style="margin:24px 0 16px;color:var(--heading);">Localisation</h3>
							<div class="form-grid">
								<label>Adresse
									<select name="id_adresse" required>
										<?php foreach ($references['adresses'] as $address): ?>
											<option value="<?= (int) $address['id_adresse'] ?>" <?= (string) ($property['id_adresse'] ?? '') === (string) $address['id_adresse'] ? ' selected' : '' ?>><?= htmlspecialchars($address['ligne1'] . ', ' . $address['ville'], ENT_QUOTES, 'UTF-8') ?></option>
										<?php endforeach; ?>
									</select>
								</label>
								<label>Immeuble
									<select name="id_immeuble">
										<option value="">Aucun immeuble</option>
										<?php foreach ($references['immeubles'] as $building): ?>
											<option value="<?= (int) $building['id_immeuble'] ?>" <?= (string) ($property['id_immeuble'] ?? '') === (string) $building['id_immeuble'] ? ' selected' : '' ?>><?= htmlspecialchars(($building['nom'] ?: 'Immeuble') . ' - ' . $building['ville'], ENT_QUOTES, 'UTF-8') ?></option>
										<?php endforeach; ?>
									</select>
								</label>
								<label>Étage <input type="number" min="0" name="etage" value="<?= htmlspecialchars((string) ($property['etage'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
							</div>

							<h3 style="margin:24px 0 16px;color:var(--heading);">Caractéristiques</h3>
							<div class="form-grid">
								<label>Surface (m²) <input type="number" step="0.01" min="0" name="surface" required value="<?= htmlspecialchars((string) ($property['surface'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
								<label>Nombre de pièces <input type="number" min="0" name="nombre_pieces" value="<?= htmlspecialchars((string) ($property['nombre_pieces'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"></label>
								<label>Meublé
									<select name="meuble">
										<option value="0" <?= empty($property['meuble']) ? ' selected' : '' ?>>Non</option>
										<option value="1" <?= !empty($property['meuble']) ? ' selected' : '' ?>>Oui</option>
									</select>
								</label>
								<label>Date de disponibilité <input type="date" name="date_disponibilite" value="<?= htmlspecialchars((string) ($property['date_disponibilite'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
							</div>

							<h3 style="margin:24px 0 16px;color:var(--heading);">Diagnostics énergétiques</h3>
							<div class="form-grid">
								<label>Classe DPE
									<select name="dpe_classe">
										<option value="">Non renseigné</option>
										<?php foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $classe): ?>
											<option value="<?= $classe ?>" <?= ($property['dpe_classe'] ?? '') === $classe ? ' selected' : '' ?>><?= $classe ?></option>
										<?php endforeach; ?>
									</select>
								</label>
								<label>Classe GES
									<select name="ges_classe">
										<option value="">Non renseigné</option>
										<?php foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $classe): ?>
											<option value="<?= $classe ?>" <?= ($property['ges_classe'] ?? '') === $classe ? ' selected' : '' ?>><?= $classe ?></option>
										<?php endforeach; ?>
									</select>
								</label>
							</div>

							<h3 style="margin:24px 0 16px;color:var(--heading);">Finances</h3>
							<div class="form-grid">
								<label>Loyer mensuel (€) <input type="number" step="0.01" min="0" name="loyer" value="<?= htmlspecialchars((string) ($property['loyer'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"></label>
								<label>Charges (€) <input type="number" step="0.01" min="0" name="charges" value="<?= htmlspecialchars((string) ($property['charges'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"></label>
								<label>Caution (€) <input type="number" step="0.01" min="0" name="caution" value="<?= htmlspecialchars((string) ($property['caution'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"></label>
							</div>

							<h3 style="margin:24px 0 16px;color:var(--heading);">Description</h3>
							<div class="form-grid">
								<label class="full">Description <textarea name="description" placeholder="Décrivez les caractéristiques du bien..."><?= htmlspecialchars((string) ($property['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></label>
							</div>

							<div class="form-actions">
								<a class="button button--ghost" href="index.php">Annuler</a>
								<button class="button--primary" type="submit">Enregistrer le bien</button>
							</div>
						</form>
					</div>
				</section>
			</main>
		</div>
	</div>
</body>

</html>