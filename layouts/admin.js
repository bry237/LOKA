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
})();
