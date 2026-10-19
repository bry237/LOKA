<?php

declare(strict_types=1);

require_once __DIR__ . '/PaiementController.php';

$controller = new PaiementController();
$locataire = $controller->getCurrentLocataire();

$paiements = $controller->getMyPaiements();
$totalPaye = $controller->getTotalPaye();
$nombrePaiements = $controller->getNombrePaiements();

$prenom = trim((string) ($locataire['prenom'] ?? ''));
$nom = trim((string) ($locataire['nom'] ?? ''));

$pageTitle = 'Historique des paiements';
$activeNav = 'Mes paiements';
$sidebarFile = 'sidebar-locataire.php';
$greetingTitle = 'Historique des paiements';
$greetingSubtitle = 'Consultez l\'ensemble de vos paiements et règlements.';
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

    <div class="mini-stats" style="margin-bottom:18px;">
      <div class="mini-stat"><div class="num"><?= number_format($totalPaye, 2, ',', ' ') ?> €</div><div class="lbl">Total payé</div></div>
      <div class="mini-stat"><div class="num"><?= $nombrePaiements ?></div><div class="lbl">Nombre de paiements</div></div>
    </div>

    <div class="panel table-panel">
      <?php if (empty($paiements)): ?>
        <div class="empty-state" style="padding:48px 20px;">
          <div class="empty-state-icon"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
          <h4>Aucun paiement</h4>
          <p>Vous n'avez encore aucun paiement enregistré.</p>
        </div>
      <?php else: ?>
        <div class="table-scroll">
          <table>
            <thead><tr><th>Date</th><th>Référence</th><th>Mode</th><th>Montant</th><th>Statut</th></tr></thead>
            <tbody>
              <?php foreach ($paiements as $paiement): ?>
                <?php
                $statut = (string) ($paiement['statut'] ?? 'PENDING');
                [$statutLabel, $statutTone] = match ($statut) {
                    'PAID' => ['Payé', 'actif'],
                    'LATE' => ['En retard', 'suspendu'],
                    'CANCELLED' => ['Annulé', 'inactif'],
                    'REFUNDED' => ['Remboursé', 'inactif'],
                    default => ['En attente', 'attente'],
                };
                $datePaiement = !empty($paiement['date_paiement']) ? date('d/m/Y', strtotime($paiement['date_paiement'])) : '-';
                ?>
                <tr>
                  <td class="cell-sub"><?= htmlspecialchars($datePaiement, ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="cell-title"><?= htmlspecialchars((string) ($paiement['reference_transaction'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="cell-sub"><?= htmlspecialchars((string) ($paiement['mode_paiement'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="cell-price"><?= number_format((float) ($paiement['montant'] ?? 0), 2, ',', ' ') ?> €</td>
                  <td><span class="status-badge <?= $statutTone ?>"><?= htmlspecialchars($statutLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
