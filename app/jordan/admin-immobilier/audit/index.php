<?php
declare(strict_types=1);
require_once dirname(__DIR__, 4) . '/core/Authorization.php';
$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Journal d'audit | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'audit'; require_once dirname(__DIR__) . '/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <span class="eyebrow">Audit</span>
                <h1>Journal d'audit</h1>
            </div>
        </header>
        <main class="page">
            <div class="card">
                <div class="card__body empty">
                    <div class="empty__icon"><svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z"/></svg></div>
                    <strong>Module en cours de construction</strong>
                    <p style="margin:6px 0 0;color:var(--gray);font-size:12.5px;">Le journal d'audit sera disponible prochainement.</p>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
