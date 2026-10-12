<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';
require_once __DIR__ . '/team-presentation.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$filters = [
	'search' => trim((string) ($_GET['search'] ?? '')),
	'statut' => (string) ($_GET['statut'] ?? ''),
];

$data = MonAgenceController::contrats($idAgence, $filters);
$contrats = $data['list'];
$stats = $data['stats'];
$icons = teamIcons();

$statusLabels = ['DRAFT' => 'Brouillon', 'ACTIVE' => 'Actif', 'EXPIRED' => 'Expiré', 'RENEWED' => 'Renouvelé', 'TERMINATED' => 'Résilié', 'ARCHIVED' => 'Archivé'];
$statusTones = ['DRAFT' => 'attente', 'ACTIVE' => 'actif', 'EXPIRED' => 'suspendu', 'RENEWED' => 'actif', 'TERMINATED' => 'inactif', 'ARCHIVED' => 'inactif'];

$renderResults = function () use ($contrats, $statusLabels, $statusTones, $icons): void {
	?>
	<div class="panel table-panel">
		<div class="panel-header"><div class="panel-title">Contrats</div></div>
		<?php if (!$contrats): ?>
			<div class="empty-state">
				<div class="empty-state-icon"><?= $icons['card'] ?></div>
				<h4>Aucun contrat</h4>
				<p>Les contrats de location de votre agence apparaîtront ici.</p>
			</div>
		<?php else: ?>
			<div class="table-scroll">
				<table>
					<thead>
						<tr><th>NUMÉRO</th><th>BIEN</th><th>LOCATAIRE</th><th>LOYER</th><th>DATE DE FIN</th><th>STATUT</th><th></th></tr>
					</thead>
					<tbody>
						<?php foreach ($contrats as $c): ?>
							<tr>
								<td class="cell-title"><?= htmlspecialchars($c['numero'], ENT_QUOTES, 'UTF-8') ?></td>
								<td class="cell-sub"><?= htmlspecialchars($c['bien_titre'] . ' (' . $c['bien_reference'] . ')', ENT_QUOTES, 'UTF-8') ?></td>
								<td class="cell-sub"><?= $c['locataire_principal'] ? htmlspecialchars($c['locataire_principal'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
								<td class="cell-sub"><?= number_format((float) $c['loyer'], 0, ',', ' ') ?> €</td>
								<td class="cell-sub"><?= $c['date_fin'] ? (new DateTimeImmutable($c['date_fin']))->format('d/m/Y') : '—' ?></td>
								<td><span class="status-badge <?= $statusTones[$c['statut']] ?? '' ?>"><?= $statusLabels[$c['statut']] ?? $c['statut'] ?></span></td>
								<td>
									<a class="consult-link" href="contrat-detail.php?id=<?= (int) $c['id_contrat'] ?>" data-modal>
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
};

if (($_GET['ajax'] ?? '') === '1') {
	$renderResults();
	exit;
}

$pageTitle = 'Contrats';
$activeNav = 'Contrats';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Gérez les contrats de location de votre agence.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';
$extraScripts = '<script src="/LOKA/layouts/live-search.js"></script>';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <div>
        <h1>Contrats</h1>
        <p>Les contrats de location gérés par votre agence.</p>
      </div>
      <a class="btn btn-primary" href="contrat-ajouter.php" data-modal>+ Nouveau contrat</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="feedback success"><?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="feedback error"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon violet"><?= $icons['card'] ?></div></div>
        <div class="kpi-value"><?= $stats['total'] ?></div>
        <div class="kpi-label">Total contrats</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon green"><?= $icons['check'] ?></div></div>
        <div class="kpi-value"><?= $stats['actifs'] ?></div>
        <div class="kpi-label">Actifs</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon blue"><?= $icons['card'] ?></div></div>
        <div class="kpi-value"><?= $stats['brouillons'] ?></div>
        <div class="kpi-label">Brouillons</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon amber"><?= $icons['alert'] ?></div></div>
        <div class="kpi-value"><?= $stats['expires'] ?></div>
        <div class="kpi-label">Expirés</div>
      </div>
    </div>

    <form class="filters-form" method="get" action="contrats.php" data-live-search data-live-target="#contratsResults">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="search" placeholder="Rechercher par num&eacute;ro, bien&hellip;" value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
      </div>
      <select class="filter-select" name="statut">
        <option value="">Tous les statuts</option>
        <?php foreach ($statusLabels as $key => $label): ?>
          <option value="<?= $key ?>"<?= $filters['statut'] === $key ? ' selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary">Filtrer</button>
    </form>

    <div id="contratsResults">
      <?php $renderResults(); ?>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
