<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/core/Authorization.php';
require_once dirname(__DIR__, 3) . '/core/SubscriptionLimiter.php';
require_once __DIR__ . '/MonAgenceModel.php';
require_once dirname(__DIR__) . '/abonnements/AbonnementModel.php';

$currentUser = Authorization::requireRole('Administrateur agence');
$idAgence = (int) $currentUser['id_agence'];

$subscription = MonAgenceModel::subscription($idAgence);
$plans = AbonnementModel::activePlans();
$usage = SubscriptionLimiter::usage($idAgence);

$pageTitle = 'Abonnement';
$activeNav = 'Abonnement';
$sidebarFile = 'sidebar-agence.php';
$greetingTitle = 'Bonjour, ' . $currentUser['prenom'];
$greetingSubtitle = 'Choisissez la formule adaptée à votre agence.';
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

require dirname(__DIR__, 3) . '/layouts/header.php';
?>
    <div class="page-header">
      <h1>Abonnement</h1>
      <p>Plan actuel : <strong><?= htmlspecialchars($subscription['nom'] ?? 'Aucun', ENT_QUOTES, 'UTF-8') ?></strong> — comparez les formules et changez à tout moment.</p>
    </div>

    <?php if (($_GET['success'] ?? '') === '1'): ?>
      <div class="feedback success">Paiement reçu, confirmation en cours (quelques secondes).</div>
    <?php elseif (isset($_GET['success'])): ?>
      <div class="feedback success"><?= htmlspecialchars((string) $_GET['success'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php elseif (isset($_GET['cancel'])): ?>
      <div class="feedback error">Paiement annulé.</div>
    <?php elseif (isset($_GET['error'])): ?>
      <div class="feedback error"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="usage-summary">
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

    <div class="plan-grid">
      <?php foreach ($plans as $plan): ?>
        <?php
          $isCurrent = ($subscription['nom'] ?? null) === $plan['nom'];
          $isFeatured = !$isCurrent && $plan['nom'] === $featuredNom;
          $tier = $tierClass((float) $plan['prix_mensuel']);
          $prix = (float) $plan['prix_mensuel'];
        ?>
        <div class="plan-card <?= $tier ?><?= $isCurrent ? ' current' : '' ?>">
          <?php if ($isFeatured): ?>
            <div class="plan-card-eyebrow">Recommandé</div>
          <?php endif; ?>
          <div class="plan-card-head<?= $isFeatured ? '' : ' no-eyebrow' ?>">
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
            <li<?= $prix > 0 ? '' : ' class="muted"' ?>><?= $checkIcon ?><span>Paiement sécurisé par Stripe</span></li>
          </ul>
          <div class="plan-card-foot">
            <?php if ($isCurrent): ?>
              <span class="plan-current-tag"><?= $checkIcon ?>Plan actuel</span>
            <?php else: ?>
              <form method="post" action="../abonnements/checkout.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id_abonnement" value="<?= (int) $plan['id_abonnement'] ?>">
                <button type="submit" class="btn <?= $isFeatured ? 'btn-primary' : '' ?>">
                  <?= $prix > 0 ? 'Passer à ce plan' : 'Choisir ce plan' ?>
                </button>
              </form>
              <?php if ($prix > 0): ?><span class="plan-cta-note">Paiement par carte via Stripe Checkout</span><?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
<?php
require dirname(__DIR__, 3) . '/layouts/footer.php';
