<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/core/UploadHelper.php';


/*
|--------------------------------------------------------------------------
| UTILISATEUR CONNECTÉ
|--------------------------------------------------------------------------
*/

$user = Authorization::requireRole('Proprietaire');

$pdo = Database::connection();

$idUtilisateur = (int) $user['id_utilisateur'];


function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}


/*
|--------------------------------------------------------------------------
| RÉCUPÉRATION DU PROPRIÉTAIRE CONNECTÉ
|--------------------------------------------------------------------------
|
| On récupère également son agence, pour renseigner automatiquement
| bien.id_agence.
|
*/

$stmt = $pdo->prepare("
    SELECT p.id_proprietaire, p.id_agence, p.prenom, p.nom
    FROM proprietaire p
    INNER JOIN utilisateur u ON u.email = p.email
    WHERE u.id_utilisateur = ?
    LIMIT 1
");

$stmt->execute([$idUtilisateur]);

$proprietaire = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$proprietaire) {
    http_response_code(403);
    exit('Propriétaire introuvable.');
}

$idProprietaire = (int) $proprietaire['id_proprietaire'];
$idAgence = (int) $proprietaire['id_agence'];

if (!$idAgence) {
    http_response_code(403);
    exit('Agence du propriétaire introuvable.');
}


$types = $pdo->query("SELECT id_type_bien, nom FROM type_bien ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

$equipementsDemandes = ['Balcon', 'Terrasse', 'Jardin', 'Piscine', 'Ascenseur', 'Climatisation'];

$erreur = null;

$titre = '';
$reference = 'BIEN-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));
$idTypeBien = '';
$surface = '';
$nombrePieces = '0';
$etage = '';
$ligne1 = '';
$ville = '';
$codePostal = '';
$region = '';
$pays = 'France';
$loyer = '0';
$charges = '0';
$caution = '0';
$statut = 'CREATED';
$description = '';
$equipementsSelectionnes = [];

$uploadDir = dirname(__DIR__, 2) . '/uploads/biens/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}


/*
|--------------------------------------------------------------------------
| TRAITEMENT DU FORMULAIRE
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titre = trim($_POST['titre'] ?? '');
    $reference = trim($_POST['reference'] ?? '');
    $idTypeBien = filter_var($_POST['id_type_bien'] ?? null, FILTER_VALIDATE_INT);
    $surface = (string) ($_POST['surface'] ?? '0');
    $nombrePieces = (string) ($_POST['nombre_pieces'] ?? '0');
    $etage = trim($_POST['etage'] ?? '');
    $ligne1 = trim($_POST['ligne1'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $codePostal = trim($_POST['code_postal'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $pays = trim($_POST['pays'] ?? 'France');
    $loyer = (string) ($_POST['loyer'] ?? '0');
    $charges = (string) ($_POST['charges'] ?? '0');
    $caution = (string) ($_POST['caution'] ?? '0');
    $statut = $_POST['statut'] ?? 'CREATED';
    $description = trim($_POST['description'] ?? '');

    $equipementsSelectionnes = $_POST['equipements'] ?? [];
    if (!is_array($equipementsSelectionnes)) {
        $equipementsSelectionnes = [];
    }

    $surfaceValue = (float) $surface;
    $nombrePiecesValue = max(0, (int) $nombrePieces);
    $etageValue = ($etage === '') ? null : (int) $etage;
    $loyerValue = max(0, (float) $loyer);
    $chargesValue = max(0, (float) $charges);
    $cautionValue = max(0, (float) $caution);

    try {

        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
            throw new Exception('Votre session a expiré. Rechargez la page.');
        }

        if ($titre === '') {
            throw new Exception('Le titre du bien est obligatoire.');
        }
        if ($reference === '') {
            throw new Exception('La référence est obligatoire.');
        }
        if (!$idTypeBien) {
            throw new Exception('Le type de bien est obligatoire.');
        }
        if ($surfaceValue <= 0) {
            throw new Exception('La surface doit être supérieure à 0.');
        }
        if ($nombrePiecesValue < 0) {
            throw new Exception('Le nombre de pièces est invalide.');
        }
        if ($ligne1 === '') {
            throw new Exception('L’adresse est obligatoire.');
        }
        if ($ville === '') {
            throw new Exception('La ville est obligatoire.');
        }
        if ($pays === '') {
            throw new Exception('Le pays est obligatoire.');
        }

        $statutsAutorises = ['CREATED', 'AVAILABLE', 'OCCUPIED', 'MAINTENANCE', 'ARCHIVED'];
        if (!in_array($statut, $statutsAutorises, true)) {
            throw new Exception('Statut du bien invalide.');
        }

        $equipementsSelectionnes = array_values(array_intersect($equipementsSelectionnes, $equipementsDemandes));

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bien WHERE reference = ?");
        $stmt->execute([$reference]);
        if ((int) $stmt->fetchColumn() > 0) {
            throw new Exception('Cette référence existe déjà. Veuillez en choisir une autre.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO adresse (ligne1, ligne2, code_postal, ville, region, pays)
            VALUES (?, NULL, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $ligne1,
            $codePostal !== '' ? $codePostal : null,
            $ville,
            $region !== '' ? $region : null,
            $pays,
        ]);
        $idAdresse = (int) $pdo->lastInsertId();

        $imagePrincipale = null;

        $stmt = $pdo->prepare("
            INSERT INTO bien (id_agence, id_type_bien, id_adresse, reference, titre, surface,
                nombre_pieces, etage, loyer, charges, caution, statut, description, image_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)
        ");
        $stmt->execute([
            $idAgence, $idTypeBien, $idAdresse, $reference, $titre, $surfaceValue,
            $nombrePiecesValue, $etageValue, $loyerValue, $chargesValue, $cautionValue,
            $statut, $description !== '' ? $description : null,
        ]);
        $idBien = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare("
            INSERT INTO bien_proprietaire (id_bien, id_proprietaire, quote_part, date_debut, date_fin)
            VALUES (?, ?, 100.00, CURDATE(), NULL)
        ");
        $stmt->execute([$idBien, $idProprietaire]);

        foreach ($equipementsSelectionnes as $nomEquipement) {

            $stmt = $pdo->prepare("SELECT id_equipement FROM equipement WHERE nom = ? LIMIT 1");
            $stmt->execute([$nomEquipement]);
            $idEquipement = $stmt->fetchColumn();

            if (!$idEquipement) {
                $stmt = $pdo->prepare("INSERT INTO equipement (nom, description) VALUES (?, ?)");
                $stmt->execute([$nomEquipement, $nomEquipement . '.']);
                $idEquipement = (int) $pdo->lastInsertId();
            } else {
                $idEquipement = (int) $idEquipement;
            }

            $stmt = $pdo->prepare("INSERT INTO bien_equipement (id_bien, id_equipement, quantite) VALUES (?, ?, 1)");
            $stmt->execute([$idBien, $idEquipement]);
        }

        /*
        |--------------------------------------------------------------------------
        | PHOTOS LOCALES
        |--------------------------------------------------------------------------
        */

        $ordre = 1;

        $photosEnvoyees = UploadHelper::resolveMultiple('photos');

        if ($photosEnvoyees !== []) {

            $formatsAutorises = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $finfo = new finfo(FILEINFO_MIME_TYPE);

            foreach ($photosEnvoyees as $photoFile) {

                $tmpName = (string) $photoFile['tmp_name'];
                $error = (int) $photoFile['error'];

                if ($error !== UPLOAD_ERR_OK) {
                    throw new Exception('Une photo n’a pas pu être téléversée.');
                }
                if ($tmpName === '' || !is_file($tmpName)) {
                    throw new Exception('Fichier photo invalide.');
                }
                if ((int) $photoFile['size'] > 5 * 1024 * 1024) {
                    throw new Exception('Une photo dépasse la taille maximale de 5 Mo.');
                }

                $mime = $finfo->file($tmpName);
                if (!isset($formatsAutorises[$mime])) {
                    throw new Exception('Format de photo non autorisé. Utilisez JPG, PNG ou WEBP.');
                }

                $extension = $formatsAutorises[$mime];
                $nomFichier = 'bien_' . $idBien . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
                $destination = $uploadDir . $nomFichier;

                if (!UploadHelper::store($photoFile, $destination)) {
                    throw new Exception('Impossible d’enregistrer une photo.');
                }

                $cheminImage = 'uploads/biens/' . $nomFichier;
                $estPrincipale = ($ordre === 1) ? 1 : 0;

                $stmt = $pdo->prepare("
                    INSERT INTO bien_image (id_bien, image_url, est_principale, ordre)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$idBien, $cheminImage, $estPrincipale, $ordre]);

                if ($estPrincipale === 1) {
                    $imagePrincipale = $cheminImage;
                }

                $ordre++;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | PHOTOS DEPUIS INTERNET
        |--------------------------------------------------------------------------
        */

        $photoUrls = trim($_POST['photo_urls'] ?? '');

        if ($photoUrls !== '') {

            $urls = preg_split('/\r\n|\r|\n/', $photoUrls);

            foreach ($urls as $url) {

                $url = trim($url);
                if ($url === '') {
                    continue;
                }

                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    throw new Exception('Une URL de photo est invalide : ' . $url);
                }

                $scheme = strtolower((string) (parse_url($url, PHP_URL_SCHEME) ?? ''));
                if ($scheme !== 'http' && $scheme !== 'https') {
                    throw new Exception('Les photos Internet doivent utiliser une URL http:// ou https://.');
                }

                $estPrincipale = ($ordre === 1) ? 1 : 0;

                $stmt = $pdo->prepare("
                    INSERT INTO bien_image (id_bien, image_url, est_principale, ordre)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$idBien, $url, $estPrincipale, $ordre]);

                if ($estPrincipale === 1) {
                    $imagePrincipale = $url;
                }

                $ordre++;
            }
        }

        if ($imagePrincipale !== null) {
            $stmt = $pdo->prepare("UPDATE bien SET image_url = ?, updated_at = NOW() WHERE id_bien = ?");
            $stmt->execute([$imagePrincipale, $idBien]);
        }

        $pdo->commit();

        header('Location: detail.php?id=' . $idBien . '&success=created');
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $erreur = $e->getMessage();
    }
}

