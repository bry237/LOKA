<?php
declare(strict_types=1);
require_once dirname(__DIR__, 4) . '/core/Authorization.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Tableau de bord Admin | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'dashboard'; require_once dirname(__DIR__) . '/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <span class="eyebrow">Administration</span>
                <h1>Tableau de bord</h1>
            </div>
        </header>
        <main class="page">
            <div class="card">
                <div class="card__body">
                    <p style="margin:0;color:var(--text);">Bienvenue dans l'espace d'administration LOKA. Utilisez le menu à gauche pour gérer les agences, les utilisateurs et la configuration de la plateforme.</p>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
