
/* =========================================================
   DATA_MOCK — Données d'exemple.
   À l'intégration réelle : remplacer chaque bloc par un appel
   API vers vos tables (utilisateur, agence, bien, abonnement,
   agence_administrateur, journal_audit...).
   ========================================================= */

const DATA_MOCK = {
  kpis: [
    { label:"Utilisateurs", value:"1 248", change:"+11.4%", up:true, sub:"vs période précédente", icon:"users", tone:"teal" },
    { label:"Agences", value:"86", change:"+6.1%", up:true, sub:"vs période précédente", icon:"building", tone:"dark" },
    { label:"Biens", value:"3 204", change:"+1.7%", up:true, sub:"vs période précédente", icon:"home", tone:"amber" },
    { label:"Abonnements actifs", value:"74", change:"+2.3%", up:true, sub:"vs période précédente", icon:"card", tone:"violet" }
  ],

  evolution: {
    "7j":  { labels:["J-6","J-5","J-4","J-3","J-2","J-1","J"], users:[860,900,930,890,950,1020,1080], agences:[60,62,63,61,65,68,70], biens:[2600,2650,2700,2680,2720,2780,2820] },
    "30j": {
      labels:["1","3","5","7","9","11","13","15","17","19","21","23","25","27","29"],
      users:[720,760,800,840,900,980,1040,1080,1040,980,940,1000,1080,1150,1248],
      agences:[48,50,53,55,58,62,66,70,68,64,60,66,72,78,86],
      biens:[2100,2180,2260,2340,2420,2520,2620,2700,2640,2560,2500,2620,2820,3000,3204]
    },
    "3m":  { labels:["S1","S2","S3","S4","S5","S6","S7","S8","S9","S10","S11","S12"], users:[520,600,680,760,820,900,980,1040,1000,1080,1180,1248], agences:[30,36,40,45,50,55,60,65,62,70,78,86], biens:[1500,1650,1800,1950,2100,2280,2420,2560,2480,2700,2960,3204] },
    "1a":  { labels:["Jan","Fév","Mar","Avr","Mai","Jun","Jul","Aoû","Sep","Oct","Nov","Déc"], users:[300,360,420,480,540,620,700,780,860,980,1120,1248], agences:[15,18,22,26,30,35,40,46,52,60,72,86], biens:[900,1050,1200,1350,1500,1700,1900,2100,2350,2650,2950,3204] }
  },

  plans: [
    { label:"Gratuit", value:24, pct:"28%", color:"#B7C6C6" },
    { label:"Pro",      value:38, pct:"44%", color:"#0F8B8D" },
    { label:"Max",      value:24, pct:"28%", color:"#075E63" }
  ],

  activity: [
    { icon:"plus", tone:"teal",  title:"Nouvelle agence créée", sub:"Horizon Immo · a rejoint la plateforme", time:"Il y a 12 min" },
    { icon:"user", tone:"blue",  title:"Nouvel utilisateur inscrit", sub:"S. Nguyen · via Toutimmobilier.fr", time:"Il y a 47 min" },
    { icon:"check",tone:"green", title:"Abonnement activé", sub:"Atlantide Gestion est passé au Pro Max", time:"Il y a 1h" },
    { icon:"edit", tone:"amber", title:"Abonnement modifié", sub:"Cap Immobilier · passage du Pro à Max", time:"Il y a 2h" },
    { icon:"x",    tone:"red",   title:"Compte désactivé", sub:"a.morel@agence-test.fr par un administrateur", time:"Hier · 18:32" },
    { icon:"shield",tone:"gray", title:"Action administrative", sub:"Mise à jour des permissions du rôle Comptable", time:"Hier · 14:05" }
  ],

  watch: [
    { icon:"⏳", tone:"amber", title:"5 agences en attente d'activation", sub:"Inscrites depuis plus de 48h" },
    { icon:"⛔", tone:"red",   title:"3 comptes récemment désactivés", sub:"Sans réactivation depuis 7 jours" },
    { icon:"⏰", tone:"blue",  title:"7 abonnements arrivent à expiration", sub:"D'ici 7 jours" },
    { icon:"✓",  tone:"green", title:"Aucune alerte système en cours", sub:"Dernière vérification : il y a 3 min" }
  ],

  agencies: [
    { name:"Horizon Immo",     type:"Agence PropTech", admin:"Claire Dubois",  plan:"Pro",     date:"21/09/2026", status:"attente"  },
    { name:"Atlantide Gestion",type:"Agence PropTech", admin:"Marc Leflem",    plan:"Pro",     date:"20/09/2026", status:"actif"    },
    { name:"Cap Immobilier",   type:"Agence PropTech", admin:"Nadia Bensaid",  plan:"Max",     date:"17/09/2026", status:"actif"    },
    { name:"Foncière du Sud",  type:"Agence PropTech", admin:"Julien Roy",     plan:"Gratuit", date:"15/09/2026", status:"suspendu" },
    { name:"Toit Bleu Agence", type:"Agence PropTech", admin:"Sophie Marchand",plan:"Pro",     date:"12/09/2026", status:"actif"    },
    { name:"Néréide Habitat",  type:"Agence PropTech", admin:"Karim Haddad",   plan:"Max",     date:"10/09/2026", status:"actif"    },
    { name:"Alizés Patrimoine",type:"Agence PropTech", admin:"Emma Petit",     plan:"Pro",     date:"08/09/2026", status:"actif"    },
    { name:"Litoral Conseil",  type:"Agence PropTech", admin:"Hugo Simon",     plan:"Gratuit", date:"05/09/2026", status:"attente"  },
    { name:"Racine Immobilier",type:"Agence PropTech", admin:"Léa Fontaine",   plan:"Max",     date:"03/09/2026", status:"actif"    },
    { name:"Ancrage Gestion",  type:"Agence PropTech", admin:"Thomas Girard",  plan:"Pro",     date:"01/09/2026", status:"suspendu" },
    { name:"Méridien Biens",   type:"Agence PropTech", admin:"Camille Roux",   plan:"Pro",     date:"29/08/2026", status:"actif"    },
    { name:"Boréal Habitat",   type:"Agence PropTech", admin:"Antoine Faure",  plan:"Gratuit", date:"27/08/2026", status:"attente"  },
  ],
  totalAgencies: 32
};

