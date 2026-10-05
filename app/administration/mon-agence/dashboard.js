
/* =========================================================
   Données du dashboard "Mon agence" — chargées depuis data.php
   (JSON), voir MonAgenceModel.php pour les requêtes réelles.
   Ce fichier ne fait que de la présentation : toute donnée
   dérivée (usage d'abonnement, répartition du parc...) est
   recalculée ici à partir de ce que data.php renvoie déjà,
   sans appel supplémentaire ni logique métier.
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
  arrow:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 15 12 9 18"/></svg>',
  inbox:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11Z"/></svg>',
};

const STATUS_LABELS = { ACTIVE:'Actif', PENDING:'En attente', SUSPENDED:'Suspendu' };

/* ---------------- Helpers ---------------- */

/** Lit la valeur numérique brute d'un KPI déjà formaté ("1 234" -> 1234). */
function kpiRawValue(label){
  const k = DATA.kpis.find(item => item.label === label);
  if(!k) return null;
  const n = parseInt(String(k.value).replace(/[^\d-]/g, ''), 10);
  return Number.isNaN(n) ? null : n;
}

/**
 * Information secondaire d'un KPI, dérivée d'autres blocs déjà chargés (jamais d'appel réseau
 * supplémentaire). Retourne null quand rien de pertinent n'est disponible : on n'affiche jamais
 * une ligne vide ou inventée.
 */
function kpiSecondary(k){
  if(k.label === 'Biens' && DATA.portfolio && DATA.portfolio.total){
    const dispo = DATA.portfolio.segments.find(s => s.statut === 'AVAILABLE');
    if(dispo && dispo.count > 0) return dispo.count + ' disponible' + (dispo.count > 1 ? 's' : '');
  }
  if(k.label === 'Contrats actifs' && DATA.contractsToWatch && DATA.contractsToWatch.length){
    const n = DATA.contractsToWatch.length;
    return n + ' à échéance sous 30 j';
  }
  if(k.label === 'Interventions en cours'){
    const n = kpiRawValue('Interventions en cours');
    if(n > 0) return 'Nécessite une action';
  }
  return null;
}

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

  const usersUsed = kpiRawValue('Équipe') ?? 0;
  const biensUsed = kpiRawValue('Biens') ?? 0;
  const usersLimit = s.limite_utilisateurs;
  const biensLimit = s.limite_biens;

  const meter = (used, limit) => {
    if(limit === null || limit === undefined){
      return { pct:6, tone:'', label: used + ' · illimité' };
    }
    const pct = limit > 0 ? Math.min(100, Math.round((used / limit) * 100)) : 100;
    const tone = pct >= 100 ? 'full' : (pct >= 80 ? 'warn' : '');
    return { pct: Math.max(pct, 4), tone, label: used + ' / ' + limit };
  };
  const usersMeter = meter(usersUsed, usersLimit);
  const biensMeter = meter(biensUsed, biensLimit);
  const planSlug = s.nom.toLowerCase();

  el.innerHTML = `
    <div class="subscription-card-head">
      <div>
        <div class="panel-title">Abonnement</div>
        <div class="subscription-plan-name">${s.nom}</div>
      </div>
      <span class="plan-badge ${planSlug}">${Number(s.prix_mensuel) > 0 ? Number(s.prix_mensuel).toFixed(2) + ' €/mois' : 'Gratuit'}</span>
    </div>
    <div class="subscription-usage">
      <div class="subscription-meter">
        <div class="subscription-meter-head"><span>Utilisateurs</span><span>${usersMeter.label}</span></div>
        <div class="usage-meter-track"><div class="usage-meter-fill ${usersMeter.tone}" style="width:${usersMeter.pct}%;"></div></div>
      </div>
      <div class="subscription-meter">
        <div class="subscription-meter-head"><span>Biens</span><span>${biensMeter.label}</span></div>
        <div class="usage-meter-track"><div class="usage-meter-fill ${biensMeter.tone}" style="width:${biensMeter.pct}%;"></div></div>
      </div>
    </div>
    <div class="subscription-card-foot">
      <a class="btn btn-primary" href="abonnement.php">Gérer mon abonnement</a>
    </div>
  `;
}

