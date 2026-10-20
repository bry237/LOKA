<?php
declare(strict_types=1);

require_once __DIR__ . '/AuthController.php';

$csrfToken = Auth::csrfToken();
$errors = [];
$submitted = false;
$devLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	[$errors, $devLink] = AuthController::handleForgotPassword($_POST);
	if (!$errors) {
		$submitted = true;
	}
}

function e(?string $value): string
{
	return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Mot de passe oublié | LOKA</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="auth.css">
	<style>
		.dev-link-box { margin: 0 0 18px; padding: 13px; border-radius: 12px; border: 1px dashed var(--primary-light); background: var(--primary-pale); font-size: 12.5px; color: var(--text); }
		.dev-link-box strong { display: block; margin-bottom: 6px; color: var(--primary-dark); }
		.dev-link-box a { word-break: break-all; }
	</style>
</head>
<body>
<main class="auth-page">
	<section class="auth-card">
		<div class="auth-panel__inner">
			<a class="brand" href="../../index.html" aria-label="Retour à l'accueil LOKA">
				<span class="brand__mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><path d="M4 14.2 16 4l12 10.2" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M7.5 12.5V27h17V12.5M12 27v-7h8v7" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/></svg></span><span class="brand__name">LOKA</span>
			</a>

			<section class="form-panel form-panel--login is-active">
				<header class="form-heading">
					<p class="eyebrow">Espace sécurisé</p>
					<h1>Mot de passe oublié</h1>
					<p>Indiquez votre adresse email, nous vous envoyons un lien de réinitialisation.</p>
				</header>

				<?php if ($submitted): ?>
					<p class="form-message form-message--info" role="status">Si un compte existe avec cette adresse, un email contenant un lien de réinitialisation vient d'être envoyé. Le lien est valable 30 minutes.</p>
					<?php if ($devLink !== null): ?>
						<div class="dev-link-box">
							<strong>Mode développement — aucun serveur d'envoi d'email n'est configuré</strong>
							Lien de réinitialisation : <a href="<?= e($devLink) ?>"><?= e($devLink) ?></a>
						</div>
					<?php endif; ?>
					<p class="switch-line"><a href="connexion.php">Retour à la connexion</a></p>
				<?php else: ?>
					<?php if (!empty($errors['general'])): ?><p class="form-message form-message--error" role="alert"><?= e($errors['general']) ?></p><?php endif; ?>
					<form class="auth-form" method="post" action="mot-de-passe-oublie.php" novalidate>
						<input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
						<div class="field">
							<label for="forgot-email">Adresse email</label>
							<input id="forgot-email" name="email" type="email" autocomplete="email" placeholder="vous@loka.fr" required>
							<?php if (!empty($errors['email'])): ?><small class="field__error" style="color:var(--danger);"><?= e($errors['email']) ?></small><?php endif; ?>
						</div>
						<button class="button button--primary" type="submit">Envoyer le lien</button>
					</form>
					<p class="switch-line"><a href="connexion.php">Retour à la connexion</a></p>
				<?php endif; ?>
			</section>
		</div>
	</section>
</main>
</body>
</html>
