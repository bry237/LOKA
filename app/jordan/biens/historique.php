<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Database.php';
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/BienModel.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier', 'Propriétaire');
$agencyId = (int) ($user['id_agence'] ?? 0);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $agencyId < 1) {
    header('Location: /LOKA/app/jordan/biens/index.php');
    exit;
}

$bien = BienModel::find($agencyId, $id);
if (!$bien) {
    header('Location: /LOKA/app/jordan/biens/index.php');
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

$stmt = $pdo->prepare("SELECT * FROM intervention WHERE id_bien = ? ORDER BY created_at DESC");
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

$contratBadge = ['DRAFT' => 'gray', 'ACTIVE' => 'green', 'EXPIRED' => 'warning', 'RENEWED' => 'blue', 'TERMINATED' => 'danger', 'ARCHIVED' => 'gray'];
$interventionBadge = ['OPEN' => 'gray', 'ASSIGNED' => 'blue', 'IN_PROGRESS' => 'warning', 'WAITING' => 'warning', 'RESOLVED' => 'green', 'CLOSED' => 'gray', 'CANCELLED' => 'danger'];

$currentPage = 'biens';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Historique du bien <?= htmlspecialchars($bien['titre'] ?? '', ENT_QUOTES, 'UTF-8') ?> | LOKA</title>
    <link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php require_once dirname(__DIR__) . '/layouts/sidebar-gestion.php'; ?>
    <div class="content">
        <header class="topbar">
            <div>
                <div class="topbar__eyebrow">Biens immobiliers</div>
                <div class="topbar__title">Historique : <?= htmlspecialchars($bien['titre'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <a class="button button--ghost" href="detail.php?id=<?= $id ?>">Retour à la fiche</a>
        </header>
        <main class="page">
            <nav class="tab-nav">
                <a class="tab-link" href="detail.php?id=<?= $id ?>">Informations</a>
                <a class="tab-link" href="photos.php?id=<?= $id ?>">Photos</a>
                <a class="tab-link tab-link--active" href="historique.php?id=<?= $id ?>">Historique</a>
            </nav>

            <div class="detail-grid detail-grid--even">
                <section class="card">
                    <div class="card__header"><h2 class="card__title">Propriétaires</h2><span class="cell-muted"><?= count($proprietaires) ?></span></div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Propriétaire</th><th>Depuis</th><th>Jusqu'à</th></tr></thead>
                            <tbody>
                                <?php foreach ($proprietaires as $prop): ?>
                                    <tr>
                                        <td class="cell-title"><?= htmlspecialchars($prop['prenom'] . ' ' . $prop['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($prop['date_debut'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($prop['date_fin'] ?? 'Présent', ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($proprietaires === []): ?>
                                    <tr class="empty-row"><td colspan="3">Aucun propriétaire enregistré.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="card">
                    <div class="card__header"><h2 class="card__title">Contrats</h2><span class="cell-muted"><?= count($contrats) ?></span></div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Numéro</th><th>Statut</th></tr></thead>
                            <tbody>
                                <?php foreach ($contrats as $contrat): ?>
                                    <tr>
                                        <td class="cell-title"><?= htmlspecialchars($contrat['numero'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="badge badge-<?= $contratBadge[$contrat['statut']] ?? 'gray' ?>"><?= htmlspecialchars($contrat['statut'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($contrats === []): ?>
                                    <tr class="empty-row"><td colspan="2">Aucun contrat enregistré.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="card">
                    <div class="card__header"><h2 class="card__title">Derniers paiements</h2><span class="cell-muted"><?= count($paiements) ?></span></div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Montant</th><th>Date</th><th>Contrat</th></tr></thead>
                            <tbody>
                                <?php foreach ($paiements as $paiement): ?>
                                    <tr>
                                        <td class="cell-title"><?= number_format((float) ($paiement['montant'] ?? 0), 2, ',', ' ') ?> €</td>
                                        <td><?= htmlspecialchars($paiement['date_paiement'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($paiement['contrat_numero'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($paiements === []): ?>
                                    <tr class="empty-row"><td colspan="3">Aucun paiement enregistré.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="card">
                    <div class="card__header"><h2 class="card__title">Interventions</h2><span class="cell-muted"><?= count($interventions) ?></span></div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Intervention</th><th>Statut</th></tr></thead>
                            <tbody>
                                <?php foreach ($interventions as $intervention): ?>
                                    <tr>
                                        <td class="cell-title"><?= htmlspecialchars($intervention['titre'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="badge badge-<?= $interventionBadge[$intervention['statut']] ?? 'gray' ?>"><?= htmlspecialchars($intervention['statut'] ?? '', ENT_QUOTES, 'UTF-8') ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($interventions === []): ?>
                                    <tr class="empty-row"><td colspan="2">Aucune intervention enregistrée.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>
</body>
</html>
