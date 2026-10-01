<?php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/core/Auth.php';
require_once __DIR__ . '/AuthModel.php';

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
if ($token === '') {
	header('Location: mot-de-passe-oublie.php');
	exit;
}

// Vérifier que le token est valide
$tokenUser = AuthModel::findByResetToken($token);
if (!$tokenUser) {
	$expired = true;
}

$csrfToken = Auth::csrfToken();
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($expired)) {
	if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
		$errors['general'] = 'Votre session a expiré. Rechargez la page.';
	} else {
		$password = (string) ($_POST['password'] ?? '');
		$passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
		if (mb_strlen($password) < 8) {
			$errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères.';
		} elseif ($password !== $passwordConfirm) {
			$errors['password_confirm'] = 'Les deux mots de passe ne correspondent pas.';
		} else {
			try {
				$result = AuthModel::resetPassword($token, $password);
				if ($result) {
					$success = true;
				} else {
					$errors['general'] = 'Le lien de réinitialisation est invalide ou a expiré.';
				}
			} catch (Throwable $exception) {
				error_log($exception->getMessage());
				$errors['general'] = 'Une erreur est survenue. Veuillez réessayer.';
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
	<title>Nouveau mot de passe | LOKA</title>
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
				<?php if (isset($expired)): ?>
					<div class="form-heading">
						<h1 class="form-heading__title">Lien expiré</h1>
						<p class="form-heading__subtitle">Ce lien de réinitialisation est invalide ou a expiré.</p>
					</div>
					<p style="text-align:center;margin-top:20px;">
						<a href="mot-de-passe-oublie.php" class="submit-btn" style="display:inline-block;text-decoration:none;">Demander un nouveau lien</a>
					</p>
				<?php elseif ($success): ?>
					<div class="form-heading">
						<h1 class="form-heading__title">Mot de passe modifié !</h1>
						<p class="form-heading__subtitle">Votre mot de passe a été réinitialisé avec succès.</p>
					</div>
					<div style="padding:16px;border-radius:var(--radius);background:var(--primary-pale);color:var(--primary-dark);margin-bottom:20px;font-size:14px;text-align:center;">
						✓ Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.
					</div>
					<p style="text-align:center;margin-top:20px;">
						<a href="connexion.php" class="submit-btn" style="display:inline-block;text-decoration:none;">Se connecter</a>
					</p>
				<?php else: ?>
					<div class="form-heading">
						<h1 class="form-heading__title">Nouveau mot de passe</h1>
						<p class="form-heading__subtitle">Choisissez un nouveau mot de passe pour votre compte <strong><?= htmlspecialchars($tokenUser['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>.</p>
					</div>
					<?php if (isset($errors['general'])): ?>
						<div style="padding:16px;border-radius:var(--radius);background:var(--danger-light);color:var(--danger);margin-bottom:20px;font-size:14px;">
							<?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?>
						</div>
					<?php endif; ?>
					<?php foreach ($errors as $field => $error): if ($field !== 'general'): ?>
							<div style="padding:16px;border-radius:var(--radius);background:var(--danger-light);color:var(--danger);margin-bottom:20px;font-size:14px;">
								<?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
							</div>
					<?php endif;
					endforeach; ?>
					<form method="post" class="auth-form">
						<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
						<input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
						<div class="field">
							<label class="field__label" for="password">Nouveau mot de passe</label>
							<input class="field__input" type="password" id="password" name="password" required minlength="8" placeholder="8 caractères minimum">
						</div>
						<div class="field">
							<label class="field__label" for="password_confirm">Confirmer le mot de passe</label>
							<input class="field__input" type="password" id="password_confirm" name="password_confirm" required minlength="8" placeholder="Retapez le mot de passe">
						</div>
						<button class="submit-btn" type="submit">Réinitialiser le mot de passe</button>
					</form>
				<?php endif; ?>
				<p class="switch-line" style="text-align:center;margin-top:20px;">
					<a href="connexion.php" class="switch-line__link">← Retour à la connexion</a>
				</p>
			</div>
		</div>
	</div>
</body>

</html>