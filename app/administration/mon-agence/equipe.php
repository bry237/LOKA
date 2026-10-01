<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$errors = [];
$values = ['nom' => '', 'prenom' => '', 'email' => '', 'telephone' => '', 'id_role' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		http_response_code(400);
		exit('Requête invalide.');
	}
	$values = array_merge($values, array_intersect_key($_POST, $values));
	$errors = MonAgenceController::invite($idAgence, $_POST);
	if (!$errors) {
		header('Location: equipe.php?success=' . urlencode('Membre invité. Ses identifiants lui ont été envoyés par SMS.'));
		exit;
	}
}

$team = MonAgenceController::team($idAgence);
$members = $team['members'];
$roles = $team['roles'];
$usage = $team['usage'];
$canInvite = $usage['limitUsers'] === null || $usage['users'] < $usage['limitUsers'];

$pageTitle = 'Équipe';
$activeNav = 'Équipe';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Gérez les membres de votre agence et leurs rôles.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));

$csrfToken = Auth::csrfToken();
$statusLabels = ['ACTIVE' => 'Actif', 'PENDING' => 'En attente', 'INACTIVE' => 'Inactif', 'LOCKED' => 'Verrouillé'];
$statusTones = ['ACTIVE' => 'actif', 'PENDING' => 'attente', 'INACTIVE' => 'inactif', 'LOCKED' => 'suspendu'];

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <div>
        <h1>Équipe</h1>
        <p><?= $usage['users'] ?> membre<?= $usage['users'] > 1 ? 's' : '' ?> sur <?= $usage['limitUsers'] ?? 'illimité' ?> (plan actuel) ·
          <a href="biens-assignation.php">Affecter des responsables aux biens →</a></p>
      </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="feedback success"><?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (!empty($errors['_global'])): ?>
      <div class="feedback error"><?= htmlspecialchars($errors['_global'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel">
      <div class="panel-header"><div class="panel-title">Inviter un membre</div></div>
      <?php if (!$canInvite): ?>
        <p class="empty-note" style="padding:0 18px 18px;">Limite d’utilisateurs de votre plan atteinte (<?= $usage['users'] ?>/<?= $usage['limitUsers'] ?>). <a href="abonnement.php">Changez de plan</a> pour inviter davantage de membres.</p>
      <?php else: ?>
        <form method="post" action="equipe.php">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <div class="form-grid">
            <div class="form-field">
              <label for="prenom">Prénom</label>
              <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($values['prenom'], ENT_QUOTES, 'UTF-8') ?>">
              <?php if (!empty($errors['prenom'])): ?><span class="field-error"><?= htmlspecialchars($errors['prenom'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
            </div>
            <div class="form-field">
              <label for="nom">Nom</label>
              <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($values['nom'], ENT_QUOTES, 'UTF-8') ?>">
              <?php if (!empty($errors['nom'])): ?><span class="field-error"><?= htmlspecialchars($errors['nom'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
            </div>
            <div class="form-field">
              <label for="email">Email</label>
              <input type="email" id="email" name="email" value="<?= htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8') ?>">
              <?php if (!empty($errors['email'])): ?><span class="field-error"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
            </div>
            <div class="form-field">
              <label for="telephone">Téléphone</label>
              <input type="text" id="telephone" name="telephone" value="<?= htmlspecialchars($values['telephone'], ENT_QUOTES, 'UTF-8') ?>">
              <?php if (!empty($errors['telephone'])): ?><span class="field-error"><?= htmlspecialchars($errors['telephone'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
            </div>
            <div class="form-field span-2">
              <label for="id_role">Rôle</label>
              <select id="id_role" name="id_role">
                <option value="">— Choisir —</option>
                <?php foreach ($roles as $role): ?>
                  <option value="<?= (int) $role['id_role'] ?>"<?= $values['id_role'] == $role['id_role'] ? ' selected' : '' ?>><?= htmlspecialchars($role['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
              </select>
              <?php if (!empty($errors['id_role'])): ?><span class="field-error"><?= htmlspecialchars($errors['id_role'], ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn btn-primary">Inviter</button>
          </div>
        </form>
      <?php endif; ?>
    </div>

    <div class="panel table-panel">
      <div class="panel-header"><div class="panel-title">Membres de l’équipe</div></div>
      <div class="table-scroll">
        <table>
          <thead>
            <tr><th>NOM</th><th>EMAIL</th><th>RÔLE</th><th>STATUT</th><th>DEPUIS</th></tr>
          </thead>
          <tbody>
            <?php if (!$members): ?>
              <tr class="empty-row"><td colspan="5">Aucun membre pour le moment.</td></tr>
            <?php endif; ?>
            <?php foreach ($members as $m): ?>
              <tr>
                <td><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($m['email'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($m['role_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="status-badge <?= $statusTones[$m['statut']] ?? '' ?>"><?= htmlspecialchars($statusLabels[$m['statut']] ?? $m['statut'], ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><?= (new DateTimeImmutable($m['created_at']))->format('d/m/Y') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
