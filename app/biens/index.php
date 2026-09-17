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
$references = BienController::references($agencyId);
$properties = BienModel::list($agencyId, $_GET);
?>
<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Biens immobiliers</title></head>
<body>
<main>
	<h1>Biens immobiliers</h1>
	<p><a href="ajouter.php">Ajouter un bien</a> | <a href="references.php">Référentiels</a></p>
	<form method="get">
		<label>Recherche <input name="q" value="<?= htmlspecialchars((string) ($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
		<label>Statut <select name="statut"><option value="">Tous sauf archivés</option><?php foreach (['CREATED','AVAILABLE','OCCUPIED','MAINTENANCE','ARCHIVED'] as $status): ?><option value="<?= $status ?>"<?= ($_GET['statut'] ?? '') === $status ? ' selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></label>
		<label>Type <select name="id_type_bien"><option value="">Tous</option><?php foreach ($references['types'] as $type): ?><option value="<?= (int) $type['id_type_bien'] ?>"<?= (string) ($_GET['id_type_bien'] ?? '') === (string) $type['id_type_bien'] ? ' selected' : '' ?>><?= htmlspecialchars($type['nom'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
		<label>Loyer max <input type="number" step="0.01" min="0" name="loyer_max" value="<?= htmlspecialchars((string) ($_GET['loyer_max'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
		<label>Surface min <input type="number" step="0.01" min="0" name="surface_min" value="<?= htmlspecialchars((string) ($_GET['surface_min'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
		<button type="submit">Filtrer</button>
	</form>
	<table><thead><tr><th>Référence</th><th>Titre</th><th>Type</th><th>Ville</th><th>Surface</th><th>Loyer</th><th>Statut</th><th>Actions</th></tr></thead><tbody>
	<?php foreach ($properties as $property): ?><tr>
		<td><?= htmlspecialchars($property['reference'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($property['titre'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($property['type_bien'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($property['ville'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string) $property['surface'], ENT_QUOTES, 'UTF-8') ?> m²</td><td><?= htmlspecialchars((string) $property['loyer'], ENT_QUOTES, 'UTF-8') ?> €</td><td><?= htmlspecialchars($property['statut'], ENT_QUOTES, 'UTF-8') ?></td>
		<td><a href="detail.php?id=<?= (int) $property['id_bien'] ?>">Détail</a> <a href="modifier.php?id=<?= (int) $property['id_bien'] ?>">Modifier</a></td>
	</tr><?php endforeach; ?>
	</tbody></table>
	<?php if ($properties === []): ?><p>Aucun bien trouvé.</p><?php endif; ?>
</main>
</body>
</html>
