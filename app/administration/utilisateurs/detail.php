<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/UtilisateurController.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$id = (int) ($_GET['id'] ?? 0);
$u = $id > 0 ? UtilisateurController::detail($id) : null;

if (!$u) {
	header('Location: index.php?error=' . urlencode('Utilisateur introuvable.') . ($isModal ? '&modal=1' : ''));
	exit;
}

$pageTitle = $u['prenom'] . ' ' . $u['nom'];
$activeNav = 'Utilisateurs';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = "Détail de l'utilisateur.";
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));

$csrfToken = Auth::csrfToken();
$redirectTarget = 'detail.php?id=' . $id;
$isSelf = (int) $u['id_utilisateur'] === (int) $currentUser['id_utilisateur'];

$statusLabels = ['ACTIVE' => 'Actif', 'INACTIVE' => 'Inactif', 'LOCKED' => 'Verrouillé'];
$statusTones = ['ACTIVE' => 'actif', 'INACTIVE' => 'inactif', 'LOCKED' => 'suspendu'];

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <a class="detail-back" href="index.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour aux utilisateurs
    </a>

    <?php if (isset($_GET['success'])): ?>
      <div class="feedback success"><?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="feedback error"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="detail-header">
      <div class="detail-identity">
        <div class="detail-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($u['prenom'], 0, 1) . mb_substr($u['nom'], 0, 1)), ENT_QUOTES, 'UTF-8') ?></div>
        <div>
          <h1 class="detail-name">
            <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom'], ENT_QUOTES, 'UTF-8') ?>
            <span class="status-badge <?= $statusTones[$u['statut']] ?? '' ?>"><?= htmlspecialchars($statusLabels[$u['statut']] ?? $u['statut'], ENT_QUOTES, 'UTF-8') ?></span>
          </h1>
          <p class="detail-sub"><?= htmlspecialchars($u['role_nom'], ENT_QUOTES, 'UTF-8') ?><?= $u['agence_nom'] ? ' · ' . htmlspecialchars($u['agence_nom'], ENT_QUOTES, 'UTF-8') : '' ?></p>
        </div>
      </div>
      <div class="detail-actions">
        <?php if ($isSelf): ?>
          <span class="cell-sub">Vous consultez votre propre compte</span>
        <?php elseif ($u['statut'] === 'ACTIVE'): ?>
          <form class="inline-form" method="post" action="desactiver.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" value="<?= (int) $u['id_utilisateur'] ?>">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectTarget, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-danger" onclick="return confirm('Désactiver ce compte ?');">Désactiver</button>
          </form>
        <?php else: ?>
          <form class="inline-form" method="post" action="activer.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id" value="<?= (int) $u['id_utilisateur'] ?>">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectTarget, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-success">Activer</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <div class="detail-grid">
      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Informations</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Email</span><span class="info-value"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-row"><span class="info-label">Téléphone</span><span class="info-value"><?= htmlspecialchars($u['telephone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-row"><span class="info-label">Rôle</span><span class="info-value"><?= htmlspecialchars($u['role_nom'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-row"><span class="info-label">Agence</span><span class="info-value"><?= $u['agence_nom'] ? htmlspecialchars($u['agence_nom'], ENT_QUOTES, 'UTF-8') : '—' ?></span></div>
            <div class="info-row"><span class="info-label">Membre depuis</span><span class="info-value"><?= (new DateTimeImmutable($u['created_at']))->format('d/m/Y') ?></span></div>
            <div class="info-row"><span class="info-label">Dernière mise à jour</span><span class="info-value"><?= (new DateTimeImmutable($u['updated_at']))->format('d/m/Y H:i') ?></span></div>
          </div>
        </div>
      </div>

      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Connexion</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Statut</span><span class="status-badge <?= $statusTones[$u['statut']] ?? '' ?>"><?= htmlspecialchars($statusLabels[$u['statut']] ?? $u['statut'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-row"><span class="info-label">Dernière connexion</span><span class="info-value"><?= $u['derniere_connexion'] ? (new DateTimeImmutable($u['derniere_connexion']))->format('d/m/Y H:i') : 'Jamais connecté' ?></span></div>
          </div>
        </div>
      </div>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
