<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/core/UploadHelper.php';

$user = Authorization::requireRole('Proprietaire');

$pdo = Database::connection();

$idUtilisateur = (int) $user['id_utilisateur'];


/*
|--------------------------------------------------------------------------
| Récupération du propriétaire connecté
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT p.id_proprietaire, p.prenom, p.nom
    FROM proprietaire p
    INNER JOIN utilisateur u ON u.email = p.email
    WHERE u.id_utilisateur = ?
    LIMIT 1
");

$stmt->execute([$idUtilisateur]);

$proprietaireRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$proprietaireRow) {
    http_response_code(403);
    exit('Propriétaire introuvable.');
}

$idProprietaire = (int) $proprietaireRow['id_proprietaire'];


/*
|--------------------------------------------------------------------------
| Récupération de l'identifiant du bien
|--------------------------------------------------------------------------
*/

$idBien = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idBien) {
    http_response_code(400);
    exit('Bien invalide.');
}


function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function imageSrc(?string $value): string
{
    $value = trim((string) $value);

    if ($value === '') {
        return '';
    }

    if (
        str_starts_with($value, 'http://')
        || str_starts_with($value, 'https://')
        || str_starts_with($value, 'data:')
        || str_starts_with($value, '/')
    ) {
        return $value;
    }

    return '/LOKA/' . ltrim($value, '/');
}


/*
|--------------------------------------------------------------------------
| Types de biens
|--------------------------------------------------------------------------
*/

$types = $pdo->query("SELECT id_type_bien, nom FROM type_bien ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| Récupération du bien
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT b.*, a.ligne1, a.ville, a.code_postal, a.region, a.pays
    FROM bien b
    INNER JOIN bien_proprietaire bp ON bp.id_bien = b.id_bien
    LEFT JOIN adresse a ON a.id_adresse = b.id_adresse
    WHERE b.id_bien = ?
      AND bp.id_proprietaire = ?
      AND b.deleted_at IS NULL
    LIMIT 1
");

$stmt->execute([$idBien, $idProprietaire]);

$bien = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bien) {
    http_response_code(403);
    exit('Accès refusé.');
}


