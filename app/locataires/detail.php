<?php declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Auth.php';
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
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Détails Locataire | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'locataires'; require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div>
                <a href="index.php" class="eyebrow">← Retour aux locataires</a>
                <h1><?= htmlspecialchars($locataire['nom'] . ' ' . $locataire['prenom'], ENT_QUOTES, 'UTF-8') ?></h1>
            </div>
            <div class="actions">
                <a href="modifier.php?id=<?= $id ?>" class="btn">Modifier</a>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Voulez-vous vraiment archiver ce locataire ?');">
                    <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                    <input type="hidden" name="action" value="archiver">
                    <button type="submit" class="btn btn-danger">Archiver</button>
                </form>
            </div>
        </header>
        <main class="page">
            <div class="detail-grid">
                <div class="card">
                    <h2>Informations personnelles</h2>
                    <dl class="data-list">
                        <dt>Nom</dt><dd><?= htmlspecialchars($locataire['nom'], ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt>Prénom</dt><dd><?= htmlspecialchars($locataire['prenom'], ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt>Date de naissance</dt><dd><?= htmlspecialchars($locataire['date_naissance'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt>Numéro identité</dt><dd><?= htmlspecialchars($locataire['numero_identite'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt>Type identité</dt><dd><?= htmlspecialchars($locataire['type_identite'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt>Situation</dt><dd><?= htmlspecialchars($locataire['situation_familiale'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt>Occupants</dt><dd><?= (int)$locataire['nombre_occupants'] ?></dd>
                        <dt>Profession</dt><dd><?= htmlspecialchars($locataire['profession'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt>Revenu mensuel</dt><dd><?= htmlspecialchars((string)($locataire['revenu_mensuel'] ?? '-'), ENT_QUOTES, 'UTF-8') ?> €</dd>
                    </dl>
                </div>
                <div class="card">
                    <h2>Contact & Adresse</h2>
                    <dl class="data-list">
                        <dt>Email</dt><dd><?= htmlspecialchars($locataire['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt>Téléphone</dt><dd><?= htmlspecialchars($locataire['telephone'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt>Adresse</dt><dd><?= htmlspecialchars($locataire['adresse'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt>Code postal</dt><dd><?= htmlspecialchars($locataire['code_postal'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt>Ville</dt><dd><?= htmlspecialchars($locataire['ville'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt>Pays</dt><dd><?= htmlspecialchars($locataire['pays'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                    </dl>
                </div>
                <div class="card">
                    <h2>Garant</h2>
                    <dl class="data-list">
                        <dt>Nom</dt><dd><?= htmlspecialchars($locataire['garant_nom'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt>Téléphone</dt><dd><?= htmlspecialchars($locataire['garant_telephone'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                        <dt>Email</dt><dd><?= htmlspecialchars($locataire['garant_email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></dd>
                    </dl>
                    <h2 class="mt-4">Notes</h2>
                    <p><?= nl2br(htmlspecialchars($locataire['notes'] ?? '-', ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
                <div class="card" style="grid-column: 1 / -1;">
                    <h2>Contrats</h2>
                    <?php if (empty($contracts)): ?>
                        <p>Aucun contrat.</p>
                    <?php else: ?>
                        <table class="table">
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
                                        <td><?= htmlspecialchars($c['bien_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($c['date_debut'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($c['date_fin'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)$c['loyer_hc'], ENT_QUOTES, 'UTF-8') ?> €</td>
                                        <td><?= htmlspecialchars($c['statut'], ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>
