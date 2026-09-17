<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/BienValidator.php';
require_once __DIR__ . '/BienModel.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);
$propertyId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($agencyId < 1 || !$propertyId) { http_response_code(400); exit('Paramètres invalides.'); }
$property = BienModel::find($agencyId, $propertyId);
if (!$property) { http_response_code(404); exit('Bien introuvable.'); }
$errors = []; $success = null;
$storedFile = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		$errors['general'] = 'Votre session a expiré. Rechargez la page.';
	} else {
		try {
			$action = $_POST['action'] ?? '';
			if ($action === 'upload') {
				$errors = BienValidator::photo($_FILES['photo'] ?? []);
				if ($errors === []) {
					$mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['photo']['tmp_name']);
					$extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
					$filename = bin2hex(random_bytes(16)) . '.' . $extension;
					$directory = dirname(__DIR__, 2) . '/public/uploads/photos';
					if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
						throw new RuntimeException('Le dossier de stockage des photos est indisponible.');
					}
					if (!move_uploaded_file($_FILES['photo']['tmp_name'], $directory . '/' . $filename)) {
						throw new RuntimeException('Le stockage de la photo a échoué.');
					}
					$storedFile = $directory . '/' . $filename;
					BienModel::addPhoto($agencyId, $propertyId, $filename, 'uploads/photos/' . $filename, !empty($_POST['est_principale']));
					$success = 'Photo ajoutée.';
				}
			} elseif ($action === 'main') {
				BienModel::setMainPhoto($agencyId, $propertyId, (int) $_POST['id_photo']);
				$success = 'Photo principale mise à jour.';
			} elseif ($action === 'delete') {
				$path = BienModel::deletePhoto($agencyId, $propertyId, (int) $_POST['id_photo']);
				if ($path !== null) {
					$file = dirname(__DIR__, 2) . '/public/' . ltrim($path, '/');
					if (is_file($file)) { unlink($file); }
				}
				$success = 'Photo supprimée.';
			} else {
				$errors['general'] = 'Action inconnue.';
			}
		} catch (Throwable $exception) {
			if ($storedFile !== null && is_file($storedFile)) {
				unlink($storedFile);
			}
			error_log($exception->getMessage());
			$errors['general'] = 'Impossible de traiter la photo.';
		}
	}
}
$photos = BienModel::photos($agencyId, $propertyId);
$csrfToken = Auth::csrfToken();
?>
<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Photos</title></head><body><main>
<h1>Photos — <?= htmlspecialchars($property['titre'], ENT_QUOTES, 'UTF-8') ?></h1>
<?php if ($success): ?><p role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<?php if (isset($errors['general'])): ?><p role="alert"><?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
<?php foreach ($errors as $field => $error): if ($field !== 'general'): ?><p role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; endforeach; ?>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="action" value="upload"><input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required><label><input type="checkbox" name="est_principale" value="1"> Photo principale</label><button type="submit">Ajouter</button></form>
<?php foreach ($photos as $photo): ?><figure><img src="/LOKA/<?= htmlspecialchars($photo['chemin_stockage'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($photo['nom_fichier'], ENT_QUOTES, 'UTF-8') ?>" width="240"><?php if ($photo['est_principale']): ?><figcaption>Photo principale</figcaption><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="id_photo" value="<?= (int) $photo['id_photo'] ?>"><?php if (!$photo['est_principale']): ?><button name="action" value="main">Définir principale</button><?php endif; ?><button name="action" value="delete">Supprimer</button></form></figure><?php endforeach; ?>
<p><a href="detail.php?id=<?= $propertyId ?>">Retour au bien</a></p></main></body></html>