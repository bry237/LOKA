<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/AuditController.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$data = AuditController::list($_GET);
$filters = $data['filters'];
$entities = $data['entities'];
$result = $data['result'];

$pageTitle = 'Journal d’audit';
$activeNav = 'Journal';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Historique des actions effectuées sur la plateforme.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));

$paginationUrl = static function (int $page) use ($filters): string {
	return '?' . http_build_query(array_filter([
		'q' => $filters['q'],
		'action' => $filters['action'],
		'entite' => $filters['entite'],
		'date_from' => $filters['date_from'],
		'date_to' => $filters['date_to'],
		'page' => $page > 1 ? $page : null,
	]));
};

$actionLabels = [
	'CREATE' => 'Création', 'UPDATE' => 'Modification', 'DELETE' => 'Suppression',
	'LOGIN' => 'Connexion', 'LOGOUT' => 'Déconnexion', 'EXPORT' => 'Export',
	'DOWNLOAD' => 'Téléchargement', 'OTHER' => 'Autre',
];
$actionTones = [
	'CREATE' => 'actif', 'UPDATE' => 'attente', 'DELETE' => 'suspendu',
	'LOGIN' => 'inactif', 'LOGOUT' => 'inactif', 'EXPORT' => 'attente',
	'DOWNLOAD' => 'attente', 'OTHER' => 'inactif',
];
$hasFilters = $filters['q'] !== '' || $filters['action'] !== '' || $filters['entite'] !== '' || $filters['date_from'] !== '' || $filters['date_to'] !== '';

$renderResults = function () use ($result, $actionLabels, $actionTones, $paginationUrl): void {
	?>
	<div class="table-scroll">
		<table>
			<thead>
				<tr>
					<th>DATE</th>
					<th>ACTION</th>
					<th>ENTITÉ</th>
					<th>AUTEUR</th>
					<th>AGENCE</th>
					<th>IP</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php if (!$result['rows']): ?>
					<tr class="empty-row"><td colspan="7">Aucune entrée ne correspond à ces critères.</td></tr>
				<?php endif; ?>
				<?php foreach ($result['rows'] as $row): ?>
					<?php $auteur = trim(($row['prenom'] ?? '') . ' ' . ($row['nom'] ?? '')); ?>
					<tr>
						<td><?= (new DateTimeImmutable($row['date_action']))->format('d/m/Y H:i:s') ?></td>
						<td><span class="status-badge <?= $actionTones[$row['action']] ?? '' ?>"><?= htmlspecialchars($actionLabels[$row['action']] ?? $row['action'], ENT_QUOTES, 'UTF-8') ?></span></td>
						<td><?= htmlspecialchars($row['entite'], ENT_QUOTES, 'UTF-8') ?><?= $row['id_entite'] ? ' #' . (int) $row['id_entite'] : '' ?></td>
						<td><?= $auteur !== '' ? htmlspecialchars($auteur, ENT_QUOTES, 'UTF-8') : '<span class="cell-sub">Système</span>' ?></td>
						<td><?= htmlspecialchars($row['agence_nom'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
						<td><span class="cell-sub"><?= htmlspecialchars($row['adresse_ip'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span></td>
						<td>
							<a class="consult-link" href="detail.php?id=<?= (int) $row['id_audit'] ?>" data-modal>
								Détail
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
		<div>Affichage <?= $shownFrom ?>-<?= $shownTo ?> sur <?= $result['total'] ?> entrées</div>
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
    <div class="page-header">
      <h1>Journal d’audit</h1>
      <p><?= $result['total'] ?> entrée<?= $result['total'] > 1 ? 's' : '' ?> enregistrée<?= $result['total'] > 1 ? 's' : '' ?></p>
    </div>

    <div class="panel table-panel">
      <form class="filters-form" method="get" action="index.php" data-live-search data-live-target="#auditResults">
        <div class="search-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" name="q" placeholder="Utilisateur, agence…" value="<?= htmlspecialchars($filters['q'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
        </div>
        <select class="filter-select" name="action">
          <option value="">Toutes les actions</option>
          <?php foreach (AuditModel::ACTIONS as $action): ?>
            <option value="<?= $action ?>"<?= $filters['action'] === $action ? ' selected' : '' ?>><?= htmlspecialchars($actionLabels[$action] ?? $action, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
        <select class="filter-select" name="entite">
          <option value="">Toutes les entités</option>
          <?php foreach ($entities as $entite): ?>
            <option value="<?= htmlspecialchars($entite, ENT_QUOTES, 'UTF-8') ?>"<?= $filters['entite'] === $entite ? ' selected' : '' ?>><?= htmlspecialchars($entite, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
        <input type="date" class="filter-select" name="date_from" value="<?= htmlspecialchars($filters['date_from'], ENT_QUOTES, 'UTF-8') ?>" title="Depuis le">
        <input type="date" class="filter-select" name="date_to" value="<?= htmlspecialchars($filters['date_to'], ENT_QUOTES, 'UTF-8') ?>" title="Jusqu'au">
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <?php if ($hasFilters): ?><a class="btn" href="index.php">Réinitialiser</a><?php endif; ?>
      </form>

      <div id="auditResults">
        <?php $renderResults(); ?>
      </div>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
