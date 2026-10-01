<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
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
    <title>Détail Utilisateur | LOKA Admin</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'utilisateurs'; require_once dirname(__DIR__, 3) . '/layouts/sidebar_admin.php'; ?>
    <div class="content">
        <header class="topbar">
            <h1>Détail de l'utilisateur: <?= htmlspecialchars($utilisateur['nom'] . ' ' . $utilisateur['prenom'], ENT_QUOTES, 'UTF-8') ?></h1>
        </header>
        <main class="page">
            <div class="details-section">
                <p><strong>Email :</strong> <?= htmlspecialchars($utilisateur['email'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Téléphone :</strong> <?= htmlspecialchars((string)$utilisateur['telephone'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Statut :</strong> <?= htmlspecialchars($utilisateur['statut'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Rôle :</strong> <?= htmlspecialchars((string)$utilisateur['role_nom'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Agence :</strong> <?= htmlspecialchars((string)$utilisateur['agence_nom'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Dernière connexion :</strong> <?= htmlspecialchars((string)$utilisateur['derniere_connexion'], ENT_QUOTES, 'UTF-8') ?></p>
                
                <div style="margin-top: 2rem;">
                    <?php if ($utilisateur['statut'] === 'ACTIVE'): ?>
                        <form method="POST" action="desactiver.php" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $utilisateur['id_utilisateur'] ?>">
                            <button type="submit" class="btn">Désactiver</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="activer.php" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $utilisateur['id_utilisateur'] ?>">
                            <button type="submit" class="btn">Activer</button>
                        </form>
                    <?php endif; ?>
                    <a href="modifier.php?id=<?= $utilisateur['id_utilisateur'] ?>" class="btn">Modifier</a>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
