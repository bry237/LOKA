"use strict";

/* =========================================================
   LOKA — landing.js
   Navbar au scroll, menu mobile, révélation au scroll,
   compteur de statistiques, barre de recherche (simulation).
   ========================================================= */

document.addEventListener("DOMContentLoaded", () => {
  initStickyNav();
  initMobileMenu();
  initScrollReveal();
  initStatsCounter();
  initSearchForm();
});

/* ---------------------------------------------------------
   1. Navbar : effet de fond au scroll
   --------------------------------------------------------- */
function initStickyNav() {
  const nav = document.getElementById("nav");
  if (!nav) return;

  const toggle = () => nav.classList.toggle("is-scrolled", window.scrollY > 12);
  toggle();
  window.addEventListener("scroll", toggle, { passive: true });
}

/* ---------------------------------------------------------
   2. Menu mobile (hamburger)
   --------------------------------------------------------- */
function initMobileMenu() {
  const burger = document.getElementById("navBurger");
  const links = document.getElementById("navLinks");
  if (!burger || !links) return;

  burger.addEventListener("click", () => {
    const isOpen = links.classList.toggle("is-open");
    burger.classList.toggle("is-active", isOpen);
    burger.setAttribute("aria-expanded", String(isOpen));
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && links.classList.contains("is-open")) {
      links.classList.remove("is-open");
      burger.classList.remove("is-active");
      burger.setAttribute("aria-expanded", "false");
      burger.focus();
    }
  });

  // Ferme le menu après un clic sur un lien (navigation ancre)
  links.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => {
      links.classList.remove("is-open");
      burger.classList.remove("is-active");
      burger.setAttribute("aria-expanded", "false");
    });
  });
}

/* ---------------------------------------------------------
   3. Apparition progressive des sections au scroll
   --------------------------------------------------------- */
function initScrollReveal() {
  const targets = document.querySelectorAll("[data-reveal]");
  if (!targets.length) return;

  if (!("IntersectionObserver" in window)) {
    targets.forEach((el) => el.classList.add("is-visible"));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15, rootMargin: "0px 0px -60px 0px" }
  );

  targets.forEach((el) => observer.observe(el));
}

/* ---------------------------------------------------------
   4. Compteur animé pour la section statistiques
   --------------------------------------------------------- */
function initStatsCounter() {
  const values = document.querySelectorAll(".stat__value");
  if (!values.length) return;

  values.forEach((el) => {
    // Les valeurs statiques (ex : "24/7") sont affichées directement
    if (el.dataset.static) {
      el.textContent = el.dataset.static;
    }
  });

  const animatedValues = Array.from(values).filter((el) => el.dataset.count);
  if (!animatedValues.length) return;

  if (!("IntersectionObserver" in window)) {
    animatedValues.forEach((el) => {
      el.textContent = el.dataset.count + (el.dataset.suffix || "");
    });
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          animateCount(entry.target);
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.6 }
  );

  animatedValues.forEach((el) => observer.observe(el));
}

function animateCount(el) {
  const target = parseInt(el.dataset.count, 10);
  const suffix = el.dataset.suffix || "";
  const duration = 900;
  const start = performance.now();

  function frame(now) {
    const progress = Math.min((now - start) / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
    const value = Math.round(target * eased);
    el.textContent = value + suffix;

    if (progress < 1) {
      requestAnimationFrame(frame);
    }
  }
  requestAnimationFrame(frame);
}

/* ---------------------------------------------------------
   5. Barre de recherche — interface prête pour le backend
   --------------------------------------------------------- */
function initSearchForm() {
  const form = document.getElementById("searchForm");
  const status = document.getElementById("searchStatus");
  if (!form) return;

  const cards = Array.from(document.querySelectorAll(".property-card"));

  form.addEventListener("submit", (event) => {
    event.preventDefault();

    const data = new FormData(form);
    const city = String(data.get("city") || "").trim().toLowerCase();
    const type = String(data.get("type") || "").toLowerCase();
    const budget = Number(data.get("budget") || 0);
    let visibleCount = 0;

    cards.forEach((card) => {
      const matchesCity = !city || card.dataset.city.includes(city);
      const matchesType = !type || card.dataset.type === type;
      const matchesBudget = !budget || Number(card.dataset.budget) <= budget;
      const visible = matchesCity && matchesType && matchesBudget;
      card.classList.toggle("is-hidden", !visible);
      if (visible) visibleCount += 1;
    });

    if (status) {
      status.textContent = visibleCount
        ? `${visibleCount} logement${visibleCount > 1 ? "s" : ""} correspond${visibleCount > 1 ? "ent" : ""} à votre recherche.`
        : "Aucun logement ne correspond encore à ces critères.";
    }

    const listingsSection = document.getElementById("logements");
    if (listingsSection) {
      listingsSection.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  });
}
