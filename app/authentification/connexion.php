<?php
declare(strict_types=1);

require_once __DIR__ . '/AuthController.php';

$activePanel = ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'register') ? 'register' : 'login';
$csrfToken = Auth::csrfToken();
$errors = [];
$successMessage = null;

if (isset($_GET['registered'])) {
	$successMessage = 'Votre compte a été créé. Vous pouvez maintenant vous connecter.';
}
if (isset($_GET['logout'])) {
	$successMessage = 'Vous êtes maintenant déconnecté.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if ($activePanel === 'login') {
		[$errors, $redirect] = AuthController::handleLogin($_POST);
	} else {
		[$errors, $redirect] = AuthController::handleRegistration($_POST);
	}
	if ($redirect !== null) {
		header('Location: ' . $redirect);
		exit;
	}
}

$message = $errors['general'] ?? null;
if ($message === null && $errors) {
	$message = (string) reset($errors);
}
?>
<!doctype html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Authentification | LOKA</title>
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

			<div class="tabs" role="tablist" aria-label="Authentification">
				<button class="tabs__button is-active" type="button" data-panel="login" role="tab" aria-selected="true">Connexion</button>
				<button class="tabs__button" type="button" data-panel="register" role="tab" aria-selected="false">Inscription</button>
				<span class="tabs__indicator" aria-hidden="true"></span>
			</div>

			<section class="form-panel form-panel--login<?= $activePanel === 'login' ? ' is-active' : '' ?>" id="login-panel"<?= $activePanel !== 'login' ? ' hidden' : '' ?> role="tabpanel">
				<header class="form-heading">
					<p class="eyebrow">Espace sécurisé</p>
					<h1>Bienvenue sur LOKA</h1>
					<p>Connectez-vous pour accéder à votre espace.</p>
				</header>
				<p class="form-message form-message--error" id="login-message" role="alert" hidden></p>
				<?php if ($successMessage !== null): ?><p class="form-message form-message--info" role="status"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
				<?php if ($activePanel === 'login' && $message !== null): ?><p class="form-message form-message--error" id="login-message" role="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php else: ?><p class="form-message form-message--error" id="login-message" role="alert" hidden></p><?php endif; ?>
				<form class="auth-form" id="login-form" method="post" action="connexion.php" novalidate>
					<input type="hidden" name="form" value="login">
					<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
					<div class="field"><label for="login-email">Adresse email</label><input id="login-email" name="email" type="email" autocomplete="email" placeholder="vous@exemple.com" required><small class="field__error"></small></div>
					<div class="field"><label for="login-password">Mot de passe</label><div class="password-field"><input id="login-password" name="password" type="password" autocomplete="current-password" placeholder="Votre mot de passe" required><button class="password-toggle" type="button" data-password="login-password" aria-label="Afficher le mot de passe">Voir</button></div><small class="field__error"></small></div>
					<div class="form-options"><label class="check"><input type="checkbox" name="remember"><span></span>Se souvenir de moi</label><a href="mot-de-passe-oublie.php">Mot de passe oublié ?</a></div>
					<button class="button button--primary" type="submit">Se connecter</button>
				</form>
				<p class="switch-line">Pas encore de compte ? <button type="button" data-switch="register">Créer un compte locataire</button></p>
			</section>

			<section class="form-panel<?= $activePanel === 'register' ? ' is-active' : '' ?>" id="register-panel"<?= $activePanel !== 'register' ? ' hidden' : '' ?> role="tabpanel">
				<header class="form-heading"><p class="eyebrow">Inscription locataire</p><h1>Créer votre compte</h1><p>Renseignez vos informations pour commencer.</p></header>
				<?php if ($activePanel === 'register' && $message !== null): ?><p class="form-message form-message--error" id="register-message" role="alert"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p><?php else: ?><p class="form-message form-message--error" id="register-message" role="alert" hidden></p><?php endif; ?>
				<form class="auth-form" id="register-form" method="post" action="connexion.php" novalidate>
					<input type="hidden" name="form" value="register">
					<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
					<div class="form-grid">
					<div class="field"><label for="register-last-name">Nom</label><input id="register-last-name" name="last_name" autocomplete="family-name" required></div><div class="field"><label for="register-first-name">Prénom</label><input id="register-first-name" name="first_name" autocomplete="given-name" required></div>
					<div class="field"><label for="register-email">Email</label><input id="register-email" name="email" type="email" autocomplete="email" placeholder="vous@exemple.com" required></div><div class="field"><label for="register-phone">Téléphone</label><input id="register-phone" name="phone" type="tel" autocomplete="tel" required></div>
					<div class="field"><label for="register-address">Adresse</label><input id="register-address" name="address" autocomplete="street-address" required></div><div class="field"><label for="register-postal-code">Code postal</label><input id="register-postal-code" name="postal_code" inputmode="numeric" autocomplete="postal-code" required></div>
					<div class="field"><label for="register-city">Ville</label><input id="register-city" name="city" autocomplete="address-level2" required></div><div class="field"><label for="register-country">Pays</label><input id="register-country" name="country" value="France" autocomplete="country-name" required></div>
					<div class="field"><label for="register-birth-date">Date de naissance</label><input id="register-birth-date" name="birth_date" type="date" autocomplete="bday" required></div><div class="field"><label for="register-profession">Profession</label><input id="register-profession" name="profession" autocomplete="organization-title" required></div>
					<div class="field"><label for="register-income">Revenu mensuel</label><select id="register-income" name="monthly_income" required><option value="">Sélectionner une fourchette</option><option>Moins de 1 000 €</option><option>1 000 € à 1 499 €</option><option>1 500 € à 2 499 €</option><option>2 500 € à 3 499 €</option><option>3 500 € à 4 999 €</option><option>5 000 € et plus</option></select></div><div class="field"><label for="register-status">Statut</label><select id="register-status" disabled><option>Actif</option></select></div>
					<div class="field"><label for="register-password">Mot de passe</label><div class="password-field"><input id="register-password" name="password" type="password" autocomplete="new-password" placeholder="8 caractères minimum" minlength="8" required><button class="password-toggle" type="button" data-password="register-password" aria-label="Afficher le mot de passe">Voir</button></div><div class="strength" aria-hidden="true"><i></i><i></i><i></i><i></i></div></div><div class="field"><label for="register-password-confirm">Confirmation du mot de passe</label><div class="password-field"><input id="register-password-confirm" name="password_confirm" type="password" autocomplete="new-password" required><button class="password-toggle" type="button" data-password="register-password-confirm" aria-label="Afficher le mot de passe">Voir</button></div></div>
					</div>
					<label class="check check--terms"><input type="checkbox" name="terms" required><span></span>J’accepte les <a href="#">conditions d’utilisation</a> de LOKA</label>
					<button class="button button--primary" type="submit">Créer mon compte locataire</button>
				</form>
				<p class="switch-line">Déjà inscrit ? <button type="button" data-switch="login">Se connecter</button></p>
			</section>
		</div>
	</section>
</main>
<script src="auth.js"></script>
</body>
</html>
