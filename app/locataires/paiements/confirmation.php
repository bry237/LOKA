<?php

declare(strict_types=1);

require_once __DIR__ . '/PaiementController.php';

$controller = new PaiementController();
$locataire = $controller->getCurrentLocataire();

$idEcheance = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$mode = (string) ($_GET['mode'] ?? '');

if (!$idEcheance) {
    http_response_code(400);
    exit('Échéance invalide.');
}

if ($mode !== 'BANK_TRANSFER') {
    http_response_code(400);
    exit('Mode de paiement invalide.');
}

$echeance = $controller->getMyEcheance($idEcheance);

if ($echeance === null) {
    http_response_code(404);
    exit('Échéance introuvable.');
}

$montantRestant = $controller->getMontantRestant($idEcheance);

$prenom = trim((string) ($locataire['prenom'] ?? ''));
$nom = trim((string) ($locataire['nom'] ?? ''));

$pageTitle = 'Virement bancaire';
$activeNav = 'Mes paiements';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Effectuer votre virement';
$greetingSubtitle = 'Virement bancaire';
$profileName = trim($prenom . ' ' . $nom) ?: 'Locataire';
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'L';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="panel" style="max-width:560px;margin:0 auto;text-align:center;padding:32px;">
      <div class="quick-link-icon" style="width:56px;height:56px;border-radius:50%;margin:0 auto 16px;">
        <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V10l7-5 7 5v11"/><path d="M9 21v-6h6v6"/><path d="M4 10h16"/></svg>
      </div>
      <p style="font-size:13px;color:var(--text);margin:0 0 20px;">Votre paiement sera enregistré comme <strong style="color:var(--heading);">en attente</strong> jusqu'à validation du virement par l'agence.</p>

      <div class="info-list" style="text-align:left;">
        <div class="info-row"><span class="info-label">Montant</span><span class="info-value"><?= number_format($montantRestant, 2, ',', ' ') ?> €</span></div>
        <div class="info-row"><span class="info-label">Mode</span><span class="info-value">Virement bancaire</span></div>
        <div class="info-row"><span class="info-label">Statut</span><span class="info-value">En attente de validation</span></div>
      </div>

      <p style="font-size:12.5px;color:#8AA0A0;margin:18px 0 0;">Les coordonnées bancaires de l'agence devront être affichées ici lorsque leur configuration sera disponible.</p>

      <div class="form-actions" style="justify-content:center;">
        <a href="detail.php?id=<?= $idEcheance ?>" class="btn">Retour</a>
        <a href="historique.php" class="btn btn-primary">Voir mon historique</a>
      </div>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
