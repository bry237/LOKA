<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Tableau de bord Admin | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'dashboard'; require_once dirname(__DIR__, 2) . '/layouts/sidebar_admin.php'; ?>
    <div class="content">
        <header class="topbar"><h1>Tableau de bord Administration</h1></header>
        <main class="page">
            <p>Bienvenue dans l'espace d'administration LOKA.</p>
        </main>
    </div>
</div>
</body>
</html>
