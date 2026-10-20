<?php

declare(strict_types=1);

require_once __DIR__ . '/LocataireController.php';
require_once dirname(__DIR__, 2) . '/core/Auth.php';

$controller = new LocataireController();
$locataire = $controller->getCurrentLocataire();

if (!$locataire) {
    http_response_code(404);
    exit('Locataire introuvable.');
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$section = $_GET['section'] ?? 'personnel';

$sectionsAutorisees = [
    'personnel',
    'adresse',
    'financiere'
];

if (!in_array($section, $sectionsAutorisees, true)) {
    $section = 'personnel';
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $erreur = 'Le formulaire a expiré. Rechargez la page et réessayez.';
    } else {

        try {

            if ($section === 'personnel') {

                $data = [
                    'nom' => trim($_POST['nom'] ?? ''),
                    'prenom' => trim($_POST['prenom'] ?? ''),
                    'email' => trim($_POST['email'] ?? ''),
                    'telephone' => trim($_POST['telephone'] ?? ''),
                    'date_naissance' => !empty($_POST['date_naissance'])
                        ? $_POST['date_naissance']
                        : null,
                    'profession' => trim($_POST['profession'] ?? '')
                ];

            } elseif ($section === 'adresse') {

                $data = [
                    'adresse' => trim($_POST['adresse'] ?? ''),
                    'code_postal' => trim($_POST['code_postal'] ?? ''),
                    'ville' => trim($_POST['ville'] ?? ''),
                    'pays' => trim($_POST['pays'] ?? '')
                ];

            } else {

                $data = [
                    'revenu_mensuel' => $_POST['revenu_mensuel'] !== ''
                        ? (float) $_POST['revenu_mensuel']
                        : null,
                    'statut' => trim($_POST['statut'] ?? '')
                ];
            }

            $controller->updateCurrentLocataire($data);

            header('Location: detail.php?success=1');
            exit;

        } catch (Throwable $ex) {
            $erreur = 'Une erreur est survenue lors de la modification.';
        }
    }
}

if ($section === 'personnel') {
    $titre = 'Informations personnelles';
    $description = 'Modifiez vos informations personnelles.';
} elseif ($section === 'adresse') {
    $titre = 'Adresse';
    $description = 'Modifiez vos informations de résidence.';
} else {
    $titre = 'Situation financière';
    $description = 'Modifiez vos informations financières.';
}

$prenom = $locataire['prenom'] ?? '';
$nom = $locataire['nom'] ?? '';

$pageTitle = $titre;
$activeNav = 'Mon profil';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = $titre;
$greetingSubtitle = $description;
$profileName = trim($prenom . ' ' . $nom);
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));
$extraHead = '<link rel="stylesheet" href="ops.css?v=' . filemtime(__DIR__ . '/ops.css') . '">';

$csrfToken = Auth::csrfToken();

require dirname(__DIR__, 2) . '/layouts/header.php';
?>
    <?php if ($erreur): ?>
      <div class="feedback error"><?= e($erreur) ?></div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-header"><div class="panel-title"><?= e($titre) ?></div></div>
      <form method="POST" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

        <?php if ($section === 'personnel'): ?>

          <div class="form-field">
            <label for="nom">Nom</label>
            <input type="text" id="nom" name="nom" value="<?= e($locataire['nom']) ?>" required>
          </div>

          <div class="form-field">
            <label for="prenom">Prénom</label>
            <input type="text" id="prenom" name="prenom" value="<?= e($locataire['prenom']) ?>" required>
          </div>

          <div class="form-field">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" value="<?= e($locataire['email']) ?>" required>
          </div>

          <div class="form-field">
            <label for="telephone">Téléphone</label>
            <input type="text" id="telephone" name="telephone" value="<?= e($locataire['telephone'] ?? '') ?>">
          </div>

          <div class="form-field">
            <label for="date_naissance">Date de naissance</label>
            <input type="date" id="date_naissance" name="date_naissance" value="<?= e($locataire['date_naissance'] ?? '') ?>">
          </div>

          <div class="form-field">
            <label for="profession">Profession</label>
            <input type="text" id="profession" name="profession" value="<?= e($locataire['profession'] ?? '') ?>">
          </div>

        <?php elseif ($section === 'adresse'): ?>

          <div class="form-field">
            <label for="adresse">Adresse</label>
            <input type="text" id="adresse" name="adresse" value="<?= e($locataire['adresse'] ?? '') ?>" required>
          </div>

          <div class="form-field">
            <label for="code_postal">Code postal</label>
            <input type="text" id="code_postal" name="code_postal" value="<?= e($locataire['code_postal'] ?? '') ?>" required>
          </div>

          <div class="form-field">
            <label for="ville">Ville</label>
            <input type="text" id="ville" name="ville" value="<?= e($locataire['ville'] ?? '') ?>" required>
          </div>

          <div class="form-field">
            <label for="pays">Pays</label>
            <input type="text" id="pays" name="pays" value="<?= e($locataire['pays'] ?? '') ?>" required>
          </div>

        <?php else: ?>

          <div class="form-field">
            <label for="revenu_mensuel">Revenu mensuel (€)</label>
            <input type="number" id="revenu_mensuel" name="revenu_mensuel" step="0.01" min="0" value="<?= e((string) ($locataire['revenu_mensuel'] ?? '')) ?>">
          </div>

          <div class="form-field">
            <label for="statut">Statut</label>
            <input type="text" id="statut" name="statut" value="<?= e($locataire['statut'] ?? '') ?>">
          </div>

        <?php endif; ?>

        <div class="form-actions">
          <a href="detail.php" class="btn">Annuler</a>
          <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
      </form>
    </div>
<?php
require dirname(__DIR__, 2) . '/layouts/footer.php';
