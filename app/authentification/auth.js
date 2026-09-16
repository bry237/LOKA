"use strict";

document.addEventListener("DOMContentLoaded", () => {
	const tabs = document.querySelectorAll(".tabs__button");
	const panels = { login: document.getElementById("login-panel"), register: document.getElementById("register-panel") };
	const routes = {
		admin_plateforme: "../administration/agences/index.php",
		admin_agence: "../administration/utilisateurs/index.php",
		gestionnaire: "../biens/index.php",
		comptable: "../paiements/index.php",
		proprietaire: "../proprietaires/index.php",
		locataire: "../locataires/index.php",
		technicien: "../maintenance/index.php",
	};
	const demoAccounts = [
		{ email: "admin@loka.test", password: "Admin123!", role: "admin_plateforme", label: "Administrateur plateforme" },
		{ email: "agence@loka.test", password: "Agence123!", role: "admin_agence", label: "Administrateur agence" },
		{ email: "gestionnaire@loka.test", password: "Gestionnaire123!", role: "gestionnaire", label: "Gestionnaire immobilier" },
		{ email: "comptable@loka.test", password: "Comptable123!", role: "comptable", label: "Comptable" },
		{ email: "proprietaire@loka.test", password: "Proprietaire123!", role: "proprietaire", label: "Propriétaire" },
		{ email: "locataire@loka.test", password: "Locataire123!", role: "locataire", label: "Locataire" },
		{ email: "technicien@loka.test", password: "Technicien123!", role: "technicien", label: "Technicien" },
	];

	function showPanel(name) {
		tabs.forEach((tab) => {
			const active = tab.dataset.panel === name;
			tab.classList.toggle("is-active", active);
			tab.setAttribute("aria-selected", String(active));
		});
		Object.entries(panels).forEach(([key, panel]) => {
			panel.hidden = key !== name;
			panel.classList.toggle("is-active", key === name);
		});
		panels[name].querySelector("input:not([type=hidden])")?.focus({ preventScroll: true });
	}

	function showMessage(id, message, type = "error") {
		const element = document.getElementById(id);
		element.textContent = message;
		element.hidden = !message;
		element.classList.toggle("form-message--error", type === "error");
		element.classList.toggle("form-message--info", type === "info");
	}

	tabs.forEach((tab) => tab.addEventListener("click", () => showPanel(tab.dataset.panel)));
	document.querySelectorAll("[data-switch]").forEach((button) => button.addEventListener("click", () => showPanel(button.dataset.switch)));

	document.querySelectorAll("[data-password]").forEach((button) => button.addEventListener("click", () => {
		const input = document.getElementById(button.dataset.password);
		const visible = input.type === "password";
		input.type = visible ? "text" : "password";
		button.textContent = visible ? "Masquer" : "Voir";
		button.setAttribute("aria-label", visible ? "Masquer le mot de passe" : "Afficher le mot de passe");
	}));

	const password = document.getElementById("register-password");
	const strength = document.querySelector(".strength");
	password?.addEventListener("input", () => {
		let level = 0;
		if (password.value.length >= 8) level++;
		if (/[a-z]/.test(password.value) && /[A-Z]/.test(password.value)) level++;
		if (/\d/.test(password.value)) level++;
		if (/[^A-Za-z0-9]/.test(password.value)) level++;
		strength.className = `strength${level ? ` level-${level}` : ""}`;
	});

	document.getElementById("login-form").addEventListener("submit", (event) => {
		event.preventDefault();
		const form = event.currentTarget;
		if (!form.checkValidity()) { form.reportValidity(); return; }
		const email = form.elements.email.value.trim().toLowerCase();
		const passwordValue = form.elements.password.value;
		const account = demoAccounts.find((item) => item.email === email && item.password === passwordValue);
		if (!account) {
			showMessage("login-message", "Identifiants incorrects. Utilisez un compte de démonstration frontend.");
			return;
		}
		showMessage("login-message", "");
		window.sessionStorage.setItem("loka_role", account.role);
		window.sessionStorage.setItem("loka_role_label", account.label);
		window.location.href = routes[account.role];
	});

	document.getElementById("register-form").addEventListener("submit", (event) => {
		event.preventDefault();
		const form = event.currentTarget;
		const passwordConfirm = document.getElementById("register-password-confirm");
		if (!form.checkValidity()) { form.reportValidity(); return; }
		if (password.value !== passwordConfirm.value) {
			showMessage("register-message", "Les mots de passe ne correspondent pas.");
			passwordConfirm.focus();
			return;
		}
		showMessage("register-message", "Inscription frontend simulée. Le compte sera raccordé au backend ultérieurement.", "info");
	});
});
