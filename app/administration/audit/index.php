<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Journal d'Audit | LOKA Admin</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'audit'; require_once dirname(__DIR__, 3) . '/layouts/sidebar_admin.php'; ?>
    <div class="content">
        <header class="topbar"><h1>Journal d'Audit</h1></header>
        <main class="page">
            <p>Journal d'audit en cours de construction.</p>
        </main>
    </div>
</div>
</body>
</html>
