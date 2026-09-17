<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/BienModel.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);
$propertyId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($agencyId < 1 || !$propertyId) { http_response_code(400); exit('Paramètres invalides.'); }
$property = BienModel::find($agencyId, $propertyId);
if (!$property) { http_response_code(404); exit('Bien introuvable.'); }
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
<p><a href="modifier.php?id=<?= $propertyId ?>">Modifier</a> | <a href="index.php">Retour</a></p>
<?php if ($property['statut'] !== 'ARCHIVED'): ?>
<form method="post" action="supprimer.php">
	<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
	<input type="hidden" name="id" value="<?= $propertyId ?>">
	<button type="submit">Archiver</button>
</form>
<?php endif; ?>
</main></body></html>