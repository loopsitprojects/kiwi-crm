@extends('layouts.app')

@section('title', 'Loops — Team Financial Performance Dashboard')
@section('header', 'Financial Performance')

@push('head')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<style>
.fp-dashboard-wrapper {
  --bg:#0b1220; --panel:#111a2e; --panel-2:#172238;
    --ink:#e6ecf5; --muted:#8aa0bf; --line:#1f2c47;
    --accent:#5b8def; --good:#23c285; --bad:#ef4d6b; --warn:#f4b740;
    --corp:#a78bfa; --dm:#5b8def; --crea:#f97373; --it:#23c285;
    --pipe:#a78bfa;
}
.fp-dashboard-wrapper {
  box-sizing:border-box
}
.fp-dashboard-wrapper, .fp-dashboard-wrapper {
  margin:0;padding:0;background:var(--bg);color:var(--ink);font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Inter,Arial,sans-serif;-webkit-font-smoothing:antialiased
}
.fp-dashboard-wrapper {
  padding:24px 28px 60px
}
.fp-dashboard-wrapper header {
  display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:12px;margin-bottom:18px;border-bottom:1px solid var(--line);padding-bottom:14px
}
.fp-dashboard-wrapper header h1 {
  font-size:22px;margin:0;font-weight:600;letter-spacing:0.2px
}
.fp-dashboard-wrapper header .sub {
  color:var(--muted);font-size:13px;margin-top:4px
}
.fp-dashboard-wrapper header .pill {
  display:inline-block;background:var(--panel);border:1px solid var(--line);color:var(--muted);padding:5px 10px;border-radius:999px;font-size:11px;margin-left:6px
}
.fp-dashboard-wrapper .tabs {
  display:flex;gap:6px;flex-wrap:wrap;margin:16px 0 22px
}
.fp-dashboard-wrapper .tab {
  background:var(--panel);border:1px solid var(--line);color:var(--muted);padding:8px 14px;border-radius:8px;cursor:pointer;font-size:13px;transition:all .15s
}
.fp-dashboard-wrapper .tab:hover {
  color:var(--ink)
}
.fp-dashboard-wrapper .tab.active {
  background:var(--accent);color:white;border-color:var(--accent)
}
.fp-dashboard-wrapper .grid {
  display:grid;gap:14px
}
.fp-dashboard-wrapper .grid.kpi {
  grid-template-columns:repeat(auto-fit,minmax(180px,1fr))
}
.fp-dashboard-wrapper .grid.cols-2 {
  grid-template-columns:repeat(auto-fit,minmax(420px,1fr))
}
.fp-dashboard-wrapper .grid.cols-3 {
  grid-template-columns:repeat(auto-fit,minmax(320px,1fr))
}
.fp-dashboard-wrapper .card {
  background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:16px 18px
}
.fp-dashboard-wrapper .card h3 {
  margin:0 0 12px;font-size:13px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:0.6px
}
.fp-dashboard-wrapper .kpi-card .label {
  font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:0.8px
}
.fp-dashboard-wrapper .kpi-card .val {
  font-size:24px;font-weight:600;margin:6px 0 2px;letter-spacing:0.2px
}
.fp-dashboard-wrapper .kpi-card .delta {
  font-size:12px;color:var(--muted)
}
.fp-dashboard-wrapper .kpi-card .delta.up {
  color:var(--good)
}
.fp-dashboard-wrapper .kpi-card .delta.down {
  color:var(--bad)
}
.fp-dashboard-wrapper .kpi-card .delta.warn {
  color:var(--warn)
}
.fp-dashboard-wrapper .section-title {
  font-size:16px;font-weight:600;margin:28px 0 10px;padding-bottom:8px;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:8px;flex-wrap:wrap
}
.fp-dashboard-wrapper .section-title .dot {
  width:8px;height:8px;border-radius:50%;background:var(--accent)
}
.fp-dashboard-wrapper .chart-wrap {
  position:relative;height:280px
}
.fp-dashboard-wrapper .chart-wrap.tall {
  height:340px
}
.fp-dashboard-wrapper table {
  width:100%;border-collapse:collapse;font-size:13px
}
.fp-dashboard-wrapper th, .fp-dashboard-wrapper td {
  padding:8px 10px;text-align:left;border-bottom:1px solid var(--line)
}
.fp-dashboard-wrapper th {
  color:var(--muted);font-weight:500;font-size:11px;text-transform:uppercase;letter-spacing:0.5px
}
.fp-dashboard-wrapper td.num, .fp-dashboard-wrapper th.num {
  text-align:right;font-variant-numeric:tabular-nums
}
.fp-dashboard-wrapper tr:last-child td {
  border-bottom:none
}
.fp-dashboard-wrapper tr:hover td {
  background:rgba(91,141,239,0.05)
}
.fp-dashboard-wrapper .badge {
  display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:500
}
.fp-dashboard-wrapper .badge.corp {
  background:rgba(167,139,250,0.18);color:#c4b0ff
}
.fp-dashboard-wrapper .badge.dm {
  background:rgba(91,141,239,0.18);color:#9ec0ff
}
.fp-dashboard-wrapper .badge.crea {
  background:rgba(249,115,115,0.18);color:#fca8a8
}
.fp-dashboard-wrapper .badge.it {
  background:rgba(35,194,133,0.18);color:#7fe1bd
}
.fp-dashboard-wrapper .badge.pipe-ft {
  background:rgba(244,183,64,0.18);color:#ffd687
}
.fp-dashboard-wrapper .badge.pipe-oh {
  background:rgba(239,77,107,0.18);color:#ff9caf
}
.fp-dashboard-wrapper .panel-section {
  display:none
}
.fp-dashboard-wrapper .panel-section.active {
  display:block
}
.fp-dashboard-wrapper .row-bar {
  display:flex;align-items:center;gap:10px
}
.fp-dashboard-wrapper .row-bar .barwrap {
  flex:1;height:14px;background:var(--line);border-radius:3px;overflow:hidden;position:relative;min-width:60px
}
.fp-dashboard-wrapper .row-bar .barwrap > div {
  height:100%
}
.fp-dashboard-wrapper .row-bar .amt {
  flex:0 0 60px;text-align:right;font-size:12px;color:var(--muted);font-variant-numeric:tabular-nums
}
.fp-dashboard-wrapper footer {
  margin-top:34px;color:var(--muted);font-size:12px;text-align:center;border-top:1px solid var(--line);padding-top:14px
}
.fp-dashboard-wrapper .note {
  font-size:11px;color:var(--muted);margin-top:6px;font-style:italic
}
.modal-overlay {
  position:fixed;inset:0;background:rgba(0,0,0,0.75);display:none;align-items:center;justify-content:center;z-index:1000;padding:20px;backdrop-filter:blur(2px)
}
.modal-overlay.active {
  display:flex
}
.fp-dashboard-wrapper .modal-card {
  background:var(--panel);border:1px solid var(--line);border-radius:12px;max-width:1100px;width:100%;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,0.5)
}
.fp-dashboard-wrapper .modal-header {
  display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--line)
}
.fp-dashboard-wrapper .modal-header h3 {
  margin:0;font-size:16px;font-weight:600;color:var(--ink)
}
.fp-dashboard-wrapper .modal-close {
  background:transparent;border:none;color:var(--muted);font-size:24px;cursor:pointer;padding:0 8px;line-height:1
}
.fp-dashboard-wrapper .modal-close:hover {
  color:var(--ink)
}
.fp-dashboard-wrapper .modal-body {
  padding:6px 20px 14px;overflow-y:auto;flex:1
}
.fp-dashboard-wrapper .modal-footer {
  padding:12px 20px;border-top:1px solid var(--line);color:var(--muted);font-size:12px;display:flex;justify-content:space-between;gap:12px
}
.fp-dashboard-wrapper .chart-wrap canvas {
  cursor:pointer
}
.fp-dashboard-wrapper .filter-row {
  display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:10px
}
.fp-dashboard-wrapper .filter-row h3 {
  margin:0
}
.fp-dashboard-wrapper .filter-select {
  background:var(--panel-2);border:1px solid var(--line);color:var(--ink);padding:6px 10px;border-radius:6px;font-size:12px;cursor:pointer;font-family:inherit
}
.fp-dashboard-wrapper .filter-select:hover {
  border-color:var(--accent)
}
.fp-dashboard-wrapper .filter-select:focus {
  outline:none;border-color:var(--accent)
}
.fp-dashboard-wrapper .filter-bar {
  display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:12px 14px;background:var(--panel);border:1px solid var(--line);border-radius:10px;margin-bottom:16px
}
.fp-dashboard-wrapper .filter-bar .filter-label {
  font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:0.8px;font-weight:600;margin-right:4px
}
.fp-dashboard-wrapper .filter-bar .filter-btn {
  background:var(--panel-2);border:1px solid var(--line);color:var(--muted);padding:6px 12px;border-radius:6px;cursor:pointer;font-size:12px;transition:all .15s;font-family:inherit
}
.fp-dashboard-wrapper .filter-bar .filter-btn:hover {
  color:var(--ink);border-color:var(--accent)
}
.fp-dashboard-wrapper .filter-bar .filter-btn.active {
  background:var(--accent);color:white;border-color:var(--accent)
}
.fp-dashboard-wrapper .filter-bar .filter-scope {
  font-size:11px;color:var(--muted);margin-left:auto;font-style:italic
}

