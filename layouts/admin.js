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

  /* ---------- Notifications de la topbar ---------- */
  const notifWrap = document.querySelector('.notif-wrap');
  const notifBtn = document.getElementById('notifBtn');
  const notifPanel = document.getElementById('notifPanel');
  const notifDot = document.getElementById('notifDot');
  const notifList = document.getElementById('notifList');
  const notifMarkAllBtn = document.getElementById('notifMarkAllBtn');

  if (notifWrap && notifBtn && notifPanel && notifList) {
    const notifCsrfToken = notifWrap.dataset.csrf || '';
    const notifTypeLabels = { SMS: 'SMS', EMAIL: 'Email', INTERNAL: 'Notification' };

    function escapeHtml(str) {
      const div = document.createElement('div');
      div.textContent = str || '';
      return div.innerHTML;
    }

    function notifTimeAgo(raw) {
      const date = new Date(String(raw).replace(' ', 'T'));
      const diff = (Date.now() - date.getTime()) / 1000;
      if (diff < 60) return 'À l’instant';
      if (diff < 3600) return 'Il y a ' + Math.floor(diff / 60) + ' min';
      if (diff < 86400) return 'Il y a ' + Math.floor(diff / 3600) + 'h';
      return date.toLocaleDateString('fr-FR');
    }

    function setUnread(count) {
      notifDot.hidden = !(count > 0);
    }

    function renderNotifs(items) {
      if (!items.length) {
        notifList.innerHTML = '<p class="empty-note">Aucune notification.</p>';
        return;
      }
      notifList.innerHTML = items.map((n) => `
        <div class="notif-item${n.lu ? '' : ' unread'}" data-id="${n.id}">
          <div class="notif-item-top">
            <span class="notif-item-title">${escapeHtml(n.titre || notifTypeLabels[n.type] || 'Notification')}</span>
            <span class="notif-item-time">${notifTimeAgo(n.date)}</span>
          </div>
          <div class="notif-item-message">${escapeHtml(n.message)}</div>
        </div>
      `).join('');
    }

    function loadNotifs() {
      fetch('/LOKA/app/administration/notifications/data.php', { credentials: 'same-origin' })
        .then((res) => (res.ok ? res.json() : Promise.reject()))
        .then((data) => {
          setUnread(data.unread || 0);
          renderNotifs(data.items || []);
        })
        .catch(() => {
          notifList.innerHTML = '<p class="empty-note">Impossible de charger les notifications.</p>';
        });
    }

    function markRead(id) {
      const body = new URLSearchParams({ csrf_token: notifCsrfToken });
      if (id) body.set('id', id); else body.set('all', '1');
      fetch('/LOKA/app/administration/notifications/marquer-lu.php', { method: 'POST', credentials: 'same-origin', body })
        .then((res) => (res.ok ? res.json() : Promise.reject()))
        .then((data) => setUnread(data.unread || 0))
        .catch(() => {});
    }

    notifBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const profileMenuEl = document.getElementById('profileMenu');
      profileMenuEl && (profileMenuEl.hidden = true);
      notifPanel.hidden = !notifPanel.hidden;
    });

    notifList.addEventListener('click', (e) => {
      const item = e.target.closest('.notif-item.unread');
      if (!item) return;
      item.classList.remove('unread');
      markRead(item.dataset.id);
    });

    notifMarkAllBtn && notifMarkAllBtn.addEventListener('click', () => {
      notifList.querySelectorAll('.notif-item.unread').forEach((el) => el.classList.remove('unread'));
      markRead(null);
    });

    document.addEventListener('click', (e) => {
      if (!e.target.closest('.notif-wrap')) notifPanel.hidden = true;
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') notifPanel.hidden = true;
    });

    loadNotifs();
  }

  /* ---------- Menu profil de la topbar ---------- */
  const profileWrap = document.querySelector('.profile-wrap');
  const profileBtn = document.getElementById('profileBtn');
  const profileMenu = document.getElementById('profileMenu');

  if (profileWrap && profileBtn && profileMenu) {
    profileBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const notifPanelEl = document.getElementById('notifPanel');
      notifPanelEl && (notifPanelEl.hidden = true);
      profileMenu.hidden = !profileMenu.hidden;
    });
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.profile-wrap')) profileMenu.hidden = true;
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
      // innerHTML ne fait pas exécuter les <script> injectés : on les reconstruit pour forcer leur exécution.
      modalBody.querySelectorAll('script').forEach((oldScript) => {
        const newScript = document.createElement('script');
        for (const attr of oldScript.attributes) newScript.setAttribute(attr.name, attr.value);
        newScript.textContent = oldScript.textContent;
        oldScript.replaceWith(newScript);
      });
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

    function submitModalForm(form) {
      const token = ++requestToken;
      modalDirty = true;
      setModalContent('<div class="modal-loading"><span class="modal-spinner"></span>Enregistrement…</div>');
      fileFieldsToBase64(form)
        .then((fileFields) => {
          const params = new URLSearchParams();
          new FormData(form).forEach((value, key) => {
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
