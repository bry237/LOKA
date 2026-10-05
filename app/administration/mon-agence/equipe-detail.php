<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/MonAgenceController.php';
require_once __DIR__ . '/team-presentation.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$id = (int) ($_GET['id'] ?? 0);
$member = $id > 0 ? MonAgenceController::memberDetail($idAgence, $id) : null;

if (!$member) {
	header('Location: equipe.php?error=' . urlencode('Membre introuvable.') . ($isModal ? '&modal=1' : ''));
	exit;
}

$pageTitle = $member['prenom'] . ' ' . $member['nom'];
$activeNav = 'Équipe';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Détail du membre.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css"><link rel="stylesheet" href="equipe.css">';

$csrfToken = Auth::csrfToken();
$roleMeta = teamRoleMeta();
$tone = $roleMeta[$member['role_nom']]['tone'] ?? 'teal';
$isSelf = (int) $member['id_utilisateur'] === (int) $currentUser['id_utilisateur'];

$statusLabels = ['ACTIVE' => 'Actif', 'PENDING' => 'En attente', 'INACTIVE' => 'Inactif', 'LOCKED' => 'Verrouillé'];
$statusTones = ['ACTIVE' => 'actif', 'PENDING' => 'attente', 'INACTIVE' => 'inactif', 'LOCKED' => 'suspendu'];

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <a class="detail-back" href="equipe.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour à l’équipe
    </a>

    <?php if (isset($_GET['error'])): ?>
      <div class="feedback error"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="detail-header">
      <div class="detail-identity">
        <div class="detail-avatar role-<?= $tone ?>"><?= htmlspecialchars(mb_strtoupper(mb_substr($member['prenom'], 0, 1) . mb_substr($member['nom'], 0, 1)), ENT_QUOTES, 'UTF-8') ?></div>
        <div>
          <h1 class="detail-name">
            <?= htmlspecialchars($member['prenom'] . ' ' . $member['nom'], ENT_QUOTES, 'UTF-8') ?>
            <span class="status-badge <?= $statusTones[$member['statut']] ?? '' ?>"><?= htmlspecialchars($statusLabels[$member['statut']] ?? $member['statut'], ENT_QUOTES, 'UTF-8') ?></span>
          </h1>
          <p class="detail-sub"><span class="role-badge <?= $tone ?>"><?= htmlspecialchars($member['role_nom'], ENT_QUOTES, 'UTF-8') ?></span><?= $isSelf ? ' · vous-même' : '' ?></p>
        </div>
      </div>
      <div class="detail-actions">
        <a class="btn" href="equipe-modifier.php?id=<?= $id ?>"<?= $isModal ? ' data-modal' : '' ?>>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
          Modifier
        </a>
        <?php if (!$isSelf): ?>
          <form class="inline-form" method="post" action="equipe-supprimer.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <?php if ($isModal): ?><input type="hidden" name="modal" value="1"><?php endif; ?>
            <button type="submit" class="btn btn-danger" onclick="return confirm('Retirer <?= htmlspecialchars(addslashes($member['prenom'] . ' ' . $member['nom']), ENT_QUOTES, 'UTF-8') ?> de l’équipe ? Cette personne ne pourra plus se connecter.');">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/></svg>
              Supprimer
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <div class="detail-grid">
      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Informations</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Email</span><span class="info-value"><?= htmlspecialchars($member['email'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-row"><span class="info-label">Téléphone</span><span class="info-value"><?= htmlspecialchars($member['telephone'] ?: '—', ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-row"><span class="info-label">Vérification</span><span class="status-badge <?= $member['telephone_verifie'] ? 'actif' : 'attente' ?>"><?= $member['telephone_verifie'] ? 'Téléphone vérifié' : 'Non vérifié' ?></span></div>
          </div>
        </div>
      </div>
      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Équipe</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Rôle</span><span class="info-value"><?= htmlspecialchars($member['role_nom'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-row"><span class="info-label">Membre depuis</span><span class="info-value"><?= (new DateTimeImmutable($member['created_at']))->format('d/m/Y') ?></span></div>
            <div class="info-row"><span class="info-label">Dernière mise à jour</span><span class="info-value"><?= (new DateTimeImmutable($member['updated_at']))->format('d/m/Y H:i') ?></span></div>
          </div>
        </div>
      </div>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
