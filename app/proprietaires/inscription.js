"use strict";

document.addEventListener("DOMContentLoaded", () => {
  const choiceView = document.getElementById("choice-view");
  const views = {
    individual: document.getElementById("individual-view"),
    professional: document.getElementById("professional-view"),
  };

  function showView(name) {
    choiceView.hidden = name !== "choice";
    choiceView.classList.toggle("is-active", name === "choice");
    Object.entries(views).forEach(([key, view]) => {
      view.hidden = key !== name;
      view.classList.toggle("is-active", key === name);
    });
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  document.querySelectorAll("[data-go-to]").forEach((button) => {
    button.addEventListener("click", (event) => {
      event.preventDefault();
      showView(button.dataset.goTo);
    });
  });

  document.querySelectorAll("[data-back]").forEach((button) => {
    button.addEventListener("click", () => showView("choice"));
  });

  document.querySelectorAll("[data-toggle-password]").forEach((button) => {
    button.addEventListener("click", () => {
      const input = document.getElementById(button.dataset.togglePassword);
      const visible = input.type === "password";
      input.type = visible ? "text" : "password";
      button.textContent = visible ? "Masquer" : "Voir";
      button.setAttribute("aria-label", visible ? "Masquer le mot de passe" : "Afficher le mot de passe");
    });
  });

  async function submitRegistration(form, feedback, accountType) {
    if (window.location.protocol === "file:") {
      throw new Error("Ouvrez LOKA via http://localhost/LOKA pour créer votre espace.");
    }
    const csrfResponse = await fetch("ajouter.php?action=csrf", { credentials: "same-origin" });
    const csrfPayload = await readResponse(csrfResponse);
    if (!csrfResponse.ok || !csrfPayload.csrf_token) throw new Error("Jeton de sécurité indisponible.");

    const payload = new FormData(form);
    payload.append("csrf_token", csrfPayload.csrf_token);
    payload.append("account_type", accountType);
    const response = await fetch(form.action, { method: "POST", body: payload, credentials: "same-origin" });
    const result = await readResponse(response);
    if (!response.ok) {
      const message = result.errors?.general || Object.values(result.errors || {})[0] || result.error || "Inscription impossible.";
      throw new Error(message);
    }
    window.location.href = result.redirect;
  }

  async function readResponse(response) {
    const text = await response.text();
    try {
      return JSON.parse(text);
    } catch {
      throw new Error("Le serveur a renvoyé une réponse inattendue.");
    }
  }

  function validateForm(form, feedbackId, passwordId, confirmationId, accountType) {
    const feedback = document.getElementById(feedbackId);
    const submitButton = form.querySelector('button[type="submit"]');
    form.addEventListener("submit", (event) => {
      event.preventDefault();
      feedback.classList.remove("is-error");
      feedback.textContent = "";

      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      const password = document.getElementById(passwordId);
      const confirmation = document.getElementById(confirmationId);
      if (password.value !== confirmation.value) {
        feedback.classList.add("is-error");
        feedback.textContent = "Les mots de passe ne correspondent pas.";
        confirmation.focus();
        return;
      }

      feedback.textContent = "";
      submitButton.disabled = true;
      submitButton.dataset.originalText = submitButton.textContent;
      submitButton.textContent = "Création en cours…";
      submitRegistration(form, feedback, accountType)
        .catch((error) => {
          feedback.classList.add("is-error");
          feedback.textContent = error.message;
        })
        .finally(() => {
          submitButton.disabled = false;
          submitButton.textContent = submitButton.dataset.originalText;
        });
    });
  }

  validateForm(document.getElementById("individual-form"), "individual-feedback", "individual-password", "individual-password-confirm", "proprietor");
  validateForm(document.getElementById("professional-form"), "professional-feedback", "manager-password", "manager-password-confirm", "agency");
});
