<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapports | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
    <style>
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .report-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-decoration: none;
            color: #333;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .report-card h3 {
            margin: 0;
            color: #2c3e50;
        }
        .report-card p {
            margin: 0;
            color: #7f8c8d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'rapports'; require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <span class="eyebrow">Outils</span>
                <h1>Rapports</h1>
            </div>
        </header>
        <main class="page">
            <div class="report-grid">
                <a href="/LOKA/app/rapports/revenus.php" class="report-card">
                    <h3>Revenus</h3>
                    <p>Analyse des encaissements et moyennes mensuelles.</p>
                </a>
                <a href="/LOKA/app/rapports/occupation.php" class="report-card">
                    <h3>Occupation</h3>
                    <p>Statut des biens et taux d'occupation.</p>
                </a>
                <a href="/LOKA/app/rapports/impayes.php" class="report-card">
                    <h3>Impayés</h3>
                    <p>Liste détaillée des paiements en retard.</p>
                </a>
                <a href="/LOKA/app/rapports/maintenance.php" class="report-card">
                    <h3>Maintenance</h3>
                    <p>Statistiques des interventions et coûts.</p>
                </a>
                <a href="/LOKA/app/rapports/performance_biens.php" class="report-card">
                    <h3>Performance biens</h3>
                    <p>Revenus et rendements par bien.</p>
                </a>
                <a href="/LOKA/app/rapports/fiabilite_locataires.php" class="report-card">
                    <h3>Fiabilité locataires</h3>
                    <p>Analyse de la régularité des paiements.</p>
                </a>
            </div>
        </main>
    </div>
</div>
</body>
</html>
