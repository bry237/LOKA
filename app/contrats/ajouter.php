<?php declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Auth.php';
require_once __DIR__ . '/ContratController.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = $user['id_agence'];

require_once dirname(__DIR__) . '/biens/BienModel.php';
$bienModel = new BienModel();
$biens = $bienModel->list($agencyId);

$errors = [];
$input = [
    'numero' => '',
    'id_bien' => '',
    'type_contrat' => '',
    'date_debut' => '',
    'date_fin' => '',
    'loyer' => '',
    'charges' => '',
    'depot_garantie' => '',
    'frequence_paiement' => 'MONTHLY',
    'jour_echeance' => '1',
    'statut' => 'DRAFT',
    'indice_revision' => '',
    'date_prochaine_revision' => '',
    'preavis_mois' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $input = array_merge($input, $_POST);
    
    $controller = new ContratController();
    [$errs, $id] = $controller->create($agencyId, $input);
    
    if (empty($errs) && $id) {
        header('Location: /LOKA/app/contrats/detail.php?id=' . $id);
        exit;
    }
    $errors = $errs;
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ajouter un contrat | LOKA</title>
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
                <span>Nouveau</span>
            </div>
        </header>

        <main class="page">
            <div class="card">
                <h2>Créer un contrat</h2>
                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="POST" class="form-grid">
                    <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                    
                    <div class="form-group full-width">
                        <h3>Informations Générales</h3>
                    </div>

                    <div class="form-group">
                        <label>Numéro de contrat *</label>
                        <input type="text" name="numero" required class="form-control" value="<?= htmlspecialchars($input['numero'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if(isset($errors['numero'])): ?><div class="error-text"><?= htmlspecialchars($errors['numero'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Bien *</label>
                        <select name="id_bien" required class="form-control">
                            <option value="">-- Sélectionner un bien --</option>
                            <?php foreach ($biens as $b): ?>
                                <option value="<?= $b['id_bien'] ?>" <?= $input['id_bien'] == $b['id_bien'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['reference'] . ' - ' . $b['nom'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if(isset($errors['id_bien'])): ?><div class="error-text"><?= htmlspecialchars($errors['id_bien'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Type de contrat</label>
                        <select name="type_contrat" class="form-control">
                            <option value="">-- Sélectionner --</option>
                            <option value="HABITATION_VIDE" <?= $input['type_contrat'] === 'HABITATION_VIDE' ? 'selected' : '' ?>>Habitation vide</option>
                            <option value="HABITATION_MEUBLE" <?= $input['type_contrat'] === 'HABITATION_MEUBLE' ? 'selected' : '' ?>>Habitation meublée</option>
                            <option value="COMMERCIAL" <?= $input['type_contrat'] === 'COMMERCIAL' ? 'selected' : '' ?>>Commercial</option>
                            <option value="PROFESSIONNEL" <?= $input['type_contrat'] === 'PROFESSIONNEL' ? 'selected' : '' ?>>Professionnel</option>
                            <option value="SAISONNIER" <?= $input['type_contrat'] === 'SAISONNIER' ? 'selected' : '' ?>>Saisonnier</option>
                        </select>
                        <?php if(isset($errors['type_contrat'])): ?><div class="error-text"><?= htmlspecialchars($errors['type_contrat'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Statut</label>
                        <select name="statut" class="form-control">
                            <option value="DRAFT" <?= $input['statut'] === 'DRAFT' ? 'selected' : '' ?>>Brouillon</option>
                            <option value="ACTIVE" <?= $input['statut'] === 'ACTIVE' ? 'selected' : '' ?>>Actif</option>
                        </select>
                        <?php if(isset($errors['statut'])): ?><div class="error-text"><?= htmlspecialchars($errors['statut'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Date de début *</label>
                        <input type="date" name="date_debut" required class="form-control" value="<?= htmlspecialchars($input['date_debut'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if(isset($errors['date_debut'])): ?><div class="error-text"><?= htmlspecialchars($errors['date_debut'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Date de fin</label>
                        <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($input['date_fin'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="form-group full-width">
                        <h3>Finances</h3>
                    </div>

                    <div class="form-group">
                        <label>Loyer HC *</label>
                        <input type="number" step="0.01" name="loyer" required class="form-control" value="<?= htmlspecialchars((string)$input['loyer'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if(isset($errors['loyer'])): ?><div class="error-text"><?= htmlspecialchars($errors['loyer'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Charges</label>
                        <input type="number" step="0.01" name="charges" class="form-control" value="<?= htmlspecialchars((string)$input['charges'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if(isset($errors['charges'])): ?><div class="error-text"><?= htmlspecialchars($errors['charges'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Dépôt de garantie</label>
                        <input type="number" step="0.01" name="depot_garantie" class="form-control" value="<?= htmlspecialchars((string)$input['depot_garantie'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if(isset($errors['depot_garantie'])): ?><div class="error-text"><?= htmlspecialchars($errors['depot_garantie'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Fréquence de paiement</label>
                        <select name="frequence_paiement" class="form-control">
                            <option value="MONTHLY" <?= $input['frequence_paiement'] === 'MONTHLY' ? 'selected' : '' ?>>Mensuelle</option>
                            <option value="QUARTERLY" <?= $input['frequence_paiement'] === 'QUARTERLY' ? 'selected' : '' ?>>Trimestrielle</option>
                            <option value="YEARLY" <?= $input['frequence_paiement'] === 'YEARLY' ? 'selected' : '' ?>>Annuelle</option>
                        </select>
                        <?php if(isset($errors['frequence_paiement'])): ?><div class="error-text"><?= htmlspecialchars($errors['frequence_paiement'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Jour d'échéance (1-31)</label>
                        <input type="number" min="1" max="31" name="jour_echeance" class="form-control" value="<?= htmlspecialchars((string)$input['jour_echeance'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="form-group full-width">
                        <h3>Révision & Préavis</h3>
                    </div>

                    <div class="form-group">
                        <label>Indice de révision</label>
                        <input type="text" name="indice_revision" class="form-control" value="<?= htmlspecialchars((string)$input['indice_revision'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="form-group">
                        <label>Prochaine révision</label>
                        <input type="date" name="date_prochaine_revision" class="form-control" value="<?= htmlspecialchars((string)$input['date_prochaine_revision'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="form-group">
                        <label>Préavis (mois)</label>
                        <input type="number" min="1" max="12" name="preavis_mois" class="form-control" value="<?= htmlspecialchars((string)$input['preavis_mois'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="form-actions full-width">
                        <a href="/LOKA/app/contrats/index.php" class="btn btn-secondary">Annuler</a>
                        <button type="submit" class="btn btn-primary">Créer le contrat</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>
</body>
</html>
