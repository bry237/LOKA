<?php
declare(strict_types=1);
require_once dirname(__DIR__, 4) . '/core/Authorization.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence');
require_once __DIR__ . '/UtilisateurModel.php';

$id = $_GET['id'] ?? null;
if (!$id) die("ID manquant");

$model = new UtilisateurModel();
$utilisateur = $model->findById($id);

if (!$utilisateur) die("Utilisateur introuvable");
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Détail utilisateur | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'utilisateurs'; require_once dirname(__DIR__) . '/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <a href="index.php" class="back-link" style="margin:0;">&larr; Utilisateurs</a>
            </div>
            <div class="topbar-right">
                <?php if ($utilisateur['statut'] === 'ACTIVE'): ?>
                    <form method="POST" action="desactiver.php" class="inline-form">
                        <input type="hidden" name="id" value="<?= $utilisateur['id_utilisateur'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-outline">Désactiver</button>
                    </form>
                <?php else: ?>
                    <form method="POST" action="activer.php" class="inline-form">
                        <input type="hidden" name="id" value="<?= $utilisateur['id_utilisateur'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-success">Activer</button>
                    </form>
                <?php endif; ?>
                <a href="modifier.php?id=<?= $utilisateur['id_utilisateur'] ?>" class="btn btn-primary">Modifier</a>
            </div>
        </header>
        <main class="page">
            <div class="page-header">
                <h1><?= htmlspecialchars($utilisateur['nom'] . ' ' . $utilisateur['prenom'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p><span class="badge badge-<?= strtolower($utilisateur['statut']) === 'active' ? 'green' : (strtolower($utilisateur['statut']) === 'locked' ? 'danger' : 'gray') ?>"><?= htmlspecialchars($utilisateur['statut'], ENT_QUOTES, 'UTF-8') ?></span></p>
            </div>
            <div class="card">
                <div class="details-section card__body">
                    <p><strong>Email :</strong> <?= htmlspecialchars($utilisateur['email'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Téléphone :</strong> <?= htmlspecialchars((string)$utilisateur['telephone'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Rôle :</strong> <?= htmlspecialchars((string)$utilisateur['role_nom'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Agence :</strong> <?= htmlspecialchars((string)$utilisateur['agence_nom'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Dernière connexion :</strong> <?= htmlspecialchars((string)$utilisateur['derniere_connexion'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
