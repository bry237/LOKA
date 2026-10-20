<?php
declare(strict_types=1);

require_once __DIR__ . '/AuthController.php';

$csrfToken = Auth::csrfToken();
$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$errors = [];
$success = false;

$tokenUser = $token !== '' ? PasswordResetService::verifyToken($token) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenUser !== null) {
	[$errors, $success] = AuthController::handleResetPassword($_POST);
	if ($success) {
		header('Location: connexion.php?reset=1');
		exit;
	}
	// Le token a pu être invalidé par la tentative elle-même (expiré entre-temps) : on le relit.
	$tokenUser = PasswordResetService::verifyToken($token);
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
	<title>Nouveau mot de passe | LOKA</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="auth.css">
</head>
<body>
<main class="auth-page">
	<section class="auth-card">
		<div class="auth-panel__inner">
			<a class="brand" href="../../index.html" aria-label="Retour à l'accueil LOKA">
				<span class="brand__mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><path d="M4 14.2 16 4l12 10.2" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M7.5 12.5V27h17V12.5M12 27v-7h8v7" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"/></svg></span><span class="brand__name">LOKA</span>
			</a>

			<section class="form-panel form-panel--login is-active">
				<?php if ($tokenUser === null): ?>
					<header class="form-heading">
						<p class="eyebrow">Espace sécurisé</p>
						<h1>Lien invalide</h1>
						<p>Ce lien de réinitialisation est invalide, a déjà été utilisé, ou a expiré.</p>
					</header>
					<p class="switch-line"><a href="mot-de-passe-oublie.php">Demander un nouveau lien</a></p>
				<?php else: ?>
					<header class="form-heading">
						<p class="eyebrow">Espace sécurisé</p>
						<h1>Nouveau mot de passe</h1>
						<p>Bonjour <?= e($tokenUser['prenom']) ?>, choisissez votre nouveau mot de passe.</p>
					</header>
					<?php if (!empty($errors['general'])): ?><p class="form-message form-message--error" role="alert"><?= e($errors['general']) ?></p><?php endif; ?>
					<form class="auth-form" method="post" action="nouveau-mot-de-passe.php" novalidate>
						<input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
						<input type="hidden" name="token" value="<?= e($token) ?>">
						<div class="field">
							<label for="reset-password">Nouveau mot de passe</label>
							<div class="password-field">
								<input id="reset-password" name="password" type="password" autocomplete="new-password" placeholder="8 caractères minimum" minlength="8" required>
								<button class="password-toggle" type="button" data-password="reset-password" aria-label="Afficher le mot de passe">Voir</button>
							</div>
							<?php if (!empty($errors['password'])): ?><small class="field__error" style="color:var(--danger);"><?= e($errors['password']) ?></small><?php endif; ?>
						</div>
						<div class="field">
							<label for="reset-password-confirm">Confirmation</label>
							<div class="password-field">
								<input id="reset-password-confirm" name="password_confirm" type="password" autocomplete="new-password" required>
								<button class="password-toggle" type="button" data-password="reset-password-confirm" aria-label="Afficher le mot de passe">Voir</button>
							</div>
							<?php if (!empty($errors['password_confirm'])): ?><small class="field__error" style="color:var(--danger);"><?= e($errors['password_confirm']) ?></small><?php endif; ?>
						</div>
						<button class="button button--primary" type="submit">Réinitialiser le mot de passe</button>
					</form>
				<?php endif; ?>
			</section>
		</div>
	</section>
</main>
<script src="auth.js"></script>
</body>
</html>
