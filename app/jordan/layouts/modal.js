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

  // L'hébergement de production bloque (403, au niveau du reverse proxy, avant PHP) toute
  // requête POST en multipart/form-data. On encode donc les fichiers sélectionnés en base64
  // et on envoie tout en application/x-www-form-urlencoded — voir core/UploadHelper.php
  // côté serveur, qui sait lire ce repli en plus de $_FILES classique.
  function fileFieldsToBase64(form) {
    const inputs = Array.from(form.querySelectorAll('input[type="file"]'));
    const reads = inputs
      .filter((input) => input.files && input.files[0])
      .map((input) => new Promise((resolve, reject) => {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = () => resolve([input.name, file.name, String(reader.result).split(',')[1] || '']);
        reader.onerror = reject;
        reader.readAsDataURL(file);
      }));
    return Promise.all(reads);
  }

  function submitModalForm(form, submitter) {
    const token = ++requestToken;
    modalDirty = true;
    setModalContent('<div class="modal-loading"><span class="modal-spinner"></span>Enregistrement…</div>');
    fileFieldsToBase64(form)
      .then((fileFields) => {
        const params = new URLSearchParams();
        // Le bouton cliqué (ex. <button name="statut" value="ASSIGNED">) doit être passé
        // explicitement à FormData : sans lui, son name/value est silencieusement omis et
        // l'action correspondante n'est jamais envoyée au serveur.
        new FormData(form, submitter).forEach((value, key) => {
          if (typeof value === 'string') params.append(key, value);
        });
        params.set('modal', '1');
        fileFields.forEach(([name, filename, base64]) => {
          params.set(name + '_b64', base64);
          params.set(name + '_name', filename);
        });
        // form.action peut être masqué par un <input name="action"> (DOM : les éléments nommés
        // du formulaire écrasent les propriétés natives) — on lit l'attribut HTML brut à la place.
        return fetch(form.getAttribute('action'), {
          method: (form.method || 'POST').toUpperCase(),
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: params.toString(),
          credentials: 'same-origin',
        });
      })
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
    submitModalForm(e.target, e.submitter);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modalOverlay.hidden) closeModal();
  });

  window.LokaModal = { open: loadIntoModal, close: closeModal };
})();
