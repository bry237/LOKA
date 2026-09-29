<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Auth.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once __DIR__ . '/DocumentController.php';

$user = Authorization::requireRole(['admin', 'gestionnaire']);
$agencyId = (int)$user['id_agence'];

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    [$errors, $id] = DocumentController::create($agencyId, $_POST, $_FILES);
    if (empty($errors)) {
        header('Location: /LOKA/app/documents/index.php');
        exit;
    }
}

$types = DocumentModel::types();

// Fetch entities for dropdowns
$db = Database::connection();

$biens = $db->query("SELECT id_bien, nom FROM bien WHERE id_agence = $agencyId ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$contrats = $db->query("SELECT id_contrat, numero FROM contrat WHERE id_agence = $agencyId ORDER BY numero")->fetchAll(PDO::FETCH_ASSOC);
$proprietaires = $db->query("SELECT id_proprietaire, nom, prenom FROM proprietaire WHERE id_agence = $agencyId ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$locataires = $db->query("SELECT id_locataire, nom, prenom FROM locataire WHERE id_agence = $agencyId ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$interventions = $db->query("SELECT id_intervention, titre FROM intervention WHERE id_agence = $agencyId ORDER BY titre")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Importer un document | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'documents'; require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <a href="/LOKA/app/documents/index.php" class="back-link">← Retour</a>
                <span class="eyebrow">Documents</span>
                <h1>Importer un document</h1>
            </div>
        </header>
        <main class="page">
            <div class="form-card">
                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if (!empty($errors['entity'])): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errors['entity'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="POST" action="importer.php" enctype="multipart/form-data">
                    <?= Auth::csrfToken() ?>

                    <div class="form-group">
                        <label for="nom">Nom du document <span class="required">*</span></label>
                        <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (isset($errors['nom'])): ?>
                            <span class="error-text"><?= htmlspecialchars($errors['nom'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="id_type_document">Type de document <span class="required">*</span></label>
                        <select id="id_type_document" name="id_type_document" required>
                            <option value="">Sélectionner un type</option>
                            <?php foreach ($types as $type): ?>
                                <option value="<?= $type['id_type_document'] ?>" <?= (($_POST['id_type_document'] ?? '') == $type['id_type_document']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type['nom'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['id_type_document'])): ?>
                            <span class="error-text"><?= htmlspecialchars($errors['id_type_document'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="entity_type">Entité rattachée <span class="required">*</span></label>
                        <select id="entity_type" name="entity_type" onchange="updateEntityDropdown()" required>
                            <option value="">Sélectionner une entité</option>
                            <option value="bien">Bien</option>
                            <option value="contrat">Contrat</option>
                            <option value="proprietaire">Propriétaire</option>
                            <option value="locataire">Locataire</option>
                            <option value="intervention">Intervention</option>
                        </select>
                    </div>

                    <div class="form-group entity-select" id="group_bien" style="display: none;">
                        <label for="id_bien">Bien</label>
                        <select id="id_bien" name="id_bien">
                            <option value="">Sélectionner un bien</option>
                            <?php foreach ($biens as $bien): ?>
                                <option value="<?= $bien['id_bien'] ?>"><?= htmlspecialchars($bien['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group entity-select" id="group_contrat" style="display: none;">
                        <label for="id_contrat">Contrat</label>
                        <select id="id_contrat" name="id_contrat">
                            <option value="">Sélectionner un contrat</option>
                            <?php foreach ($contrats as $contrat): ?>
                                <option value="<?= $contrat['id_contrat'] ?>"><?= htmlspecialchars((string)$contrat['numero'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group entity-select" id="group_proprietaire" style="display: none;">
                        <label for="id_proprietaire">Propriétaire</label>
                        <select id="id_proprietaire" name="id_proprietaire">
                            <option value="">Sélectionner un propriétaire</option>
                            <?php foreach ($proprietaires as $proprietaire): ?>
                                <option value="<?= $proprietaire['id_proprietaire'] ?>"><?= htmlspecialchars($proprietaire['prenom'] . ' ' . $proprietaire['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group entity-select" id="group_locataire" style="display: none;">
                        <label for="id_locataire">Locataire</label>
                        <select id="id_locataire" name="id_locataire">
                            <option value="">Sélectionner un locataire</option>
                            <?php foreach ($locataires as $locataire): ?>
                                <option value="<?= $locataire['id_locataire'] ?>"><?= htmlspecialchars($locataire['prenom'] . ' ' . $locataire['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group entity-select" id="group_intervention" style="display: none;">
                        <label for="id_intervention">Intervention</label>
                        <select id="id_intervention" name="id_intervention">
                            <option value="">Sélectionner une intervention</option>
                            <?php foreach ($interventions as $intervention): ?>
                                <option value="<?= $intervention['id_intervention'] ?>"><?= htmlspecialchars($intervention['titre'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="fichier">Fichier <span class="required">*</span></label>
                        <input type="file" id="fichier" name="fichier" required accept=".pdf,.jpeg,.jpg,.png,.docx,.xlsx,.txt">
                        <small class="help-text">Formats acceptés : PDF, JPEG, PNG, DOCX, XLSX, TXT (Max 10 Mo).</small>
                        <?php if (isset($errors['fichier'])): ?>
                            <span class="error-text"><?= htmlspecialchars($errors['fichier'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Importer</button>
                        <a href="/LOKA/app/documents/index.php" class="btn btn-outline">Annuler</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>

<script>
function updateEntityDropdown() {
    // Hide all entity selects and clear their values
    document.querySelectorAll('.entity-select').forEach(function(el) {
        el.style.display = 'none';
        var select = el.querySelector('select');
        if (select) select.value = '';
    });
    
    // Show selected entity
    var type = document.getElementById('entity_type').value;
    if (type) {
        var group = document.getElementById('group_' + type);
        if (group) group.style.display = 'block';
    }
}
</script>
</body>
</html>
