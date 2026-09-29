<?php declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Auth.php';
require_once __DIR__ . '/LocataireController.php';

$user = Authorization::requireRole(['AGENCE_ADMIN', 'GESTIONNAIRE']);
$agencyId = $user['id_agence'];

$errors = [];
$input = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf($_POST['csrf_token'] ?? '');
    $input = $_POST;
    $controller = new LocataireController();
    [$errors, $id] = $controller->save($agencyId, $input);
    
    if (empty($errors) && $id) {
        header("Location: detail.php?id=" . $id);
        exit;
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ajouter un locataire | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'locataires'; require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div>
                <a href="index.php" class="eyebrow">← Retour aux locataires</a>
                <h1>Nouveau locataire</h1>
            </div>
        </header>
        <main class="page">
            <form method="POST" action="ajouter.php" class="form-card">
                <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                
                <?php if (isset($errors['global'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errors['global'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                
                <h2 class="section-title">Informations personnelles</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="label">Nom *</label>
                        <input type="text" name="nom" class="input <?= isset($errors['nom']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($input['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (isset($errors['nom'])): ?><div class="invalid-feedback"><?= $errors['nom'] ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="label">Prénom *</label>
                        <input type="text" name="prenom" class="input <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($input['prenom'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (isset($errors['prenom'])): ?><div class="invalid-feedback"><?= $errors['prenom'] ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="label">Date de naissance</label>
                        <input type="date" name="date_naissance" class="input" value="<?= htmlspecialchars($input['date_naissance'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group">
                        <label class="label">Profession</label>
                        <input type="text" name="profession" class="input" value="<?= htmlspecialchars($input['profession'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group">
                        <label class="label">Revenu mensuel (€)</label>
                        <input type="number" step="0.01" name="revenu_mensuel" class="input" value="<?= htmlspecialchars((string)($input['revenu_mensuel'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group">
                        <label class="label">Statut</label>
                        <select name="statut" class="input">
                            <option value="ACTIVE" <?= ($input['statut'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Actif</option>
                            <option value="INACTIVE" <?= ($input['statut'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>Inactif</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="label">Type d'identité</label>
                        <select name="type_identite" class="input">
                            <option value="">Sélectionner</option>
                            <option value="CNI" <?= ($input['type_identite'] ?? '') === 'CNI' ? 'selected' : '' ?>>Carte d'Identité</option>
                            <option value="PASSEPORT" <?= ($input['type_identite'] ?? '') === 'PASSEPORT' ? 'selected' : '' ?>>Passeport</option>
                            <option value="TITRE_SEJOUR" <?= ($input['type_identite'] ?? '') === 'TITRE_SEJOUR' ? 'selected' : '' ?>>Titre de Séjour</option>
                            <option value="AUTRE" <?= ($input['type_identite'] ?? '') === 'AUTRE' ? 'selected' : '' ?>>Autre</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="label">Numéro d'identité</label>
                        <input type="text" name="numero_identite" class="input" value="<?= htmlspecialchars($input['numero_identite'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group">
                        <label class="label">Situation familiale</label>
                        <select name="situation_familiale" class="input">
                            <option value="">Sélectionner</option>
                            <option value="CELIBATAIRE" <?= ($input['situation_familiale'] ?? '') === 'CELIBATAIRE' ? 'selected' : '' ?>>Célibataire</option>
                            <option value="MARIE" <?= ($input['situation_familiale'] ?? '') === 'MARIE' ? 'selected' : '' ?>>Marié(e)</option>
                            <option value="PACSE" <?= ($input['situation_familiale'] ?? '') === 'PACSE' ? 'selected' : '' ?>>Pacsé(e)</option>
                            <option value="DIVORCE" <?= ($input['situation_familiale'] ?? '') === 'DIVORCE' ? 'selected' : '' ?>>Divorcé(e)</option>
                            <option value="VEUF" <?= ($input['situation_familiale'] ?? '') === 'VEUF' ? 'selected' : '' ?>>Veuf/Veuve</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="label">Nombre d'occupants</label>
                        <input type="number" name="nombre_occupants" class="input" value="<?= htmlspecialchars((string)($input['nombre_occupants'] ?? '1'), ENT_QUOTES, 'UTF-8') ?>" min="1">
                    </div>
                </div>

                <h2 class="section-title mt-4">Contact</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="label">Email</label>
                        <input type="email" name="email" class="input <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($input['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= $errors['email'] ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="label">Téléphone</label>
                        <input type="text" name="telephone" class="input" value="<?= htmlspecialchars($input['telephone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label class="label">Adresse</label>
                        <input type="text" name="adresse" class="input" value="<?= htmlspecialchars($input['adresse'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group">
                        <label class="label">Code postal</label>
                        <input type="text" name="code_postal" class="input" value="<?= htmlspecialchars($input['code_postal'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group">
                        <label class="label">Ville</label>
                        <input type="text" name="ville" class="input" value="<?= htmlspecialchars($input['ville'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group">
                        <label class="label">Pays</label>
                        <input type="text" name="pays" class="input" value="<?= htmlspecialchars($input['pays'] ?? 'France', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <h2 class="section-title mt-4">Garant</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="label">Nom du garant</label>
                        <input type="text" name="garant_nom" class="input" value="<?= htmlspecialchars($input['garant_nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group">
                        <label class="label">Téléphone du garant</label>
                        <input type="text" name="garant_telephone" class="input" value="<?= htmlspecialchars($input['garant_telephone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group">
                        <label class="label">Email du garant</label>
                        <input type="email" name="garant_email" class="input" value="<?= htmlspecialchars($input['garant_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                
                <h2 class="section-title mt-4">Notes</h2>
                <div class="form-group">
                    <textarea name="notes" class="input" rows="4"><?= htmlspecialchars($input['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="form-actions mt-4">
                    <a href="index.php" class="btn">Annuler</a>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </main>
    </div>
</div>
</body>
</html>
