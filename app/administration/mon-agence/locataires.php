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

$data = MonAgenceController::locataires($idAgence, $filters);
$locataires = $data['list'];
$stats = $data['stats'];
$icons = teamIcons();

$statusLabels = ['ACTIVE' => 'Actif', 'INACTIVE' => 'Inactif'];
$statusTones = ['ACTIVE' => 'actif', 'INACTIVE' => 'inactif'];

$renderResults = function () use ($locataires, $statusLabels, $statusTones, $icons): void {
	?>
	<div class="panel table-panel">
		<div class="panel-header"><div class="panel-title">Locataires</div></div>
		<?php if (!$locataires): ?>
			<div class="empty-state">
				<div class="empty-state-icon"><?= $icons['key'] ?></div>
				<h4>Aucun locataire</h4>
				<p>Les locataires suivis par votre agence apparaîtront ici.</p>
			</div>
		<?php else: ?>
			<div class="table-scroll">
				<table>
					<thead>
						<tr><th>NOM</th><th>EMAIL</th><th>TÉLÉPHONE</th><th>CONTRATS ACTIFS</th><th>STATUT</th><th></th></tr>
					</thead>
					<tbody>
						<?php foreach ($locataires as $l): ?>
							<tr>
								<td class="cell-title"><?= htmlspecialchars($l['prenom'] . ' ' . $l['nom'], ENT_QUOTES, 'UTF-8') ?></td>
								<td class="cell-sub"><?= $l['email'] ? htmlspecialchars($l['email'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
								<td class="cell-sub"><?= $l['telephone'] ? htmlspecialchars($l['telephone'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
								<td class="cell-sub"><?= (int) $l['contrats_actifs'] ?></td>
								<td><span class="status-badge <?= $statusTones[$l['statut']] ?? '' ?>"><?= $statusLabels[$l['statut']] ?? $l['statut'] ?></span></td>
								<td>
									<a class="consult-link" href="locataire-detail.php?id=<?= (int) $l['id_locataire'] ?>" data-modal>
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

$pageTitle = 'Locataires';
$activeNav = 'Locataires';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Suivez les locataires de votre agence.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';
$extraScripts = '<script src="/LOKA/layouts/live-search.js"></script>';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <div>
        <h1>Locataires</h1>
        <p>Les locataires suivis par votre agence.</p>
      </div>
      <a class="btn btn-primary" href="locataire-ajouter.php" data-modal>+ Nouveau locataire</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="feedback success"><?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="feedback error"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon amber"><?= $icons['key'] ?></div></div>
        <div class="kpi-value"><?= $stats['total'] ?></div>
        <div class="kpi-label">Total locataires</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon green"><?= $icons['check'] ?></div></div>
        <div class="kpi-value"><?= $stats['actifs'] ?></div>
        <div class="kpi-label">Actifs</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon teal"><?= $icons['users'] ?></div></div>
        <div class="kpi-value"><?= $stats['inactifs'] ?></div>
        <div class="kpi-label">Inactifs</div>
      </div>
    </div>

    <form class="filters-form" method="get" action="locataires.php" data-live-search data-live-target="#locatairesResults">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="search" placeholder="Rechercher par nom, pr&eacute;nom, email&hellip;" value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
      </div>
      <select class="filter-select" name="statut">
        <option value="">Tous les statuts</option>
        <?php foreach ($statusLabels as $key => $label): ?>
          <option value="<?= $key ?>"<?= $filters['statut'] === $key ? ' selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary">Filtrer</button>
    </form>

    <div id="locatairesResults">
      <?php $renderResults(); ?>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
