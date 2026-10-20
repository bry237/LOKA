<?php

declare(strict_types=1);

require_once __DIR__ . '/MaintenanceController.php';

$controller = new MaintenanceController();

$idIntervention = isset($_POST['id']) ? (int) $_POST['id'] : (int) ($_GET['id'] ?? 0);

if ($idIntervention <= 0) {
    header('Location: index.php');
    exit;
}

$intervention = $controller->getRequest($idIntervention);

if (!$intervention) {
    header('Location: index.php');
    exit;
}

if ($intervention['statut'] !== 'OPEN') {
    header('Location: detail.php?id=' . $idIntervention . '&error=non_supprimable');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        exit('Votre session a expiré. Rechargez la page.');
    }

    $success = $controller->deleteRequest($idIntervention);

    if ($success) {
        header('Location: index.php?success=supprimee');
        exit;
    }
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$locataire = $controller->getCurrentLocataire();

if (!$locataire) {
    header('Location: index.php');
    exit;
}

$prenom = $locataire['prenom'] ?? '';
$nom = $locataire['nom'] ?? '';

$pageTitle = 'Supprimer la demande';
$activeNav = 'Maintenance';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Maintenance';
$greetingSubtitle = 'Suppression d\'une demande.';
$profileName = trim($prenom . ' ' . $nom);
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));

require dirname(__DIR__, 2) . '/layouts/header.php';
?>
    <div class="panel" style="max-width:600px;">
      <div class="panel-header"><div class="panel-title">Supprimer cette demande ?</div></div>
      <p style="padding:0 18px;font-size:12.5px;color:var(--text);">Cette action est définitive.</p>

      <div style="padding:14px 18px;">
        <h3 style="margin:0 0 8px;font-size:15px;color:var(--heading);"><?= e($intervention['titre']) ?></h3>
        <p style="margin:0;font-size:13px;color:var(--text);line-height:1.5;"><?= nl2br(e($intervention['description'])) ?></p>
      </div>

      <div class="feedback error" style="margin:0 18px 14px;">La suppression supprimera également les commentaires et documents éventuellement associés à cette intervention.</div>

      <form method="POST">
        <input type="hidden" name="id" value="<?= $idIntervention ?>">
        <input type="hidden" name="csrf_token" value="<?= e(Auth::csrfToken()) ?>">
        <div class="form-actions">
          <a href="detail.php?id=<?= $idIntervention ?>" class="btn">Annuler</a>
          <button type="submit" class="btn btn-danger">Supprimer</button>
        </div>
      </form>
    </div>
<?php
require dirname(__DIR__, 2) . '/layouts/footer.php';
