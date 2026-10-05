<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once __DIR__ . '/AbonnementController.php';

$currentUser = Authorization::requireRole('Administrateur plateforme');

$plans = AbonnementController::list();

$pageTitle = 'Abonnements';
$activeNav = 'Abonnements';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Gérez les formules d’abonnement proposées aux agences.';
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));

$csrfToken = Auth::csrfToken();

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;">
      <div>
        <h1>Abonnements</h1>
        <p><?= count($plans) ?> formule<?= count($plans) > 1 ? 's' : '' ?> au catalogue</p>
      </div>
      <a class="btn btn-primary" href="ajouter.php" data-modal>+ Nouveau plan</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div class="feedback success"><?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <div class="feedback error"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="panel table-panel">
      <div class="table-scroll">
        <table>
          <thead>
            <tr>
              <th>FORMULE</th>
              <th>PRIX / MOIS</th>
              <th>LIMITE UTILISATEURS</th>
              <th>LIMITE BIENS</th>
              <th>PAIEMENT STRIPE</th>
              <th>STATUT</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$plans): ?>
              <tr class="empty-row"><td colspan="7">Aucun plan au catalogue.</td></tr>
            <?php endif; ?>
            <?php foreach ($plans as $p): ?>
              <tr>
                <td>
                  <div class="cell-title"><?= htmlspecialchars($p['nom'], ENT_QUOTES, 'UTF-8') ?></div>
                  <?php if ($p['description']): ?><div class="cell-sub"><?= htmlspecialchars($p['description'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                </td>
                <td><span class="cell-price"><?= (float) $p['prix_mensuel'] > 0 ? number_format((float) $p['prix_mensuel'], 2, ',', ' ') . ' €' : 'Gratuit' ?></span></td>
                <td><span class="cell-limit<?= $p['limite_utilisateurs'] === null ? ' unlimited' : '' ?>"><?= $p['limite_utilisateurs'] !== null ? (int) $p['limite_utilisateurs'] : 'Illimité' ?></span></td>
                <td><span class="cell-limit<?= $p['limite_biens'] === null ? ' unlimited' : '' ?>"><?= $p['limite_biens'] !== null ? (int) $p['limite_biens'] : 'Illimité' ?></span></td>
                <td><span class="status-badge <?= $p['stripe_price_id'] ? 'actif' : 'attente' ?>"><?= $p['stripe_price_id'] ? 'Configuré' : ((float) $p['prix_mensuel'] > 0 ? 'Non configuré' : 'Gratuit') ?></span></td>
                <td><span class="status-badge <?= $p['actif'] ? 'actif' : 'suspendu' ?>"><?= $p['actif'] ? 'Actif' : 'Désactivé' ?></span></td>
                <td>
                  <div style="display:flex;gap:8px;align-items:center;justify-content:flex-end;">
                    <a class="consult-link" href="modifier.php?id=<?= (int) $p['id_abonnement'] ?>" data-modal>
                      Modifier
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 15 12 9 18"/></svg>
                    </a>
                    <form class="inline-form" method="post" action="toggle-actif.php">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                      <input type="hidden" name="id" value="<?= (int) $p['id_abonnement'] ?>">
                      <input type="hidden" name="actif" value="<?= $p['actif'] ? '0' : '1' ?>">
                      <button type="submit" class="btn btn-sm <?= $p['actif'] ? 'btn-danger' : 'btn-success' ?>"><?= $p['actif'] ? 'Désactiver' : 'Activer' ?></button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
