
/* =========================================================
   Données du dashboard — chargées depuis data.php (JSON),
   voir DashboardModel.php pour les requêtes réelles.
   ========================================================= */

let DATA_MOCK = null;

async function loadDashboardData(){
  const res = await fetch('data.php', { credentials: 'same-origin' });
  if(!res.ok) throw new Error('Échec du chargement des données (HTTP ' + res.status + ')');
  return res.json();
}

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
  document.getElementById('footerCount').textContent = `Affichage ${shownFrom}-${shownTo} sur ${data.length} agences`;

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
        { label:'Agences', data:d.agences, borderColor:'#075E63', backgroundColor:'transparent', fill:false, tension:.4, pointRadius:0, borderWidth:2, yAxisID:'y2' },
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
        y1:{ position:'right', display:false },
        y2:{ position:'right', display:false }
      }
    }
  });
}

function buildDonutChart(){
  const total = DATA_MOCK.plans.reduce((sum,p)=>sum+p.value, 0);
  document.getElementById('donutTotal').textContent = total;

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
async function init(){
  try {
    DATA_MOCK = await loadDashboardData();
  } catch (err) {
    console.error(err);
    document.getElementById('kpiGrid').innerHTML =
      '<p style="color:#DC2626;font-size:13px;">Impossible de charger les données du dashboard. Réessayez plus tard.</p>';
    return;
  }
  renderKpis();
  renderActivity();
  renderWatch();
  renderDonutLegend();
  renderTable();
  buildEvolutionChart('30j');
  buildDonutChart();
}
init();

