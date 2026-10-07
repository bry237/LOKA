<?php
declare(strict_types=1);
require_once dirname(__DIR__, 4) . '/core/Authorization.php';
$user = Authorization::requireRole('Administrateur plateforme');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Abonnements | LOKA Admin</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'abonnements'; require_once dirname(__DIR__) . '/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <span class="eyebrow">Configuration</span>
                <h1>Abonnements</h1>
            </div>
        </header>
        <main class="page">
            <div class="card">
                <div class="card__body empty">
                    <div class="empty__icon">🧩</div>
                    <strong>Module en cours de construction</strong>
                    <p style="margin:6px 0 0;color:var(--gray);font-size:12.5px;">La gestion des abonnements sera disponible prochainement.</p>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
