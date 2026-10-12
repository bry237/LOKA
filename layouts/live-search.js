/* =========================================================
   live-search.js — recherche/filtres instantanés sans
   rechargement de page, réutilisable sur les listes du
   back-office (utilisateurs, agences, équipe…).

   Utilisation :
   <form data-live-search data-live-target="#resultsZone" method="get">
     ... champs de recherche / filtres ...
   </form>
   <div id="resultsZone"> ... fragment rendu par le serveur ... </div>

   Le serveur doit répondre, pour la même URL + "&ajax=1",
   avec uniquement le fragment HTML à injecter dans la cible.
   ========================================================= */
(function () {
  document.querySelectorAll('form[data-live-search]').forEach((form) => {
    const targetSelector = form.dataset.liveTarget;
    const target = targetSelector ? document.querySelector(targetSelector) : null;
    if (!target) return;

    const wrapper = form.querySelector('.search-box');
    let timer;
    let activeRequest = 0;

    function setLoading(isLoading) {
      wrapper && wrapper.classList.toggle('is-loading', isLoading);
    }

    function run(pushState) {
      const params = new URLSearchParams(new FormData(form));
      params.set('ajax', '1');
      const url = form.getAttribute('action') || window.location.pathname;
      const requestId = ++activeRequest;
      setLoading(true);

      fetch(url + '?' + params.toString(), { credentials: 'same-origin' })
        .then((res) => (res.ok ? res.text() : Promise.reject()))
        .then((html) => {
          if (requestId !== activeRequest) return;
          target.innerHTML = html;
          setLoading(false);
          if (pushState) {
            params.delete('ajax');
            const newUrl = url + (params.toString() ? '?' + params.toString() : '');
            window.history.replaceState(null, '', newUrl);
          }
        })
        .catch(() => {
          if (requestId !== activeRequest) return;
          setLoading(false);
        });
    }

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      clearTimeout(timer);
      run(true);
    });

    form.querySelectorAll('input[type="text"], input[type="search"], input[type="number"]').forEach((input) => {
      input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => run(true), 350);
      });
    });

    form.querySelectorAll('select').forEach((select) => {
      select.addEventListener('change', () => {
        clearTimeout(timer);
        run(true);
      });
    });

    // Pagination : intercepter les liens de page pour rester en AJAX.
    target.addEventListener('click', (e) => {
      const link = e.target.closest('a.page-btn[href]');
      if (!link || link.getAttribute('aria-disabled') === 'true') return;
      e.preventDefault();
      const pageUrl = new URL(link.getAttribute('href'), window.location.href);
      pageUrl.searchParams.set('ajax', '1');
      const requestId = ++activeRequest;
      setLoading(true);
      fetch(pageUrl.toString(), { credentials: 'same-origin' })
        .then((res) => (res.ok ? res.text() : Promise.reject()))
        .then((html) => {
          if (requestId !== activeRequest) return;
          target.innerHTML = html;
          setLoading(false);
          pageUrl.searchParams.delete('ajax');
          window.history.replaceState(null, '', pageUrl.toString());
        })
        .catch(() => {
          if (requestId !== activeRequest) return;
          setLoading(false);
        });
    });
  });
})();
