<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';
require_once __DIR__ . '/team-presentation.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$filters = [
	'search' => trim((string) ($_GET['search'] ?? '')),
];

$data = MonAgenceController::proprietaires($idAgence, $filters);
$proprietaires = $data['list'];
$stats = $data['stats'];
$icons = teamIcons();

$renderResults = function () use ($proprietaires, $icons): void {
	?>
	<div class="panel table-panel">
		<div class="panel-header"><div class="panel-title">Propriétaires</div></div>
		<?php if (!$proprietaires): ?>
			<div class="empty-state">
				<div class="empty-state-icon"><?= $icons['shield'] ?></div>
				<h4>Aucun propriétaire</h4>
				<p>Les propriétaires suivis par votre agence apparaîtront ici.</p>
			</div>
		<?php else: ?>
			<div class="table-scroll">
				<table>
					<thead>
						<tr><th>NOM</th><th>EMAIL</th><th>TÉLÉPHONE</th><th>VILLE</th><th>BIENS</th><th></th></tr>
					</thead>
					<tbody>
						<?php foreach ($proprietaires as $p): ?>
							<tr>
								<td class="cell-title"><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom'], ENT_QUOTES, 'UTF-8') ?></td>
								<td class="cell-sub"><?= $p['email'] ? htmlspecialchars($p['email'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
								<td class="cell-sub"><?= $p['telephone'] ? htmlspecialchars($p['telephone'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
								<td class="cell-sub"><?= $p['ville'] ? htmlspecialchars($p['ville'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
								<td class="cell-sub"><?= (int) $p['biens_count'] ?></td>
								<td>
									<a class="consult-link" href="proprietaire-detail.php?id=<?= (int) $p['id_proprietaire'] ?>" data-modal>
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

$pageTitle = 'Propriétaires';
$activeNav = 'Propriétaires';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Suivez les propriétaires de votre agence.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';
$extraScripts = '<script src="/LOKA/layouts/live-search.js"></script>';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <div>
        <h1>Propriétaires</h1>
        <p>Les propriétaires suivis par votre agence.</p>
      </div>
      <a class="btn btn-primary" href="proprietaire-ajouter.php" data-modal>+ Nouveau propriétaire</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="feedback success"><?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="feedback error"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon violet"><?= $icons['shield'] ?></div></div>
        <div class="kpi-value"><?= $stats['total'] ?></div>
        <div class="kpi-label">Total propriétaires</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon green"><?= $icons['home'] ?></div></div>
        <div class="kpi-value"><?= $stats['avec_bien'] ?></div>
        <div class="kpi-label">Avec au moins un bien</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon blue"><?= $icons['users'] ?></div></div>
        <div class="kpi-value"><?= $stats['sans_bien'] ?></div>
        <div class="kpi-label">Sans bien rattaché</div>
      </div>
    </div>

    <form class="filters-form" method="get" action="proprietaires.php" data-live-search data-live-target="#proprietairesResults">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="search" placeholder="Rechercher par nom, pr&eacute;nom, email&hellip;" value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
      </div>
      <button type="submit" class="btn btn-primary">Filtrer</button>
    </form>

    <div id="proprietairesResults">
      <?php $renderResults(); ?>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
