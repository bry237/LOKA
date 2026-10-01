<?php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/DocumentModel.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);
$documentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($agencyId < 1 || !$documentId) {
	http_response_code(400);
	exit('Paramètres invalides.');
}

$model = new DocumentModel();
$document = $model->find($agencyId, $documentId);
if (!$document) {
	http_response_code(404);
	exit('Document introuvable.');
}

// Liste les versions d'un document (même nom_original, même entité)
$db = Database::connection();
$stmt = $db->prepare(
	"SELECT d.id_document, d.nom, d.nom_original, d.version, d.taille_octets, d.mime_type, d.created_at
	 FROM document d
	 WHERE d.nom_original = :nom_original
	   AND (
	       (d.id_bien IS NOT NULL AND d.id_bien = :id_bien)
	    OR (d.id_contrat IS NOT NULL AND d.id_contrat = :id_contrat)
	    OR (d.id_proprietaire IS NOT NULL AND d.id_proprietaire = :id_proprietaire)
	    OR (d.id_locataire IS NOT NULL AND d.id_locataire = :id_locataire)
	    OR (d.id_intervention IS NOT NULL AND d.id_intervention = :id_intervention)
	   )
	 ORDER BY d.version DESC"
);
$stmt->execute([
	'nom_original' => $document['nom_original'],
	'id_bien' => $document['id_bien'] ?? 0,
	'id_contrat' => $document['id_contrat'] ?? 0,
	'id_proprietaire' => $document['id_proprietaire'] ?? 0,
	'id_locataire' => $document['id_locataire'] ?? 0,
	'id_intervention' => $document['id_intervention'] ?? 0,
]);
$versions = $stmt->fetchAll();
$csrfToken = Auth::csrfToken();
?>
<!doctype html>
<html lang="fr">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Versions — <?= htmlspecialchars($document['nom_original'], ENT_QUOTES, 'UTF-8') ?> | LOKA</title>
	<link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>

<body>
	<div class="app-shell">
		<?php $currentPage = 'documents';
		require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
		<div class="content">
			<header class="topbar">
				<div>
					<div class="topbar__eyebrow">Outils · Documents</div>
					<div class="topbar__title">Historique des versions</div>
				</div>
				<a class="button button--ghost" href="index.php">Retour</a>
			</header>
			<main class="page">
				<div class="page__header">
					<div>
						<h1 class="page__title"><?= htmlspecialchars($document['nom_original'], ENT_QUOTES, 'UTF-8') ?></h1>
						<p class="page__subtitle"><?= count($versions) ?> version(s)</p>
					</div>
				</div>
				<section class="card">
					<div class="card__header">
						<h2 class="card__title">Versions</h2>
					</div>
					<div class="table-wrap">
						<table>
							<thead>
								<tr>
									<th>Version</th>
									<th>Nom</th>
									<th>Taille</th>
									<th>Type</th>
									<th>Date</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($versions as $version): ?>
									<tr>
										<td><span class="badge badge--<?= (int) $version['id_document'] === $documentId ? 'available' : 'created' ?>">v<?= (int) $version['version'] ?></span></td>
										<td class="cell-title"><?= htmlspecialchars($version['nom'], ENT_QUOTES, 'UTF-8') ?></td>
										<td><?= number_format((int) $version['taille_octets'] / 1024, 1, ',', ' ') ?> Ko</td>
										<td><?= htmlspecialchars($version['mime_type'], ENT_QUOTES, 'UTF-8') ?></td>
										<td><?= htmlspecialchars($version['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
										<td><a class="button button--ghost" href="telecharger.php?id=<?= (int) $version['id_document'] ?>">Télécharger</a></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php if ($versions === []): ?>
						<div class="empty"><strong>Aucune version disponible</strong></div>
					<?php endif; ?>
				</section>
			</main>
		</div>
	</div>
</body>

</html>