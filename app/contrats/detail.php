<?php declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Auth.php';
require_once __DIR__ . '/ContratModel.php';
require_once __DIR__ . '/ContratController.php';

$user = Authorization::requireRole(['AGENCY_ADMIN', 'MANAGER', 'AGENT']);
$agencyId = $user['id_agence'];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: /LOKA/app/contrats/index.php');
    exit;
}

$model = new ContratModel();
$contrat = $model->find($agencyId, $id);

if (!$contrat) {
    header('Location: /LOKA/app/contrats/index.php');
    exit;
}

$tenants = $model->tenants($agencyId, $id);

// Handle POST actions (attach tenant, detach, terminate)
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $action = $_POST['action'] ?? '';
    $controller = new ContratController();

    if ($action === 'attach_tenant') {
        $input = [
            'id_locataire' => $_POST['id_locataire'] ?? null,
            'titulaire_principal' => isset($_POST['titulaire_principal']) ? 1 : 0,
            'date_entree' => $_POST['date_entree'] ?? null,
            'date_sortie' => $_POST['date_sortie'] ?? null,
        ];
        [$errs, $res] = $controller->attachTenant($agencyId, $id, $input);
        if ($errs) {
            $errors = $errs;
        } else {
            $success = 'Locataire associé avec succès.';
            $tenants = $model->tenants($agencyId, $id); // refresh
        }
    } elseif ($action === 'detach_tenant') {
        $model->detachTenant($agencyId, $id, (int)($_POST['id_locataire'] ?? 0));
        $success = 'Locataire dissocié.';
        $tenants = $model->tenants($agencyId, $id); // refresh
    } elseif ($action === 'terminate') {
        $reason = $_POST['motif_resiliation'] ?? '';
        [$errs, $res] = $controller->terminate($agencyId, $id, $reason);
        if ($errs) {
            $errors = $errs;
        } else {
            $success = 'Contrat résilié.';
            $contrat = $model->find($agencyId, $id); // refresh
        }
    }
}

