<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/core/Authorization.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);

if ($agencyId < 1) {
    http_response_code(400);
    exit('Aucune agence n’est associée à ce compte.');
}

$pdo = Database::connection();
$stmt = $pdo->prepare("
    SELECT q.*, p.montant, p.date_paiement, c.numero as contrat_numero, b.nom as bien_nom, l.nom as locataire_nom, l.prenom as locataire_prenom
    FROM quittance q
    JOIN paiement p ON q.id_paiement = p.id_paiement
    JOIN contrat c ON p.id_contrat = c.id_contrat
    JOIN bien b ON c.id_bien = b.id_bien
    JOIN contrat_locataire cl ON c.id_contrat = cl.id_contrat
    JOIN locataire l ON cl.id_locataire = l.id_locataire
    WHERE b.id_agence = ? AND p.deleted_at IS NULL AND c.deleted_at IS NULL
    ORDER BY q.date_emission DESC
");
$stmt->execute([$agencyId]);
$quittances = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentPage = 'quittances';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quittances | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <span class="topbar-eyebrow">Finances</span>
                <h1 class="topbar-title">Quittances</h1>
            </div>
        </header>
        <main class="page">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Numéro</th>
                        <th>Période</th>
                        <th>Montant</th>
                        <th>Bien</th>
                        <th>Locataire</th>
                        <th>Date émission</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quittances as $quittance): ?>
                        <tr>
                            <td><?= htmlspecialchars($quittance['numero_quittance'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($quittance['periode_debut'] ?? '', ENT_QUOTES, 'UTF-8') ?> au <?= htmlspecialchars($quittance['periode_fin'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($quittance['montant'] ?? ''), ENT_QUOTES, 'UTF-8') ?> €</td>
                            <td><?= htmlspecialchars($quittance['bien_nom'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(($quittance['locataire_prenom'] ?? '') . ' ' . ($quittance['locataire_nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($quittance['date_emission'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <a href="telecharger_quittance.php?id=<?= (int)$quittance['id_quittance'] ?>">Télécharger PDF</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</div>
</body>
</html>
