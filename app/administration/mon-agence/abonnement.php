<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once dirname(__DIR__, 3) . '/core/SubscriptionLimiter.php';
require_once __DIR__ . '/MonAgenceModel.php';
require_once dirname(__DIR__) . '/abonnements/AbonnementModel.php';
require_once dirname(__DIR__) . '/abonnements/AbonnementController.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

// Confirme le paiement directement auprès de Stripe au retour de Checkout, en secours du
// webhook (indispensable en local où Stripe ne peut pas appeler une URL localhost).
$planJustConfirmed = false;
$planPending = false;
if (($_GET['success'] ?? '') === '1') {
	if (!empty($_GET['session_id'])) {
		$confirmStatus = AbonnementController::confirmCheckoutSession((string) $_GET['session_id']);
		$planPending = $confirmStatus === 'pending';
	}
	$planJustConfirmed = !$planPending;
} elseif (isset($_GET['success'])) {
	$planJustConfirmed = true; // changement de plan gratuit, déjà appliqué par checkout.php
}

$subscription = MonAgenceModel::subscription($idAgence);
$plans = AbonnementModel::activePlans();
$usage = SubscriptionLimiter::usage($idAgence);

// Le catalogue contient aussi d'anciens plans créés pour tester l'intégration Stripe (noms et
// descriptions de test). On ne les affiche pas ici : présentation uniquement, rien n'est modifié
// en base — un administrateur peut les désactiver proprement depuis la gestion des abonnements.
$catalogPlanNames = ['Gratuit', 'Pro', 'Max'];
$plans = array_values(array_filter(
	$plans,
	static fn (array $p): bool => in_array($p['nom'], $catalogPlanNames, true) || ($subscription && $p['nom'] === $subscription['nom'])
));

$pageTitle = 'Abonnement';
$activeNav = 'Abonnement';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = "Suivez votre formule et vos limites d'usage en un coup d'œil.";
$profileName = trim($currentUser['prenom'] . ' ' . $currentUser['nom']);
$profileRole = $currentUser['role_nom'];
$profileInitials = mb_strtoupper(mb_substr($currentUser['prenom'], 0, 1) . mb_substr($currentUser['nom'], 0, 1));
$extraHead = '<link rel="stylesheet" href="dashboard.css">';

$csrfToken = Auth::csrfToken();

// Prix du moins cher au plus cher : situe chaque formule (entrée / standard / premium) pour l'accent visuel
// et repère la formule médiane à mettre en avant — utile seulement sur le cas courant à 3 formules actives.
$sortedPrices = array_values(array_unique(array_map(static fn (array $p): float => (float) $p['prix_mensuel'], $plans)));
sort($sortedPrices);
$minPrice = $sortedPrices[0] ?? 0.0;
$maxPrice = $sortedPrices[count($sortedPrices) - 1] ?? 0.0;
$featuredNom = count($plans) === 3 ? ($plans[1]['nom'] ?? null) : null;

$tierClass = static function (float $prix) use ($minPrice, $maxPrice): string {
	if ($prix <= $minPrice) return 'entry';
	if ($prix >= $maxPrice && $maxPrice > $minPrice) return 'premium';
	return 'standard';
};

$meter = static function (int $used, ?int $limit): array {
	if ($limit === null) {
		return ['pct' => 8, 'tone' => '', 'label' => 'Illimité'];
	}
	$pct = $limit > 0 ? min(100, (int) round($used / $limit * 100)) : 100;
	$tone = $pct >= 100 ? 'full' : ($pct >= 80 ? 'warn' : '');
	return ['pct' => max($pct, 4), 'tone' => $tone, 'label' => $used . ' / ' . $limit];
};
$usersMeter = $meter($usage['users'], $usage['limitUsers']);
$biensMeter = $meter($usage['biens'], $usage['limitBiens']);

$checkIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';

