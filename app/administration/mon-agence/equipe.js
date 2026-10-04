
/* =========================================================
   equipe.js — interactions de la page "Équipe" et de la
   modale d'invitation (equipe-inviter.php, injectée dans
   #modalBody : les écouteurs sont donc délégués sur document
   pour fonctionner même sur du contenu ajouté après coup).
   ========================================================= */
(function(){

  /* ---------- Copier l'email d'un membre ---------- */
  document.addEventListener('click', function(e){
    var btn = e.target.closest('[data-copy-email]');
    if (!btn) return;
    var email = btn.getAttribute('data-copy-email') || '';
    if (!email || !navigator.clipboard) return;
    navigator.clipboard.writeText(email).then(function(){
      btn.classList.add('copied');
      setTimeout(function(){ btn.classList.remove('copied'); }, 1400);
    }).catch(function(){});
  });

  /* ---------- Indice contextuel selon le rôle choisi (invitation) ---------- */
  document.addEventListener('change', function(e){
    if (!e.target || e.target.id !== 'id_role') return;
    var select = e.target;
    var selectedLabel = select.options[select.selectedIndex] ? select.options[select.selectedIndex].text : '';
    var hints = document.querySelectorAll('.role-hint');
    hints.forEach(function(hint){
      hint.classList.toggle('active', hint.dataset.roleHint === selectedLabel);
    });
  });

})();
