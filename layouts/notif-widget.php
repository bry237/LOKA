<?php
declare(strict_types=1);

/**
 * Widget de notifications autonome (bouton cloche + panneau), à inclure dans n'importe quelle
 * sidebar/topbar en dehors du socle admin.css (jordan, proprietaires...). Réutilise les mêmes
 * endpoints que layouts/admin.js (core/NotificationModel.php, gated uniquement sur la session,
 * pas sur le rôle), mais avec son propre CSS/JS scopés pour ne rien percuter des styles existants
 * de la page hôte.
 */
require_once __DIR__ . '/../core/Auth.php';
$lkNotifCsrf = Auth::csrfToken();
?>
<div class="lk-notif-wrap" data-csrf="<?= htmlspecialchars($lkNotifCsrf, ENT_QUOTES, 'UTF-8') ?>">
  <button type="button" class="lk-notif-btn" id="lkNotifBtn" aria-label="Notifications">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
    <span class="lk-notif-dot" id="lkNotifDot" hidden></span>
  </button>
  <div class="lk-notif-panel" id="lkNotifPanel" hidden>
    <div class="lk-notif-panel-head">
      <span>Notifications</span>
      <button type="button" id="lkNotifMarkAll">Tout marquer comme lu</button>
    </div>
    <div class="lk-notif-list" id="lkNotifList"><p class="lk-notif-empty">Chargement…</p></div>
  </div>
</div>
<style>
  .lk-notif-wrap{position:relative;display:inline-block;}
  .lk-notif-btn{
    position:relative;width:38px;height:38px;display:flex;align-items:center;justify-content:center;
    background:#fff;border:1px solid #D9E2E2;border-radius:10px;cursor:pointer;color:#475656;
  }
  .lk-notif-dot{
    position:absolute;top:8px;right:8px;width:7px;height:7px;border-radius:50%;
    background:#DC2626;border:1.5px solid #fff;
  }
  .lk-notif-panel{
    position:fixed;width:320px;max-width:calc(100vw - 32px);z-index:9999;
    background:#fff;border:1px solid #D9E2E2;border-radius:12px;
    box-shadow:0 16px 34px rgba(4,63,66,.14);
    font-family:'Plus Jakarta Sans',-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
  }
  .lk-notif-panel-head{
    display:flex;align-items:center;justify-content:space-between;gap:10px;
    padding:12px 14px;border-bottom:1px solid #D9E2E2;font-size:12.5px;font-weight:700;color:#172525;
  }
  .lk-notif-panel-head button{background:none;border:none;color:#0F8B8D;font-size:11px;font-weight:700;cursor:pointer;padding:0;}
  .lk-notif-list{max-height:360px;overflow-y:auto;}
  .lk-notif-item{padding:10px 14px;border-bottom:1px solid #D9E2E2;cursor:default;}
  .lk-notif-item:last-child{border-bottom:none;}
  .lk-notif-item.unread{background:#F3FAFA;cursor:pointer;}
  .lk-notif-item.unread:hover{background:#E9F5F5;}
  .lk-notif-item-top{display:flex;align-items:baseline;justify-content:space-between;gap:10px;}
  .lk-notif-item-title{font-size:12.5px;font-weight:700;color:#172525;}
  .lk-notif-item.unread .lk-notif-item-title::before{
    content:'';display:inline-block;width:6px;height:6px;border-radius:50%;background:#0F8B8D;margin-right:6px;
  }
  .lk-notif-item-time{font-size:10.5px;color:#8AA0A0;white-space:nowrap;flex-shrink:0;}
  .lk-notif-item-message{font-size:12px;color:#475656;margin-top:3px;line-height:1.4;}
  .lk-notif-empty{padding:18px 14px;text-align:center;font-size:12.5px;color:#8AA0A0;margin:0;}
</style>
<script>
(function(){
  var btn = document.getElementById('lkNotifBtn');
  var panel = document.getElementById('lkNotifPanel');
  var dot = document.getElementById('lkNotifDot');
  var list = document.getElementById('lkNotifList');
  var markAllBtn = document.getElementById('lkNotifMarkAll');
  if (!btn || !panel || !list) return;
  var wrap = document.querySelector('.lk-notif-wrap');
  var csrf = (wrap && wrap.dataset.csrf) || '';
  var typeLabels = { SMS: 'SMS', EMAIL: 'Email', INTERNAL: 'Notification' };

  function esc(str) {
    var div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
  }
  function timeAgo(raw) {
    var date = new Date(String(raw).replace(' ', 'T'));
    var diff = (Date.now() - date.getTime()) / 1000;
    if (diff < 60) return 'À l’instant';
    if (diff < 3600) return 'Il y a ' + Math.floor(diff / 60) + ' min';
    if (diff < 86400) return 'Il y a ' + Math.floor(diff / 3600) + 'h';
    return date.toLocaleDateString('fr-FR');
  }
  function setUnread(count) { dot.hidden = !(count > 0); }
  function render(items) {
    if (!items.length) { list.innerHTML = '<p class="lk-notif-empty">Aucune notification.</p>'; return; }
    list.innerHTML = items.map(function (n) {
      return '<div class="lk-notif-item' + (n.lu ? '' : ' unread') + '" data-id="' + n.id + '">' +
        '<div class="lk-notif-item-top"><span class="lk-notif-item-title">' + esc(n.titre || typeLabels[n.type] || 'Notification') + '</span>' +
        '<span class="lk-notif-item-time">' + timeAgo(n.date) + '</span></div>' +
        '<div class="lk-notif-item-message">' + esc(n.message) + '</div></div>';
    }).join('');
  }
  function load() {
    fetch('/LOKA/app/administration/notifications/data.php', { credentials: 'same-origin' })
      .then(function (res) { return res.ok ? res.json() : Promise.reject(); })
      .then(function (data) { setUnread(data.unread || 0); render(data.items || []); })
      .catch(function () { list.innerHTML = '<p class="lk-notif-empty">Impossible de charger les notifications.</p>'; });
  }
  function markRead(id) {
    var body = new URLSearchParams({ csrf_token: csrf });
    if (id) body.set('id', id); else body.set('all', '1');
    fetch('/LOKA/app/administration/notifications/marquer-lu.php', { method: 'POST', credentials: 'same-origin', body: body })
      .then(function (res) { return res.ok ? res.json() : Promise.reject(); })
      .then(function (data) { setUnread(data.unread || 0); })
      .catch(function () {});
  }
  function positionPanel() {
    var rect = btn.getBoundingClientRect();
    var width = panel.offsetWidth || 320;
    var left = Math.min(rect.left, window.innerWidth - width - 12);
    left = Math.max(left, 12);
    panel.style.left = left + 'px';
    panel.style.bottom = (window.innerHeight - rect.top + 8) + 'px';
    panel.style.top = 'auto';
  }
  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    panel.hidden = !panel.hidden;
    if (!panel.hidden) positionPanel();
  });
  list.addEventListener('click', function (e) {
    var item = e.target.closest('.lk-notif-item.unread');
    if (!item) return;
    item.classList.remove('unread');
    markRead(item.dataset.id);
  });
  markAllBtn && markAllBtn.addEventListener('click', function () {
    list.querySelectorAll('.lk-notif-item.unread').forEach(function (el) { el.classList.remove('unread'); });
    markRead(null);
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.lk-notif-wrap')) panel.hidden = true;
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') panel.hidden = true;
  });
  load();
})();
</script>
