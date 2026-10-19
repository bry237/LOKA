<?php declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';
require_once __DIR__ . '/LocataireModel.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = $user['id_agence'];
$id = (int)($_GET['id'] ?? 0);

$model = new LocataireModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'archiver') {
    Auth::verifyCsrf($_POST['csrf_token'] ?? '');
    $model->archive($agencyId, $id);
    header("Location: index.php");
    exit;
}

$locataire = $model->find($agencyId, $id);
if (!$locataire) {
    header("Location: index.php");
    exit;
}

$contracts = $model->contracts($agencyId, $id);
$contratBadge = ['DRAFT' => 'gray', 'ACTIVE' => 'green', 'EXPIRED' => 'warning', 'RENEWED' => 'blue', 'TERMINATED' => 'danger', 'ARCHIVED' => 'gray'];
$locBadge = ['ACTIVE' => 'green', 'INACTIVE' => 'gray', 'ARCHIVED' => 'danger'];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Détails Locataire | LOKA</title>
    <link rel="stylesheet" href="/LOKA/app/jordan/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'locataires'; require_once dirname(__DIR__) . '/layouts/sidebar-gestion.php'; ?>
    <div class="content">
        <header class="topbar">
            <div>
                <a href="index.php" class="back-link" style="margin:0;"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:4px;"><polyline points="15 18 9 12 15 6"/></svg>Retour aux locataires</a>
            </div>
            <div class="actions">
                <a href="modifier.php?id=<?= $id ?>" class="btn btn-outline">Modifier</a>
                <form method="POST" class="inline-form" onsubmit="return confirm('Voulez-vous vraiment archiver ce locataire ?');">
                    <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                    <input type="hidden" name="action" value="archiver">
                    <button type="submit" class="btn btn-danger">Archiver</button>
                </form>
            </div>
        </header>
        <main class="page">
            <div class="page-header">
                <h1><?= htmlspecialchars($locataire['nom'] . ' ' . $locataire['prenom'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p><span class="badge badge-<?= $locBadge[$locataire['statut']] ?? 'gray' ?>"><?= htmlspecialchars($locataire['statut'], ENT_QUOTES, 'UTF-8') ?></span></p>
            </div>
            <div class="detail-grid detail-grid--even">
                <div class="card">
                    <div class="card__header"><h2 class="card__title">Informations personnelles</h2></div>
                    <div class="card__body">
                        <dl class="data-list">
                            <dt>Nom</dt><dd><?= htmlspecialchars($locataire['nom'], ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt>Prénom</dt><dd><?= htmlspecialchars($locataire['prenom'], ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt>Date de naissance</dt><dd><?= htmlspecialchars($locataire['date_naissance'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt>Numéro identité</dt><dd><?= htmlspecialchars($locataire['numero_identite'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt>Type identité</dt><dd><?= htmlspecialchars($locataire['type_identite'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt>Situation</dt><dd><?= htmlspecialchars($locataire['situation_familiale'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt>Occupants</dt><dd><?= (int)$locataire['nombre_occupants'] ?></dd>
                            <dt>Profession</dt><dd><?= htmlspecialchars($locataire['profession'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt>Revenu mensuel</dt><dd><?= htmlspecialchars((string)($locataire['revenu_mensuel'] ?? '—'), ENT_QUOTES, 'UTF-8') ?> €</dd>
                        </dl>
                    </div>
                </div>
                <div class="card">
                    <div class="card__header"><h2 class="card__title">Contact &amp; adresse</h2></div>
                    <div class="card__body">
                        <dl class="data-list">
                            <dt>Email</dt><dd><?= htmlspecialchars($locataire['email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt>Téléphone</dt><dd><?= htmlspecialchars($locataire['telephone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt>Adresse</dt><dd><?= htmlspecialchars($locataire['adresse'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt>Code postal</dt><dd><?= htmlspecialchars($locataire['code_postal'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt>Ville</dt><dd><?= htmlspecialchars($locataire['ville'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt>Pays</dt><dd><?= htmlspecialchars($locataire['pays'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                        </dl>
                    </div>
                </div>
                <div class="card">
                    <div class="card__header"><h2 class="card__title">Garant</h2></div>
                    <div class="card__body">
                        <dl class="data-list">
                            <dt>Nom</dt><dd><?= htmlspecialchars($locataire['garant_nom'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt>Téléphone</dt><dd><?= htmlspecialchars($locataire['garant_telephone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                            <dt>Email</dt><dd><?= htmlspecialchars($locataire['garant_email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                        </dl>
                        <h3 class="form-section-title mt-4">Notes</h3>
                        <p style="font-size:13px;color:var(--text);"><?= nl2br(htmlspecialchars($locataire['notes'] ?? '—', ENT_QUOTES, 'UTF-8')) ?></p>
                    </div>
                </div>
                <div class="card">
                    <div class="card__header">
                        <h2 class="card__title">Contrats</h2>
                        <span class="cell-muted"><?= count($contracts) ?> contrat(s)</span>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Bien</th>
                                    <th>Date début</th>
                                    <th>Date fin</th>
                                    <th>Loyer</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contracts as $c): ?>
                                    <tr>
                                        <td class="cell-title"><?= htmlspecialchars($c['bien_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($c['date_debut'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($c['date_fin'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)$c['loyer'], ENT_QUOTES, 'UTF-8') ?> €</td>
                                        <td><span class="badge badge-<?= $contratBadge[$c['statut']] ?? 'gray' ?>"><?= htmlspecialchars($c['statut'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($contracts === []): ?>
                                    <tr class="empty-row"><td colspan="5">Aucun contrat.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
