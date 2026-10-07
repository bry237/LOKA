<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapports | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'rapports'; require_once dirname(__DIR__) . '/layouts/sidebar-gestion.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <span class="eyebrow">Outils</span>
                <h1>Rapports</h1>
            </div>
        </header>
        <main class="page">
            <div class="report-grid">
                <a href="/LOKA/app/jordan/rapports/revenus.php" class="report-card">
                    <h3>Revenus</h3>
                    <p>Analyse des encaissements et moyennes mensuelles.</p>
                </a>
                <a href="/LOKA/app/jordan/rapports/occupation.php" class="report-card">
                    <h3>Occupation</h3>
                    <p>Statut des biens et taux d'occupation.</p>
                </a>
                <a href="/LOKA/app/jordan/rapports/impayes.php" class="report-card">
                    <h3>Impayés</h3>
                    <p>Liste détaillée des paiements en retard.</p>
                </a>
                <a href="/LOKA/app/jordan/rapports/maintenance.php" class="report-card">
                    <h3>Maintenance</h3>
                    <p>Statistiques des interventions et coûts.</p>
                </a>
                <a href="/LOKA/app/jordan/rapports/performance_biens.php" class="report-card">
                    <h3>Performance biens</h3>
                    <p>Revenus et rendements par bien.</p>
                </a>
                <a href="/LOKA/app/jordan/rapports/fiabilite_locataires.php" class="report-card">
                    <h3>Fiabilité locataires</h3>
                    <p>Analyse de la régularité des paiements.</p>
                </a>
            </div>
        </main>
    </div>
</div>
</body>
</html>
