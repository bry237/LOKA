<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';

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

$pageTitle = 'Affectation des biens';
$activeNav = 'Équipe';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Affectez un responsable (gestionnaire immobilier, technicien…) à chaque bien.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));

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

    <div class="panel table-panel">
      <?php if (!$biens): ?>
        <p class="empty-note" style="padding:18px;">Aucun bien enregistré pour le moment.</p>
      <?php else: ?>
        <div class="table-scroll">
          <table>
            <thead>
              <tr><th>RÉFÉRENCE</th><th>BIEN</th><th>STATUT</th><th>RESPONSABLE</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($biens as $b): ?>
                <tr>
                  <td><?= htmlspecialchars($b['reference'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars($b['titre'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><span class="status-badge"><?= htmlspecialchars($statutLabels[$b['statut']] ?? $b['statut'], ENT_QUOTES, 'UTF-8') ?></span></td>
                  <td>
                    <form class="inline-form" method="post" action="biens-assignation.php">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="id_bien" value="<?= (int) $b['id_bien'] ?>">
                      <select name="id_responsable" class="filter-select" onchange="this.form.requestSubmit()">
                        <option value="">— Aucun —</option>
                        <?php foreach ($team as $m): ?>
                          <option value="<?= (int) $m['id_utilisateur'] ?>"<?= (int) $b['id_responsable'] === (int) $m['id_utilisateur'] ? ' selected' : '' ?>><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($m['role_nom'], ENT_QUOTES, 'UTF-8') ?>)</option>
                        <?php endforeach; ?>
                      </select>
                    </form>
                  </td>
                  <td></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
