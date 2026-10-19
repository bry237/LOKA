<?php

declare(strict_types=1);

require_once __DIR__ . '/DocumentController.php';

$controller = new DocumentController();
$locataire = $controller->getCurrentLocataire();

$idDocument = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idDocument) {
    http_response_code(400);
    exit('Document invalide.');
}

$document = $controller->getDocument($idDocument);

if (!$document) {
    http_response_code(404);
    exit('Document introuvable ou accès refusé.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deletedDocument = $controller->deleteDocument((int) $idDocument);

    if (!$deletedDocument) {
        http_response_code(404);
        exit('Document introuvable ou accès refusé.');
    }

    $chemin = $deletedDocument['chemin_stockage'];

    if ($chemin) {
        $projectRoot = dirname(__DIR__, 2);

        if (!str_starts_with($chemin, DIRECTORY_SEPARATOR)) {
            $chemin = $projectRoot . DIRECTORY_SEPARATOR . $chemin;
        }

        $realPath = realpath($chemin);

        if ($realPath && is_file($realPath)) {
            unlink($realPath);
        }
    }

    header('Location: index.php?deleted=1');
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$prenom = (string) ($locataire['prenom'] ?? '');
$nom = (string) ($locataire['nom'] ?? '');

$pageTitle = 'Supprimer un document';
$activeNav = 'Documents';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Supprimer le document';
$greetingSubtitle = 'Cette action est irréversible.';
$profileName = trim($prenom . ' ' . $nom);
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));

require dirname(__DIR__, 2) . '/layouts/header.php';
?>
    <div class="panel" style="max-width:560px;">
      <div class="panel-header"><div class="panel-title">Supprimer le document</div></div>
      <div style="padding:0 18px 18px;">
        <p style="font-size:13px;color:var(--text);margin:0 0 6px;">Voulez-vous vraiment supprimer le document :</p>
        <p style="font-size:14px;color:var(--heading);font-weight:700;margin:0 0 10px;"><?= e($document['nom_original'] ?: $document['nom']) ?></p>
        <div class="feedback error">Cette action est irréversible.</div>
      </div>
      <form method="POST" action="supprimer.php?id=<?= (int) $idDocument ?>">
        <div class="form-actions">
          <a href="detail.php?id=<?= (int) $idDocument ?>" class="btn">Annuler</a>
          <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
        </div>
      </form>
    </div>
<?php
require dirname(__DIR__, 2) . '/layouts/footer.php';
