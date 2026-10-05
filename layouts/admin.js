/* =========================================================
   admin.js — comportements partagés par toutes les pages
   "administration plateforme" (sidebar mobile, déconnexion).
   Chargé automatiquement par layouts/footer.php.
   ========================================================= */
(function(){
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');
  const menuToggle = document.getElementById('menuToggle');
  const logoutLink = document.getElementById('logoutLink');

  function openSidebar(){ sidebar.classList.add('open'); overlay.classList.add('show'); }
  function closeSidebar(){ sidebar.classList.remove('open'); overlay.classList.remove('show'); }

  menuToggle && menuToggle.addEventListener('click', openSidebar);
  overlay && overlay.addEventListener('click', closeSidebar);

  logoutLink && logoutLink.addEventListener('click', () => {
    window.location.href = '/LOKA/app/authentification/deconnexion.php';
  });

  /* ---------- Bannières de confirmation : disparition auto après 5s ---------- */
  document.querySelectorAll('.feedback').forEach((el) => {
    setTimeout(() => {
      el.style.transition = 'opacity .4s ease';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 400);
    }, 5000);
  });

  /* ---------- Barres de recherche des listes (filters-form) : auto-soumission ---------- */
  document.querySelectorAll('.filters-form input[type="text"]').forEach((input) => {
    let timer;
    input.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(() => input.form && input.form.requestSubmit(), 450);
    });
  });

  /* ---------- Recherche globale de la topbar : résultats live ---------- */
  const searchInput = document.getElementById('globalSearchInput');
  const searchResults = document.getElementById('globalSearchResults');

  if (searchInput && searchResults) {
    let searchTimer;
    let activeRequest = 0;

    function renderResults(data) {
      const groups = [
        { label: 'Agences', items: data.agences || [] },
        { label: 'Utilisateurs', items: data.utilisateurs || [] },
      ];
      searchResults.textContent = '';

      const hasResults = groups.some((g) => g.items.length > 0);
      if (!hasResults) {
        const empty = document.createElement('div');
        empty.className = 'sr-empty';
        empty.textContent = 'Aucun résultat.';
        searchResults.appendChild(empty);
        searchResults.hidden = false;
        return;
      }

      groups.filter((g) => g.items.length > 0).forEach((g) => {
        const label = document.createElement('div');
        label.className = 'sr-group-label';
        label.textContent = g.label;
        searchResults.appendChild(label);

        g.items.forEach((item) => {
          const link = document.createElement('a');
          link.className = 'sr-item';
          link.href = item.url;

          const title = document.createElement('span');
          title.className = 'sr-title';
          title.textContent = item.title;

          const sub = document.createElement('span');
          sub.className = 'sr-sub';
          sub.textContent = item.sub;

          link.dataset.modal = '1';
          link.appendChild(title);
          link.appendChild(sub);
          searchResults.appendChild(link);
        });
      });
      searchResults.hidden = false;
    }

    searchInput.addEventListener('input', () => {
      const q = searchInput.value.trim();
      clearTimeout(searchTimer);
      if (q.length < 2) {
        searchResults.hidden = true;
        return;
      }
      searchTimer = setTimeout(() => {
        const requestId = ++activeRequest;
        fetch('../search.php?q=' + encodeURIComponent(q), { credentials: 'same-origin' })
          .then((res) => (res.ok ? res.json() : Promise.reject()))
          .then((data) => { if (requestId === activeRequest) renderResults(data); })
          .catch(() => {});
      }, 300);
    });

    document.addEventListener('click', (e) => {
      if (!e.target.closest('.search-box')) searchResults.hidden = true;
    });
    searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') searchResults.hidden = true;
    });
  }

  /* ---------- Modale globale : ajouter / consulter / modifier ---------- */
  const modalOverlay = document.getElementById('modalOverlay');
  const modalPanel = document.getElementById('modalPanel');
  const modalBody = document.getElementById('modalBody');
  const modalClose = document.getElementById('modalClose');

  if (modalOverlay && modalPanel && modalBody) {
    let modalDirty = false;
    let requestToken = 0;

    function withModalParam(url) {
      return url + (url.includes('?') ? '&' : '?') + 'modal=1';
    }

    function setModalContent(html) {
      modalBody.innerHTML = html;
      modalPanel.scrollTop = 0;
    }

    function openModalShell() {
      searchResults && (searchResults.hidden = true);
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
        if (html.indexOf('<html') !== -1) {
          window.location.href = res.url;
          return null;
        }
        return html;
      });
    }

    function loadIntoModal(url) {
      const token = ++requestToken;
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
      fetch(form.action, { method: (form.method || 'POST').toUpperCase(), body: fd, credentials: 'same-origin' })
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
        openModalShell();
        loadIntoModal(trigger.href);
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
  }
})();
