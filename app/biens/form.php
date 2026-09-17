<?php $property = $property ?? $_POST; $action = $action ?? 'ajouter.php'; ?>
<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= htmlspecialchars($title ?? 'Bien', ENT_QUOTES, 'UTF-8') ?></title></head>
<body><main>
<h1><?= htmlspecialchars($title ?? 'Bien', ENT_QUOTES, 'UTF-8') ?></h1>
<?php if (isset($errors['general'])): ?><p role="alert"><?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<?php foreach ($errors as $field => $error): if ($field !== 'general'): ?><p role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; endforeach; ?>
<form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
<label>Référence <input name="reference" maxlength="100" required value="<?= htmlspecialchars((string) ($property['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
<label>Titre <input name="titre" maxlength="200" required value="<?= htmlspecialchars((string) ($property['titre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
<label>Type <select name="id_type_bien" required><?php foreach ($references['types'] as $type): ?><option value="<?= (int) $type['id_type_bien'] ?>"<?= (string) ($property['id_type_bien'] ?? '') === (string) $type['id_type_bien'] ? ' selected' : '' ?>><?= htmlspecialchars($type['nom'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
<label>Adresse <select name="id_adresse" required><?php foreach ($references['adresses'] as $address): ?><option value="<?= (int) $address['id_adresse'] ?>"<?= (string) ($property['id_adresse'] ?? '') === (string) $address['id_adresse'] ? ' selected' : '' ?>><?= htmlspecialchars($address['ligne1'] . ', ' . $address['ville'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
<label>Immeuble <select name="id_immeuble"><option value="">Aucun</option><?php foreach ($references['immeubles'] as $building): ?><option value="<?= (int) $building['id_immeuble'] ?>"<?= (string) ($property['id_immeuble'] ?? '') === (string) $building['id_immeuble'] ? ' selected' : '' ?>><?= htmlspecialchars(($building['nom'] ?: 'Immeuble') . ' - ' . $building['ville'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
<label>Surface <input type="number" step="0.01" min="0" name="surface" required value="<?= htmlspecialchars((string) ($property['surface'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
<label>Pièces <input type="number" min="0" name="nombre_pieces" value="<?= htmlspecialchars((string) ($property['nombre_pieces'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"></label>
<label>Étage <input type="number" min="0" name="etage" value="<?= htmlspecialchars((string) ($property['etage'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></label>
<label>Loyer <input type="number" step="0.01" min="0" name="loyer" value="<?= htmlspecialchars((string) ($property['loyer'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"></label>
<label>Charges <input type="number" step="0.01" min="0" name="charges" value="<?= htmlspecialchars((string) ($property['charges'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"></label>
<label>Caution <input type="number" step="0.01" min="0" name="caution" value="<?= htmlspecialchars((string) ($property['caution'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"></label>
<label>Statut <select name="statut"><?php foreach (['CREATED','AVAILABLE','OCCUPIED','MAINTENANCE'] as $status): ?><option value="<?= $status ?>"<?= ($property['statut'] ?? 'CREATED') === $status ? ' selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select></label>
<label>Description <textarea name="description"><?= htmlspecialchars((string) ($property['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea></label>
<button type="submit">Enregistrer</button>
</form>
<p><a href="index.php">Retour à la liste</a></p>
</main></body></html>
