<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';
require_once __DIR__ . '/team-presentation.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$idBien = (int) ($_POST['id_bien'] ?? 0);
	$idResponsable = ($_POST['id_responsable'] ?? '') !== '' ? (int) $_POST['id_responsable'] : null;
	$error = MonAgenceController::assignResponsable($idAgence, $idBien, $idResponsable);

	$redirect = 'biens-assignation.php';
	$redirect .= $error !== null ? '?error=' . urlencode($error) : '?success=' . urlencode('Responsable mis à jour.');
	header('Location: ' . $redirect);
	exit;
}

$data = MonAgenceController::biensForAssignment($idAgence);
$biens = $data['biens'];
$team = $data['team'];

// Présentation uniquement : le modèle ne renvoie pas le rôle du responsable sur `bien`, on le
// retrouve ici via la liste d'équipe déjà chargée (même requête, aucun nouvel accès base).
$teamById = [];
foreach ($team as $m) {
	$teamById[(int) $m['id_utilisateur']] = $m;
}

$roleMeta = teamRoleMeta();
$icons = teamIcons();

$totalBiens = count($biens);
$assignedCount = count(array_filter($biens, static fn (array $b): bool => $b['id_responsable'] !== null));
$unassignedCount = $totalBiens - $assignedCount;

$filtre = (string) ($_GET['filtre'] ?? 'tous');
if (!in_array($filtre, ['tous', 'affectes', 'non_affectes'], true)) {
	$filtre = 'tous';
}
$biensAffiches = match ($filtre) {
	'affectes' => array_values(array_filter($biens, static fn (array $b): bool => $b['id_responsable'] !== null)),
	'non_affectes' => array_values(array_filter($biens, static fn (array $b): bool => $b['id_responsable'] === null)),
	default => $biens,
};

// Données pour la modale "Affectation automatique" (JS pur, rien n'est envoyé tant que l'agence
// n'a pas cliqué sur "Confirmer l'affectation" — qui réutilise ensuite cette même page, un bien à
// la fois, exactement comme le ferait une affectation manuelle).
$autoAssignBiens = array_map(static function (array $b): array {
	return [
		'id' => (int) $b['id_bien'],
		'reference' => $b['reference'],
		'titre' => $b['titre'],
		'assigned' => $b['id_responsable'] !== null,
		'responsable' => $b['responsable_nom'],
	];
}, $biens);

$eligibleMembers = array_values(array_filter($team, static fn (array $m): bool => $roleMeta[$m['role_nom']]['eligible'] ?? false));
$autoAssignMembers = array_map(static function (array $m) use ($roleMeta): array {
	return [
		'id' => (int) $m['id_utilisateur'],
		'nom' => trim($m['prenom'] . ' ' . $m['nom']),
		'role' => $m['role_nom'],
		'tone' => $roleMeta[$m['role_nom']]['tone'] ?? 'teal',
		'initials' => mb_strtoupper(mb_substr($m['prenom'], 0, 1) . mb_substr($m['nom'], 0, 1)),
	];
}, $eligibleMembers);

$autoAssignDataJson = json_encode(
	['biens' => $autoAssignBiens, 'members' => $autoAssignMembers, 'csrfToken' => Auth::csrfToken()],
	JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
);

$pageTitle = 'Affectation des biens';
$activeNav = 'Équipe';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Affectez un responsable à chaque bien de votre portefeuille.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="equipe.css">';

