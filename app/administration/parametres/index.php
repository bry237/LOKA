<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/ParametreController.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$overview = ParametreController::overview();

$pageTitle = 'Paramètres';
$activeNav = 'Parametres';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Vue d’ensemble de la plateforme.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header">
      <h1>Paramètres</h1>
      <p>État général de la plateforme et de ses intégrations.</p>
    </div>

    <div style="display:flex;gap:8px;margin:0 0 18px;">
      <a class="btn btn-primary" href="index.php">Général</a>
      <a class="btn" href="agence.php">Agences</a>
      <a class="btn" href="notifications.php">Notifications</a>
    </div>

    <div class="detail-grid">
      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Agences</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Actives</span><span class="info-value"><?= $overview['agences']['ACTIVE'] ?></span></div>
            <div class="info-row"><span class="info-label">En attente</span><span class="info-value"><?= $overview['agences']['PENDING'] ?></span></div>
            <div class="info-row"><span class="info-label">Suspendues</span><span class="info-value"><?= $overview['agences']['SUSPENDED'] ?></span></div>
          </div>
        </div>
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Comptes & abonnements</div></div>
          <div class="info-list">
            <div class="info-row"><span class="info-label">Utilisateurs actifs</span><span class="info-value"><?= $overview['utilisateurs_actifs'] ?></span></div>
            <div class="info-row"><span class="info-label">Abonnements actifs</span><span class="info-value"><?= $overview['abonnements_actifs'] ?></span></div>
          </div>
        </div>
      </div>

      <div class="detail-col">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Intégrations</div></div>
          <div class="info-list">
            <div class="info-row">
              <span class="info-label">Stripe</span>
              <span class="status-badge <?= $overview['integrations']['stripe']['configure'] ? 'actif' : 'suspendu' ?>">
                <?= $overview['integrations']['stripe']['configure'] ? ('Configuré · mode ' . $overview['integrations']['stripe']['mode']) : 'Non configuré' ?>
              </span>
            </div>
            <div class="info-row">
              <span class="info-label">SMS</span>
              <span class="status-badge <?= $overview['integrations']['sms']['configure'] ? 'actif' : 'attente' ?>">
                <?= htmlspecialchars($overview['integrations']['sms']['provider'], ENT_QUOTES, 'UTF-8') ?><?= $overview['integrations']['sms']['configure'] ? '' : ' · identifiants manquants' ?>
              </span>
            </div>
            <div class="info-row">
              <span class="info-label">IA (analyse documents)</span>
              <span class="status-badge <?= $overview['integrations']['ia']['configure'] ? 'actif' : 'attente' ?>">
                <?= $overview['integrations']['ia']['configure'] ? 'Configurée' : 'Non configurée' ?>
              </span>
            </div>
          </div>
          <p class="cell-sub" style="padding:0 18px 14px;display:block;">Ces services se configurent par variables d’environnement, jamais depuis l’interface.</p>
        </div>
      </div>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
