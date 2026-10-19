
/* =========================================================
   Statistiques plateforme — données chargées depuis data.php,
   voir StatistiqueModel.php pour les requêtes réelles.
   ========================================================= */

let STATS = null;

async function loadStatistiques(){
  const res = await fetch('data.php', { credentials: 'same-origin' });
  if(!res.ok) throw new Error('Échec du chargement des statistiques (HTTP ' + res.status + ')');
  return res.json();
}

function formatEuros(n){
  return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 }).format(n) + ' €';
}

const ICONS = {
  card:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2.5"/><path d="M2 10h20"/></svg>',
  trending:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>',
  home:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9.5 12 3l9 6.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>',
  users:'<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'
};

function renderKpis(){
  const k = STATS.kpis;
  const grid = document.getElementById('kpiGrid');
  const growthUp = k.userGrowth >= 0;
  grid.innerHTML = `
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon green">${ICONS.card}</div></div>
      <div class="kpi-value">${formatEuros(k.revenueTotal)}</div>
      <div class="kpi-label">Revenu total encaissé</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon blue">${ICONS.trending}</div></div>
      <div class="kpi-value">${formatEuros(k.mrr)}</div>
      <div class="kpi-label">Revenu mensuel récurrent (MRR)</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top"><div class="kpi-icon teal">${ICONS.home}</div></div>
      <div class="kpi-value">${k.occupancyRate}%</div>
      <div class="kpi-label">Taux d'occupation du parc</div>
      <div class="kpi-sub">${k.totalBiens} biens au total</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-top">
        <div class="kpi-icon amber">${ICONS.users}</div>
        <div class="kpi-change ${growthUp ? 'up' : 'down'}">${growthUp ? '+' : ''}${k.userGrowth}%</div>
      </div>
      <div class="kpi-value">${k.usersNow30j}</div>
      <div class="kpi-label">Nouveaux utilisateurs (30j)</div>
    </div>
  `;
}

function renderTopAgences(){
  const body = document.getElementById('topAgencesBody');
  body.innerHTML = STATS.topAgences.map(a => `
    <tr>
      <td class="cell-title">${a.nom}</td>
      <td class="cell-sub">${a.ville ?? '—'}</td>
      <td class="cell-sub">${a.plan_nom ?? 'Aucun'}</td>
      <td class="cell-sub">${a.nb_biens}</td>
      <td class="cell-sub">${a.nb_utilisateurs}</td>
      <td class="cell-sub">${formatEuros(Number(a.revenu))}</td>
      <td style="text-align:right;">
        <a class="consult-link" href="../agences/detail.php?id=${a.id_agence}" data-modal>Consulter</a>
      </td>
    </tr>
  `).join('') || '<tr class="empty-row"><td colspan="7">Aucune agence.</td></tr>';
}

let revenueChart, growthChart, geoChart;

function buildRevenueChart(){
  const ctx = document.getElementById('revenueChart').getContext('2d');
  const grad = ctx.createLinearGradient(0, 0, 0, 230);
  grad.addColorStop(0, 'rgba(15,139,141,0.22)');
  grad.addColorStop(1, 'rgba(15,139,141,0)');

  revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: STATS.revenue.labels,
      datasets: [{ label: 'Revenu (€)', data: STATS.revenue.values, borderColor: '#0F8B8D', backgroundColor: grad, fill: true, tension: .4, pointRadius: 0, borderWidth: 2.4 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { backgroundColor: '#043F42', titleColor: '#fff', bodyColor: '#DDEDEC', padding: 10, cornerRadius: 8 } },
      scales: { x: { grid: { display: false }, ticks: { color: '#8AA0A0', font: { size: 10.5 } } }, y: { grid: { color: '#EEF3F3' }, ticks: { color: '#8AA0A0', font: { size: 10.5 } } } }
    }
  });
}

function buildGrowthChart(){
  const ctx = document.getElementById('growthChart').getContext('2d');
  growthChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: STATS.userGrowth.labels,
      datasets: [{ label: 'Nouveaux utilisateurs', data: STATS.userGrowth.values, backgroundColor: '#F59E0B', borderRadius: 4, maxBarThickness: 28 }]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { backgroundColor: '#043F42', titleColor: '#fff', bodyColor: '#DDEDEC', padding: 10, cornerRadius: 8 } },
      scales: { x: { grid: { display: false }, ticks: { color: '#8AA0A0', font: { size: 10.5 } } }, y: { grid: { color: '#EEF3F3' }, ticks: { color: '#8AA0A0', font: { size: 10.5 }, precision: 0 } } }
    }
  });
}

function buildGeoChart(){
  const ctx = document.getElementById('geoChart').getContext('2d');
  geoChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: STATS.geographic.map(g => g.ville),
      datasets: [{ label: 'Biens', data: STATS.geographic.map(g => Number(g.nb_biens)), backgroundColor: '#075E63', borderRadius: 4, maxBarThickness: 32 }]
    },
    options: {
      indexAxis: 'y',
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { backgroundColor: '#043F42', titleColor: '#fff', bodyColor: '#DDEDEC', padding: 10, cornerRadius: 8 } },
      scales: { x: { grid: { color: '#EEF3F3' }, ticks: { color: '#8AA0A0', font: { size: 10.5 }, precision: 0 } }, y: { grid: { display: false }, ticks: { color: '#8AA0A0', font: { size: 10.5 } } } }
    }
  });
}

async function init(){
  try {
    STATS = await loadStatistiques();
  } catch (err) {
    console.error(err);
    document.getElementById('kpiGrid').innerHTML =
      '<p style="color:#DC2626;font-size:13px;">Impossible de charger les statistiques. Réessayez plus tard.</p>';
    return;
  }
  renderKpis();
  renderTopAgences();
  buildRevenueChart();
  buildGrowthChart();
  buildGeoChart();
}
init();
