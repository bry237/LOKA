<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
$user = Authorization::requireRole('Administrateur plateforme');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Abonnements | LOKA Admin</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'abonnements'; require_once dirname(__DIR__, 3) . '/layouts/sidebar_admin.php'; ?>
    <div class="content">
        <header class="topbar"><h1>Abonnements</h1></header>
        <main class="page">
            <p>Gestion des abonnements en cours de construction.</p>
        </main>
    </div>
</div>
</body>
</html>
