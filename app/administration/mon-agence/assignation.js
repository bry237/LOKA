
/* =========================================================
   assignation.js — page "Affectation des biens" :
   - bouton "Affecter manuellement" : focus la table existante
   - bouton "Affectation automatique" : modale de répartition
     (sélection des biens, membres éligibles, aperçu équilibré,
     confirmation) construite entièrement côté client à partir
     des données embarquées par biens-assignation.php.
   Aucun nouvel endpoint : la confirmation rejoue, bien par bien,
   le même POST que la réaffectation manuelle (biens-assignation.php).
   ========================================================= */
(function(){
  var dataEl = document.getElementById('autoAssignData');
  if (!dataEl) return;
  var DATA = JSON.parse(dataEl.textContent || '{}');
  var BIENS = DATA.biens || [];
  var MEMBERS = DATA.members || [];
  var CSRF = DATA.csrfToken || '';

  var ICON_ALERT = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';

  /* ---------- "Affecter manuellement" : ramène simplement au tableau ---------- */
  var btnManual = document.getElementById('btnManualAssign');
  btnManual && btnManual.addEventListener('click', function(){
    var table = document.getElementById('biensTable');
    table && table.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  /* ---------- Répartition équilibrée (round-robin), aléatoire en option ---------- */
  function shuffle(arr){
    var copy = arr.slice();
    for (var i = copy.length - 1; i > 0; i--){
      var j = Math.floor(Math.random() * (i + 1));
      var tmp = copy[i]; copy[i] = copy[j]; copy[j] = tmp;
    }
    return copy;
  }

  function distribute(selectedBiens, members, random){
    var biensOrder = random ? shuffle(selectedBiens) : selectedBiens.slice();
    var membersOrder = random ? shuffle(members) : members.slice();
    var plan = membersOrder.map(function(m){ return { member: m, biens: [] }; });
    biensOrder.forEach(function(bien, i){
      plan[i % plan.length].biens.push(bien);
    });
    return plan;
  }

  /* ---------- Construction de la modale ---------- */
  var overlay = document.getElementById('modalOverlay');
  var panel = document.getElementById('modalPanel');
  var body = document.getElementById('modalBody');

  function openModal(){
    panel.style.maxWidth = '';
    panel.style.minHeight = '';
    panel.style.alignSelf = '';
    overlay.hidden = false;
    document.body.classList.add('modal-open');
    requestAnimationFrame(function(){ overlay.classList.add('show'); });
  }

  function selectedBienIds(){
    return Array.prototype.slice.call(body.querySelectorAll('.auto-assign-bien input:checked')).map(function(cb){
      return parseInt(cb.value, 10);
    });
  }

  function renderPreview(){
    var ids = selectedBienIds();
    var selected = BIENS.filter(function(b){ return ids.indexOf(b.id) !== -1; });
    var preview = body.querySelector('.auto-assign-preview');
    var confirmBtn = document.getElementById('autoAssignConfirm');
    var countLabel = body.querySelector('.auto-assign-section-title span');

    if (!selected.length || !MEMBERS.length){
      preview.innerHTML = '<div class="auto-assign-placeholder">' + (MEMBERS.length ? 'Sélectionnez au moins un bien pour voir la répartition.' : 'Aucun membre éligible pour le moment.') + '</div>';
      confirmBtn.disabled = true;
      if (countLabel) countLabel.textContent = ids.length + ' sélectionné' + (ids.length > 1 ? 's' : '');
      return;
    }

    var random = body.querySelector('input[name="autoAssignMode"]:checked').value === 'random';
    var plan = distribute(selected, MEMBERS, random);

    preview.innerHTML = plan.map(function(row){
      var m = row.member;
      var list = row.biens.map(function(b){ return b.reference; }).join(', ');
      return '' +
        '<div class="auto-assign-preview-row">' +
          '<div class="cell-avatar role-' + m.tone + '">' + m.initials + '</div>' +
          '<div>' +
            '<div class="auto-assign-preview-name">' + m.nom + '</div>' +
            '<div class="auto-assign-preview-role">' + m.role + '</div>' +
          '</div>' +
          '<div class="auto-assign-preview-count">' + row.biens.length + ' bien' + (row.biens.length > 1 ? 's' : '') + '</div>' +
          (list ? '<div class="auto-assign-preview-list">' + list + '</div>' : '') +
        '</div>';
    }).join('');

    confirmBtn.disabled = false;
    if (countLabel) countLabel.textContent = ids.length + ' sélectionné' + (ids.length > 1 ? 's' : '');
  }

  function renderModal(){
    var biensListHtml = BIENS.map(function(b){
      var checkedByDefault = !b.assigned;
      return '' +
        '<label class="auto-assign-bien' + (b.assigned ? ' already-assigned' : '') + '">' +
          '<input type="checkbox" value="' + b.id + '"' + (checkedByDefault ? ' checked' : '') + '>' +
          '<span class="auto-assign-bien-title">' + b.reference + ' — ' + b.titre + '</span>' +
          (b.assigned ? '<span class="auto-assign-bien-sub">déjà affecté à ' + b.responsable + '</span>' : '') +
        '</label>';
    }).join('');

    var membersHtml = MEMBERS.map(function(m){
      return '' +
        '<div class="auto-assign-member">' +
          '<div class="cell-avatar role-' + m.tone + '">' + m.initials + '</div>' +
          '<span class="auto-assign-member-name">' + m.nom + '</span>' +
          '<span class="auto-assign-member-role">' + m.role + '</span>' +
        '</div>';
    }).join('');

    var noMembersWarning = !MEMBERS.length ? (
      '<div class="auto-assign-empty">' + ICON_ALERT +
      '<span>Aucun gestionnaire immobilier ni technicien dans votre équipe. Invitez-en un pour pouvoir répartir vos biens automatiquement — un comptable ou un administrateur n’est jamais éligible à cette répartition.</span></div>'
    ) : '';

    body.innerHTML = '' +
      '<div class="page-header">' +
        '<h1>Affectation automatique</h1>' +
        '<p>Répartissez plusieurs biens en une fois entre vos gestionnaires et techniciens.</p>' +
      '</div>' +
      '<div class="auto-assign">' +
        '<div>' +
          '<div class="auto-assign-section-title">Biens à affecter <span></span></div>' +
          '<div class="auto-assign-biens">' + (biensListHtml || '<div class="auto-assign-placeholder">Aucun bien disponible.</div>') + '</div>' +
        '</div>' +
        '<div>' +
          '<div class="auto-assign-section-title">Membres éligibles</div>' +
          (noMembersWarning || '<div class="auto-assign-members">' + membersHtml + '</div>') +
        '</div>' +
        (MEMBERS.length ? (
          '<div>' +
            '<div class="auto-assign-section-title">Mode de répartition</div>' +
            '<div class="auto-assign-mode">' +
              '<label><input type="radio" name="autoAssignMode" value="balanced" checked> Répartition équilibrée (par ordre)</label>' +
              '<label><input type="radio" name="autoAssignMode" value="random"> Répartition aléatoire équilibrée</label>' +
            '</div>' +
          '</div>' +
          '<div>' +
            '<div class="auto-assign-preview-head"><b>Aperçu de la répartition</b></div>' +
            '<div class="auto-assign-preview"></div>' +
          '</div>'
        ) : '') +
        '<div class="auto-assign-footer">' +
          '<span class="auto-assign-progress" id="autoAssignProgress"></span>' +
          '<button type="button" class="btn" data-modal-cancel>Annuler</button>' +
          '<button type="button" class="btn btn-primary" id="autoAssignConfirm" disabled>Confirmer l’affectation</button>' +
        '</div>' +
      '</div>';

    var confirmBtn = document.getElementById('autoAssignConfirm');
    confirmBtn && confirmBtn.addEventListener('click', confirmAssignment);

    if (MEMBERS.length) renderPreview();
  }

  /* ---------- Confirmation : rejoue le POST existant, bien par bien ---------- */
  function confirmAssignment(){
    var ids = selectedBienIds();
    if (!ids.length) return;
    var selected = BIENS.filter(function(b){ return ids.indexOf(b.id) !== -1; });
    var random = body.querySelector('input[name="autoAssignMode"]:checked').value === 'random';
    var plan = distribute(selected, MEMBERS, random);

    var pairs = [];
    plan.forEach(function(row){
      row.biens.forEach(function(bien){ pairs.push({ bien: bien, member: row.member }); });
    });

    var confirmBtn = document.getElementById('autoAssignConfirm');
    var cancelBtn = body.querySelector('[data-modal-cancel]');
    var progress = document.getElementById('autoAssignProgress');
    confirmBtn.disabled = true;
    cancelBtn.disabled = true;

    var done = 0;
    function next(){
      if (done >= pairs.length){
        window.location.href = 'biens-assignation.php?success=' + encodeURIComponent(pairs.length + ' bien' + (pairs.length > 1 ? 's affectés' : ' affecté') + ' automatiquement.');
        return;
      }
      var pair = pairs[done];
      progress.textContent = 'Affectation ' + (done + 1) + ' / ' + pairs.length + '…';

      var fd = new FormData();
      fd.set('csrf_token', CSRF);
      fd.set('id_bien', String(pair.bien.id));
      fd.set('id_responsable', String(pair.member.id));

      fetch('biens-assignation.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function(res){
          var failed = new URL(res.url).searchParams.has('error');
          if (failed) throw new Error('assign-failed');
          done++;
          next();
        })
        .catch(function(){
          progress.textContent = '';
          var errorNote = document.createElement('div');
          errorNote.className = 'feedback error';
          errorNote.style.marginTop = '10px';
          errorNote.textContent = 'Une erreur est survenue pendant l’affectation. Rechargez la page et réessayez.';
          body.querySelector('.auto-assign').appendChild(errorNote);
          confirmBtn.disabled = false;
          cancelBtn.disabled = false;
        });
    }
    next();
  }

  body.addEventListener('change', function(e){
    if (e.target.matches('.auto-assign-bien input, input[name="autoAssignMode"]')) renderPreview();
  });

  var btnAuto = document.getElementById('btnAutoAssign');
  btnAuto && btnAuto.addEventListener('click', function(){
    openModal();
    renderModal();
  });
})();
