"use strict";

document.addEventListener("DOMContentLoaded", () => {
	const tabs = document.querySelectorAll(".tabs__button");
	const panels = { login: document.getElementById("login-panel"), register: document.getElementById("register-panel") };

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
	const hash = window.location.hash.replace("#", "");
	if (hash === "inscription" || hash === "register") showPanel("register");

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
		const form = event.currentTarget;
		if (!form.checkValidity()) { event.preventDefault(); form.reportValidity(); }
	});

	document.getElementById("register-form").addEventListener("submit", (event) => {
		const form = event.currentTarget;
		const passwordConfirm = document.getElementById("register-password-confirm");
		if (!form.checkValidity()) { event.preventDefault(); form.reportValidity(); return; }
		if (password.value !== passwordConfirm.value) {
			event.preventDefault();
			showMessage("register-message", "Les mots de passe ne correspondent pas.");
			passwordConfirm.focus();
			return;
		}
	});
});
