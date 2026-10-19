<?php

declare(strict_types=1);

require_once __DIR__ . '/LocataireController.php';

$controller = new LocataireController();
$locataire = $controller->getCurrentLocataire();

if (!$locataire) {
    http_response_code(404);
    exit('Locataire introuvable.');
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

    } catch (Throwable $e) {
        $erreur = 'Une erreur est survenue lors de la modification.';
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

?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($titre) ?> - LOKA</title>

    <link rel="stylesheet" href="/LOKA/app/locataires/locataire.css">

</head>

<body class="modification-page">

    <div class="modification-container">

        <div class="modification-card">

            <div class="modification-header">

                <div class="modification-logo">
                    LOKA
                </div>

                <h1><?= htmlspecialchars($titre) ?></h1>

                <p>
                    <?= htmlspecialchars($description) ?>
                </p>

            </div>

            <?php if ($erreur): ?>

                <div class="form-message form-error">
                    <?= htmlspecialchars($erreur) ?>
                </div>

            <?php endif; ?>

            <form method="POST" class="modification-form">

                <?php if ($section === 'personnel'): ?>

                    <div class="form-group">

                        <label for="nom">
                            Nom
                        </label>

                        <input
                            type="text"
                            id="nom"
                            name="nom"
                            value="<?= htmlspecialchars($locataire['nom']) ?>"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="prenom">
                            Prénom
                        </label>

                        <input
                            type="text"
                            id="prenom"
                            name="prenom"
                            value="<?= htmlspecialchars($locataire['prenom']) ?>"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="email">
                            Adresse email
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars($locataire['email']) ?>"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="telephone">
                            Téléphone
                        </label>

                        <input
                            type="text"
                            id="telephone"
                            name="telephone"
                            value="<?= htmlspecialchars($locataire['telephone'] ?? '') ?>"
                        >

                    </div>

                    <div class="form-group">

                        <label for="date_naissance">
                            Date de naissance
                        </label>

                        <input
                            type="date"
                            id="date_naissance"
                            name="date_naissance"
                            value="<?= htmlspecialchars($locataire['date_naissance'] ?? '') ?>"
                        >

                    </div>

                    <div class="form-group">

                        <label for="profession">
                            Profession
                        </label>

                        <input
                            type="text"
                            id="profession"
                            name="profession"
                            value="<?= htmlspecialchars($locataire['profession'] ?? '') ?>"
                        >

                    </div>

                <?php elseif ($section === 'adresse'): ?>

                    <div class="form-group">

                        <label for="adresse">
                            Adresse
                        </label>

                        <input
                            type="text"
                            id="adresse"
                            name="adresse"
                            value="<?= htmlspecialchars($locataire['adresse'] ?? '') ?>"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="code_postal">
                            Code postal
                        </label>

                        <input
                            type="text"
                            id="code_postal"
                            name="code_postal"
                            value="<?= htmlspecialchars($locataire['code_postal'] ?? '') ?>"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="ville">
                            Ville
                        </label>

                        <input
                            type="text"
                            id="ville"
                            name="ville"
                            value="<?= htmlspecialchars($locataire['ville'] ?? '') ?>"
                            required
                        >

                    </div>

                    <div class="form-group">

                        <label for="pays">
                            Pays
                        </label>

                        <input
                            type="text"
                            id="pays"
                            name="pays"
                            value="<?= htmlspecialchars($locataire['pays'] ?? '') ?>"
                            required
                        >

                    </div>

                <?php else: ?>

                    <div class="form-group">

                        <label for="revenu_mensuel">
                            Revenu mensuel
                        </label>

                        <div class="input-with-suffix">

                            <input
                                type="number"
                                id="revenu_mensuel"
                                name="revenu_mensuel"
                                step="0.01"
                                min="0"
                                value="<?= htmlspecialchars((string)($locataire['revenu_mensuel'] ?? '')) ?>"
                            >

                            <span>€</span>

                        </div>

                    </div>

                    <div class="form-group">

                        <label for="statut">
                            Statut
                        </label>

                        <input
                            type="text"
                            id="statut"
                            name="statut"
                            value="<?= htmlspecialchars($locataire['statut'] ?? '') ?>"
                        >

                    </div>

                <?php endif; ?>

                <div class="modification-actions">

                    <a href="detail.php" class="btn-cancel">
                        Annuler
                    </a>

                    <button type="submit" class="btn-save">
                        Enregistrer
                    </button>

                </div>

            </form>

        </div>

    </div>

</body>

</html>