$csrfToken = Auth::csrfToken();
$statutLabels = ['CREATED' => 'À publier', 'AVAILABLE' => 'Disponible', 'OCCUPIED' => 'Occupé', 'MAINTENANCE' => 'Maintenance', 'ARCHIVED' => 'Archivé'];

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <a class="detail-back" href="equipe.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour à l’équipe
    </a>

    <div class="page-header">
      <h1>Affectation des biens</h1>
      <p>Qui suit quoi dans votre parc immobilier.</p>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="feedback success"><?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="feedback error"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="assign-summary">
      <div class="assign-summary-stat"><b><?= $totalBiens ?></b><span>bien<?= $totalBiens > 1 ? 's' : '' ?></span></div>
      <div class="assign-summary-stat"><b><?= $assignedCount ?></b><span>affecté<?= $assignedCount > 1 ? 's' : '' ?></span></div>
      <div class="assign-summary-stat"><b><?= $unassignedCount ?></b><span>non affecté<?= $unassignedCount > 1 ? 's' : '' ?></span></div>
      <div class="assign-summary-actions">
        <button type="button" class="btn" id="btnManualAssign"><?= $icons['hand'] ?>Affecter manuellement</button>
        <button type="button" class="btn btn-primary" id="btnAutoAssign"<?= !$totalBiens ? ' disabled' : '' ?>><?= $icons['wand'] ?>Affectation automatique</button>
      </div>
    </div>

    <div class="panel table-panel" id="biensTable">
      <div class="assign-tabs">
        <a class="assign-tab<?= $filtre === 'tous' ? ' active' : '' ?>" href="?filtre=tous">Tous <span class="count"><?= $totalBiens ?></span></a>
        <a class="assign-tab<?= $filtre === 'non_affectes' ? ' active' : '' ?>" href="?filtre=non_affectes">Non affectés <span class="count"><?= $unassignedCount ?></span></a>
        <a class="assign-tab<?= $filtre === 'affectes' ? ' active' : '' ?>" href="?filtre=affectes">Affectés <span class="count"><?= $assignedCount ?></span></a>
      </div>

      <?php if (!$biens): ?>
        <div class="empty-state">
          <div class="empty-state-icon"><?= $icons['home'] ?></div>
          <h4>Aucun bien à affecter pour le moment</h4>
          <p>Les biens de votre agence apparaîtront ici dès leur création pour que vous puissiez leur assigner un responsable.</p>
        </div>
      <?php elseif (!$biensAffiches): ?>
        <div class="empty-state">
          <div class="empty-state-icon"><?= $icons['check'] ?></div>
          <h4><?= $filtre === 'affectes' ? 'Aucun bien affecté pour le moment' : 'Tous les biens sont affectés' ?></h4>
          <p><?= $filtre === 'affectes' ? 'Affectez un premier bien pour le voir apparaître ici.' : 'Chaque bien de votre portefeuille a déjà un responsable.' ?></p>
        </div>
      <?php else: ?>
        <div class="table-scroll">
          <table>
            <thead>
              <tr><th>RÉFÉRENCE</th><th>BIEN</th><th>STATUT</th><th>RESPONSABLE</th></tr>
            </thead>
            <tbody>
              <?php foreach ($biensAffiches as $b): ?>
                <?php
                  $responsable = $b['id_responsable'] !== null ? ($teamById[(int) $b['id_responsable']] ?? null) : null;
                  $responsableTone = $responsable ? ($roleMeta[$responsable['role_nom']]['tone'] ?? 'teal') : null;
                ?>
                <tr>
                  <td class="cell-title"><?= htmlspecialchars($b['reference'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars($b['titre'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><span class="status-badge"><?= htmlspecialchars($statutLabels[$b['statut']] ?? $b['statut'], ENT_QUOTES, 'UTF-8') ?></span></td>
                  <td>
                    <div class="responsable-cell">
                      <?php if ($responsable): ?>
                        <div class="responsable-chip">
                          <div class="cell-avatar role-<?= $responsableTone ?>"><?= htmlspecialchars(mb_strtoupper(mb_substr($responsable['prenom'], 0, 1) . mb_substr($responsable['nom'], 0, 1)), ENT_QUOTES, 'UTF-8') ?></div>
                          <div>
                            <div class="responsable-chip-name"><?= htmlspecialchars($responsable['prenom'] . ' ' . $responsable['nom'], ENT_QUOTES, 'UTF-8') ?></div>
                            <span class="role-badge <?= $responsableTone ?>"><?= htmlspecialchars($responsable['role_nom'], ENT_QUOTES, 'UTF-8') ?></span>
                          </div>
                        </div>
                      <?php else: ?>
                        <span class="responsable-unassigned">Non affecté</span>
                      <?php endif; ?>
                      <form class="inline-form" method="post" action="biens-assignation.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id_bien" value="<?= (int) $b['id_bien'] ?>">
                        <select name="id_responsable" class="filter-select responsable-select" onchange="this.form.requestSubmit()">
                          <option value="">— Aucun —</option>
                          <?php foreach ($team as $m): ?>
                            <option value="<?= (int) $m['id_utilisateur'] ?>"<?= (int) $b['id_responsable'] === (int) $m['id_utilisateur'] ? ' selected' : '' ?>><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($m['role_nom'], ENT_QUOTES, 'UTF-8') ?>)</option>
                          <?php endforeach; ?>
                        </select>
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

    <script id="autoAssignData" type="application/json"><?= $autoAssignDataJson ?></script>
<?php
$extraScripts = '<script src="assignation.js"></script>';
require dirname(__DIR__, 3) . '/layouts/footer.php';
