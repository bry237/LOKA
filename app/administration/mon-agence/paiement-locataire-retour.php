<?php
declare(strict_types=1);

/**
 * Page de retour Stripe (success_url / cancel_url) après une demande de paiement de loyer.
 * Non authentifiée — comme app/administration/abonnements/webhook.php, il n'y a pas de session
 * locataire. On n'y affiche volontairement aucune donnée sensible (montant, coordonnées) :
 * juste une confirmation neutre, même si quelqu'un d'autre tombe sur l'URL.
 */
require_once __DIR__ . '/MonAgenceController.php';

$sessionId = (string) ($_GET['session_id'] ?? '');
$cancelled = isset($_GET['cancel']);
$status = 'pending';

if (!$cancelled && $sessionId !== '') {
	// Filet de sécurité si le webhook n'a pas encore (ou jamais, en local sans `stripe listen`)
	// traité l'événement : on confirme directement auprès de Stripe au retour du locataire.
	$status = MonAgenceController::confirmRentCheckoutSession($sessionId);
}

if ($cancelled) {
	$title = 'Paiement annulé';
	$message = 'Vous avez annulé le paiement. Aucune somme n’a été prélevée.';
	$tone = 'neutral';
} elseif (in_array($status, ['confirmed', 'already'], true)) {
	$title = 'Paiement reçu';
	$message = 'Merci, votre paiement a bien été enregistré. Une quittance sera disponible auprès de votre agence.';
	$tone = 'success';
} elseif ($status === 'pending') {
	$title = 'Paiement en cours de confirmation';
	$message = 'Votre paiement a été transmis. Il sera confirmé dans quelques instants.';
	$tone = 'neutral';
} else {
	$title = 'Paiement introuvable';
	$message = 'Nous n’avons pas pu retrouver ce paiement. Contactez votre agence si le problème persiste.';
	$tone = 'neutral';
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> — LOKA</title>
<style>
  body{font-family:'Plus Jakarta Sans',Arial,sans-serif;background:#f4f7f7;color:#1a2b2b;margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}
  .card{background:#fff;border-radius:16px;box-shadow:0 8px 28px rgba(15,139,141,.1);max-width:420px;width:100%;padding:36px 32px;text-align:center;}
  .icon{width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;}
  .icon.success{background:#e4f6ef;color:#1f9d6f;}
  .icon.neutral{background:#eef2f2;color:#5a6b6b;}
  h1{font-size:19px;margin:0 0 10px;}
  p{font-size:14px;color:#5a6b6b;line-height:1.6;margin:0;}
  .brand{margin-top:28px;font-size:12px;color:#9aa8a8;letter-spacing:.04em;text-transform:uppercase;}
</style>
</head>
<body>
  <div class="card">
    <div class="icon <?= $tone ?>">
      <?php if ($tone === 'success'): ?>
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      <?php else: ?>
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?php endif; ?>
    </div>
    <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
    <div class="brand">LOKA</div>
  </div>
</body>
</html>
