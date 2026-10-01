<?php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Auth.php';
require_once __DIR__ . '/AuthModel.php';

$csrfToken = Auth::csrfToken();
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		$errors['general'] = 'Votre session a expiré. Rechargez la page.';
	} else {
		$email = trim((string) ($_POST['email'] ?? ''));
		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$errors['email'] = 'Veuillez saisir une adresse e-mail valide.';
		} else {
			// On génère toujours un token, mais on ne révèle pas si l'e-mail existe ou non
			$token = AuthModel::createResetToken($email);
			if ($token !== null) {
				// En production, envoyer un e-mail avec le lien :
				// $resetUrl = "http://{$_SERVER['HTTP_HOST']}/LOKA/app/authentification/nouveau-mot-de-passe.php?token={$token}";
				// mail($email, 'Réinitialisation de votre mot de passe LOKA', "Cliquez ici : {$resetUrl}");
				// Pour le développement, on affiche le lien directement
				$success = 'Si cette adresse existe dans notre système, un lien de réinitialisation a été envoyé.';
				// DEV ONLY — à retirer en production :
				$devLink = '/LOKA/app/authentification/nouveau-mot-de-passe.php?token=' . $token;
			} else {
				// Même message pour ne pas révéler l'existence d'un compte
				$success = 'Si cette adresse existe dans notre système, un lien de réinitialisation a été envoyé.';
			}
		}
	}
}
?>
<!doctype html>
<html lang="fr">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Mot de passe oublié | LOKA</title>
	<link rel="stylesheet" href="auth.css">
</head>

<body>
	<div class="auth-page">
		<div class="auth-card" style="width:min(100%,540px);">
			<div class="brand">
				<div class="brand__mark">L</div>
				<div class="brand__name">LOKA</div>
			</div>
			<div class="auth-panel__inner">
				<div class="form-heading">
					<h1 class="form-heading__title">Mot de passe oublié</h1>
					<p class="form-heading__subtitle">Entrez votre adresse e-mail et nous vous enverrons un lien de réinitialisation.</p>
				</div>
				<?php if ($success): ?>
					<div style="padding:16px;border-radius:var(--radius);background:var(--primary-pale);color:var(--primary-dark);margin-bottom:20px;font-size:14px;">
						<?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
					</div>
					<?php if (isset($devLink)): ?>
						<div style="padding:16px;border-radius:var(--radius);background:var(--info-light);color:var(--info);margin-bottom:20px;font-size:13px;">
							<strong>DEV :</strong> <a href="<?= htmlspecialchars($devLink, ENT_QUOTES, 'UTF-8') ?>">Cliquer ici pour réinitialiser</a>
						</div>
					<?php endif; ?>
				<?php endif; ?>
				<?php if (isset($errors['general'])): ?>
					<div style="padding:16px;border-radius:var(--radius);background:var(--danger-light);color:var(--danger);margin-bottom:20px;font-size:14px;">
						<?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?>
					</div>
				<?php endif; ?>
				<?php if (isset($errors['email'])): ?>
					<div style="padding:16px;border-radius:var(--radius);background:var(--danger-light);color:var(--danger);margin-bottom:20px;font-size:14px;">
						<?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?>
					</div>
				<?php endif; ?>
				<form method="post" class="auth-form">
					<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
					<div class="field">
						<label class="field__label" for="email">Adresse e-mail</label>
						<input class="field__input" type="email" id="email" name="email" required autofocus placeholder="exemple@loka.fr" value="<?= htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
					</div>
					<button class="submit-btn" type="submit">Envoyer le lien</button>
				</form>
				<p class="switch-line" style="text-align:center;margin-top:20px;">
					<a href="connexion.php" class="switch-line__link">← Retour à la connexion</a>
				</p>
			</div>
		</div>
	</div>
</body>

</html>