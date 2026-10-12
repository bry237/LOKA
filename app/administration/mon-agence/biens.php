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
	'id_type_bien' => (string) ($_GET['id_type_bien'] ?? ''),
];

$data = MonAgenceController::biens($idAgence, $filters);
$biens = $data['list'];
$stats = $data['stats'];
$types = MonAgenceModel::bienTypes();
$icons = teamIcons();

$statusLabels = ['CREATED' => 'Brouillon', 'AVAILABLE' => 'Disponible', 'OCCUPIED' => 'Loué', 'MAINTENANCE' => 'Maintenance', 'ARCHIVED' => 'Archivé'];
$statusTones = ['CREATED' => 'attente', 'AVAILABLE' => 'actif', 'OCCUPIED' => 'attente', 'MAINTENANCE' => 'suspendu', 'ARCHIVED' => 'inactif'];

$renderResults = function () use ($biens, $statusLabels, $statusTones, $icons): void {
	?>
	<div class="panel table-panel">
		<div class="panel-header"><div class="panel-title">Biens</div></div>
		<?php if (!$biens): ?>
			<div class="empty-state">
				<div class="empty-state-icon"><?= $icons['home'] ?></div>
				<h4>Aucun bien</h4>
				<p>Le patrimoine immobilier de votre agence apparaîtra ici.</p>
			</div>
		<?php else: ?>
			<div class="table-scroll">
				<table>
					<thead>
						<tr><th>RÉFÉRENCE</th><th>TITRE</th><th>TYPE</th><th>VILLE</th><th>SURFACE</th><th>LOYER</th><th>STATUT</th><th></th></tr>
					</thead>
					<tbody>
						<?php foreach ($biens as $b): ?>
							<tr>
								<td class="cell-sub"><?= htmlspecialchars($b['reference'], ENT_QUOTES, 'UTF-8') ?></td>
								<td class="cell-title"><?= htmlspecialchars($b['titre'], ENT_QUOTES, 'UTF-8') ?></td>
								<td class="cell-sub"><?= htmlspecialchars($b['type_bien'], ENT_QUOTES, 'UTF-8') ?></td>
								<td class="cell-sub"><?= htmlspecialchars($b['ville'], ENT_QUOTES, 'UTF-8') ?></td>
								<td class="cell-sub"><?= number_format((float) $b['surface'], 0) ?> m²</td>
								<td class="cell-sub"><?= number_format((float) $b['loyer'], 0, ',', ' ') ?> €</td>
								<td><span class="status-badge <?= $statusTones[$b['statut']] ?? '' ?>"><?= $statusLabels[$b['statut']] ?? $b['statut'] ?></span></td>
								<td>
									<a class="consult-link" href="bien-detail.php?id=<?= (int) $b['id_bien'] ?>" data-modal>
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

$pageTitle = 'Biens';
$activeNav = 'Biens';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Gérez le patrimoine immobilier de votre agence.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';
$extraScripts = '<script src="/LOKA/layouts/live-search.js"></script>';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <div>
        <h1>Biens</h1>
        <p>Le patrimoine immobilier géré par votre agence.</p>
      </div>
      <a class="btn btn-primary" href="bien-ajouter.php" data-modal>+ Nouveau bien</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="feedback success"><?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="feedback error"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon blue"><?= $icons['home'] ?></div></div>
        <div class="kpi-value"><?= $stats['total'] ?></div>
        <div class="kpi-label">Total biens</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon green"><?= $icons['check'] ?></div></div>
        <div class="kpi-value"><?= $stats['disponibles'] ?></div>
        <div class="kpi-label">Disponibles</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon teal"><?= $icons['home'] ?></div></div>
        <div class="kpi-value"><?= $stats['occupes'] ?></div>
        <div class="kpi-label">Occupés</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon amber"><?= $icons['tool'] ?></div></div>
        <div class="kpi-value"><?= $stats['maintenance'] ?></div>
        <div class="kpi-label">En maintenance</div>
      </div>
    </div>

    <form class="filters-form" method="get" action="biens.php" data-live-search data-live-target="#biensResults">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="search" placeholder="Rechercher par référence, titre, ville…" value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
      </div>
      <select class="filter-select" name="statut">
        <option value="">Tous les statuts</option>
        <?php foreach ($statusLabels as $key => $label): ?>
          <option value="<?= $key ?>"<?= $filters['statut'] === $key ? ' selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
      <select class="filter-select" name="id_type_bien">
        <option value="">Tous les types</option>
        <?php foreach ($types as $type): ?>
          <option value="<?= (int) $type['id_type_bien'] ?>"<?= $filters['id_type_bien'] === (string) $type['id_type_bien'] ? ' selected' : '' ?>><?= htmlspecialchars($type['nom'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary">Filtrer</button>
    </form>

    <div id="biensResults">
      <?php $renderResults(); ?>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