/* ---------------- Render: KPIs ---------------- */
function renderKpis(){
  const grid = document.getElementById('kpiGrid');
  grid.innerHTML = DATA.kpis.map(k => {
    const sub = kpiSecondary(k);
    return `
      <div class="kpi-card">
        <div class="kpi-top">
          <div class="kpi-icon ${k.tone}">${ICONS[k.icon]}</div>
        </div>
        <div class="kpi-value">${k.value}</div>
        <div class="kpi-label">${k.label}</div>
        ${sub ? `<div class="kpi-sub">${sub}</div>` : ''}
      </div>
    `;
  }).join('');
}

/* ---------------- Render: État du parc immobilier ---------------- */
function renderPortfolio(){
  const el = document.getElementById('portfolioBlock');
  const p = DATA.portfolio;

  if(!p || !p.total){
    el.innerHTML = `
      <div class="empty-state">
        <div class="empty-state-icon">${ICONS.home}</div>
        <h4>Votre portefeuille est encore vide</h4>
        <p>Ajoutez votre premier bien pour commencer à gérer votre patrimoine.</p>
        <span class="btn empty-state-cta" title="Bientôt disponible">${ICONS.plus}Ajouter un bien<span class="nav-soon light">Bientôt</span></span>
      </div>
    `;
    return;
  }

  let cursor = 0;
  const stops = p.segments.map((s, i) => {
    const from = cursor;
    const isLast = i === p.segments.length - 1;
    cursor = isLast ? 100 : cursor + s.pct;
    return `var(--seg-${s.tone}) ${from}% ${cursor}%`;
  }).join(', ');

  const legend = p.segments.map(s => `
    <div class="donut-legend-row">
      <span class="lg"><i class="dot-legend tone-${s.tone}"></i>${s.label}</span>
      <span><b>${s.count}</b> <em>(${s.pct}%)</em></span>
    </div>
  `).join('');

  el.innerHTML = `
    <div class="portfolio-block">
      <div class="portfolio-donut" style="background:conic-gradient(${stops});">
        <div class="portfolio-donut-hole">
          <span class="portfolio-donut-total">${p.total}</span>
          <span class="portfolio-donut-label">bien${p.total > 1 ? 's' : ''}</span>
        </div>
      </div>
      <div class="portfolio-legend">${legend}</div>
    </div>
  `;
}

/* ---------------- Render: Contrats à surveiller ---------------- */
function renderContractsToWatch(){
  const list = document.getElementById('contractsWatchList');
  const items = DATA.contractsToWatch;
  if(!items.length){
    list.innerHTML = `
      <div class="empty-state empty-state-compact">
        <div class="empty-state-icon sm">${ICONS.calendar}</div>
        <p>Aucun contrat n'arrive à échéance dans les 30 prochains jours.</p>
      </div>
    `;
    return;
  }
  list.innerHTML = items.map(c => `
    <div class="watch-item">
      <div class="watch-icon ${c.tone}">${ICONS.calendar}</div>
      <div class="watch-main">
        <div class="watch-title">${c.bien} <span class="watch-dot">·</span> ${c.locataire}</div>
        <div class="watch-sub">Contrat ${c.numero} — échéance le ${c.date_fin}</div>
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
        <div class="watch-main">
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
      <div class="watch-main">
        <div class="watch-title">${t.title}</div>
        <div class="watch-sub">${t.sub}</div>
      </div>
      ${t.count ? `<div class="watch-days ${t.tone}">${t.count}</div>` : ''}
    </div>
  `).join('');
}

/* ---------------- Render: Activity ---------------- */
/* Les connexions/déconnexions (icône "shield") sont du bruit répétitif : on les masque pour ne
   garder que les événements métier (biens, locataires, contrats, paiements, interventions...). */
function renderActivity(){
  const list = document.getElementById('activityList');
  const items = (DATA.activity || []).filter(a => a.icon !== 'shield');
  if(!items.length){
    list.innerHTML = `
      <div class="empty-state empty-state-compact">
        <div class="empty-state-icon sm">${ICONS.inbox}</div>
        <p>Aucune activité métier récente.</p>
      </div>
    `;
    return;
  }
  list.innerHTML = items.map(a => `
    <div class="activity-item">
      <div class="activity-icon ${a.tone}">${ICONS[a.icon]}</div>
      <div class="activity-main">
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
      <div class="quick-action-arrow">${ICONS.arrow}</div>
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
