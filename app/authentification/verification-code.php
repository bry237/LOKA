<?php
declare(strict_types=1);

require_once __DIR__ . '/AuthController.php';

Session::start();
$pending = $_SESSION['loka_pending_verification'] ?? null;
if (!$pending || ($pending['expires_at'] ?? 0) < time()) {
	unset($_SESSION['loka_pending_verification']);
	header('Location: connexion.php?verification_expired=1');
	exit;
}

$userId = (int) $pending['id_utilisateur'];
$telephone = (string) $pending['telephone'];
$csrfToken = Auth::csrfToken();
$error = null;
$resent = false;
$cooldownSeconds = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		$error = 'Votre session a expiré. Rechargez la page.';
	} elseif (($_POST['action'] ?? '') === 'resend') {
		$result = OtpService::generateAndSend($userId, $telephone);
		if (!$result['ok'] && $result['error'] === 'COOLDOWN') {
			$cooldownSeconds = $result['retry_after'];
			$error = 'Merci de patienter avant de redemander un code.';
		} else {
			$resent = true;
		}
	} else {
		$code = trim((string) ($_POST['code'] ?? ''));
		$result = OtpService::verify($userId, $code);
		if ($result['ok']) {
			unset($_SESSION['loka_pending_verification']);
			header('Location: connexion.php?registered=1');
			exit;
		}
		$error = match ($result['error']) {
			'INVALID' => 'Code incorrect. Réessayez.',
			'EXPIRED' => 'Ce code a expiré. Demandez-en un nouveau.',
			'LOCKED' => 'Trop de tentatives. Demandez un nouveau code.',
			default => 'Aucun code en attente. Demandez-en un nouveau.',
		};
	}
}
?>
<!doctype html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Vérification du téléphone | LOKA</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="auth.css">
</head>
<body>
<main class="auth-page">
	<section class="auth-card">
		<div class="auth-panel__inner">
			<a class="brand" href="../../index%20(2).html" aria-label="Retour à l’accueil LOKA">
				<span class="brand__mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><path d="M4 14.2 16 4l12 10.2" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M7.5 12.5V27h17V12.5M12 27v-7h8v7" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/></svg></span><span class="brand__name">LOKA</span>
			</a>
			<section class="form-panel is-active" role="tabpanel">
				<header class="form-heading">
					<p class="eyebrow">Vérification</p>
					<h1>Confirmez votre numéro</h1>
					<p>Saisissez le code à 6 chiffres envoyé par SMS au <?= htmlspecialchars($telephone, ENT_QUOTES, 'UTF-8') ?>.</p>
				</header>
				<?php if ($resent): ?><p class="form-message form-message--info" role="status">Un nouveau code vous a été envoyé par SMS.</p><?php endif; ?>
				<?php if ($error !== null): ?><p class="form-message form-message--error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
				<form class="auth-form" method="post" action="verification-code.php" novalidate>
					<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
					<input type="hidden" name="action" value="verify">
					<div class="field"><label for="otp-code">Code de vérification</label><input id="otp-code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus></div>
					<button class="button button--primary" type="submit">Vérifier</button>
				</form>
				<form method="post" action="verification-code.php">
					<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
					<input type="hidden" name="action" value="resend">
					<p class="switch-line"><button class="button" type="submit"<?= $cooldownSeconds !== null ? ' disabled' : '' ?>>Renvoyer le code</button></p>
				</form>
			</section>
		</div>
	</section>
</main>
</body>
</html>
