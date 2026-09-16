"use strict";

/* =========================================================
   LOKA — auth.js
   Bascule connexion/inscription, affichage mot de passe,
   validation client, affichage des erreurs.
   Aucun appel réseau : simulation locale uniquement.
   ========================================================= */

document.addEventListener("DOMContentLoaded", () => {
  initTabs();
  initPasswordToggles();
  initPasswordStrength();
  initLoginForm();
  initRegisterForm();
});

/* ---------------------------------------------------------
   1. Bascule Connexion / Inscription
   --------------------------------------------------------- */
function initTabs() {
  const tabsWrap = document.querySelector(".tabs");
  const tabButtons = document.querySelectorAll(".tabs__btn");
  const panels = {
    login: document.getElementById("panel-login"),
    register: document.getElementById("panel-register"),
  };

  function activate(target) {
    tabButtons.forEach((btn) => {
      const isActive = btn.dataset.target === target;
      btn.classList.toggle("is-active", isActive);
      btn.setAttribute("aria-selected", String(isActive));
    });
    tabsWrap.dataset.active = target;

    Object.entries(panels).forEach(([key, panel]) => {
      if (key === target) {
        panel.hidden = false;
        panel.classList.add("is-active");
      } else {
        panel.hidden = true;
        panel.classList.remove("is-active");
      }
    });

    // Replace le focus sur le premier champ du panneau actif (confort clavier)
    const firstInput = panels[target].querySelector("input");
    if (firstInput) firstInput.focus({ preventScroll: true });
  }

  tabButtons.forEach((btn) => {
    btn.addEventListener("click", () => activate(btn.dataset.target));
  });

  // Liens "Créer un compte" / "Se connecter" en bas des formulaires
  document.querySelectorAll("[data-switch]").forEach((el) => {
    el.addEventListener("click", () => activate(el.dataset.switch));
  });
}

/* ---------------------------------------------------------
   2. Afficher / masquer le mot de passe
   --------------------------------------------------------- */
function initPasswordToggles() {
  document.querySelectorAll(".toggle-visibility").forEach((btn) => {
    btn.addEventListener("click", () => {
      const input = document.getElementById(btn.dataset.target);
      const isHidden = input.type === "password";

      input.type = isHidden ? "text" : "password";
      btn.setAttribute("aria-pressed", String(isHidden));
      btn.setAttribute(
        "aria-label",
        isHidden ? "Masquer le mot de passe" : "Afficher le mot de passe"
      );
      btn.querySelector(".icon-eye").hidden = isHidden;
      btn.querySelector(".icon-eye-off").hidden = !isHidden;
    });
  });
}

/* ---------------------------------------------------------
   3. Indicateur de robustesse du mot de passe (inscription)
   --------------------------------------------------------- */
function initPasswordStrength() {
  const input = document.getElementById("regPassword");
  const meter = document.getElementById("strengthMeter");
  if (!input || !meter) return;

  input.addEventListener("input", () => {
    const level = passwordStrength(input.value);
    meter.className = "strength-meter" + (level > 0 ? ` level-${level}` : "");
  });
}

function passwordStrength(value) {
  if (!value) return 0;
  let score = 0;
  if (value.length >= 8) score++;
  if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score++;
  if (/\d/.test(value)) score++;
  if (/[^A-Za-z0-9]/.test(value)) score++;
  return Math.max(score, value.length >= 4 ? 1 : 0);
}

/* ---------------------------------------------------------
   4. Utilitaires de validation / affichage des erreurs
   --------------------------------------------------------- */
const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const PHONE_REGEX = /^(\+33|0)[1-9](\d{2}){4}$/; // accepte espaces retirés

function setFieldError(inputId, message) {
  const input = document.getElementById(inputId);
  const field = input.closest(".field");
  const errorEl = document.getElementById(`${inputId}-error`);

  field.classList.add("has-error");
  field.classList.remove("is-valid");
  errorEl.textContent = message;
}

function clearFieldError(inputId) {
  const input = document.getElementById(inputId);
  const field = input.closest(".field");
  const errorEl = document.getElementById(`${inputId}-error`);

  field.classList.remove("has-error");
  field.classList.add("is-valid");
  errorEl.textContent = "";
}

function showBanner(bannerEl, message, type = "error") {
  bannerEl.textContent = message;
  bannerEl.hidden = false;
  bannerEl.classList.toggle("is-success", type === "success");
}

function hideBanner(bannerEl) {
  bannerEl.hidden = true;
  bannerEl.textContent = "";
}

/* ---------------------------------------------------------
   5. Formulaire de connexion
   --------------------------------------------------------- */
