<?php

declare(strict_types=1);

require_once __DIR__ . '/PaiementController.php';
require_once dirname(__DIR__, 3) . '/core/Auth.php';

$controller = new PaiementController();
$locataire = $controller->getCurrentLocataire();

$idEcheance = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idEcheance) {
    http_response_code(400);
    exit('Échéance invalide.');
}

$echeance = $controller->getMyEcheance($idEcheance);

if ($echeance === null) {
    http_response_code(404);
    exit('Échéance introuvable.');
}

$montantRestant = $controller->getMontantRestant($idEcheance);

if ($montantRestant <= 0) {
    header('Location: detail.php?id=' . $idEcheance);
    exit;
}

$dateEcheance = !empty($echeance['date_echeance']) ? date('d/m/Y', strtotime($echeance['date_echeance'])) : '-';

$prenom = trim((string) ($locataire['prenom'] ?? ''));
$nom = trim((string) ($locataire['nom'] ?? ''));

$pageTitle = 'Payer';
$activeNav = 'Mes paiements';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Payer mon échéance';
$greetingSubtitle = 'Choisissez votre moyen de paiement.';
$profileName = trim($prenom . ' ' . $nom) ?: 'Locataire';
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'L';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/locataires/ops.css?v=' . filemtime(dirname(__DIR__) . '/ops.css') . '">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <a class="detail-back" href="detail.php?id=<?= $idEcheance ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour au détail
    </a>

    <div class="detail-grid">
      <div class="panel">
        <div class="panel-header"><div class="panel-title">Montant à payer</div></div>
        <div style="padding:0 18px 18px;">
          <div class="mini-stat" style="margin-bottom:14px;">
            <div class="num"><?= number_format($montantRestant, 2, ',', ' ') ?> €</div>
            <div class="lbl">Montant restant</div>
          </div>
          <p style="font-size:13px;color:var(--text);margin:0;">Date d'échéance : <strong style="color:var(--heading);"><?= htmlspecialchars($dateEcheance, ENT_QUOTES, 'UTF-8') ?></strong></p>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header"><div class="panel-title">Moyen de paiement</div></div>
        <?php if (!empty($_GET['error'])): ?>
          <div class="feedback error" style="margin:0 18px 14px;"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <form method="post" action="checkout.php">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="id_echeance" value="<?= (int) $idEcheance ?>">
          <div class="choice-card-list">
            <label class="choice-card">
              <input type="radio" name="mode_paiement" value="CARD" checked>
              <div class="choice-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
              <div class="choice-card-text"><strong>Carte bancaire</strong><span>Paiement sécurisé par carte.</span></div>
            </label>
            <label class="choice-card">
              <input type="radio" name="mode_paiement" value="PAYPAL">
              <div class="choice-card-icon" style="font-weight:800;font-size:15px;">P</div>
              <div class="choice-card-text"><strong>PayPal</strong><span>Paiement via votre compte PayPal.</span></div>
            </label>
          </div>
          <div class="form-actions">
            <a href="detail.php?id=<?= $idEcheance ?>" class="btn">Annuler</a>
            <button type="submit" class="btn btn-primary">Payer</button>
          </div>
        </form>
      </div>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
