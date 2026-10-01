
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
  key:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="15" r="4"/><path d="M10.85 12.15 19 4m0 0h-4m4 0v4"/></svg>',
  trending:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>',
  tool:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z"/></svg>',
  calendar:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
  alert:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
  plus:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
  check:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
  edit:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>',
  x:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
  shield:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v6c0 5 3.4 8.8 8 11 4.6-2.2 8-6 8-11V5l-8-3Z"/></svg>',
  arrow:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 15 12 9 18"/></svg>'
};

const STATUS_LABELS = { ACTIVE:'Actif', PENDING:'En attente', SUSPENDED:'Suspendu' };

/* ---------------- Render: subscription widget ---------------- */
function renderSubscription(){
  const el = document.getElementById('subscriptionCard');
  const s = DATA.subscription;
  if(!s){
    el.innerHTML = `
      <div class="panel-header"><div class="panel-title">Abonnement</div></div>
      <p class="empty-note">Aucun abonnement actif.</p>
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
    <div class="form-actions"><a class="btn btn-sm" href="abonnement.php">Changer de plan</a></div>
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

/* ---------------- Render: État du parc immobilier ---------------- */
function renderPortfolio(){
  const el = document.getElementById('portfolioBlock');
  const p = DATA.portfolio;
  if(!p || !p.total){
    el.innerHTML = `<p class="empty-note">Aucun bien enregistré pour le moment. La répartition par statut apparaîtra ici dès l'ajout de votre premier bien.</p>`;
    return;
  }
  const bar = p.segments.map(s => `<span class="portfolio-seg ${s.tone}" style="width:${s.pct}%" title="${s.label} — ${s.count}"></span>`).join('');
  const legend = p.segments.map(s => `
    <div class="donut-legend-row">
      <span class="lg"><i class="dot-legend tone-${s.tone}"></i>${s.label}</span>
      <span><b>${s.count}</b> (${s.pct}%)</span>
    </div>
  `).join('');
  el.innerHTML = `
    <div class="portfolio-block">
      <div class="portfolio-bar">${bar}</div>
      <div class="portfolio-legend">${legend}</div>
      <div class="portfolio-total">${p.total} bien${p.total > 1 ? 's' : ''} au total</div>
    </div>
  `;
}

/* ---------------- Render: Contrats à surveiller ---------------- */
function renderContractsToWatch(){
  const list = document.getElementById('contractsWatchList');
  const items = DATA.contractsToWatch;
  if(!items.length){
    list.innerHTML = `<p class="empty-note">Aucun contrat n'arrive à échéance dans les 30 prochains jours.</p>`;
    return;
  }
  list.innerHTML = items.map(c => `
    <div class="watch-item">
      <div class="watch-icon ${c.tone}">${ICONS.calendar}</div>
      <div>
        <div class="watch-title">${c.bien} — ${c.locataire}</div>
        <div class="watch-sub">Contrat ${c.numero} · échéance le ${c.date_fin}</div>
      </div>
      <div class="watch-days ${c.tone}">${c.days_left <= 0 ? 'Échu' : c.days_left + ' j'}</div>
    </div>
  `).join('');
}

/* ---------------- Render: À traiter aujourd'hui ---------------- */
function renderTodayTasks(){
  const list = document.getElementById('todayTasksList');
  const tasks = DATA.todayTasks;
  if(!tasks.length){
    list.innerHTML = `
      <div class="watch-item all-clear">
        <div class="watch-icon green">${ICONS.check}</div>
        <div>
          <div class="watch-title">Rien à signaler</div>
          <div class="watch-sub">Aucune action urgente aujourd'hui</div>
        </div>
      </div>
    `;
    return;
  }
  list.innerHTML = tasks.map(t => `
    <div class="watch-item">
      <div class="watch-icon ${t.tone}">${ICONS[t.icon]}</div>
      <div>
        <div class="watch-title">${t.title}</div>
        <div class="watch-sub">${t.sub}</div>
      </div>
    </div>
  `).join('');
}

/* ---------------- Render: Activity ---------------- */
function renderActivity(){
  const list = document.getElementById('activityList');
  if(!DATA.activity.length){
    list.innerHTML = '<p class="empty-note">Aucune activité pour le moment.</p>';
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

/* ---------------- Render: Actions rapides ---------------- */
const QUICK_ACTIONS = [
  { icon:'home', label:'Ajouter un bien', module:'Biens' },
  { icon:'user', label:'Ajouter un locataire', module:'Locataires' },
  { icon:'card', label:'Créer un contrat', module:'Contrats' },
  { icon:'users', label:'Inviter un collaborateur', href:'equipe.php' },
];
function renderQuickActions(){
  const el = document.getElementById('quickActions');
  el.innerHTML = QUICK_ACTIONS.map(a => a.href ? `
    <a class="quick-action" href="${a.href}">
      <div class="quick-action-icon">${ICONS[a.icon]}</div>
      <div class="quick-action-label">${a.label}</div>
    </a>
  ` : `
    <div class="quick-action disabled" title="Disponible avec le module ${a.module}">
      <div class="quick-action-icon">${ICONS[a.icon]}</div>
      <div class="quick-action-label">${a.label}</div>
      <span class="nav-soon light">Bientôt</span>
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
  renderKpis();
  renderPortfolio();
  renderContractsToWatch();
  renderTodayTasks();
  renderSubscription();
  renderQuickActions();
  renderActivity();
}
init();
