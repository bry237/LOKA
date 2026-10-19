<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/BienAdminController.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$data = BienAdminController::list($_GET);
$filters = $data['filters'];
$result = $data['result'];
$stats = $data['stats'];
$types = $data['types'];
$agences = $data['agences'];

$pageTitle = 'Biens';
$activeNav = 'Biens';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Supervisez le patrimoine immobilier de toutes les agences.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));

$csrfToken = Auth::csrfToken();

$currentQuery = http_build_query(array_filter([
	'q' => $filters['q'],
	'statut' => $filters['statut'],
	'id_agence' => $filters['id_agence'],
	'id_type_bien' => $filters['id_type_bien'],
	'page' => $result['page'] > 1 ? $result['page'] : null,
]));

$paginationUrl = static function (int $page) use ($filters): string {
	return '?' . http_build_query(array_filter([
		'q' => $filters['q'],
		'statut' => $filters['statut'],
		'id_agence' => $filters['id_agence'],
		'id_type_bien' => $filters['id_type_bien'],
		'page' => $page > 1 ? $page : null,
	]));
};

$statusLabels = ['CREATED' => 'Brouillon', 'AVAILABLE' => 'Disponible', 'OCCUPIED' => 'Loué', 'MAINTENANCE' => 'Maintenance', 'ARCHIVED' => 'Archivé'];
$statusTones = ['CREATED' => 'attente', 'AVAILABLE' => 'actif', 'OCCUPIED' => 'attente', 'MAINTENANCE' => 'suspendu', 'ARCHIVED' => 'inactif'];
$hasFilters = $filters['q'] !== '' || $filters['statut'] !== '' || $filters['id_agence'] !== '' || $filters['id_type_bien'] !== '';

$renderResults = function () use ($result, $statusLabels, $statusTones, $currentQuery, $paginationUrl): void {
	?>
	<div class="table-scroll">
		<table>
			<thead>
				<tr>
					<th>BIEN</th>
					<th>AGENCE</th>
					<th>TYPE</th>
					<th>VILLE</th>
					<th>LOYER</th>
					<th>STATUT</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if (!$result['rows']): ?>
					<tr class="empty-row"><td colspan="7">Aucun bien ne correspond à ces critères.</td></tr>
				<?php endif; ?>
				<?php foreach ($result['rows'] as $b): ?>
					<tr>
						<td>
							<div class="cell-title"><?= htmlspecialchars($b['titre'], ENT_QUOTES, 'UTF-8') ?></div>
							<div class="cell-sub"><?= htmlspecialchars($b['reference'], ENT_QUOTES, 'UTF-8') ?></div>
						</td>
						<td class="cell-sub"><?= htmlspecialchars($b['agence_nom'], ENT_QUOTES, 'UTF-8') ?></td>
						<td class="cell-sub"><?= htmlspecialchars($b['type_bien'], ENT_QUOTES, 'UTF-8') ?></td>
						<td class="cell-sub"><?= htmlspecialchars($b['ville'], ENT_QUOTES, 'UTF-8') ?></td>
						<td class="cell-sub"><?= number_format((float) $b['loyer'], 0, ',', ' ') ?> €</td>
						<td><span class="status-badge <?= $statusTones[$b['statut']] ?? '' ?>"><?= $statusLabels[$b['statut']] ?? $b['statut'] ?></span></td>
						<td style="text-align:right;">
							<a class="consult-link" href="detail.php?id=<?= (int) $b['id_bien'] ?>" data-modal>
								Consulter
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 15 12 9 18"/></svg>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<div class="table-footer">
		<?php
			$shownFrom = $result['total'] ? ($result['page'] - 1) * $result['pageSize'] + 1 : 0;
			$shownTo = min($result['page'] * $result['pageSize'], $result['total']);
		?>
		<div>Affichage <?= $shownFrom ?>-<?= $shownTo ?> sur <?= $result['total'] ?> biens</div>
		<div class="pagination">
			<?php if ($result['page'] > 1): ?>
				<a class="page-btn" href="<?= htmlspecialchars($paginationUrl($result['page'] - 1), ENT_QUOTES, 'UTF-8') ?>">‹</a>
			<?php else: ?>
				<span class="page-btn" aria-disabled="true">‹</span>
			<?php endif; ?>
			<?php for ($i = 1; $i <= $result['totalPages']; $i++): ?>
				<a class="page-btn<?= $i === $result['page'] ? ' active' : '' ?>" href="<?= htmlspecialchars($paginationUrl($i), ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a>
			<?php endfor; ?>
			<?php if ($result['page'] < $result['totalPages']): ?>
				<a class="page-btn" href="<?= htmlspecialchars($paginationUrl($result['page'] + 1), ENT_QUOTES, 'UTF-8') ?>">›</a>
			<?php else: ?>
				<span class="page-btn" aria-disabled="true">›</span>
			<?php endif; ?>
		</div>
	</div>
	<?php
};

if (($_GET['ajax'] ?? '') === '1') {
	$renderResults();
	exit;
}

$extraScripts = '<script src="/LOKA/layouts/live-search.js"></script>';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <div>
        <h1>Biens</h1>
        <p><?= $stats['total'] ?> bien<?= $stats['total'] > 1 ? 's' : '' ?> sur la plateforme · <?= $stats['disponibles'] ?> disponible<?= $stats['disponibles'] > 1 ? 's' : '' ?> · <?= $stats['occupes'] ?> loué<?= $stats['occupes'] > 1 ? 's' : '' ?> · <?= $stats['maintenance'] ?> en maintenance · <?= $stats['archives'] ?> archivé<?= $stats['archives'] > 1 ? 's' : '' ?></p>
      </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="feedback success"><?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="feedback error"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel table-panel">
      <form class="filters-form" method="get" action="index.php" data-live-search data-live-target="#biensResults">
        <div class="search-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" name="q" placeholder="Référence, titre, ville, agence…" value="<?= htmlspecialchars($filters['q'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
        </div>
        <select class="filter-select" name="id_agence">
          <option value="">Toutes les agences</option>
          <?php foreach ($agences as $a): ?>
            <option value="<?= (int) $a['id_agence'] ?>"<?= $filters['id_agence'] === (string) $a['id_agence'] ? ' selected' : '' ?>><?= htmlspecialchars($a['nom'], ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
        <select class="filter-select" name="id_type_bien">
          <option value="">Tous les types</option>
          <?php foreach ($types as $t): ?>
            <option value="<?= (int) $t['id_type_bien'] ?>"<?= $filters['id_type_bien'] === (string) $t['id_type_bien'] ? ' selected' : '' ?>><?= htmlspecialchars($t['nom'], ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
        <select class="filter-select" name="statut">
          <option value="">Tous les statuts</option>
          <?php foreach ($statusLabels as $key => $label): ?>
            <option value="<?= $key ?>"<?= $filters['statut'] === $key ? ' selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <?php if ($hasFilters): ?><a class="btn" href="index.php">Réinitialiser</a><?php endif; ?>
      </form>

      <div id="biensResults">
        <?php $renderResults(); ?>
      </div>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
