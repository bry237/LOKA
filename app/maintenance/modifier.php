<?php declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once dirname(__DIR__, 2) . '/core/Auth.php';
require_once __DIR__ . '/MaintenanceController.php';

$user = Authorization::requireRole(['admin', 'gestionnaire']);
$agencyId = (int)$user['id_agence'];

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    header("Location: index.php");
    exit;
}

$controller = new MaintenanceController();
$intervention = $controller->find($agencyId, (int)$id);

if (!$intervention) {
    header("Location: index.php");
    exit;
}

$db = Database::connection();
$biens = $db->query("SELECT id_bien, nom, reference FROM bien WHERE id_agence = $agencyId AND deleted_at IS NULL")->fetchAll(PDO::FETCH_ASSOC);
$locataires = $db->query("SELECT id_locataire, nom, prenom FROM locataire WHERE id_agence = $agencyId AND deleted_at IS NULL")->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$data = [
    'titre' => $intervention['titre'],
    'description' => $intervention['description'] ?? '',
    'priorite' => $intervention['priorite'],
    'cout_estime' => $intervention['cout_estime'] ?? '',
    'id_bien' => $intervention['id_bien'] ?? '',
    'id_locataire' => $intervention['id_locataire'] ?? ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    $data = [
        'titre' => $_POST['titre'] ?? '',
        'description' => $_POST['description'] ?? '',
        'priorite' => $_POST['priorite'] ?? 'MEDIUM',
        'cout_estime' => $_POST['cout_estime'] ?? '',
        'id_bien' => $_POST['id_bien'] ?? '',
        'id_locataire' => $_POST['id_locataire'] ?? ''
    ];
    
    // empty to null for fk
    if ($data['id_bien'] === '') $data['id_bien'] = null;
    if ($data['id_locataire'] === '') $data['id_locataire'] = null;

    [$validationErrors, $updatedId] = $controller->update($agencyId, (int)$id, $data);
    if (empty($validationErrors)) {
        header("Location: detail.php?id=" . $id);
        exit;
    }
    $errors = $validationErrors;
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Modifier Intervention | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php $currentPage = 'maintenance'; require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <a href="detail.php?id=<?= $id ?>" class="btn btn-secondary" style="margin-right: 1rem;">&larr; Annuler</a>
                <div>
                    <div class="eyebrow">Technique</div>
                    <h1>Modifier : <?= htmlspecialchars($intervention['titre'], ENT_QUOTES, 'UTF-8') ?></h1>
                </div>
            </div>
        </header>
        <main class="page">
            <div class="form-container" style="max-width: 800px; background: white; padding: 2rem; border-radius: 8px; border: 1px solid var(--border-color);">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Titre *</label>
                        <input type="text" name="titre" class="form-control <?= isset($errors['titre']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string)$data['titre'], ENT_QUOTES, 'UTF-8') ?>" required>
                        <?php if (isset($errors['titre'])): ?><div class="invalid-feedback"><?= $errors['titre'] ?></div><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars((string)$data['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">Bien *</label>
                            <select name="id_bien" class="form-control <?= isset($errors['id_bien']) ? 'is-invalid' : '' ?>" required>
                                <option value="">Sélectionner un bien...</option>
                                <?php foreach ($biens as $b): ?>
                                    <option value="<?= $b['id_bien'] ?>" <?= $data['id_bien'] == $b['id_bien'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['nom'] . ' (' . $b['reference'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['id_bien'])): ?><div class="invalid-feedback"><?= $errors['id_bien'] ?></div><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Locataire (Optionnel)</label>
                            <select name="id_locataire" class="form-control">
                                <option value="">Sélectionner un locataire...</option>
                                <?php foreach ($locataires as $l): ?>
                                    <option value="<?= $l['id_locataire'] ?>" <?= $data['id_locataire'] == $l['id_locataire'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($l['prenom'] . ' ' . $l['nom'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Priorité *</label>
                            <select name="priorite" class="form-control <?= isset($errors['priorite']) ? 'is-invalid' : '' ?>" required>
                                <option value="LOW" <?= $data['priorite'] === 'LOW' ? 'selected' : '' ?>>Faible</option>
                                <option value="MEDIUM" <?= $data['priorite'] === 'MEDIUM' ? 'selected' : '' ?>>Moyenne</option>
                                <option value="HIGH" <?= $data['priorite'] === 'HIGH' ? 'selected' : '' ?>>Haute</option>
                                <option value="URGENT" <?= $data['priorite'] === 'URGENT' ? 'selected' : '' ?>>Urgente</option>
                            </select>
                            <?php if (isset($errors['priorite'])): ?><div class="invalid-feedback"><?= $errors['priorite'] ?></div><?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Coût estimé (€)</label>
                            <input type="number" step="0.01" name="cout_estime" class="form-control <?= isset($errors['cout_estime']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string)$data['cout_estime'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (isset($errors['cout_estime'])): ?><div class="invalid-feedback"><?= $errors['cout_estime'] ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="form-actions mt-4">
                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</div>
</body>
</html>
