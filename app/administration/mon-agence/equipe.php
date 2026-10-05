<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';
require_once __DIR__ . '/team-presentation.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$team = MonAgenceController::team($idAgence);
$members = $team['members'];
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
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="equipe.css">';

$statusLabels = ['ACTIVE' => 'Actif', 'PENDING' => 'En attente', 'INACTIVE' => 'Inactif', 'LOCKED' => 'Verrouillé'];
$statusTones = ['ACTIVE' => 'actif', 'PENDING' => 'attente', 'INACTIVE' => 'inactif', 'LOCKED' => 'suspendu'];

// Rôles affichables pour une agence (cf. MonAgenceModel::TEAM_ROLE_IDS) : chacun a sa couleur et
// son icône dédiées, réutilisées pour l'avatar, le badge de rôle et les cartes de répartition.
$roleMeta = teamRoleMeta();
$roleCounts = array_fill_keys(array_keys($roleMeta), 0);
foreach ($members as $m) {
	if (isset($roleCounts[$m['role_nom']])) {
		$roleCounts[$m['role_nom']]++;
	}
}

$icons = teamIcons();

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <div>
        <h1>Équipe</h1>
        <p><?= $usage['users'] ?> membre<?= $usage['users'] > 1 ? 's' : '' ?> sur <?= $usage['limitUsers'] ?? 'illimité' ?> (plan actuel) · <a href="biens-assignation.php">Affectation des biens →</a></p>
      </div>
      <?php if ($canInvite): ?>
        <a class="btn btn-primary" href="equipe-inviter.php" data-modal><?= $icons['users'] ?>+ Inviter un membre</a>
      <?php else: ?>
        <span class="btn disabled" title="Limite d’utilisateurs de votre plan atteinte"><?= $icons['users'] ?>+ Inviter un membre</span>
      <?php endif; ?>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="feedback success"><?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <!-- Équipe de l'agence : répartition par rôle -->
    <div class="team-stats">
      <div class="team-stat-card total">
        <div class="team-stat-value"><?= $usage['users'] ?></div>
        <div class="team-stat-label">Membre<?= $usage['users'] > 1 ? 's' : '' ?> au total</div>
        <div class="team-stat-sub"><?= $usage['limitUsers'] !== null ? ('sur ' . $usage['limitUsers'] . ' autorisés') : 'plan illimité' ?></div>
      </div>
      <?php foreach ($roleMeta as $roleName => $meta): ?>
        <div class="team-stat-card">
          <div class="team-stat-icon <?= $meta['tone'] ?>"><?= $icons[$meta['icon']] ?></div>
          <div class="team-stat-value"><?= $roleCounts[$roleName] ?></div>
          <div class="team-stat-label"><?= htmlspecialchars($meta['short'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="panel table-panel">
      <div class="panel-header"><div class="panel-title">Membres de l’équipe</div></div>
      <?php if (!$members): ?>
        <div class="empty-state">
          <div class="empty-state-icon"><?= $icons['users'] ?></div>
          <h4>Votre équipe est encore vide</h4>
          <p>Invitez vos premiers collaborateurs pour commencer à répartir le travail.</p>
          <?php if ($canInvite): ?>
            <a class="btn btn-primary" href="equipe-inviter.php" data-modal style="margin-top:16px;"><?= $icons['users'] ?>Inviter un membre</a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="table-scroll">
          <table>
            <thead>
              <tr><th>MEMBRE</th><th>EMAIL</th><th>RÔLE</th><th>STATUT</th><th>DEPUIS</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($members as $m): ?>
                <?php
                  $tone = $roleMeta[$m['role_nom']]['tone'] ?? 'teal';
                  $initials = mb_strtoupper(mb_substr($m['prenom'], 0, 1) . mb_substr($m['nom'], 0, 1));
                ?>
                <tr>
                  <td>
                    <div class="cell">
                      <div class="cell-avatar role-<?= $tone ?>"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                      <div class="cell-title"><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                  </td>
                  <td><?= htmlspecialchars($m['email'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><span class="role-badge <?= $tone ?>"><?= htmlspecialchars($m['role_nom'], ENT_QUOTES, 'UTF-8') ?></span></td>
                  <td><span class="status-badge <?= $statusTones[$m['statut']] ?? '' ?>"><?= htmlspecialchars($statusLabels[$m['statut']] ?? $m['statut'], ENT_QUOTES, 'UTF-8') ?></span></td>
                  <td class="cell-sub"><?= (new DateTimeImmutable($m['created_at']))->format('d/m/Y') ?></td>
                  <td>
                    <div class="row-actions">
                      <a class="consult-link" href="equipe-detail.php?id=<?= (int) $m['id_utilisateur'] ?>" data-modal>
                        Consulter
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 15 12 9 18"/></svg>
                      </a>
                      <button type="button" class="icon-action" data-copy-email="<?= htmlspecialchars($m['email'], ENT_QUOTES, 'UTF-8') ?>" title="Copier l’email">
                        <?= $icons['copy'] ?>
                      </button>
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
$extraScripts = '<script src="equipe.js"></script>';
require dirname(__DIR__, 3) . '/layouts/footer.php';
