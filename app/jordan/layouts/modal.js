/* =========================================================
   modal.js — modale globale partagée par les pages "Jordan"
   (biens, propriétaires, locataires, contrats, paiements…).
   Même mécanique que layouts/admin.js : un lien/bouton avec
   [data-modal] charge son href en fragment (?modal=1) dans la
   modale ; les formulaires soumis depuis la modale sont envoyés
   en fetch et la réponse remplace le contenu de la modale.
   ========================================================= */
(function () {
  const modalOverlay = document.getElementById('modalOverlay');
  const modalPanel = document.getElementById('modalPanel');
  const modalBody = document.getElementById('modalBody');
  const modalClose = document.getElementById('modalClose');

  if (!modalOverlay || !modalPanel || !modalBody) return;

  let modalDirty = false;
  let requestToken = 0;

  function withModalParam(url) {
    return url + (url.includes('?') ? '&' : '?') + 'modal=1';
  }

  function setModalContent(html) {
    modalBody.innerHTML = html;
    // innerHTML ne fait pas exécuter les <script> injectés : on les reconstruit pour forcer leur exécution
    // (nécessaire aux formulaires avec leur propre JS, ex. paiements/ajouter.php).
    modalBody.querySelectorAll('script').forEach((oldScript) => {
      const newScript = document.createElement('script');
      for (const attr of oldScript.attributes) newScript.setAttribute(attr.name, attr.value);
      newScript.textContent = oldScript.textContent;
      oldScript.replaceWith(newScript);
    });
    modalPanel.scrollTop = 0;
  }

  function openModalShell(size) {
    modalPanel.classList.toggle('modal-panel--sm', size === 'sm');
    modalOverlay.hidden = false;
    document.body.classList.add('modal-open');
    requestAnimationFrame(() => modalOverlay.classList.add('show'));
    setModalContent('<div class="modal-loading"><span class="modal-spinner"></span>Chargement…</div>');
  }

  function closeModal() {
    modalOverlay.classList.remove('show');
    document.body.classList.remove('modal-open');
    setTimeout(() => {
      modalOverlay.hidden = true;
      setModalContent('');
    }, 220);
    if (modalDirty) {
      modalDirty = false;
      window.location.reload();
    }
  }

  function handleFragmentResponse(res) {
    return res.text().then((html) => {
      if (html.indexOf('<!doctype') !== -1 || html.indexOf('<html') !== -1) {
        window.location.href = res.url;
        return null;
      }
      return html;
    });
  }

  function loadIntoModal(url, size) {
    const token = ++requestToken;
    openModalShell(size);
    fetch(withModalParam(url), { credentials: 'same-origin' })
      .then(handleFragmentResponse)
      .then((html) => {
        if (html === null || token !== requestToken) return;
        setModalContent(html);
      })
      .catch(() => {
        if (token !== requestToken) return;
        setModalContent('<p class="empty-note">Impossible de charger le contenu. Réessayez.</p>');
      });
  }

  function submitModalForm(form) {
    const token = ++requestToken;
    const fd = new FormData(form);
    fd.set('modal', '1');
    modalDirty = true;
    setModalContent('<div class="modal-loading"><span class="modal-spinner"></span>Enregistrement…</div>');
    // form.action peut être masqué par un <input name="action"> (DOM : les éléments nommés
    // du formulaire écrasent les propriétés natives) — on lit l'attribut HTML brut à la place.
    fetch(form.getAttribute('action'), { method: (form.method || 'POST').toUpperCase(), body: fd, credentials: 'same-origin' })
      .then(handleFragmentResponse)
      .then((html) => {
        if (html === null || token !== requestToken) return;
        setModalContent(html);
      })
      .catch(() => {
        if (token !== requestToken) return;
        setModalContent('<p class="empty-note">Une erreur est survenue. Réessayez.</p>');
      });
  }

  document.addEventListener('click', (e) => {
    const cancel = e.target.closest('[data-modal-cancel]');
    if (cancel && cancel.closest('#modalBody')) {
      e.preventDefault();
      closeModal();
      return;
    }
    const trigger = e.target.closest('[data-modal]');
    if (trigger && trigger.tagName === 'A') {
      e.preventDefault();
      loadIntoModal(trigger.getAttribute('href'), trigger.dataset.modalSize);
      return;
    }
    if (e.target === modalOverlay) closeModal();
  });

  modalClose && modalClose.addEventListener('click', closeModal);

  document.addEventListener('submit', (e) => {
    if (!e.target.closest('#modalBody')) return;
    e.preventDefault();
    submitModalForm(e.target);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modalOverlay.hidden) closeModal();
  });

  window.LokaModal = { open: loadIntoModal, close: closeModal };
})();
