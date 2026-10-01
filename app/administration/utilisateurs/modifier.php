<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence');
require_once __DIR__ . '/UtilisateurModel.php';
require_once __DIR__ . '/UtilisateurController.php';

$id = $_GET['id'] ?? null;
if (!$id) die("ID manquant");

$model = new UtilisateurModel();
$utilisateur = $model->findById($id);
if (!$utilisateur) die("Utilisateur introuvable");

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new UtilisateurController();
    $errors = $controller->validate($_POST, true);
    
    if (empty($errors)) {
        $model->update($id, $_POST);
        header('Location: detail.php?id=' . $id);
        exit;
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Modifier un Utilisateur | LOKA Admin</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'utilisateurs'; require_once dirname(__DIR__, 3) . '/layouts/sidebar_admin.php'; ?>
    <div class="content">
        <header class="topbar"><h1>Modifier l'Utilisateur</h1></header>
        <main class="page">
            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <ul>
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form method="POST" class="form-container">
                <div class="form-group">
                    <label>Nom :</label>
                    <input type="text" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? $utilisateur['nom'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="form-group">
                    <label>Prénom :</label>
                    <input type="text" name="prenom" value="<?= htmlspecialchars($_POST['prenom'] ?? $utilisateur['prenom'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="form-group">
                    <label>Email :</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $utilisateur['email'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="form-group">
                    <label>Téléphone :</label>
                    <input type="text" name="telephone" value="<?= htmlspecialchars($_POST['telephone'] ?? (string)$utilisateur['telephone'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group">
                    <label>ID Rôle :</label>
                    <input type="number" name="id_role" value="<?= htmlspecialchars($_POST['id_role'] ?? (string)$utilisateur['id_role'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                <div class="form-group">
                    <label>ID Agence :</label>
                    <input type="number" name="id_agence" value="<?= htmlspecialchars($_POST['id_agence'] ?? (string)$utilisateur['id_agence'], ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form>
        </main>
    </div>
</div>
</body>
</html>
