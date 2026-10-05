<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/BienModel.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier', 'Propriétaire');
$agencyId = (int) ($user['id_agence'] ?? 0);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $agencyId < 1) {
    header('Location: /LOKA/app/biens/index.php');
    exit;
}

$bien = BienModel::read($agencyId, $id);
if (!$bien) {
    header('Location: /LOKA/app/biens/index.php');
    exit;
}

$pdo = Database::connection();

$stmt = $pdo->prepare("SELECT * FROM contrat WHERE id_bien = ? AND deleted_at IS NULL ORDER BY date_debut DESC");
$stmt->execute([$id]);
$contrats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT p.*, c.numero as contrat_numero 
    FROM paiement p 
    JOIN contrat c ON p.id_contrat = c.id_contrat 
    WHERE c.id_bien = ? 
    ORDER BY p.date_paiement DESC LIMIT 20
");
$stmt->execute([$id]);
$paiements = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM intervention WHERE id_bien = ? ORDER BY date_demande DESC");
$stmt->execute([$id]);
$interventions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT bp.*, p.nom, p.prenom 
    FROM bien_proprietaire bp 
    JOIN proprietaire p ON bp.id_proprietaire = p.id_proprietaire 
    WHERE bp.id_bien = ? 
    ORDER BY bp.date_debut DESC
");
$stmt->execute([$id]);
$proprietaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentPage = 'biens';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Historique du bien <?= htmlspecialchars($bien['titre'] ?? '', ENT_QUOTES, 'UTF-8') ?> | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <span class="topbar-eyebrow">Biens immobiliers</span>
                <h1 class="topbar-title">Historique : <?= htmlspecialchars($bien['titre'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
            </div>
        </header>
        <main class="page">
            <nav class="tabs">
                <a href="detail.php?id=<?= $id ?>">Détails</a>
                <a href="photos.php?id=<?= $id ?>">Photos</a>
                <a href="historique.php?id=<?= $id ?>" class="active">Historique</a>
            </nav>
            
            <section class="history-section">
                <h2>Propriétaires</h2>
                <ul>
                    <?php foreach ($proprietaires as $prop): ?>
                        <li><?= htmlspecialchars($prop['prenom'] . ' ' . $prop['nom'], ENT_QUOTES, 'UTF-8') ?> (du <?= htmlspecialchars($prop['date_debut'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?> au <?= htmlspecialchars($prop['date_fin'] ?? 'Présent', ENT_QUOTES, 'UTF-8') ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </section>
            
            <section class="history-section">
                <h2>Contrats</h2>
                <ul>
                    <?php foreach ($contrats as $contrat): ?>
                        <li>Contrat <?= htmlspecialchars($contrat['numero'] ?? '', ENT_QUOTES, 'UTF-8') ?> - Statut: <?= htmlspecialchars($contrat['statut'] ?? '', ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            
            <section class="history-section">
                <h2>Derniers Paiements</h2>
                <ul>
                    <?php foreach ($paiements as $paiement): ?>
                        <li><?= htmlspecialchars($paiement['montant'] ?? '', ENT_QUOTES, 'UTF-8') ?> € le <?= htmlspecialchars($paiement['date_paiement'] ?? '', ENT_QUOTES, 'UTF-8') ?> (Contrat <?= htmlspecialchars($paiement['contrat_numero'] ?? '', ENT_QUOTES, 'UTF-8') ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </section>
            
            <section class="history-section">
                <h2>Interventions</h2>
                <ul>
                    <?php foreach ($interventions as $intervention): ?>
                        <li><?= htmlspecialchars($intervention['titre'] ?? '', ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($intervention['statut'] ?? '', ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </main>
    </div>
</div>
</body>
</html>