/* ---------------- Icons ---------------- */
const ICONS = {
  users:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
  building:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 9h1M14 9h1M9 13h1M14 13h1M9 17h1M14 17h1"/></svg>',
  home:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>',
  card:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>',
  plus:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
  user:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/></svg>',
  check:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
  edit:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>',
  x:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
  shield:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 4 5v6c0 5 3.4 8.8 8 11 4.6-2.2 8-6 8-11V5l-8-3Z"/></svg>',
  arrow:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 15 12 9 18"/></svg>'
};

/* ---------------- Render: KPIs ---------------- */
function renderKpis(){
  const grid = document.getElementById('kpiGrid');
  grid.innerHTML = DATA_MOCK.kpis.map(k => `
    <div class="kpi-card">
      <div class="kpi-top">
        <div class="kpi-icon ${k.tone}">${ICONS[k.icon]}</div>
        <div class="kpi-change ${k.up?'up':'down'}">${k.change}</div>
      </div>
      <div class="kpi-value">${k.value}</div>
      <div class="kpi-label">${k.label}</div>
      <div class="kpi-sub">${k.sub}</div>
    </div>
  `).join('');
}

/* ---------------- Render: Activity ---------------- */
function renderActivity(){
  const list = document.getElementById('activityList');
  list.innerHTML = DATA_MOCK.activity.map(a => `
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

/* ---------------- Render: Watch ---------------- */
function renderWatch(){
  const list = document.getElementById('watchList');
  list.innerHTML = DATA_MOCK.watch.map(w => `
    <div class="watch-item">
      <div class="watch-icon ${w.tone}">${w.icon}</div>
      <div>
        <div class="watch-title">${w.title}</div>
        <div class="watch-sub">${w.sub}</div>
      </div>
      <div class="watch-chev">${ICONS.arrow}</div>
    </div>
  `).join('');
}

/* ---------------- Render: Donut legend ---------------- */
function renderDonutLegend(){
  const el = document.getElementById('donutLegend');
  el.innerHTML = DATA_MOCK.plans.map(p => `
    <div class="donut-legend-row">
      <span class="lg"><i class="dot-legend" style="background:${p.color}"></i>${p.label}</span>
      <span><b>${p.value}</b> (${p.pct})</span>
    </div>
  `).join('');
}

/* ---------------- Table ---------------- */
let currentPage = 1;
const pageSize = 6;
let statusFilterVal = "all";
let searchVal = "";

function filteredAgencies(){
  return DATA_MOCK.agencies.filter(a=>{
    const okStatus = statusFilterVal==="all" || a.status===statusFilterVal;
    const okSearch = a.name.toLowerCase().includes(searchVal.toLowerCase()) || a.admin.toLowerCase().includes(searchVal.toLowerCase());
    return okStatus && okSearch;
  });
}

function initials(name){
  return name.split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
}

const STATUS_LABEL = { actif:"Actif", attente:"En attente", suspendu:"Suspendu" };

function renderTable(){
  const data = filteredAgencies();
  const totalPages = Math.max(1, Math.ceil(data.length/pageSize));
  if(currentPage>totalPages) currentPage = totalPages;
  const start = (currentPage-1)*pageSize;
  const pageData = data.slice(start, start+pageSize);

  const body = document.getElementById('tableBody');
  body.innerHTML = pageData.map(a => `
    <tr>
      <td>
        <div class="agency-cell">
          <div class="agency-avatar">${initials(a.name)}</div>
          <div>
            <div class="agency-name">${a.name}</div>
            <div class="agency-type">${a.type}</div>
          </div>
        </div>
      </td>
      <td>${a.admin}</td>
      <td><span class="plan-badge ${a.plan.toLowerCase()}">${a.plan}</span></td>
      <td>${a.date}</td>
      <td><span class="status-badge ${a.status}">${STATUS_LABEL[a.status]}</span></td>
      <td><span class="consult-link">Consulter ${ICONS.arrow}</span></td>
    </tr>
  `).join('') || `<tr><td colspan="6" style="text-align:center;color:#8AA0A0;padding:26px;">Aucune agence ne correspond à votre recherche.</td></tr>`;

  const shownFrom = data.length ? start+1 : 0;
  const shownTo = Math.min(start+pageSize, data.length);
  document.getElementById('footerCount').textContent = `Affichage ${shownFrom}-${shownTo} sur ${DATA_MOCK.totalAgencies} agences`;

  const pag = document.getElementById('pagination');
  let html = `<button class="page-btn" id="prevPage" ${currentPage===1?'disabled':''}>‹</button>`;
  for(let i=1;i<=totalPages;i++){
    html += `<button class="page-btn ${i===currentPage?'active':''}" data-page="${i}">${i}</button>`;
  }
  html += `<button class="page-btn" id="nextPage" ${currentPage===totalPages?'disabled':''}>›</button>`;
  pag.innerHTML = html;

  pag.querySelectorAll('[data-page]').forEach(btn=>{
    btn.addEventListener('click', ()=>{ currentPage = parseInt(btn.dataset.page); renderTable(); });
  });
  const prev = document.getElementById('prevPage');
  const next = document.getElementById('nextPage');
  if(prev) prev.addEventListener('click', ()=>{ if(currentPage>1){currentPage--; renderTable();} });
  if(next) next.addEventListener('click', ()=>{ if(currentPage<totalPages){currentPage++; renderTable();} });
}

/* ---------------- Charts ---------------- */
let evolutionChart, donutChart;

function buildEvolutionChart(range){
  const d = DATA_MOCK.evolution[range];
  const ctx = document.getElementById('evolutionChart').getContext('2d');

  const gradUsers = ctx.createLinearGradient(0,0,0,230);
  gradUsers.addColorStop(0,'rgba(15,139,141,0.22)');
  gradUsers.addColorStop(1,'rgba(15,139,141,0)');

  if(evolutionChart) evolutionChart.destroy();
  evolutionChart = new Chart(ctx, {
    type:'line',
    data:{
      labels:d.labels,
      datasets:[
        { label:'Utilisateurs', data:d.users, borderColor:'#0F8B8D', backgroundColor:gradUsers, fill:true, tension:.4, pointRadius:0, borderWidth:2.4 },
        { label:'Agences', data:d.agences, borderColor:'#075E63', backgroundColor:'transparent', fill:false, tension:.4, pointRadius:0, borderWidth:2 },
        { label:'Biens', data:d.biens, borderColor:'#F59E0B', backgroundColor:'transparent', fill:false, tension:.4, pointRadius:0, borderWidth:2, yAxisID:'y1' }
      ]
    },
    options:{
      responsive:true,
      maintainAspectRatio:false,
      interaction:{ mode:'index', intersect:false },
      plugins:{
        legend:{ display:false },
        tooltip:{
          backgroundColor:'#043F42', titleColor:'#fff', bodyColor:'#DDEDEC',
          borderColor:'#0F8B8D', borderWidth:1, padding:10, cornerRadius:8,
          titleFont:{size:11}, bodyFont:{size:11.5}
        }
      },
      scales:{
        x:{ grid:{ display:false }, ticks:{ color:'#8AA0A0', font:{size:10.5}, maxRotation:0 } },
        y:{ position:'left', grid:{ color:'#EEF3F3' }, ticks:{ color:'#8AA0A0', font:{size:10.5} } },
        y1:{ position:'right', display:false }
      }
    }
  });
}

function buildDonutChart(){
  const ctx = document.getElementById('donutChart').getContext('2d');
  donutChart = new Chart(ctx, {
    type:'doughnut',
    data:{
      labels: DATA_MOCK.plans.map(p=>p.label),
      datasets:[{
        data: DATA_MOCK.plans.map(p=>p.value),
        backgroundColor: DATA_MOCK.plans.map(p=>p.color),
        borderColor:'#ffffff',
        borderWidth:3,
        hoverOffset:4
      }]
    },
    options:{
      responsive:true,
      maintainAspectRatio:false,
      cutout:'72%',
      plugins:{
        legend:{ display:false },
        tooltip:{
          backgroundColor:'#043F42', titleColor:'#fff', bodyColor:'#DDEDEC',
          padding:9, cornerRadius:8, bodyFont:{size:11.5}
        }
      }
    }
  });
}

/* ---------------- Interactions ---------------- */
document.getElementById('bannerClose').addEventListener('click', ()=>{
  document.getElementById('banner').style.display='none';
});

document.getElementById('rangeTabs').addEventListener('click', (e)=>{
  const tab = e.target.closest('.range-tab');
  if(!tab) return;
  document.querySelectorAll('.range-tab').forEach(t=>t.classList.remove('active'));
  tab.classList.add('active');
  buildEvolutionChart(tab.dataset.range);
});

document.getElementById('tableSearch').addEventListener('input', (e)=>{
  searchVal = e.target.value; currentPage = 1; renderTable();
});

const statusOptions = [
  {v:"all", label:"Tous les statuts"},
  {v:"actif", label:"Actif"},
  {v:"attente", label:"En attente"},
  {v:"suspendu", label:"Suspendu"}
];
let statusIdx = 0;
document.getElementById('statusFilter').addEventListener('click', ()=>{
  statusIdx = (statusIdx+1) % statusOptions.length;
  statusFilterVal = statusOptions[statusIdx].v;
  document.getElementById('statusFilterLabel').textContent = statusOptions[statusIdx].label;
  currentPage = 1;
  renderTable();
});

/* Sidebar nav active state; connect routing here when backend pages are ready. */
document.querySelectorAll('.nav-link[data-page]').forEach(link=>{
  link.addEventListener('click', ()=>{
    document.querySelectorAll('.nav-link').forEach(l=>l.classList.remove('active'));
    link.classList.add('active');
    if(window.innerWidth<=900){ closeSidebar(); }
  });
});

document.getElementById('logoutLink').addEventListener('click', ()=>{
  window.location.href = '../../authentification/deconnexion.php';
});

/* Mobile sidebar */
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
function openSidebar(){ sidebar.classList.add('open'); overlay.classList.add('show'); }
function closeSidebar(){ sidebar.classList.remove('open'); overlay.classList.remove('show'); }
document.getElementById('menuToggle').addEventListener('click', openSidebar);
overlay.addEventListener('click', closeSidebar);

/* ---------------- Init ---------------- */
renderKpis();
renderActivity();
renderWatch();
renderDonutLegend();
renderTable();
buildEvolutionChart('30j');
buildDonutChart();

