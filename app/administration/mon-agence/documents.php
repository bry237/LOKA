<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';
require_once __DIR__ . '/team-presentation.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$filters = [
	'search' => trim((string) ($_GET['search'] ?? '')),
	'id_type_document' => (string) ($_GET['id_type_document'] ?? ''),
	'entity_type' => (string) ($_GET['entity_type'] ?? ''),
];

$data = MonAgenceController::documents($idAgence, $filters);
$documents = $data['list'];
$types = $data['types'];
$icons = teamIcons();

$entityLabels = ['bien' => 'Bien', 'contrat' => 'Contrat', 'proprietaire' => 'Propriétaire', 'locataire' => 'Locataire', 'intervention' => 'Intervention'];

function formatDocBytes(?int $bytes): string
{
	if (!$bytes) return '—';
	$units = ['o', 'Ko', 'Mo', 'Go'];
	$i = min((int) floor(log($bytes, 1024)), count($units) - 1);
	return round($bytes / (1024 ** $i), 1) . ' ' . $units[$i];
}

function docEntityLabel(array $doc): string
{
	if ($doc['id_bien']) return 'Bien · ' . $doc['bien_nom'];
	if ($doc['id_contrat']) return 'Contrat · ' . $doc['contrat_numero'];
	if ($doc['id_proprietaire']) return 'Propriétaire · ' . $doc['proprietaire_prenom'] . ' ' . $doc['proprietaire_nom'];
	if ($doc['id_locataire']) return 'Locataire · ' . $doc['locataire_prenom'] . ' ' . $doc['locataire_nom'];
	if ($doc['id_intervention']) return 'Intervention · ' . $doc['intervention_titre'];
	return '—';
}

$renderResults = function () use ($documents, $icons): void {
	?>
	<div class="panel table-panel">
		<div class="panel-header"><div class="panel-title">Documents</div></div>
		<?php if (!$documents): ?>
			<div class="empty-state">
				<div class="empty-state-icon"><?= $icons['folder'] ?></div>
				<h4>Aucun document</h4>
				<p>Les documents importés pour vos biens, contrats, propriétaires et locataires apparaîtront ici.</p>
			</div>
		<?php else: ?>
			<div class="table-scroll">
				<table>
					<thead>
						<tr><th>DOCUMENT</th><th>TYPE</th><th>RATTACHÉ À</th><th>TAILLE</th><th>IMPORTÉ LE</th><th></th></tr>
					</thead>
					<tbody>
						<?php foreach ($documents as $doc): ?>
							<tr>
								<td>
									<div class="doc-row">
										<div class="doc-icon"><?= $icons['folder'] ?></div>
										<span class="cell-title"><?= htmlspecialchars($doc['nom'], ENT_QUOTES, 'UTF-8') ?></span>
									</div>
								</td>
								<td class="cell-sub"><?= htmlspecialchars($doc['type_nom'], ENT_QUOTES, 'UTF-8') ?></td>
								<td class="cell-sub"><?= htmlspecialchars(docEntityLabel($doc), ENT_QUOTES, 'UTF-8') ?></td>
								<td class="cell-sub"><?= formatDocBytes($doc['taille_octets'] !== null ? (int) $doc['taille_octets'] : null) ?></td>
								<td class="cell-sub"><?= (new DateTimeImmutable($doc['created_at']))->format('d/m/Y') ?></td>
								<td>
									<div class="row-actions">
										<a class="icon-action" href="document-telecharger.php?id=<?= (int) $doc['id_document'] ?>" target="_blank" title="Télécharger"><?= $icons['download'] ?></a>
										<form method="post" action="document-supprimer.php" onsubmit="return confirm('Supprimer ce document ?');">
											<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
											<input type="hidden" name="id" value="<?= (int) $doc['id_document'] ?>">
											<button type="submit" class="icon-action danger" title="Supprimer"><?= $icons['trash'] ?></button>
										</form>
									</div>
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

$pageTitle = 'Documents';
$activeNav = 'Documents';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Les documents rattachés aux biens, contrats, propriétaires et locataires de votre agence.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="ops.css">';
$extraScripts = '<script src="/LOKA/layouts/live-search.js"></script>';

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <div>
        <h1>Documents</h1>
        <p><?= count($documents) ?> document<?= count($documents) > 1 ? 's' : '' ?> (filtre courant)</p>
      </div>
      <a class="btn btn-primary" href="document-importer.php" data-modal>+ Importer un document</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="feedback success"><?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="feedback error"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form class="filters-form" method="get" action="documents.php" data-live-search data-live-target="#documentsResults">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="search" placeholder="Nom du document…" value="<?= htmlspecialchars($filters['search'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
      </div>
      <select class="filter-select" name="id_type_document">
        <option value="">Tous les types</option>
        <?php foreach ($types as $type): ?>
          <option value="<?= (int) $type['id_type_document'] ?>"<?= $filters['id_type_document'] === (string) $type['id_type_document'] ? ' selected' : '' ?>><?= htmlspecialchars($type['nom'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
      <select class="filter-select" name="entity_type">
        <option value="">Toutes les entités</option>
        <?php foreach ($entityLabels as $key => $label): ?>
          <option value="<?= $key ?>"<?= $filters['entity_type'] === $key ? ' selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary">Filtrer</button>
    </form>

    <div id="documentsResults">
      <?php $renderResults(); ?>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