function getBienImages(PDO $pdo, int $idBien): array
{
    $stmt = $pdo->prepare("
        SELECT id_image, id_bien, image_url, est_principale, ordre, created_at
        FROM bien_image
        WHERE id_bien = ?
        ORDER BY est_principale DESC, ordre ASC, id_image ASC
    ");
    $stmt->execute([$idBien]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


$erreur = null;
$succes = null;

$uploadDir = dirname(__DIR__, 2) . '/uploads/biens/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}


/*
|--------------------------------------------------------------------------
| TRAITEMENT POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? 'update';

    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {

        $erreur = "Votre session a expiré. Rechargez la page.";

    } elseif ($action === 'delete_image') {

        $idImage = filter_var($_POST['id_image'] ?? null, FILTER_VALIDATE_INT);

        if (!$idImage) {
            $erreur = "Image invalide.";
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    SELECT bi.id_image, bi.image_url, bi.est_principale
                    FROM bien_image bi
                    INNER JOIN bien_proprietaire bp ON bp.id_bien = bi.id_bien
                    WHERE bi.id_image = ? AND bi.id_bien = ? AND bp.id_proprietaire = ?
                    LIMIT 1
                ");
                $stmt->execute([$idImage, $idBien, $idProprietaire]);
                $image = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$image) {
                    throw new Exception("Image introuvable ou accès refusé.");
                }

                $stmt = $pdo->prepare("DELETE FROM bien_image WHERE id_image = ?");
                $stmt->execute([$idImage]);

                $imageUrl = (string) $image['image_url'];
                if (str_starts_with($imageUrl, 'uploads/biens/')) {
                    $filePath = dirname(__DIR__, 2) . '/' . $imageUrl;
                    if (is_file($filePath)) {
                        unlink($filePath);
                    }
                }

                if ((int) $image['est_principale'] === 1) {
                    $stmt = $pdo->prepare("
                        SELECT id_image, image_url FROM bien_image
                        WHERE id_bien = ? ORDER BY ordre ASC, id_image ASC LIMIT 1
                    ");
                    $stmt->execute([$idBien]);
                    $nouvellePrincipale = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($nouvellePrincipale) {
                        $stmt = $pdo->prepare("UPDATE bien_image SET est_principale = 0 WHERE id_bien = ?");
                        $stmt->execute([$idBien]);

                        $stmt = $pdo->prepare("UPDATE bien_image SET est_principale = 1 WHERE id_image = ?");
                        $stmt->execute([$nouvellePrincipale['id_image']]);

                        $stmt = $pdo->prepare("UPDATE bien SET image_url = ?, updated_at = NOW() WHERE id_bien = ?");
                        $stmt->execute([$nouvellePrincipale['image_url'], $idBien]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE bien SET image_url = NULL, updated_at = NOW() WHERE id_bien = ?");
                        $stmt->execute([$idBien]);
                    }
                }

                $pdo->commit();

                header("Location: modifier.php?id=" . $idBien . "&success=image_deleted");
                exit;

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $erreur = "Impossible de supprimer cette image.";
            }
        }

    } elseif ($action === 'set_primary') {

        $idImage = filter_var($_POST['id_image'] ?? null, FILTER_VALIDATE_INT);

        if (!$idImage) {
            $erreur = "Image invalide.";
        } else {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    SELECT bi.id_image, bi.image_url
                    FROM bien_image bi
                    INNER JOIN bien_proprietaire bp ON bp.id_bien = bi.id_bien
                    WHERE bi.id_image = ? AND bi.id_bien = ? AND bp.id_proprietaire = ?
                    LIMIT 1
                ");
                $stmt->execute([$idImage, $idBien, $idProprietaire]);
                $image = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$image) {
                    throw new Exception("Image introuvable ou accès refusé.");
                }

                $stmt = $pdo->prepare("UPDATE bien_image SET est_principale = 0 WHERE id_bien = ?");
                $stmt->execute([$idBien]);

                $stmt = $pdo->prepare("UPDATE bien_image SET est_principale = 1 WHERE id_image = ? AND id_bien = ?");
                $stmt->execute([$idImage, $idBien]);

                $stmt = $pdo->prepare("UPDATE bien SET image_url = ?, updated_at = NOW() WHERE id_bien = ?");
                $stmt->execute([$image['image_url'], $idBien]);

                $pdo->commit();

                header("Location: modifier.php?id=" . $idBien . "&success=image_primary");
                exit;

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $erreur = "Impossible de définir cette image comme principale.";
            }
        }

    } else {

        try {
            $pdo->beginTransaction();

            $titre = trim($_POST['titre'] ?? '');
            $reference = trim($_POST['reference'] ?? '');
            $idTypeBien = filter_var($_POST['id_type_bien'] ?? null, FILTER_VALIDATE_INT);
            $surface = (float) ($_POST['surface'] ?? 0);
            $ligne1 = trim($_POST['ligne1'] ?? '');
            $ville = trim($_POST['ville'] ?? '');
            $codePostal = trim($_POST['code_postal'] ?? '');
            $region = trim($_POST['region'] ?? '');
            $pays = trim($_POST['pays'] ?? '');
            $loyer = (float) ($_POST['loyer'] ?? 0);
            $charges = (float) ($_POST['charges'] ?? 0);
            $statut = $_POST['statut'] ?? 'CREATED';

            if ($titre === '') {
                throw new Exception("Le titre est obligatoire.");
            }
            if ($reference === '') {
                throw new Exception("La référence est obligatoire.");
            }
            if (!$idTypeBien) {
                throw new Exception("Le type de bien est obligatoire.");
            }
            if ($surface <= 0) {
                throw new Exception("La surface doit être supérieure à 0.");
            }

            $stmt = $pdo->prepare("
                UPDATE adresse SET ligne1 = ?, ville = ?, code_postal = ?, region = ?, pays = ?
                WHERE id_adresse = ?
            ");
            $stmt->execute([$ligne1, $ville, $codePostal, $region, $pays, $bien['id_adresse']]);

            $stmt = $pdo->prepare("
                UPDATE bien SET titre = ?, reference = ?, id_type_bien = ?, surface = ?,
                    loyer = ?, charges = ?, statut = ?, updated_at = NOW()
                WHERE id_bien = ?
            ");
            $stmt->execute([$titre, $reference, $idTypeBien, $surface, $loyer, $charges, $statut, $idBien]);

            /*
            |--------------------------------------------------------------------------
            | Ajout des nouvelles photos
            |--------------------------------------------------------------------------
            */

            $photosEnvoyees = UploadHelper::resolveMultiple('photos');

            if ($photosEnvoyees !== []) {

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM bien_image WHERE id_bien = ?");
                $stmt->execute([$idBien]);
                $nombreImages = (int) $stmt->fetchColumn();

                $ordre = $nombreImages + 1;

                $formatsAutorises = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                $finfo = new finfo(FILEINFO_MIME_TYPE);

                foreach ($photosEnvoyees as $photoFile) {

                    $tmpName = (string) $photoFile['tmp_name'];
                    $error = (int) $photoFile['error'];

                    if ($error !== UPLOAD_ERR_OK) {
                        throw new Exception("Une photo n'a pas pu être téléversée.");
                    }

                    if ($tmpName === '' || !is_file($tmpName)) {
                        throw new Exception("Fichier photo invalide.");
                    }

                    if ((int) $photoFile['size'] > 5 * 1024 * 1024) {
                        throw new Exception("Une photo dépasse la taille maximale de 5 Mo.");
                    }

                    $mime = $finfo->file($tmpName);

                    if (!isset($formatsAutorises[$mime])) {
                        throw new Exception("Format non autorisé. Utilisez JPG, PNG ou WEBP.");
                    }

                    $extension = $formatsAutorises[$mime];
                    $nomFichier = 'bien_' . $idBien . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
                    $destination = $uploadDir . $nomFichier;

                    if (!UploadHelper::store($photoFile, $destination)) {
                        throw new Exception("Impossible d'enregistrer une photo.");
                    }

                    $cheminImage = 'uploads/biens/' . $nomFichier;
                    $estPrincipale = ($nombreImages === 0) ? 1 : 0;

                    $stmt = $pdo->prepare("
                        INSERT INTO bien_image (id_bien, image_url, est_principale, ordre)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$idBien, $cheminImage, $estPrincipale, $ordre]);

                    if ($estPrincipale === 1) {
                        $stmt = $pdo->prepare("UPDATE bien SET image_url = ?, updated_at = NOW() WHERE id_bien = ?");
                        $stmt->execute([$cheminImage, $idBien]);
                    }

                    $nombreImages++;
                    $ordre++;
                }
            }

            $pdo->commit();

            header("Location: detail.php?id=" . $idBien . "&success=1");
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $erreur = $e->getMessage();
        }
    }
}


