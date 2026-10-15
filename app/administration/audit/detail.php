<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/AuditController.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$isModal = (($_GET['modal'] ?? $_POST['modal'] ?? '') === '1');

$id = (int) ($_GET['id'] ?? 0);
$entry = $id > 0 ? AuditController::detail($id) : null;

if (!$entry) {
	header('Location: index.php?error=' . urlencode('Entrée introuvable.') . ($isModal ? '&modal=1' : ''));
	exit;
}

$pageTitle = 'Détail de l’action';
$activeNav = 'Journal';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = "Détail d'une entrée du journal d'audit.";
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));

$actionLabels = [
	'CREATE' => 'Création', 'UPDATE' => 'Modification', 'DELETE' => 'Suppression',
	'LOGIN' => 'Connexion', 'LOGOUT' => 'Déconnexion', 'EXPORT' => 'Export',
	'DOWNLOAD' => 'Téléchargement', 'OTHER' => 'Autre',
];
$auteur = trim(($entry['prenom'] ?? '') . ' ' . ($entry['nom'] ?? ''));

// Chemins absolus : voir le même correctif sur agences/detail.php et utilisateurs/detail.php.
$base = '/LOKA/app/administration/audit';

if (!$isModal) require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <?php if (!$isModal): ?>
    <a class="detail-back" href="<?= $base ?>/index.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Retour au journal
    </a>
    <?php endif; ?>

    <div class="page-header">
      <h1><?= htmlspecialchars($actionLabels[$entry['action']] ?? $entry['action'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($entry['entite'], ENT_QUOTES, 'UTF-8') ?><?= $entry['id_entite'] ? ' #' . (int) $entry['id_entite'] : '' ?></h1>
      <p><?= (new DateTimeImmutable($entry['date_action']))->format('d/m/Y à H:i:s') ?></p>
    </div>

    <div class="detail-grid">
      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Contexte</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Auteur</span><span class="info-value"><?= $auteur !== '' ? htmlspecialchars($auteur . ' (' . $entry['email'] . ')', ENT_QUOTES, 'UTF-8') : 'Système' ?></span></div>
            <div class="info-row"><span class="info-label">Agence</span><span class="info-value"><?= $entry['agence_nom'] ? htmlspecialchars($entry['agence_nom'], ENT_QUOTES, 'UTF-8') : '—' ?></span></div>
            <div class="info-row"><span class="info-label">Adresse IP</span><span class="info-value"><?= htmlspecialchars($entry['adresse_ip'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="info-row"><span class="info-label">Navigateur</span><span class="info-value" style="word-break:break-all;"><?= htmlspecialchars($entry['user_agent'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span></div>
          </div>
        </div>
      </div>

      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Avant</div></div>
          <?php if (!$entry['ancienne_valeur']): ?>
            <p class="empty-note">Aucune donnée précédente.</p>
          <?php else: ?>
            <pre style="margin:0;padding:14px 18px;font-size:12px;white-space:pre-wrap;word-break:break-word;"><?= htmlspecialchars(json_encode($entry['ancienne_valeur'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
          <?php endif; ?>
        </div>
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Après</div></div>
          <?php if (!$entry['nouvelle_valeur']): ?>
            <p class="empty-note">Aucune donnée enregistrée.</p>
          <?php else: ?>
            <pre style="margin:0;padding:14px 18px;font-size:12px;white-space:pre-wrap;word-break:break-word;"><?= htmlspecialchars(json_encode($entry['nouvelle_valeur'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
          <?php endif; ?>
        </div>
      </div>
    </div>
<?php
if (!$isModal) require dirname(__DIR__, 3) . '/layouts/footer.php';
