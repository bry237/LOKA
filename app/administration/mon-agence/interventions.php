<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';
require_once __DIR__ . '/team-presentation.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$filters = [
	'search' => trim((string) ($_GET['search'] ?? '')),
	'priorite' => (string) ($_GET['priorite'] ?? ''),
	'statut' => (string) ($_GET['statut'] ?? ''),
];

$data = MonAgenceController::interventions($idAgence, $filters);
$interventions = $data['list'];
$stats = $data['stats'];
$icons = teamIcons();

$priorityLabels = ['LOW' => 'Faible', 'MEDIUM' => 'Moyenne', 'HIGH' => 'Haute', 'URGENT' => 'Urgente'];
$priorityTones = ['LOW' => 'low', 'MEDIUM' => 'medium', 'HIGH' => 'high', 'URGENT' => 'urgent'];
$statusLabels = ['OPEN' => 'Ouverte', 'ASSIGNED' => 'Assignée', 'IN_PROGRESS' => 'En cours', 'WAITING' => 'En attente', 'RESOLVED' => 'Résolue', 'CLOSED' => 'Clôturée', 'CANCELLED' => 'Annulée'];
$statusTones = ['OPEN' => 'inactif', 'ASSIGNED' => 'attente', 'IN_PROGRESS' => 'attente', 'WAITING' => 'attente', 'RESOLVED' => 'actif', 'CLOSED' => 'inactif', 'CANCELLED' => 'suspendu'];

$renderResults = function () use ($interventions, $priorityLabels, $priorityTones, $statusLabels, $statusTones, $icons): void {
	?>
	<div class="panel table-panel">
		<div class="panel-header"><div class="panel-title">Interventions</div></div>
		<?php if (!$interventions): ?>
			<div class="empty-state">
				<div class="empty-state-icon"><?= $icons['tool'] ?></div>
				<h4>Aucune intervention</h4>
				<p>Les interventions de maintenance de votre agence apparaîtront ici.</p>
			</div>
		<?php else: ?>
			<div class="table-scroll">
				<table>
					<thead>
						<tr><th>TITRE</th><th>BIEN</th><th>PRIORITÉ</th><th>STATUT</th><th>ASSIGNÉ À</th><th>CRÉÉE LE</th><th></th></tr>
					</thead>
					<tbody>
						<?php foreach ($interventions as $i): ?>
							<tr>
								<td class="cell-title"><?= htmlspecialchars($i['titre'], ENT_QUOTES, 'UTF-8') ?></td>
								<td class="cell-sub"><?= htmlspecialchars($i['bien_titre'] . ' (' . $i['bien_reference'] . ')', ENT_QUOTES, 'UTF-8') ?></td>
								<td><span class="priority-badge <?= $priorityTones[$i['priorite']] ?? 'low' ?>"><?= $priorityLabels[$i['priorite']] ?? $i['priorite'] ?></span></td>
								<td><span class="status-badge <?= $statusTones[$i['statut']] ?? '' ?>"><?= $statusLabels[$i['statut']] ?? $i['statut'] ?></span></td>
								<td class="cell-sub"><?= $i['assigne_nom'] ? htmlspecialchars($i['assigne_prenom'] . ' ' . $i['assigne_nom'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
								<td class="cell-sub"><?= (new DateTimeImmutable($i['created_at']))->format('d/m/Y') ?></td>
								<td>
									<a class="consult-link" href="intervention-detail.php?id=<?= (int) $i['id_intervention'] ?>" data-modal>
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

$pageTitle = 'Interventions';
$activeNav = 'Interventions';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Suivez les interventions de maintenance de votre agence.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';
$extraScripts = '<script src="/LOKA/layouts/live-search.js"></script>';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <div>
        <h1>Interventions</h1>
        <p>Maintenance et réparations sur le patrimoine de votre agence.</p>
      </div>
      <a class="btn btn-primary" href="intervention-ajouter.php" data-modal>+ Nouvelle intervention</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="feedback success"><?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon amber"><?= $icons['tool'] ?></div></div>
        <div class="kpi-value"><?= $stats['ouvertes'] ?></div>
        <div class="kpi-label">Ouvertes / assignées</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon blue"><?= $icons['tool'] ?></div></div>
        <div class="kpi-value"><?= $stats['en_cours'] ?></div>
        <div class="kpi-label">En cours</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon red"><?= $icons['alert'] ?></div></div>
        <div class="kpi-value"><?= $stats['urgentes'] ?></div>
        <div class="kpi-label">Urgentes</div>
      </div>
      <div class="kpi-card">
        <div class="kpi-top"><div class="kpi-icon green"><?= $icons['check'] ?></div></div>
        <div class="kpi-value"><?= $stats['resolues_ce_mois'] ?></div>
        <div class="kpi-label">Résolues ce mois</div>
      </div>
    </div>

    <form class="filters-form" method="get" action="interventions.php" data-live-search data-live-target="#interventionsResults">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="search" placeholder="Rechercher par titre…" value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
      </div>
      <select class="filter-select" name="priorite">
        <option value="">Toutes les priorités</option>
        <?php foreach ($priorityLabels as $key => $label): ?>
          <option value="<?= $key ?>"<?= $filters['priorite'] === $key ? ' selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
      <select class="filter-select" name="statut">
        <option value="">Tous les statuts</option>
        <?php foreach ($statusLabels as $key => $label): ?>
          <option value="<?= $key ?>"<?= $filters['statut'] === $key ? ' selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary">Filtrer</button>
    </form>

    <div id="interventionsResults">
      <?php $renderResults(); ?>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
