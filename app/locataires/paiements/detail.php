<?php

declare(strict_types=1);

require_once __DIR__ . '/PaiementController.php';

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

$statut = (string) ($echeance['statut'] ?? 'PENDING');

[$statutLabel, $statutTone] = match ($statut) {
    'PAID' => ['Payée', 'actif'],
    'LATE' => ['En retard', 'suspendu'],
    'CANCELLED' => ['Annulée', 'inactif'],
    default => ['À payer', 'attente'],
};

$montantTotal = (float) ($echeance['montant_total'] ?? 0);
$dateDebut = !empty($echeance['periode_debut']) ? date('d/m/Y', strtotime($echeance['periode_debut'])) : '-';
$dateFin = !empty($echeance['periode_fin']) ? date('d/m/Y', strtotime($echeance['periode_fin'])) : '-';
$dateEcheance = !empty($echeance['date_echeance']) ? date('d/m/Y', strtotime($echeance['date_echeance'])) : '-';

$retourPaiement = (string) ($_GET['paiement'] ?? '');
$messagesRetour = [
    'success' => ['success', 'Paiement reçu, merci ! Votre quittance est disponible dans « Quittances ».'],
    'pending' => ['success', 'Paiement en cours de confirmation par la banque. Cette page se mettra à jour dès sa validation.'],
    'cancel' => ['error', 'Paiement annulé : aucun montant n’a été débité.'],
    'error' => ['error', 'Le paiement n’a pas pu être vérifié. Si vous avez été débité, contactez votre agence.'],
];

$loyer = (float) ($echeance['montant_loyer'] ?? 0);
$charges = (float) ($echeance['montant_charges'] ?? 0);

$prenom = trim((string) ($locataire['prenom'] ?? ''));
$nom = trim((string) ($locataire['nom'] ?? ''));

$pageTitle = 'Détail du paiement';
$activeNav = 'Mes paiements';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Détail de l’échéance';
$greetingSubtitle = 'Période du ' . $dateDebut . ' au ' . $dateFin . '.';
$profileName = trim($prenom . ' ' . $nom) ?: 'Locataire';
$profileRole = 'Locataire';
$profileInitials = mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: 'L';
$extraHead = '<link rel="stylesheet" href="/LOKA/app/locataires/ops.css?v=' . filemtime(dirname(__DIR__) . '/ops.css') . '">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <a class="detail-back" href="paiement.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour à mes paiements
    </a>

    <?php if (isset($messagesRetour[$retourPaiement])): ?>
      <div class="feedback <?= $messagesRetour[$retourPaiement][0] ?>"><?= htmlspecialchars($messagesRetour[$retourPaiement][1], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-header">
        <div>
          <p class="panel-sub" style="margin:0 0 2px;">DÉTAIL DE L'ÉCHÉANCE</p>
          <div class="panel-title">Échéance de loyer</div>
        </div>
        <span class="status-badge <?= $statutTone ?>"><?= htmlspecialchars($statutLabel, ENT_QUOTES, 'UTF-8') ?></span>
      </div>

      <div class="mini-stats cols-4" style="padding:4px 18px 18px;">
        <div class="mini-stat"><div class="num" style="font-size:14px;"><?= htmlspecialchars($dateEcheance, ENT_QUOTES, 'UTF-8') ?></div><div class="lbl">Date d'échéance</div></div>
        <div class="mini-stat"><div class="num" style="font-size:14px;"><?= htmlspecialchars((string) ($echeance['numero_contrat'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div><div class="lbl">Contrat</div></div>
        <div class="mini-stat"><div class="num"><?= number_format($loyer, 0, ',', ' ') ?> €</div><div class="lbl">Loyer</div></div>
        <div class="mini-stat"><div class="num"><?= number_format($charges, 0, ',', ' ') ?> €</div><div class="lbl">Charges</div></div>
      </div>

      <div class="info-list">
        <div class="info-row"><span class="info-label">Montant total</span><span class="info-value"><?= number_format($montantTotal, 2, ',', ' ') ?> €</span></div>
        <div class="info-row"><span class="info-label">Montant restant</span><span class="info-value"><?= number_format($montantRestant, 2, ',', ' ') ?> €</span></div>
        <div class="info-row"><span class="info-label">À payer</span><span class="info-value" style="font-size:16px;"><?= number_format($montantRestant, 2, ',', ' ') ?> €</span></div>
      </div>

      <div class="form-actions">
        <?php if ($montantRestant > 0 && $statut !== 'CANCELLED'): ?>
          <a href="payer.php?id=<?= $idEcheance ?>" class="btn btn-primary">Payer cette échéance</a>
        <?php endif; ?>
        <a href="historique.php" class="btn">Voir mon historique</a>
      </div>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