$equipementsLabels = [
    'Balcon' => 'Balcon', 'Terrasse' => 'Terrasse', 'Jardin' => 'Jardin',
    'Piscine' => 'Piscine', 'Ascenseur' => 'Ascenseur', 'Climatisation' => 'Climatisation',
];

$prenom = (string) ($proprietaire['prenom'] ?? '');
$nom = (string) ($proprietaire['nom'] ?? '');

$pageTitle = 'Ajouter un bien';
$activeNav = 'Mes biens';
$sidebarFile = 'sidebar-proprietaire.php';
$greetingTitle = 'Ajouter un bien';
$greetingSubtitle = 'Renseignez les informations du bien à ajouter à votre patrimoine.';
$profileName = trim($prenom . ' ' . $nom) ?: 'Propriétaire';
$profileRole = 'Propriétaire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'P';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/administration/mon-agence/dashboard.css">';

require dirname(__DIR__, 2) . '/layouts/header.php';
?>
    <a class="detail-back" href="biens.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour à mes biens
    </a>

    <?php if ($erreur): ?>
      <div class="feedback error"><?= e($erreur) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" data-upload-fallback>
      <input type="hidden" name="csrf_token" value="<?= e(Auth::csrfToken()) ?>">

      <div class="panel" style="margin-bottom:18px;">
        <div class="panel-header"><div class="panel-title">Informations du bien</div></div>
        <div class="form-grid" style="padding:18px;">
          <div class="form-field">
            <label for="titre">Titre</label>
            <input type="text" id="titre" name="titre" required maxlength="200" value="<?= e($titre) ?>" placeholder="Ex : Appartement T3 lumineux">
          </div>

          <div class="form-field">
            <label for="reference">Référence</label>
            <input type="text" id="reference" name="reference" required maxlength="100" value="<?= e($reference) ?>">
          </div>

          <div class="form-field">
            <label for="id_type_bien">Type de bien</label>
            <select id="id_type_bien" name="id_type_bien" required>
              <option value="">— Choisir un type —</option>
              <?php foreach ($types as $type): ?>
                <option value="<?= (int) $type['id_type_bien'] ?>" <?= (int) $idTypeBien === (int) $type['id_type_bien'] ? 'selected' : '' ?>><?= e($type['nom']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-field">
            <label for="surface">Surface (m²)</label>
            <input type="number" id="surface" name="surface" required min="0.01" step="0.01" value="<?= e($surface) ?>" placeholder="Ex : 65">
          </div>

          <div class="form-field">
            <label for="nombre_pieces">Nombre de pièces</label>
            <input type="number" id="nombre_pieces" name="nombre_pieces" min="0" step="1" value="<?= e($nombrePieces) ?>">
          </div>

          <div class="form-field">
            <label for="etage">Étage</label>
            <input type="number" id="etage" name="etage" step="1" value="<?= e($etage) ?>" placeholder="Ex : 3">
          </div>

          <div class="form-field">
            <label for="statut">Statut</label>
            <select id="statut" name="statut">
              <?php $statuts = ['CREATED' => 'Créé', 'AVAILABLE' => 'Disponible', 'OCCUPIED' => 'Occupé', 'MAINTENANCE' => 'Maintenance', 'ARCHIVED' => 'Archivé']; ?>
              <?php foreach ($statuts as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $statut === $value ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-field full">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" placeholder="Décrivez le bien..."><?= e($description) ?></textarea>
          </div>
        </div>
      </div>

      <div class="panel" style="margin-bottom:18px;">
        <div class="panel-header"><div class="panel-title">Adresse du bien</div></div>
        <div class="form-grid" style="padding:18px;">
          <div class="form-field full">
            <label for="ligne1">Adresse</label>
            <input type="text" id="ligne1" name="ligne1" required maxlength="255" value="<?= e($ligne1) ?>" placeholder="Ex : 15 rue de Paris">
          </div>
          <div class="form-field">
            <label for="ville">Ville</label>
            <input type="text" id="ville" name="ville" required maxlength="100" value="<?= e($ville) ?>" placeholder="Ex : Limoges">
          </div>
          <div class="form-field">
            <label for="code_postal">Code postal</label>
            <input type="text" id="code_postal" name="code_postal" maxlength="20" value="<?= e($codePostal) ?>" placeholder="Ex : 87000">
          </div>
          <div class="form-field">
            <label for="region">Région</label>
            <input type="text" id="region" name="region" maxlength="100" value="<?= e($region) ?>" placeholder="Ex : Nouvelle-Aquitaine">
          </div>
          <div class="form-field">
            <label for="pays">Pays</label>
            <input type="text" id="pays" name="pays" maxlength="100" value="<?= e($pays) ?>">
          </div>
        </div>
      </div>

      <div class="panel" style="margin-bottom:18px;">
        <div class="panel-header"><div class="panel-title">Informations financières</div></div>
        <div class="form-grid" style="padding:18px;">
          <div class="form-field">
            <label for="loyer">Loyer (€)</label>
            <input type="number" id="loyer" name="loyer" min="0" step="0.01" value="<?= e($loyer) ?>" placeholder="Ex : 450">
          </div>
          <div class="form-field">
            <label for="charges">Charges (€)</label>
            <input type="number" id="charges" name="charges" min="0" step="0.01" value="<?= e($charges) ?>" placeholder="Ex : 75">
          </div>
          <div class="form-field">
            <label for="caution">Caution (€)</label>
            <input type="number" id="caution" name="caution" min="0" step="0.01" value="<?= e($caution) ?>" placeholder="Ex : 450">
          </div>
        </div>
        <div style="padding:0 18px 18px;display:flex;justify-content:space-between;align-items:center;">
          <span class="panel-sub" style="margin:0;">Total mensuel</span>
          <strong id="totalMensuel" style="font-size:22px;color:var(--primary, #0f8b8d);">0,00 €</strong>
        </div>
      </div>

      <div class="panel" style="margin-bottom:18px;">
        <div class="panel-header"><div class="panel-title">Équipements</div></div>
        <div style="padding:18px;display:grid;grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));gap:10px;">
          <?php foreach ($equipementsLabels as $value => $label): ?>
            <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid var(--border, #e4e8eb);border-radius:10px;cursor:pointer;">
              <input type="checkbox" name="equipements[]" value="<?= e($value) ?>" <?= in_array($value, $equipementsSelectionnes, true) ? 'checked' : '' ?>>
              <span><?= e($label) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="panel" style="margin-bottom:18px;">
        <div class="panel-header"><div class="panel-title">Photos du bien</div></div>
        <div class="form-grid" style="padding:18px;">
          <div class="form-field full">
            <label for="photos">Photos depuis votre appareil</label>
            <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple>
            <p class="cell-sub" style="margin:4px 0 0;">JPG, PNG ou WEBP — 5 Mo maximum par photo.</p>
          </div>
          <div class="form-field full">
            <label for="photo_urls">Ajouter des photos depuis Internet</label>
            <textarea id="photo_urls" name="photo_urls" rows="4" placeholder="Collez une URL d'image par ligne..."></textarea>
            <p class="cell-sub" style="margin:4px 0 0;">Une URL par ligne (http:// ou https://). Vous pouvez mélanger photos locales et photos Internet ; la première photo ajoutée devient la photo principale.</p>
            <div id="urlPreview" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(120px, 1fr));gap:10px;margin-top:10px;"></div>
          </div>
        </div>
      </div>

      <div class="form-actions" style="margin-bottom:24px;">
        <a href="biens.php" class="btn">Annuler</a>
        <button type="submit" class="btn btn-primary">Enregistrer le bien</button>
      </div>
    </form>

<script>
(function () {
  var loyerInput = document.getElementById('loyer');
  var chargesInput = document.getElementById('charges');
  var totalMensuel = document.getElementById('totalMensuel');

  function calculerTotalMensuel() {
    var loyer = parseFloat(loyerInput.value) || 0;
    var charges = parseFloat(chargesInput.value) || 0;
    totalMensuel.textContent = (loyer + charges).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
  }

  loyerInput.addEventListener('input', calculerTotalMensuel);
  chargesInput.addEventListener('input', calculerTotalMensuel);
  calculerTotalMensuel();

  var photoUrlsInput = document.getElementById('photo_urls');
  var urlPreview = document.getElementById('urlPreview');

  function afficherApercuUrls() {
    urlPreview.innerHTML = '';
    var urls = photoUrlsInput.value.split(/\r\n|\r|\n/).map(function (u) { return u.trim(); }).filter(function (u) { return u !== ''; });

    urls.forEach(function (url) {
      var item = document.createElement('div');
      item.style.borderRadius = '10px';
      item.style.overflow = 'hidden';
      item.style.border = '1px solid var(--border, #e4e8eb)';
      item.style.aspectRatio = '4/3';
      item.style.background = 'var(--panel-muted, #f1f5f5)';

      var img = document.createElement('img');
      img.src = url;
      img.alt = 'Aperçu';
      img.loading = 'lazy';
      img.style.width = '100%';
      img.style.height = '100%';
      img.style.objectFit = 'cover';
      img.style.display = 'block';
      img.onerror = function () { item.remove(); };

      item.appendChild(img);
      urlPreview.appendChild(item);
    });
  }

  photoUrlsInput.addEventListener('input', afficherApercuUrls);
})();
</script>
<script src="/LOKA/public/assets/js/upload-fallback.js"></script>
<?php
require dirname(__DIR__, 2) . '/layouts/footer.php';
