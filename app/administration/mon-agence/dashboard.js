
/* =========================================================
   Données du dashboard "Mon agence" — chargées depuis data.php
   (JSON), voir MonAgenceModel.php pour les requêtes réelles.
   ========================================================= */

let DATA = null;

async function loadDashboardData(){
  const res = await fetch('data.php', { credentials: 'same-origin' });
  if(!res.ok) throw new Error('Échec du chargement des données (HTTP ' + res.status + ')');
  return res.json();
}

/* ---------------- Icons ---------------- */
const ICONS = {
  users:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
  home:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>',
  card:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>',
  user:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg>',
  plus:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
  check:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
  edit:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>',
  x:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
  shield:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v6c0 5 3.4 8.8 8 11 4.6-2.2 8-6 8-11V5l-8-3Z"/></svg>'
};

const STATUS_LABELS = { ACTIVE:'Actif', PENDING:'En attente', SUSPENDED:'Suspendu' };
const STATUS_TONES = { ACTIVE:'actif', PENDING:'attente', SUSPENDED:'suspendu' };

/* ---------------- Render: agency identity card ---------------- */
function renderAgencyCard(){
  const el = document.getElementById('agencyCard');
  const a = DATA.agency;
  if(!a){
    el.innerHTML = '<p style="padding:18px;color:#8AA0A0;font-size:13px;">Agence introuvable.</p>';
    return;
  }
  el.innerHTML = `
    <div class="panel-header">
      <div class="panel-title">${a.nom}</div>
      <span class="status-badge ${STATUS_TONES[a.statut] || 'attente'}">${STATUS_LABELS[a.statut] || a.statut}</span>
    </div>
    <div class="agency-details">
      <div><span class="lbl">Ville</span><span class="val">${a.ville || '—'}</span></div>
      <div><span class="lbl">Email</span><span class="val">${a.email}</span></div>
      <div><span class="lbl">Téléphone</span><span class="val">${a.telephone || '—'}</span></div>
    </div>
  `;
}

/* ---------------- Render: subscription widget ---------------- */
function renderSubscription(){
  const el = document.getElementById('subscriptionCard');
  const s = DATA.subscription;
  if(!s){
    el.innerHTML = `
      <div class="panel-header"><div class="panel-title">Abonnement</div></div>
      <p style="padding:0 18px 18px;color:#8AA0A0;font-size:13px;">Aucun abonnement actif.</p>
    `;
    return;
  }
  const limiteUsers = s.limite_utilisateurs ?? 'Illimité';
  const limiteBiens = s.limite_biens ?? 'Illimité';
  el.innerHTML = `
    <div class="panel-header">
      <div class="panel-title">Abonnement</div>
      <span class="plan-badge ${s.nom.toLowerCase()}">${s.nom}</span>
    </div>
    <div class="agency-details">
      <div><span class="lbl">Prix mensuel</span><span class="val">${Number(s.prix_mensuel).toFixed(2)} €</span></div>
      <div><span class="lbl">Limite utilisateurs</span><span class="val">${limiteUsers}</span></div>
      <div><span class="lbl">Limite biens</span><span class="val">${limiteBiens}</span></div>
    </div>
  `;
}

/* ---------------- Render: KPIs ---------------- */
function renderKpis(){
  const grid = document.getElementById('kpiGrid');
  grid.innerHTML = DATA.kpis.map(k => `
    <div class="kpi-card">
      <div class="kpi-top">
        <div class="kpi-icon ${k.tone}">${ICONS[k.icon]}</div>
      </div>
      <div class="kpi-value">${k.value}</div>
      <div class="kpi-label">${k.label}</div>
    </div>
  `).join('');
}

/* ---------------- Render: Activity ---------------- */
function renderActivity(){
  const list = document.getElementById('activityList');
  if(!DATA.activity.length){
    list.innerHTML = '<p style="padding:0 18px 18px;color:#8AA0A0;font-size:13px;">Aucune activité pour le moment.</p>';
    return;
  }
  list.innerHTML = DATA.activity.map(a => `
    <div class="activity-item">
      <div class="activity-icon ${a.tone}">${ICONS[a.icon]}</div>
      <div class="activity-main" style="flex:1;min-width:0;">
        <div class="activity-row">
          <div class="activity-title">${a.title}</div>
          <div class="activity-time">${a.time}</div>
        </div>
        <div class="activity-sub">${a.sub}</div>
      </div>
    </div>
  `).join('');
}

/* ---------------- Init ---------------- */
async function init(){
  try {
    DATA = await loadDashboardData();
  } catch (err) {
    console.error(err);
    document.getElementById('kpiGrid').innerHTML =
      '<p style="color:#DC2626;font-size:13px;">Impossible de charger les données du dashboard. Réessayez plus tard.</p>';
    return;
  }
  renderAgencyCard();
  renderSubscription();
  renderKpis();
  renderActivity();
}
init();