.fp-dashboard-wrapper {
  background: var(--bg);
  color: var(--ink);
  border-radius: 14px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.35);
  position: relative;
  transition: all 0.3s ease;
  min-height: calc(100vh - 120px);
}
.fp-dashboard-wrapper.is-fullscreen {
  position: fixed !important;
  inset: 0 !important;
  z-index: 99999 !important;
  border-radius: 0 !important;
  overflow-y: auto !important;
  height: 100vh !important;
  width: 100vw !important;
  margin: 0 !important;
}
.fp-top-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 18px;
  background: var(--panel-2);
  border: 1px solid var(--line);
  border-radius: 10px;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 12px;
}
.fp-top-actions .status-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: var(--muted);
  font-weight: 500;
}
.fp-top-actions .status-dot {
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background: var(--good);
  box-shadow: 0 0 10px var(--good);
  animation: pulse 2s infinite;
}
@keyframes pulse {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(35, 194, 133, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(35, 194, 133, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(35, 194, 133, 0); }
}
.fp-action-btn {
  background: var(--panel);
  border: 1px solid var(--line);
  color: var(--ink);
  padding: 7px 14px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.2s;
  text-decoration: none;
}
.fp-action-btn:hover {
  background: var(--line);
  color: #fff;
  border-color: var(--accent);
}
.fp-action-btn.primary {
  background: var(--accent);
  border-color: var(--accent);
  color: #fff;
}
.fp-action-btn.primary:hover {
  filter: brightness(1.1);
}

</style>
@endpush

@section('content')
<div class="fp-dashboard-wrapper" id="fpDashboardWrapper">
  <!-- Executive Control Toolbar -->
  <div class="fp-top-actions">
    <div class="status-badge">
      <span class="status-dot"></span>
      <span><strong>Live CRM Connected</strong> &bull; Executive Financial Performance &bull; Authorized Leadership Access</span>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <button type="button" class="fp-action-btn" id="btnRefreshLive" onclick="refreshLiveDashboard()">
        <i class="fas fa-sync-alt" id="refreshIcon"></i>
        <span>Sync Live Data</span>
      </button>
      <button type="button" class="fp-action-btn" id="btnFullscreen" onclick="toggleFullscreenMode()">
        <i class="fas fa-expand" id="fullscreenIcon"></i>
        <span id="fullscreenText">Focus / Fullscreen Mode</span>
      </button>
      <a href="{{ route('dashboard') }}" class="fp-action-btn">
        <i class="fas fa-arrow-left"></i>
        <span>Back to CRM</span>
      </a>
    </div>
  </div>

<header>
  <div>
    <h1>Loops — Team Performance Dashboard</h1>
    <div class="sub">Live CRM Real-Time Financial Performance &bull; Real Deals &amp; Revenue from Kiwi CRM
      <span class="pill">Live CRM Data &bull; Revenue, Contribution &amp; Sales activity</span>
    </div>
  </div>
  <div style="text-align:right">
    <div class="sub">Group Annual Target</div>
    <div style="font-size:18px;font-weight:600">LKR {{ number_format($dashboardData['group']['target_annual'] ?? 190000000) }}</div>
  </div>
</header>

<div class="grid kpi" id="groupKpis"></div>

<div class="tabs" id="tabs">
  <div class="tab active" data-tab="group">Group</div>
  <div class="tab" data-tab="Corporate">Corporate</div>
  <div class="tab" data-tab="DM">Digital (DM)</div>
  <div class="tab" data-tab="Creative">Creative</div>
  <div class="tab" data-tab="IT">IT</div>
  <div class="tab" data-tab="sales">Sales Units</div>
  <div class="tab" data-tab="pipeline">Pipeline</div>
</div>

<div class="filter-bar" id="periodFilter">
  <span class="filter-label">Period</span>
  <button class="filter-btn active" data-months="April,May,June,July,August,September">All YTD (Apr–Sep)</button>
  <button class="filter-btn" data-months="April,May,June">Q1 (Apr–Jun)</button>
  <button class="filter-btn" data-months="July,August,September">Q2 (Jul–Sep)</button>
  <button class="filter-btn" data-months="October,November,December">Q3 (Oct–Dec)</button>
  <button class="filter-btn" data-months="January,February,March">Q4 (Jan–Mar)</button>
  <span style="width:1px;height:20px;background:var(--line);margin:0 6px"></span>
  <button class="filter-btn" data-months="April">Apr</button>
  <button class="filter-btn" data-months="May">May</button>
  <button class="filter-btn" data-months="June">Jun</button>
  <button class="filter-btn" data-months="July">Jul</button>
  <button class="filter-btn" data-months="August">Aug</button>
  <button class="filter-btn" data-months="September">Sep</button>
  <button class="filter-btn" data-months="October">Oct</button>
  <button class="filter-btn" data-months="November">Nov</button>
  <button class="filter-btn" data-months="December">Dec</button>
  <button class="filter-btn" data-months="January">Jan</button>
  <button class="filter-btn" data-months="February">Feb</button>
  <button class="filter-btn" data-months="March">Mar</button>
  <span class="filter-scope" id="filterScope">Showing: Apr–Sep (YTD)</span>
</div>

<div class="panel-section active" id="panel-group">
  <div class="section-title"><span class="dot"></span>Contribution vs Target &amp; Profitability — Group + SBUs <span class="pill">YTD Apr–Sep</span></div>
  <div class="grid cols-2">
    <div class="card">
      <h3>YTD Contribution vs YTD Target (Apr–Sep)</h3>
      <div class="chart-wrap"><canvas id="chartContribTarget"></canvas></div>
      <div class="note">YTD Target is pro-rated to the selected period from live CRM department targets. Corporate has no formal contribution target.</div>
    </div>
  </div>

  <div class="section-title"><span class="dot" style="background:var(--good)"></span>SBU Snapshot <span class="pill">YTD Apr–Sep</span></div>
  <div class="card" style="padding:6px 18px;overflow-x:auto">
    <table id="sbuTable">
      <thead><tr>
        <th>SBU</th>
        <th class="num">YTD Revenue</th>
        
        <th class="num">YTD Contribution</th>
        <th class="num">YTD Target<br><span style="font-weight:400;text-transform:none;font-size:10px">(annual÷12×4)</span></th>
        <th class="num">Achievement</th>
        <th class="num">Gap</th>
        
      </tr></thead>
      <tbody></tbody>
    </table>
    
  </div>

  <div class="section-title"><span class="dot" style="background:var(--accent)"></span>Monthly Achievement vs Target <span class="pill">Apr–Sep closed-won</span></div>
  <div id="group_monthly"></div>

  <div class="section-title"><span class="dot" style="background:var(--good)"></span>Quarterly Performance vs Target <span class="pill">FY starts 1 Apr</span></div>
  <div id="group_quarterly"></div>

  

  <div class="section-title"><span class="dot" style="background:var(--warn)"></span>Project Analysis — Group (all SBUs combined) <span class="pill">Apr–Sep closed-won</span></div>
  <div class="grid cols-3">
    <div class="card">
      <div class="filter-row"><h3 id="g_brand_title">Client (Brand) breakdown — top 30</h3>
        <button class="filter-select" id="g_brand_toggle" style="cursor:pointer">Show all</button>
      </div>
      <div class="chart-wrap xtall" id="g_brand_wrap"><canvas id="g_brand"></canvas></div>
    </div>
    <div class="card"><h3>Project type breakdown</h3><div class="chart-wrap"><canvas id="g_cat"></canvas></div></div>
    <div class="card"><h3>Revenue mix by SBU</h3><div class="chart-wrap"><canvas id="g_dept"></canvas></div></div>
  </div>

  <div class="section-title"><span class="dot" style="background:var(--corp)"></span>Contribution mix by project type — month-on-month <span class="pill">Apr–Sep closed-won</span></div>
  <div id="g_mom_type"></div>

  <div class="card" style="margin-top:14px;overflow-x:auto">
    <div class="filter-row">
      <h3 id="g_top_title">Top 15 billed projects — Group (Apr–Sep)</h3>
      <label style="color:var(--muted);font-size:11px;text-transform:uppercase;letter-spacing:0.5px">Month
        <select id="g_top_filter" class="filter-select" style="margin-left:6px">
          <option value="All">All (Apr–Sep)</option>
          <option value="April">April</option>
          <option value="May">May</option>
          <option value="June">June</option>
          <option value="July">July</option>
        </select>
      </label>
    </div>
    <table id="g_top"><thead><tr>
      <th>Month</th><th>Brand</th><th>Project</th><th>SBU</th><th>Type</th><th>Manager</th><th class="num">Amount (LKR)</th>
    </tr></thead><tbody></tbody></table>
  </div>
</div>

<div class="panel-section" id="panel-Corporate"></div>
<div class="panel-section" id="panel-DM"></div>
<div class="panel-section" id="panel-Creative"></div>
<div class="panel-section" id="panel-IT"></div>

<div class="panel-section" id="panel-sales">
  <div class="section-title"><span class="dot" style="background:var(--corp)"></span>Sales Unit Performance <span class="pill">YTD Apr–Sep</span></div>
  <div class="grid cols-2">
    <div class="card">
      <h3>Sales unit YTD contribution vs YTD target</h3>
      <div class="chart-wrap tall"><canvas id="su_targets"></canvas></div>
      <div class="note">YTD Target = annual ÷ 12 × 6 months (Apr–Sep). Annual targets: Brands 142M · BD 24M · IT 24M (total = LKR 190M, matches Group target).</div>
    </div>
    <div class="card">
      <h3>Individual contribution — YTD Apr–Sep</h3>
      <div class="chart-wrap tall"><canvas id="su_indiv"></canvas></div>
      <div class="note">Click any bar to see the underlying projects.</div>
    </div>
  </div>


  <div class="card" style="margin-top:14px;overflow-x:auto">
    <h3>Sales unit detail — YTD Apr–Sep</h3>
    <table id="su_table"><thead><tr>
      <th>Sales Unit</th><th class="num">YTD Contribution</th><th class="num">YTD Target<br><span style="font-weight:400;text-transform:none;font-size:10px">(annual÷12×4)</span></th>
      <th class="num">Achievement</th><th class="num">Gap to YTD Target</th>
    </tr></thead><tbody></tbody></table>
  </div>

  <div class="section-title"><span class="dot" style="background:var(--accent)"></span>Sales Units — Monthly Achievement vs Target</div>
  <div id="sales_monthly"></div>

  <div class="section-title"><span class="dot" style="background:var(--good)"></span>Sales Units — Quarterly Performance vs Target <span class="pill">FY starts 1 Apr</span></div>
  <div id="sales_quarterly"></div>

  <div class="card" style="margin-top:14px;overflow-x:auto">
    <h3>Individual sales contribution — YTD Apr–Sep</h3>
    <table id="su_indiv_table"><thead><tr>
      <th>Sales person</th>
      <th class="num">Contribution (period)</th>
      <th class="num">Target (period)</th>
      <th class="num">Achievement</th>
      <th class="num">Gap</th>
      <th style="width:25%">Share of total</th>
    </tr></thead><tbody></tbody></table>
    <div class="note">Contribution and target sum over the selected period. Achievement colour-coded: green ≥100%, amber 85–99%, red &lt;85%. Bar fill and colour reflect achievement.</div>
  </div>
</div>

<div class="panel-section" id="panel-pipeline">
  <div class="section-title"><span class="dot" style="background:var(--pipe)"></span>Pipeline &amp; Post-YTD Activity <span class="pill">Jul–Aug open pipeline</span></div>
  <div class="grid kpi" id="pipelineKpis"></div>

  <div class="grid cols-2" style="margin-top:14px">
    <div class="card">
      <h3>Open pipeline by sales unit</h3>
      <div class="chart-wrap"><canvas id="pipe_unit"></canvas></div>
      <div class="note">Forward-looking contribution by ownership. Click bars to view deals.</div>
    </div>
    <div class="card">
      <h3>Open pipeline by stage &amp; month</h3>
      <div class="chart-wrap"><canvas id="pipe_stage"></canvas></div>
      <div class="note">Finalising Term = close to commitment.</div>
    </div>
  </div>

  <div class="section-title" style="margin-top:20px"><span class="dot" style="background:var(--pipe)"></span>Pipeline by Department</div>
  <div class="grid cols-2">
    <div class="card">
      <h3>Open pipeline by SBU (department)</h3>
      <div class="chart-wrap"><canvas id="pipe_dept"></canvas></div>
      <div class="note">Contribution by delivering SBU. Click a bar to view underlying deals.</div>
    </div>
    <div class="card" style="overflow-x:auto">
      <h3>SBU pipeline detail</h3>
      <table id="pipe_dept_table"><thead><tr>
        <th>SBU</th>
        <th class="num">Amount (LKR)</th>
        <th class="num">Deals</th>
        <th class="num">Avg deal</th>
        <th class="num">Share</th>
      </tr></thead><tbody></tbody></table>
      <div class="note">Avg deal = amount ÷ deal count.</div>
    </div>
  </div>

  <div class="section-title" style="margin-top:20px"><span class="dot" style="background:var(--pipe)"></span>Pipeline by Sales Person</div>
  <div class="grid cols-2">
    <div class="card">
      <h3>Open pipeline by sales person</h3>
      <div class="chart-wrap"><canvas id="pipe_mgr"></canvas></div>
      <div class="note">Attributed to the sales manager on each pipeline deal. Click a bar to view deals.</div>
    </div>
    <div class="card" style="overflow-x:auto">
      <h3>Sales person pipeline detail</h3>
      <table id="pipe_mgr_table"><thead><tr>
        <th>Sales person</th>
        <th class="num">Amount (LKR)</th>
        <th class="num">Deals</th>
        <th class="num">Avg deal</th>
        <th class="num">Share</th>
      </tr></thead><tbody></tbody></table>
      <div class="note">Avg deal = amount ÷ deal count.</div>
    </div>
  </div>

  <div class="card" style="margin-top:14px;overflow-x:auto">
    <h3>Open pipeline deals</h3>
    <table id="pipe_table"><thead><tr>
      <th>Month</th><th>Stage</th><th>Owner</th><th>Brand</th><th>Project</th><th>SBU</th><th>Manager</th><th class="num">Amount (LKR)</th>
    </tr></thead><tbody></tbody></table>
  </div>

  <div class="section-title" style="margin-top:24px"><span class="dot" style="background:var(--good)"></span>September Closed-Won Breakdown <span class="pill">Sep is fully in YTD; Sep Revenue pending</span></div>
  <div class="card" style="overflow-x:auto">
    <h3>September closed-won deals</h3>
    <table id="aug_table"><thead><tr>
      <th>Owner</th><th>Brand</th><th>Project</th><th>SBU</th><th>Manager</th><th class="num">Amount (LKR)</th>
    </tr></thead><tbody></tbody></table>
    <div class="note">September Contribution, HR and Dept Cost are now in YTD from the summary sheets. Only Revenue is not yet populated in the workbook — it will flow in when the next refresh lands.</div>
  </div>
</div>


<div id="drillModal" class="modal-overlay" role="dialog" aria-modal="true">
  <div class="modal-card">
    <div class="modal-header">
      <h3 id="drillModalTitle">Projects</h3>
      <button class="modal-close" id="drillModalClose" aria-label="Close">×</button>
    </div>
    <div class="modal-body" id="drillModalBody"></div>
    <div class="modal-footer">
      <div id="drillModalCount"></div>
      <div id="drillModalTotal"></div>
    </div>
  </div>
</div>

<footer>
  Loops Team Performance Dashboard &bull; Live Real-Time Financial Performance &bull; Synced with Live CRM Database
</footer>
</div>
@endsection

@push('scripts')
<script>
  window.DATA = {!! json_encode($dashboardData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};

  function toggleFullscreenMode() {
    const wrapper = document.getElementById('fpDashboardWrapper');
    const icon = document.getElementById('fullscreenIcon');
    const text = document.getElementById('fullscreenText');
    if (wrapper.classList.contains('is-fullscreen')) {
      wrapper.classList.remove('is-fullscreen');
      icon.className = 'fas fa-expand';
      text.innerText = 'Focus / Fullscreen Mode';
      document.body.style.overflow = '';
    } else {
      wrapper.classList.add('is-fullscreen');
      icon.className = 'fas fa-compress';
      text.innerText = 'Exit Fullscreen';
      document.body.style.overflow = 'hidden';
    }
  }

  // ESC key exits fullscreen
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const wrapper = document.getElementById('fpDashboardWrapper');
      if (wrapper && wrapper.classList.contains('is-fullscreen')) {
        toggleFullscreenMode();
      }
    }
  });

  function refreshLiveDashboard() {
    const icon = document.getElementById('refreshIcon');
    if (icon) icon.classList.add('fa-spin');

    fetch('{{ route('financial-performance.data') }}')
      .then(r => {
        if (!r.ok) {
          throw new Error('Server returned HTTP ' + r.status);
        }
        return r.json();
      })
      .then(newData => {
        if (typeof window.destroyAllCharts === 'function') {
          window.destroyAllCharts();
        }
        if (typeof newData === 'object' && newData !== null) {
          Object.assign(window.DATA, newData);
        }
        if (typeof window.renderAll === 'function') {
          window.renderAll();
        }
        if (icon) icon.classList.remove('fa-spin');
      })
      .catch(err => {
        console.error('Error refreshing financial data:', err);
        if (icon) icon.classList.remove('fa-spin');
        alert('Failed to refresh live data from CRM: ' + (err.message || ''));
      });
  }
</script>
<script src="{{ asset('js/financial-performance-dashboard.js') }}"></script>
@endpush