$images = getBienImages($pdo, $idBien);

$successParam = $_GET['success'] ?? null;

if ($successParam === 'image_deleted') {
    $succes = "La photo a été supprimée.";
}

if ($successParam === 'image_primary') {
    $succes = "La photo principale a été modifiée.";
}

$prenom = (string) ($proprietaireRow['prenom'] ?? '');
$nom = (string) ($proprietaireRow['nom'] ?? '');

$pageTitle = 'Modifier ' . $bien['titre'];
$activeNav = 'Mes biens';
$sidebarFile = 'sidebar-proprietaire.php';
$greetingTitle = 'Modifier le bien';
$greetingSubtitle = $bien['titre'];
$profileName = trim($prenom . ' ' . $nom) ?: 'Propriétaire';
$profileRole = 'Propriétaire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'P';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

require dirname(__DIR__, 2) . '/layouts/header.php';
?>
    <a class="detail-back" href="detail.php?id=<?= $idBien ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour au bien
    </a>

    <?php if ($erreur): ?>
      <div class="feedback error"><?= e($erreur) ?></div>
    <?php endif; ?>
    <?php if ($succes): ?>
      <div class="feedback success"><?= e($succes) ?></div>
    <?php endif; ?>

    <div class="panel" style="margin-bottom:18px;">
      <div class="panel-header">
        <div class="panel-title">Photos du bien</div>
        <span class="panel-sub" style="margin:0;"><?= count($images) ?> photo<?= count($images) > 1 ? 's' : '' ?></span>
      </div>
      <?php if (!empty($images)): ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(160px, 1fr));gap:12px;padding:18px;">
          <?php foreach ($images as $image): ?>
            <div style="position:relative;border-radius:12px;overflow:hidden;aspect-ratio:4/3;background:var(--panel-muted, #f1f5f5);">
              <img src="<?= e(imageSrc($image['image_url'])) ?>" alt="Photo du bien" style="width:100%;height:100%;object-fit:cover;display:block;">
              <?php if ((int) $image['est_principale'] === 1): ?>
                <span class="status-badge actif" style="position:absolute;top:8px;left:8px;font-size:11px;padding:3px 9px;">Principale</span>
              <?php endif; ?>
              <div style="position:absolute;bottom:8px;right:8px;display:flex;gap:6px;">
                <?php if ((int) $image['est_principale'] !== 1): ?>
                  <form method="POST" style="margin:0;">
                    <input type="hidden" name="csrf_token" value="<?= e(Auth::csrfToken()) ?>">
                    <input type="hidden" name="action" value="set_primary">
                    <input type="hidden" name="id_image" value="<?= (int) $image['id_image'] ?>">
                    <button type="submit" class="btn btn-sm" title="Définir comme principale">★</button>
                  </form>
                <?php endif; ?>
                <form method="POST" style="margin:0;" onsubmit="return confirm('Supprimer cette photo ?');">
                  <input type="hidden" name="csrf_token" value="<?= e(Auth::csrfToken()) ?>">
                  <input type="hidden" name="action" value="delete_image">
                  <input type="hidden" name="id_image" value="<?= (int) $image['id_image'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">✕</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state" style="padding:32px 20px;">
          <p>Aucune photo pour ce bien.</p>
        </div>
      <?php endif; ?>
    </div>

    <div class="panel">
      <div class="panel-header"><div class="panel-title">Informations du bien</div></div>
      <form method="POST" enctype="multipart/form-data" data-upload-fallback class="form-grid" style="padding:18px;">
        <input type="hidden" name="csrf_token" value="<?= e(Auth::csrfToken()) ?>">
        <input type="hidden" name="action" value="update">

        <div class="form-field full">
          <label for="photos">Ajouter des photos (optionnel)</label>
          <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
          <p class="cell-sub" style="margin:4px 0 0;">JPG, PNG ou WEBP — 5 Mo maximum par photo.</p>
        </div>

        <div class="form-field">
          <label for="titre">Titre</label>
          <input type="text" id="titre" name="titre" required value="<?= e($bien['titre']) ?>">
        </div>

        <div class="form-field">
          <label for="reference">Référence</label>
          <input type="text" id="reference" name="reference" required value="<?= e($bien['reference']) ?>">
        </div>

        <div class="form-field">
          <label for="id_type_bien">Type de bien</label>
          <select id="id_type_bien" name="id_type_bien" required>
            <?php foreach ($types as $type): ?>
              <option value="<?= (int) $type['id_type_bien'] ?>" <?= (int) $type['id_type_bien'] === (int) $bien['id_type_bien'] ? 'selected' : '' ?>><?= e($type['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-field">
          <label for="surface">Surface (m²)</label>
          <input type="number" id="surface" name="surface" step="0.01" min="0" value="<?= e((string) $bien['surface']) ?>">
        </div>

        <div class="form-field full">
          <label for="ligne1">Adresse</label>
          <input type="text" id="ligne1" name="ligne1" value="<?= e($bien['ligne1']) ?>">
        </div>

        <div class="form-field">
          <label for="ville">Ville</label>
          <input type="text" id="ville" name="ville" value="<?= e($bien['ville']) ?>">
        </div>

        <div class="form-field">
          <label for="code_postal">Code postal</label>
          <input type="text" id="code_postal" name="code_postal" value="<?= e($bien['code_postal']) ?>">
        </div>

        <div class="form-field">
          <label for="region">Région</label>
          <input type="text" id="region" name="region" value="<?= e($bien['region']) ?>">
        </div>

        <div class="form-field">
          <label for="pays">Pays</label>
          <input type="text" id="pays" name="pays" value="<?= e($bien['pays']) ?>">
        </div>

        <div class="form-field">
          <label for="loyer">Loyer (€)</label>
          <input type="number" id="loyer" name="loyer" step="0.01" min="0" value="<?= e((string) $bien['loyer']) ?>">
        </div>

        <div class="form-field">
          <label for="charges">Charges (€)</label>
          <input type="number" id="charges" name="charges" step="0.01" min="0" value="<?= e((string) $bien['charges']) ?>">
        </div>

        <div class="form-field">
          <label for="statut">Statut</label>
          <select id="statut" name="statut">
            <?php $statuts = ['CREATED' => 'Créé', 'AVAILABLE' => 'Disponible', 'OCCUPIED' => 'Occupé', 'MAINTENANCE' => 'Maintenance', 'ARCHIVED' => 'Archivé']; ?>
            <?php foreach ($statuts as $value => $label): ?>
              <option value="<?= e($value) ?>" <?= $bien['statut'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-actions full">
          <a href="detail.php?id=<?= $idBien ?>" class="btn">Annuler</a>
          <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
      </form>
    </div>
<script src="/LOKA/public/assets/js/upload-fallback.js"></script>
<?php
require dirname(__DIR__, 2) . '/layouts/footer.php';