// Modale de confirmation affichée au chargement : réutilise la modale globale (layouts/footer.php)
// plutôt qu'un simple bandeau, pour rendre le changement de plan explicite et sans ambiguïté.
// Compacte à dessein (pas de gros pictogramme ni d'espace vide) : un repère de statut discret,
// le plan mis en avant, une ligne de confirmation, un seul bouton.
$extraScripts = '';
if ($planJustConfirmed && $subscription) {
	$checkMini = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
	$modalHtml = '<div class="plan-confirm">'
		. '<div class="plan-confirm-head"><span class="plan-confirm-check">' . $checkMini . '</span><h3>Abonnement mis à jour</h3></div>'
		. '<p class="plan-confirm-lead">Votre agence est maintenant sur le plan</p>'
		. '<p class="plan-confirm-plan">' . htmlspecialchars($subscription['nom'], ENT_QUOTES, 'UTF-8') . '</p>'
		. '<p class="plan-confirm-text">Votre nouvel abonnement est actif dès maintenant.</p>'
		. '<button type="button" class="btn btn-primary plan-confirm-btn" data-modal-cancel>Continuer</button>'
		. '</div>';
	$modalHtmlJs = json_encode($modalHtml, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
	$extraScripts = <<<HTML
<script>
(function(){
  var overlay = document.getElementById('modalOverlay');
  var panel = document.getElementById('modalPanel');
  var body = document.getElementById('modalBody');
  if (!overlay || !panel || !body) return;
  body.innerHTML = {$modalHtmlJs};
  panel.style.maxWidth = 'min(380px, calc(100vw - 32px))';
  panel.style.minHeight = '0';
  panel.style.alignSelf = 'center';
  overlay.hidden = false;
  document.body.classList.add('modal-open');
  requestAnimationFrame(function(){ overlay.classList.add('show'); });
  if (window.history.replaceState) window.history.replaceState({}, '', window.location.pathname);
})();
</script>
HTML;
}

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header">
      <h1>Abonnement</h1>
      <p>Comparez les formules et changez de plan à tout moment.</p>
    </div>

    <?php if ($planPending): ?>
      <div class="feedback success">Paiement reçu, confirmation en cours — rechargez la page dans quelques secondes si le plan ne s'est pas encore mis à jour.</div>
    <?php elseif (isset($_GET['cancel'])): ?>
      <div class="feedback error">Paiement annulé.</div>
    <?php elseif (isset($_GET['error'])): ?>
      <div class="feedback error"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <!-- Votre abonnement -->
    <div class="panel subscription-overview">
      <div class="subscription-overview-top">
        <div>
          <span class="subscription-overview-label">Votre abonnement</span>
          <div class="subscription-overview-name">
            <?= htmlspecialchars($subscription['nom'] ?? 'Aucun plan actif', ENT_QUOTES, 'UTF-8') ?>
            <?php if ($subscription): ?><span class="plan-current-badge"><?= $checkIcon ?>Plan actuel</span><?php endif; ?>
          </div>
        </div>
        <?php if ($subscription): ?>
          <div class="subscription-overview-price">
            <?php if ((float) $subscription['prix_mensuel'] > 0): ?>
              <span class="amount"><?= number_format((float) $subscription['prix_mensuel'], 2, ',', ' ') ?> €</span><span class="period">/ mois</span>
            <?php else: ?>
              <span class="amount">Gratuit</span>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="subscription-overview-usage">
        <div class="usage-meter">
          <div class="usage-meter-head">
            <span class="usage-meter-label">Utilisateurs</span>
            <span class="usage-meter-value"><?= htmlspecialchars($usersMeter['label'], ENT_QUOTES, 'UTF-8') ?></span>
          </div>
          <div class="usage-meter-track"><div class="usage-meter-fill <?= $usersMeter['tone'] ?>" style="width:<?= $usersMeter['pct'] ?>%;"></div></div>
        </div>
        <div class="usage-meter">
          <div class="usage-meter-head">
            <span class="usage-meter-label">Biens</span>
            <span class="usage-meter-value"><?= htmlspecialchars($biensMeter['label'], ENT_QUOTES, 'UTF-8') ?></span>
          </div>
          <div class="usage-meter-track"><div class="usage-meter-fill <?= $biensMeter['tone'] ?>" style="width:<?= $biensMeter['pct'] ?>%;"></div></div>
        </div>
      </div>
    </div>

    <!-- Nos formules -->
    <div class="plans-heading">
      <h2>Nos formules</h2>
      <p>Choisissez la formule adaptée à la taille de votre agence.</p>
    </div>

    <div class="plan-grid">
      <?php foreach ($plans as $plan): ?>
        <?php
          $isCurrent = ($subscription['nom'] ?? null) === $plan['nom'];
          $isFeatured = !$isCurrent && $plan['nom'] === $featuredNom;
          $tier = $tierClass((float) $plan['prix_mensuel']);
          $prix = (float) $plan['prix_mensuel'];
        ?>
        <div class="plan-card <?= $tier ?><?= $isCurrent ? ' current' : '' ?>">
          <?php if ($isCurrent): ?>
            <div class="plan-card-eyebrow current">Plan actuel</div>
          <?php elseif ($isFeatured): ?>
            <div class="plan-card-eyebrow">Recommandé</div>
          <?php endif; ?>
          <div class="plan-card-head<?= ($isCurrent || $isFeatured) ? '' : ' no-eyebrow' ?>">
            <div class="plan-card-name"><?= htmlspecialchars($plan['nom'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="plan-card-tagline"><?= $plan['description'] ? htmlspecialchars($plan['description'], ENT_QUOTES, 'UTF-8') : '' ?></div>
          </div>
          <div class="plan-card-price">
            <span class="amount"><?= $prix > 0 ? number_format($prix, 2, ',', ' ') . ' €' : 'Gratuit' ?></span>
            <?php if ($prix > 0): ?><span class="period">/ mois</span><?php endif; ?>
          </div>
          <ul class="plan-card-features">
            <li><?= $checkIcon ?><span><?= $plan['limite_utilisateurs'] !== null ? ((int) $plan['limite_utilisateurs'] . ' utilisateur' . ((int) $plan['limite_utilisateurs'] > 1 ? 's' : '')) : 'Utilisateurs illimités' ?></span></li>
            <li><?= $checkIcon ?><span><?= $plan['limite_biens'] !== null ? ((int) $plan['limite_biens'] . ' biens gérés') : 'Biens illimités' ?></span></li>
          </ul>
          <div class="plan-card-foot">
            <?php if ($isCurrent): ?>
              <span class="plan-current-tag"><?= $checkIcon ?>Plan actuel</span>
            <?php else: ?>
              <form method="post" action="../abonnements/checkout.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id_abonnement" value="<?= (int) $plan['id_abonnement'] ?>">
                <button type="submit" class="btn <?= $isFeatured ? 'btn-primary' : '' ?>">Passer à ce plan</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <p class="plans-secure-note"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2.5"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>Paiement sécurisé par Stripe</p>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