function initLoginForm() {
  const form = document.getElementById("loginForm");
  const banner = document.getElementById("loginBanner");
  if (!form) return;

  const emailInput = document.getElementById("loginEmail");
  const passwordInput = document.getElementById("loginPassword");

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    hideBanner(banner);

    let isValid = true;

    if (!emailInput.value.trim()) {
      setFieldError("loginEmail", "L'adresse email est requise.");
      isValid = false;
    } else if (!EMAIL_REGEX.test(emailInput.value.trim())) {
      setFieldError("loginEmail", "Adresse email invalide.");
      isValid = false;
    } else {
      clearFieldError("loginEmail");
    }

    if (!passwordInput.value) {
      setFieldError("loginPassword", "Le mot de passe est requis.");
      isValid = false;
    } else {
      clearFieldError("loginPassword");
    }

    if (!isValid) {
      // Focus le premier champ en erreur pour l'accessibilité
      form.querySelector(".field.has-error input")?.focus();
      return;
    }

    simulateSubmit(form, () => {
      showBanner(banner, "Connexion réussie. Redirection en cours…", "success");
      // Ici viendra l'appel API réel (hors périmètre de ce livrable frontend).
    });
  });

  // Nettoie l'erreur dès que l'utilisateur corrige le champ
  [emailInput, passwordInput].forEach((input) => {
    input.addEventListener("input", () => {
      const field = input.closest(".field");
      if (field.classList.contains("has-error")) {
        field.classList.remove("has-error");
        document.getElementById(`${input.id}-error`).textContent = "";
      }
    });
  });

  // Bouton Google : purement visuel, aucun backend disponible pour le moment
  document.getElementById("googleLoginBtn")?.addEventListener("click", () => {
    showBanner(
      banner,
      "La connexion avec Google sera bientôt disponible.",
      "error"
    );
  });
}

/* ---------------------------------------------------------
   6. Formulaire d'inscription
   --------------------------------------------------------- */
function initRegisterForm() {
  const form = document.getElementById("registerForm");
  const banner = document.getElementById("registerBanner");
  if (!form) return;

  const fields = {
    firstName: document.getElementById("regFirstName"),
    lastName: document.getElementById("regLastName"),
    email: document.getElementById("regEmail"),
    phone: document.getElementById("regPhone"),
    password: document.getElementById("regPassword"),
    passwordConfirm: document.getElementById("regPasswordConfirm"),
  };
  const termsInput = document.getElementById("acceptTerms");

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    hideBanner(banner);

    let isValid = true;

    if (!fields.firstName.value.trim()) {
      setFieldError("regFirstName", "Le prénom est requis.");
      isValid = false;
    } else {
      clearFieldError("regFirstName");
    }

    if (!fields.lastName.value.trim()) {
      setFieldError("regLastName", "Le nom est requis.");
      isValid = false;
    } else {
      clearFieldError("regLastName");
    }

    if (!fields.email.value.trim()) {
      setFieldError("regEmail", "L'adresse email est requise.");
      isValid = false;
    } else if (!EMAIL_REGEX.test(fields.email.value.trim())) {
      setFieldError("regEmail", "Adresse email invalide.");
      isValid = false;
    } else {
      clearFieldError("regEmail");
    }

    const phoneValue = fields.phone.value.trim().replace(/\s+/g, "");
    if (phoneValue && !PHONE_REGEX.test(phoneValue)) {
      setFieldError("regPhone", "Numéro de téléphone invalide.");
      isValid = false;
    } else {
      clearFieldError("regPhone");
    }

    if (!fields.password.value) {
      setFieldError("regPassword", "Le mot de passe est requis.");
      isValid = false;
    } else if (fields.password.value.length < 8) {
      setFieldError("regPassword", "8 caractères minimum.");
      isValid = false;
    } else {
      clearFieldError("regPassword");
    }

    if (!fields.passwordConfirm.value) {
      setFieldError("regPasswordConfirm", "Merci de confirmer le mot de passe.");
      isValid = false;
    } else if (fields.passwordConfirm.value !== fields.password.value) {
      setFieldError("regPasswordConfirm", "Les mots de passe ne correspondent pas.");
      isValid = false;
    } else {
      clearFieldError("regPasswordConfirm");
    }

    const termsError = document.getElementById("acceptTerms-error");
    if (!termsInput.checked) {
      termsError.textContent = "Vous devez accepter les conditions d'utilisation.";
      isValid = false;
    } else {
      termsError.textContent = "";
    }

    if (!isValid) {
      form.querySelector(".field.has-error input")?.focus();
      return;
    }

    simulateSubmit(form, () => {
      showBanner(banner, "Compte créé avec succès. Vous pouvez vous connecter.", "success");
      form.reset();
      document.getElementById("strengthMeter").className = "strength-meter";
      Object.keys(fields).forEach((key) => {
        fields[key].closest(".field")?.classList.remove("is-valid", "has-error");
      });
    });
  });

  // Nettoie les erreurs à la frappe
  Object.values(fields).forEach((input) => {
    input.addEventListener("input", () => {
      const field = input.closest(".field");
      if (field.classList.contains("has-error")) {
        field.classList.remove("has-error");
        document.getElementById(`${input.id}-error`).textContent = "";
      }
    });
  });

  termsInput.addEventListener("change", () => {
    if (termsInput.checked) {
      document.getElementById("acceptTerms-error").textContent = "";
    }
  });
}

/* ---------------------------------------------------------
   7. Simulation d'envoi (pas de backend pour le moment)
   --------------------------------------------------------- */
function simulateSubmit(form, onSuccess) {
  const submitBtn = form.querySelector('button[type="submit"]');
  const originalText = submitBtn.textContent;

  submitBtn.disabled = true;
  submitBtn.textContent = "Patientez…";

  window.setTimeout(() => {
    submitBtn.disabled = false;
    submitBtn.textContent = originalText;
    onSuccess();
  }, 700);
}