// Need LocataireModel to list available tenants for dropdown
require_once dirname(__DIR__) . '/locataires/LocataireModel.php';
$locataireModel = new LocataireModel();
$allLocataires = $locataireModel->list($agencyId);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Détail Contrat <?= htmlspecialchars($contrat['numero'], ENT_QUOTES, 'UTF-8') ?> | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'contrats'; require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="breadcrumbs">
                <a href="/LOKA/app/contrats/index.php">Contrats</a>
                <span class="separator">&rsaquo;</span>
                <span><?= htmlspecialchars($contrat['numero'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="actions">
                <a href="/LOKA/app/contrats/modifier.php?id=<?= $id ?>" class="btn btn-secondary">Modifier</a>
                <?php if ($contrat['statut'] === 'ACTIVE'): ?>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('modal-terminate').style.display='block'">Résilier</button>
                <?php endif; ?>
            </div>
        </header>

        <main class="page">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach($errors as $err): ?>
                        <p><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <div class="grid-2">
                <div class="card">
                    <h2>Informations du Contrat</h2>
                    <div class="info-group">
                        <label>Numéro</label>
                        <p><?= htmlspecialchars($contrat['numero'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="info-group">
                        <label>Statut</label>
                        <p><span class="badge badge-<?= strtolower($contrat['statut']) ?>"><?= htmlspecialchars($contrat['statut'], ENT_QUOTES, 'UTF-8') ?></span></p>
                    </div>
                    <div class="info-group">
                        <label>Type</label>
                        <p><?= htmlspecialchars($contrat['type_contrat'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="info-group">
                        <label>Période</label>
                        <p>
                            Du <?= htmlspecialchars($contrat['date_debut'], ENT_QUOTES, 'UTF-8') ?> 
                            <?= $contrat['date_fin'] ? 'au ' . htmlspecialchars($contrat['date_fin'], ENT_QUOTES, 'UTF-8') : '(en cours)' ?>
                        </p>
                    </div>
                    <div class="info-group">
                        <label>Loyer HC</label>
                        <p><?= htmlspecialchars(number_format((float)$contrat['loyer'], 2, ',', ' '), ENT_QUOTES, 'UTF-8') ?> €</p>
                    </div>
                    <div class="info-group">
                        <label>Charges</label>
                        <p><?= htmlspecialchars(number_format((float)$contrat['charges'], 2, ',', ' '), ENT_QUOTES, 'UTF-8') ?> €</p>
                    </div>
                    <div class="info-group">
                        <label>Dépôt de garantie</label>
                        <p><?= htmlspecialchars(number_format((float)$contrat['depot_garantie'], 2, ',', ' '), ENT_QUOTES, 'UTF-8') ?> €</p>
                    </div>
                    <div class="info-group">
                        <label>Fréquence & Échéance</label>
                        <p><?= htmlspecialchars($contrat['frequence_paiement'], ENT_QUOTES, 'UTF-8') ?>, le <?= htmlspecialchars((string)$contrat['jour_echeance'], ENT_QUOTES, 'UTF-8') ?> du mois</p>
                    </div>
                </div>

                <div class="card">
                    <h2>Bien Associé</h2>
                    <?php if ($contrat['id_bien']): ?>
                        <div class="info-group">
                            <label>Nom / Réf</label>
                            <p><?= htmlspecialchars($contrat['bien_nom'] ?? $contrat['bien_reference'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="info-group">
                            <label>Adresse</label>
                            <p>
                                <?= htmlspecialchars($contrat['adresse_rue'] ?? '', ENT_QUOTES, 'UTF-8') ?><br>
                                <?= htmlspecialchars($contrat['adresse_cp'] ?? '', ENT_QUOTES, 'UTF-8') ?> 
                                <?= htmlspecialchars($contrat['adresse_ville'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>
                        <a href="/LOKA/app/biens/detail.php?id=<?= $contrat['id_bien'] ?>" class="btn btn-sm btn-secondary">Voir le bien</a>
                    <?php else: ?>
                        <p>Aucun bien associé.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card" style="margin-top: 1rem;">
                <h2>Locataires Associés</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Principal</th>
                                <th>Entrée</th>
                                <th>Sortie</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tenants as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['prenom'] . ' ' . $t['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($t['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= $t['titulaire_principal'] ? 'Oui' : 'Non' ?></td>
                                <td><?= htmlspecialchars($t['date_entree'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($t['date_sortie'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Retirer ce locataire du contrat ?');">
                                        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                                        <input type="hidden" name="action" value="detach_tenant">
                                        <input type="hidden" name="id_locataire" value="<?= $t['id_locataire'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Retirer</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <h3 style="margin-top: 2rem;">Ajouter un locataire</h3>
                <form method="POST" class="form-grid">
                    <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                    <input type="hidden" name="action" value="attach_tenant">
                    <div class="form-group">
                        <label>Locataire</label>
                        <select name="id_locataire" required class="form-control">
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($allLocataires as $al): ?>
                                <option value="<?= $al['id_locataire'] ?>"><?= htmlspecialchars($al['prenom'] . ' ' . $al['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date d'entrée</label>
                        <input type="date" name="date_entree" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Date de sortie</label>
                        <input type="date" name="date_sortie" class="form-control">
                    </div>
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="titulaire_principal" value="1"> Titulaire principal
                        </label>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<!-- Modal Résiliation -->
<div id="modal-terminate" class="modal" style="display:none; position:fixed; top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5); z-index:1000;">
    <div class="modal-content card" style="width:400px; margin: 10% auto;">
        <h2>Résilier le contrat</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
            <input type="hidden" name="action" value="terminate">
            <div class="form-group">
                <label>Motif de résiliation</label>
                <textarea name="motif_resiliation" required class="form-control" rows="4"></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-terminate').style.display='none'">Annuler</button>
                <button type="submit" class="btn btn-danger">Confirmer</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
