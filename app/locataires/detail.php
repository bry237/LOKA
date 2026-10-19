<?php

declare(strict_types=1);

require_once __DIR__ . '/LocataireController.php';

$controller = new LocataireController();
$locataire = $controller->getCurrentLocataire();

if (!$locataire) {
    http_response_code(404);
    exit('Locataire introuvable.');
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$prenom = $locataire['prenom'] ?? '';
$nom = $locataire['nom'] ?? '';

$pageTitle = 'Mon profil';
$activeNav = 'Mon profil';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Mon profil';
$greetingSubtitle = 'Consultez vos informations personnelles.';
$profileName = trim($prenom . ' ' . $nom);
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));
$extraHead = '<link rel="stylesheet" href="ops.css?v=' . filemtime(__DIR__ . '/ops.css') . '">';

require dirname(__DIR__, 2) . '/layouts/header.php';
?>
    <div class="detail-header">
      <div class="detail-identity">
        <div class="detail-avatar"><?= e($profileInitials) ?></div>
        <div>
          <h1 class="detail-name"><?= e($profileName) ?></h1>
          <p class="detail-sub"><?= e($locataire['email']) ?></p>
        </div>
      </div>
    </div>

    <div class="panel" style="margin-bottom:18px;">
      <div class="panel-header">
        <div class="panel-title">Informations personnelles</div>
        <a class="panel-link" href="modifier.php?section=personnel">Modifier</a>
      </div>
      <div class="info-list">
        <div class="info-row"><span class="info-label">Nom</span><span class="info-value"><?= e($locataire['nom']) ?></span></div>
        <div class="info-row"><span class="info-label">Prénom</span><span class="info-value"><?= e($locataire['prenom']) ?></span></div>
        <div class="info-row"><span class="info-label">Email</span><span class="info-value"><?= e($locataire['email']) ?></span></div>
        <div class="info-row"><span class="info-label">Téléphone</span><span class="info-value"><?= e($locataire['telephone'] ?? 'Non renseigné') ?></span></div>
        <div class="info-row"><span class="info-label">Date de naissance</span><span class="info-value"><?= !empty($locataire['date_naissance']) ? e($locataire['date_naissance']) : 'Non renseignée' ?></span></div>
        <div class="info-row"><span class="info-label">Profession</span><span class="info-value"><?= e($locataire['profession'] ?? 'Non renseignée') ?></span></div>
      </div>
    </div>

    <div class="panel" style="margin-bottom:18px;">
      <div class="panel-header">
        <div class="panel-title">Adresse</div>
        <a class="panel-link" href="modifier.php?section=adresse">Modifier</a>
      </div>
      <div class="info-list">
        <div class="info-row"><span class="info-label">Adresse</span><span class="info-value"><?= e($locataire['adresse'] ?? 'Non renseignée') ?></span></div>
        <div class="info-row"><span class="info-label">Code postal</span><span class="info-value"><?= e($locataire['code_postal'] ?? 'Non renseigné') ?></span></div>
        <div class="info-row"><span class="info-label">Ville</span><span class="info-value"><?= e($locataire['ville'] ?? 'Non renseignée') ?></span></div>
        <div class="info-row"><span class="info-label">Pays</span><span class="info-value"><?= e($locataire['pays'] ?? 'Non renseigné') ?></span></div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header">
        <div class="panel-title">Situation financière</div>
        <a class="panel-link" href="modifier.php?section=financiere">Modifier</a>
      </div>
      <div class="info-list">
        <div class="info-row"><span class="info-label">Revenu mensuel</span><span class="info-value"><?= $locataire['revenu_mensuel'] !== null ? e(number_format((float) $locataire['revenu_mensuel'], 2, ',', ' ') . ' €') : 'Non renseigné' ?></span></div>
        <div class="info-row"><span class="info-label">Statut</span><span class="info-value"><?= e($locataire['statut'] ?? 'Non renseigné') ?></span></div>
      </div>
    </div>
<?php
require dirname(__DIR__, 2) . '/layouts/footer.php';
