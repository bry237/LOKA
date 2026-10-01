<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
$user = Authorization::requireRole('Administrateur plateforme');
require_once __DIR__ . '/AgenceModel.php';

$id = $_GET['id'] ?? null;
if (!$id) die("ID manquant");

$model = new AgenceModel();
$agence = $model->findById($id);

if (!$agence) die("Agence introuvable");
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Détail Agence | LOKA Admin</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'agences'; require_once dirname(__DIR__, 3) . '/layouts/sidebar_admin.php'; ?>
    <div class="content">
        <header class="topbar">
            <h1>Agence : <?= htmlspecialchars($agence['nom'], ENT_QUOTES, 'UTF-8') ?></h1>
        </header>
        <main class="page">
            <div class="details-section">
                <p><strong>Email :</strong> <?= htmlspecialchars($agence['email'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Téléphone :</strong> <?= htmlspecialchars((string)$agence['telephone'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Adresse :</strong> <?= htmlspecialchars((string)$agence['adresse'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Ville :</strong> <?= htmlspecialchars((string)$agence['ville'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Statut :</strong> <?= htmlspecialchars($agence['statut'], ENT_QUOTES, 'UTF-8') ?></p>
                <p><strong>Créée le :</strong> <?= htmlspecialchars((string)$agence['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
                
                <div style="margin-top: 2rem;">
                    <a href="modifier.php?id=<?= $agence['id_agence'] ?>" class="btn">Modifier</a>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
