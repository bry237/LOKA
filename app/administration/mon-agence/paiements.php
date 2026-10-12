<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';
require_once __DIR__ . '/team-presentation.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$filters = [
	'statut' => (string) ($_GET['statut'] ?? ''),
];

$data = MonAgenceController::echeances($idAgence, $filters);
$echeances = $data['list'];
$stats = $data['stats'];
$icons = teamIcons();

$statusLabels = ['PENDING' => 'En attente', 'PARTIAL' => 'Partiel', 'PAID' => 'Payée', 'LATE' => 'En retard', 'CANCELLED' => 'Annulée'];
$statusTones = ['PENDING' => 'attente', 'PARTIAL' => 'attente', 'PAID' => 'actif', 'LATE' => 'suspendu', 'CANCELLED' => 'inactif'];

$pageTitle = 'Paiements';
$activeNav = 'Paiements';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Suivez les échéances et encaissements de votre agence.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <div>
        <h1>Paiements</h1>
        <p>L'échéancier et les encaissements de votre agence.</p>
      </div>
      <a class="btn btn-primary" href="echeance-ajouter.php" data-modal>+ Nouvelle échéance</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="feedback success"><?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="feedback error"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon green"><?= $icons['trending'] ?></div></div>
        <div class="kpi-value"><?= number_format($stats['encaisse_mois'], 0, ',', ' ') ?> €</div>
        <div class="kpi-label">Encaissé ce mois</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon red"><?= $icons['alert'] ?></div></div>
        <div class="kpi-value"><?= $stats['en_retard_count'] ?></div>
        <div class="kpi-label">Échéances en retard (<?= number_format($stats['en_retard_montant'], 0, ',', ' ') ?> €)</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon amber"><?= $icons['card'] ?></div></div>
        <div class="kpi-value"><?= $stats['en_attente_count'] ?></div>
        <div class="kpi-label">En attente de règlement</div>
      </div>
    </div>

    <form class="filters-form" method="get" action="paiements.php">
      <select class="filter-select" name="statut" onchange="this.form.submit()">
        <option value="">Tous les statuts</option>
        <?php foreach ($statusLabels as $key => $label): ?>
          <option value="<?= $key ?>"<?= $filters['statut'] === $key ? ' selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </form>

    <div class="panel table-panel">
      <div class="panel-header"><div class="panel-title">Échéances</div></div>
      <?php if (!$echeances): ?>
        <div class="empty-state">
          <div class="empty-state-icon"><?= $icons['card'] ?></div>
          <h4>Aucune échéance</h4>
          <p>Créez une échéance pour un contrat afin de pouvoir enregistrer un encaissement.</p>
        </div>
      <?php else: ?>
        <div class="table-scroll">
          <table>
            <thead>
              <tr><th>CONTRAT</th><th>BIEN</th><th>LOCATAIRE</th><th>PÉRIODE</th><th>MONTANT</th><th>ÉCHÉANCE</th><th>STATUT</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($echeances as $e): ?>
                <tr>
                  <td class="cell-title"><?= htmlspecialchars($e['contrat_numero'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="cell-sub"><?= htmlspecialchars($e['bien_titre'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="cell-sub"><?= $e['locataire_principal'] ? htmlspecialchars($e['locataire_principal'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
                  <td class="cell-sub"><?= (new DateTimeImmutable($e['periode_debut']))->format('d/m/Y') ?> — <?= (new DateTimeImmutable($e['periode_fin']))->format('d/m/Y') ?></td>
                  <td class="cell-sub"><?= number_format((float) $e['montant_total'], 0, ',', ' ') ?> €</td>
                  <td class="cell-sub"><?= (new DateTimeImmutable($e['date_echeance']))->format('d/m/Y') ?></td>
                  <td>
                    <span class="status-badge <?= $statusTones[$e['statut']] ?? '' ?>"><?= $statusLabels[$e['statut']] ?? $e['statut'] ?></span>
                    <?php if ($e['demande_pending']): ?><span class="status-badge attente" style="margin-left:4px;">Demande envoyée</span><?php endif; ?>
                  </td>
                  <td>
                    <a class="consult-link" href="echeance-detail.php?id=<?= (int) $e['id_echeance'] ?>" data-modal>
                      Consulter
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 15 12 9 18"/></svg>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
