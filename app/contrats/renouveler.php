<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/Authorization.php';
require_once __DIR__ . '/ContratModel.php';

$user = Authorization::requireRole('Administrateur plateforme', 'Administrateur agence', 'Gestionnaire immobilier');
$agencyId = (int) ($user['id_agence'] ?? 0);

$idContrat = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$idContrat || $agencyId < 1) {
    header('Location: index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors['general'] = 'Votre session a expiré.';
    } else {
        $dateDebut = $_POST['date_debut'] ?? '';
        $dateFin = $_POST['date_fin'] ?? '';
        
        if (empty($dateDebut) || empty($dateFin)) {
            $errors['general'] = 'Les dates sont requises.';
        } else {
            $newId = ContratModel::duplicate($agencyId, $idContrat, $dateDebut, $dateFin);
            header('Location: detail.php?id=' . $newId);
            exit;
        }
    }
}

$csrfToken = Auth::csrfToken();
$currentPage = 'contrats';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Renouveler le contrat | LOKA</title>
    <link rel="stylesheet" href="/LOKA/public/assets/css/global.css">
    <link rel="stylesheet" href="/LOKA/app/biens/bien.css">
</head>
<body>
<div class="app-shell">
    <?php require_once dirname(__DIR__, 2) . '/layouts/sidebar.php'; ?>
    <div class="content">
        <header class="topbar">
            <div class="topbar-left">
                <span class="topbar-eyebrow">Contrats</span>
                <h1 class="topbar-title">Renouveler le contrat</h1>
            </div>
        </header>
        <main class="page">
            <?php if (isset($errors['general'])): ?><p role="alert"><?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" value="<?= $idContrat ?>">
                <label>Nouvelle date de début: <input type="date" name="date_debut" required></label>
                <label>Nouvelle date de fin: <input type="date" name="date_fin" required></label>
                <button type="submit">Renouveler</button>
            </form>
        </main>
    </div>
</div>
</body>
</html>
