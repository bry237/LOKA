<?php

declare(strict_types=1);

require_once __DIR__ . '/LocataireController.php';
require_once dirname(__DIR__, 2) . '/core/Auth.php';

$controller = new LocataireController();
$locataire = $controller->getCurrentLocataire();

if (!$locataire) {
    http_response_code(404);
    exit('Profil locataire introuvable.');
}

$prenom = $locataire['prenom'] ?? '';
$nom = $locataire['nom'] ?? '';
$ville = $locataire['ville'] ?? '';
$adresse = $locataire['adresse'] ?? '';

$pageTitle = 'Accueil';
$activeNav = 'Accueil';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Bonjour, ' . $prenom;
$greetingSubtitle = 'Bienvenue dans votre espace locataire.';
$profileName = trim($prenom . ' ' . $nom);
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));
$extraHead = '<link rel="stylesheet" href="ops.css?v=' . filemtime(__DIR__ . '/ops.css') . '">';

require dirname(__DIR__, 2) . '/layouts/header.php';
?>
    <div class="panel housing-banner">
      <div>
        <p class="eyebrow">Mon logement</p>
        <h2><?= htmlspecialchars($adresse, ENT_QUOTES, 'UTF-8') ?></h2>
        <p><?= htmlspecialchars($ville, ENT_QUOTES, 'UTF-8') ?></p>
      </div>
      <a href="logement.php" class="btn btn-primary">Voir mon logement</a>
    </div>

    <div class="page-header">
      <h1 style="font-size:18px;">Mes services</h1>
      <p>Tout ce dont vous avez besoin pour gérer votre location.</p>
    </div>

    <div class="quick-link-grid">
      <a href="contrats/index.php" class="quick-link-card">
        <div class="quick-link-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg></div>
        <h3>Mon contrat</h3>
        <p>Consultez votre contrat de location.</p>
      </a>

      <a href="paiements/paiement.php" class="quick-link-card">
        <div class="quick-link-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
        <h3>Mes paiements</h3>
        <p>Consultez vos paiements et vos reçus.</p>
      </a>

      <a href="../maintenance/index.php" class="quick-link-card">
        <div class="quick-link-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z"/></svg></div>
        <h3>Maintenance</h3>
        <p>Signalez et suivez vos demandes d'intervention.</p>
      </a>

      <a href="../documents/index.php" class="quick-link-card">
        <div class="quick-link-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg></div>
        <h3>Mes documents</h3>
        <p>Retrouvez vos documents de location.</p>
      </a>

      <a href="notifications/notification.php" class="quick-link-card">
        <div class="quick-link-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>
        <h3>Notifications</h3>
        <p>Consultez les informations importantes.</p>
      </a>

      <a href="detail.php" class="quick-link-card">
        <div class="quick-link-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg></div>
        <h3>Mon profil</h3>
        <p>Consultez et modifiez vos informations.</p>
      </a>
    </div>
<?php
require dirname(__DIR__, 2) . '/layouts/footer.php';
