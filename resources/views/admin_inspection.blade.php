<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Admin | Inspection / Renewal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">  <script src="https://cdn.tailwindcss.com"></script>
 <script>
  tailwind.config = {
    darkMode: 'class',
    theme: {
      extend: {
        colors: { sidebarBg: '#181c21', sidebarDark: '#23272f', mainBg: '#1a2025', accent: '#3ec6ff' },
        fontFamily: { inter: ['Inter', 'system-ui', 'sans-serif'] }
      }
    }
  }
</script>
<style>
  /* ── SIDEBAR ── */
#sidebar{width:240px;min-width:64px;background:#181c21;border-right:1px solid #23272f;display:flex;flex-direction:column;padding:16px 12px;transition:width 0.28s cubic-bezier(.4,0,.2,1);overflow:hidden;position:sticky;top:0;height:100vh;}
#sidebar.sidebar-collapsed{width:64px;}
.sb-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;min-height:40px;position:relative;}
#sidebar.sidebar-collapsed .sb-top{justify-content:center;}
.sb-logo{display:flex;align-items:center;gap:10px;overflow:hidden;flex:1;}
.sb-logo img{width:38px;height:38px;border-radius:50%;border:2px solid #3ec6ff;object-fit:cover;flex-shrink:0;background:#23272f;}
.sb-logo-text{color:#e5eaf2;font-weight:700;font-size:1rem;white-space:nowrap;transition:opacity 0.2s,width 0.2s;overflow:hidden;}
#sidebar.sidebar-collapsed .sb-logo-text{opacity:0;width:0;}
.sb-toggle{background:transparent;border:none;color:#94a3b8;cursor:pointer;padding:6px;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:background 0.15s,color 0.15s;}
.sb-toggle:hover{background:#23272f;color:#e5eaf2;}
#sidebar.sidebar-collapsed .sb-toggle{position:absolute;top:0;left:50%;transform:translateX(-50%);}
#sidebar.sidebar-collapsed .sb-logo{flex:0;}
nav.sb-nav{flex:1;display:flex;flex-direction:column;gap:2px;}
.nav-item{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;color:#e5eaf2 !important;text-decoration:none;font-size:0.85rem;font-weight:500;white-space:nowrap;transition:background 0.15s,color 0.15s;overflow:hidden;}
.nav-item:hover{background:#23272f;color:#e5eaf2;}
.nav-item.active{background:#23272f;color:#e5eaf2;}
.nav-item svg{width:20px;height:20px;flex-shrink:0;}
.nav-label{transition:opacity 0.15s,width 0.15s;overflow:hidden;white-space:nowrap;}
#sidebar.sidebar-collapsed .nav-label{opacity:0;width:0;}
#sidebar.sidebar-collapsed .nav-item{justify-content:center;padding:9px 0;gap:0;}
.sb-bottom{padding-top:12px;border-top:1px solid #23272f;display:flex;justify-content:center;}
.theme-btn{background:#23272f;border:none;color:#94a3b8;cursor:pointer;padding:8px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:background 0.15s,color 0.15s;}
.theme-btn:hover{background:#2d3340;color:#e5eaf2;}

/* ── STAT CARDS ── */
.stat-card{background:#23272f;border:2px solid #2a2f3a;border-radius:14px;padding:18px 20px;display:flex;align-items:center;gap:14px;cursor:pointer;transition:border-color .2s,box-shadow .2s,background .2s;user-select:none;position:relative;}
.stat-card .text-2xl{color:#e5eaf2;}
body.light-mode .stat-card .text-2xl{color:#1e293b;}
.stat-card:hover{border-color:#363b48;background:#2a2f3a;}
.stat-card.active-pending{border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.13);}
.stat-card.active-renewal{border-color:#3ec6ff;box-shadow:0 0 0 3px rgba(62,198,255,.12);}
.stat-card.active-under{border-color:#a78bfa;box-shadow:0 0 0 3px rgba(167,139,250,.12);}
/* Inspection workflow order: Pending -> Under Inspection -> Ready for Renewal */
#card-pending{order:1;}
#card-under{order:2;}
#card-renewal{order:3;}
.stat-card .card-count{position:absolute;top:14px;right:16px;font-size:.78rem;font-weight:800;padding:2px 9px;border-radius:999px;}
#statPending{color:#f59e0b;}
#statApproved{color:#3ec6ff;}
#statUnder{color:#a78bfa;}
.stat-card.active-pending .card-count{background:#2a1f00;color:#f59e0b;}
.stat-card.active-renewal .card-count{background:#0a1f3a;color:#3ec6ff;}
.stat-card.active-under .card-count{background:#1e1040;color:#a78bfa;}
.stat-card:not(.active-pending):not(.active-renewal):not(.active-under) .card-count{background:#1a2025;color:#94a3b8;}

/* ── BADGES ── */
.badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:999px;font-size:.72rem;font-weight:700;}
.badge-pending{background:#2a1f00;color:#f59e0b;border:1px solid #3a2d00;}
.badge-under{background:#1e1040;color:#a78bfa;border:1px solid #2d1a5a;}
.badge-approved{background:#0d3325;color:#33b481;border:1px solid #166534;}
.badge-needs_repair{background:#2d0a0a;color:#fc8181;border:1px solid #991b1b;}

/* ── TABLE ── */
.tbl th{padding:9px 12px;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#64748b;border-bottom:1px solid #23272f;text-align:left;white-space:nowrap;}
.tbl td{padding:10px 12px;font-size:.82rem;border-bottom:1px solid #1a2025;color:#cbd5e1;}
.tbl tr:hover td{background:#1a2025;}

/* ── ACTION BUTTONS ── */
.btn-inspect{background:#3ec6ff;color:#0a1f3a;border:none;border-radius:6px;padding:5px 14px;font-size:.75rem;font-weight:700;cursor:pointer;white-space:nowrap;}
.btn-inspect:hover{background:#6dd4ff;}
.btn-notify{background:#0d3325;color:#33b481;border:1px solid #166534;border-radius:6px;padding:5px 14px;font-size:.75rem;font-weight:700;cursor:pointer;white-space:nowrap;}
.btn-notify:hover{background:#14532d;}
body.light-mode .btn-notify{background:#d1fae5;color:#047857;border-color:#a7f3d0;}
body.light-mode .btn-notify:hover{background:#a7f3d0;}
.btn-continue{background:#1e1040;color:#a78bfa;border:1px solid #4c1d95;border-radius:6px;padding:5px 14px;font-size:.75rem;font-weight:700;cursor:pointer;white-space:nowrap;}
.btn-continue:hover{background:#2d1a5a;}

/* ── TAB LABELS ── */
.tab-label{font-size:.72rem;padding:2px 9px;border-radius:999px;font-weight:700;margin-left:8px;vertical-align:middle;}
.tab-label-pending{background:#2a1f00;color:#f59e0b;}
.tab-label-renewal{background:#0a1f3a;color:#3ec6ff;}
.tab-label-under{background:#1e1040;color:#a78bfa;}

/* ── CHECKLIST ── */
.cl-table{width:100%;border-collapse:collapse;font-size:.73rem;}
.cl-table th{background:#181c21;color:#64748b;padding:5px 6px;border:1px solid #2a2f3a;text-align:center;font-weight:600;font-size:.68rem;}
.cl-table td{padding:4px 6px;border:1px solid #2a2f3a;color:#cbd5e1;vertical-align:middle;}
.cl-table tr:nth-child(even) td{background:#1a2025;}
.check-radio{width:13px;height:13px;accent-color:#3ec6ff;cursor:pointer;}

/* ── SIG BLOCK ── */
.sig-block{border:1px solid #2a2f3a;border-radius:8px;padding:12px 10px;text-align:center;background:#181c21;min-height:140px;display:flex;flex-direction:column;align-items:center;}
.sig-img{max-height:48px;max-width:100%;object-fit:contain;margin:6px auto;}
.sig-name{font-weight:700;font-size:.75rem;color:#e5eaf2;margin-top:6px;text-transform:uppercase;}
.sig-pos{font-size:.68rem;color:#94a3b8;line-height:1.4;}

/* ── CHECKLIST ACTION BUTTONS ── */
.btn-renew{background:#0d3325;color:#33b481;border:1px solid #166534;border-radius:7px;padding:9px 20px;font-size:.82rem;font-weight:700;cursor:pointer;}
.btn-renew:hover{background:#14532d;}
.btn-print{background:#23272f;color:#94a3b8;border:1px solid #363b48;border-radius:7px;padding:9px 20px;font-size:.82rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;}
.btn-print:hover{border-color:#94a3b8;color:#e5eaf2;}
.btn-edit{font-size:.72rem;color:#3ec6ff;border:1px solid #3ec6ff;border-radius:5px;padding:3px 10px;background:transparent;cursor:pointer;margin-top:8px;}
.btn-edit:hover{background:#0a1f3a;}
.btn-save{background:#3ec6ff;color:#0a1f3a;border:none;border-radius:7px;padding:9px 20px;font-size:.82rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;}
.btn-save:hover{background:#6dd4ff;}

/* ── PAGINATION ── */
.pg-btn{background:#23272f;border:1px solid #363b48;color:#94a3b8;border-radius:6px;padding:5px 11px;font-size:.78rem;cursor:pointer;}
.pg-btn:hover,.pg-btn.active{background:#3ec6ff;color:#0a1f3a;border-color:#3ec6ff;}
/* ── ICON CIRCLES ── */
.icon-circle-pending{background:#2a1f00;}
.icon-circle-renewal{background:#0a1f3a;}
.icon-circle-under{background:#1e1040;}

body.light-mode .icon-circle-pending{background:#fef3c7;}
body.light-mode .icon-circle-renewal{background:#dbeafe;}
body.light-mode .icon-circle-under{background:#ede9fe;}

/* ── LIGHT MODE BADGES ── */
body.light-mode .badge-pending{background:#fef3c7;color:#b45309;border-color:#fde68a;}
body.light-mode .badge-under{background:#ede9fe;color:#6d28d9;border-color:#ddd6fe;}
body.light-mode .badge-approved{background:#d1fae5;color:#047857;border-color:#a7f3d0;}
body.light-mode .badge-needs_repair{background:#fee2e2;color:#b91c1c;border-color:#fecaca;}
body.light-mode .tab-label-pending{background:#fef3c7;color:#b45309;}
body.light-mode .tab-label-renewal{background:#dbeafe;color:#1d4ed8;}
body.light-mode .tab-label-under{background:#ede9fe;color:#6d28d9;}
/* ── LIGHT MODE CHECKLIST BOX ── */
body.light-mode .bg-\[\#1e2430\]{background:#fff !important;border:1px solid #e2e8f0 !important;}

/* ── LIGHT MODE NOTIFY MODAL ── */
body.light-mode .bg-\[\#0d2e1a\]{background:#d1fae5 !important;}
body.light-mode .bg-\[\#13151a\]{background:#f1f5f9 !important;border-color:#e2e8f0 !important;}

/* ── MODALS ── */
.modal-overlay{display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.75);align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal-box{background:#23272f;border:1px solid #363b48;border-radius:12px;padding:24px;width:100%;max-width:380px;}
.modal-box-lg{background:#23272f;border:1px solid #363b48;border-radius:12px;padding:28px;width:100%;max-width:480px;}
.fi{background:#181c21;border:1px solid #363b48;border-radius:6px;padding:7px 10px;font-size:.82rem;color:#e5eaf2;width:100%;outline:none;margin-top:4px;box-sizing:border-box;}
.fi:focus{border-color:#3ec6ff;}
.force-light-text{color:#e5eaf2;}
/* ── APP BACKGROUND ── */
.app-body,.app-main{background:#1a2025;}
body.light-mode.app-body,
body.light-mode .app-main{background:#f1f5fa;}

/* ── LIGHT MODE ── */
body.light-mode{background:#f1f5fa;color:#222;}
body.light-mode #sidebar{background:#f7fafc;border-color:#e2e8f0;}
body.light-mode .nav-item{color:#64748b !important;}
body.light-mode .nav-item:hover,body.light-mode .nav-item.active{background:#e8edf5;color:#1e293b;}
body.light-mode .sb-logo-text{color:#1e293b;}
body.light-mode .sb-bottom{border-color:#e2e8f0;}
body.light-mode .theme-btn{background:#e8edf5;color:#64748b;}
body.light-mode .force-light-text{color:#1e293b !important;}
body.light-mode .stat-card{background:#fff;border-color:#e2e8f0;}
body.light-mode .stat-card:hover{background:#f8fafc;}
body.light-mode .tbl td{color:#374151;border-color:#e5e7eb;}
body.light-mode .tbl th{color:#64748b;border-color:#e5e7eb;}
body.light-mode .tbl tr:hover td{background:#f8fafc;}
body.light-mode .cl-table th{background:#f1f5f9;color:#475569;border-color:#e2e8f0;}
body.light-mode .cl-table td{border-color:#e2e8f0;color:#374151;}
body.light-mode .cl-table tr:nth-child(even) td{background:#f8fafc;}
body.light-mode .sig-block{background:#f8fafc;border-color:#e2e8f0;}
body.light-mode .sig-name{color:#1e293b;}
body.light-mode .modal-box,.modal-box-lg{background:#fff;border-color:#e2e8f0;}
body.light-mode .fi{background:#f8fafc;border-color:#cbd5e0;color:#1e293b;}
body.light-mode input,body.light-mode select,body.light-mode textarea{background:#f8fafc !important;color:#1e293b !important;border-color:#cbd5e1 !important;}
body.light-mode .btn-print{background:#e8edf5;color:#475569;border-color:#cbd5e1;}
body.light-mode .bg-\[\#23272f\]{background:#fff !important;border:1px solid #e2e8f0 !important;}
</style>
</head>
<body class="min-h-screen font-inter app-body">
<div class="flex min-h-screen">
<aside id="sidebar">
  <div class="sb-top">
    <div class="sb-logo">
      <img src="{{ asset('images/logo.png') }}" alt="Logo" onerror="this.src=''">
      <span class="sb-logo-text">4ID APAO Firearms</span>
    </div>
    <button class="sb-toggle" id="sidebarToggleBtn" title="Toggle sidebar">
      <svg id="sb-icon-menu" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
      <svg id="sb-icon-close" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
  </div>
  <nav class="sb-nav">
    <a href="{{ route('admin.dashboard') }}" class="nav-item">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      <span class="nav-label">Dashboard</span>
    </a>
    <a href="{{ route('admin.personnel') }}" class="nav-item">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
      <span class="nav-label">List of Personnel</span>
    </a>
    <a href="{{ route('admin.inspection') }}" class="nav-item active">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      <span class="nav-label">Inspection/Renewal</span>
    </a>
    <a href="{{ route('admin.reports') }}" class="nav-item">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
      <span class="nav-label">Report</span>
    </a>
    <a href="{{ route('admin.archive') }}" class="nav-item">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 8v13H3V8"/><path d="M23 3H1v5h22V3z"/><path d="M10 12h4"/></svg>
      <span class="nav-label">Archive Data</span>
    </a>
    <a href="{{ route('admin.users') }}" class="nav-item">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      <span class="nav-label">Manage Users</span>
    </a>
    <a href="{{ route('admin.audit') }}" class="nav-item">
      <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/><circle cx="17" cy="17" r="4"/><path d="M19.5 19.5L21 21"/></svg>
      <span class="nav-label">Audit Log</span>
    </a>
  </nav>
  <div class="sb-bottom">
    <button class="theme-btn" id="themeToggle" title="Toggle theme">
      <svg id="icon-sun" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
      <svg id="icon-moon" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display:none"><path d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z"/></svg>
    </button>
  </div>
</aside>

  <!-- MAIN -->
  <main class="flex-1 p-7 overflow-y-auto app-main">

    <!-- Header -->
   <header class="flex flex-wrap justify-between mb-8 items-center gap-4">
  <div class="relative w-72">
    <input id="searchInput" type="text" placeholder="Search personnel..." oninput="filterTable()" class="bg-[#23272f] text-white border border-[#363b48] rounded px-4 py-2 w-full focus:outline-none focus:ring-2 focus:ring-[#3ec6ff] pr-10 force-light-text" />
    <svg class="absolute right-3 top-1/2 -translate-y-1/2 h-5 w-5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M21 21l-4.35-4.35M5 11a6 6 0 1112 0 6 6 0 01-12 0z"/></svg>
  </div>
  <div class="flex items-center gap-4">
    <span class="text-sm force-light-text opacity-70">Welcome, <strong>{{ $user->name ?? 'Admin' }}</strong></span>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="text-red-400 hover:underline text-base font-semibold tracking-tight">Logout</button>
    </form>
  </div>
</header>


      <!-- ===== LIST VIEW ===== -->
      <div id="viewList">
       <h1 class="text-2xl font-bold mb-1 force-light-text">Inspection / Renewal</h1>
<p class="text-sm text-[#64748b] mb-6 force-light-text">Review and inspect firearms and documents of personnel. After inspection, update the status accordingly.</p>

        <!-- ── Stat Cards (filter tabs) ── -->
        <div class="grid grid-cols-3 gap-4 mb-6">

          <!-- Pending Inspection -->
<div class="stat-card" id="card-pending" onclick="setActiveTab('pending')">
  <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 icon-circle-pending">
    <svg fill="none" stroke="#f59e0b" stroke-width="2" viewBox="0 0 24 24" width="20" height="20"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M12 6v6l4 2"/></svg>
  </div>
  <div>
    <div class="text-xs font-bold uppercase tracking-wide" style="color:#f59e0b;">Pending Inspection</div>
    <div class="text-2xl font-black" id="statPending">—</div>
    <div class="text-xs text-[#64748b]">Awaiting inspection and review</div>
  </div>
  <span class="card-count" id="countPending">—</span>
</div>

          <!-- Ready for Renewal -->
          <div class="stat-card" id="card-renewal" onclick="setActiveTab('renewal')">
         <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 icon-circle-renewal">
               <svg fill="none" stroke="#3ec6ff" stroke-width="2" viewBox="0 0 24 24" width="20" height="20">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
              </svg>
            </div>
            <div>
            <div class="text-xs font-bold uppercase tracking-wide" style="color:#3ec6ff;">Ready for Renewal</div>
              <div class="text-2xl font-black" id="statApproved">—</div>
              <div class="text-xs text-[#64748b]">For approval and renewal</div>
            </div>
            <span class="card-count" id="countRenewal">—</span>
          </div>

          <!-- Under Inspection -->
          <div class="stat-card" id="card-under" onclick="setActiveTab('under')">
         <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 icon-circle-under">
          <svg fill="none" stroke="#a78bfa" stroke-width="2" viewBox="0 0 24 24" width="20" height="20"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div>
              <div class="text-xs font-bold uppercase text-[#a78bfa] tracking-wide">Under Inspection</div>
              <div class="text-2xl font-black" id="statUnder">—</div>
              <div class="text-xs text-[#64748b]">Currently being inspected</div>
            </div>
            <span class="card-count" id="countUnder">—</span>
          </div>

        </div>

        <!-- Table -->
        <div class="bg-[#23272f] rounded-lg p-6 shadow shadow-black/10">
          <div class="flex items-center justify-between px-5 py-3 border-b border-[#2a2f3a]">
            <span class="font-bold text-sm" id="tableTitle">
              Pending Inspection
              <span class="tab-label tab-label-pending" id="tableTitleBadge">Pending</span>
            </span>
            <div class="flex items-center gap-2 text-sm text-[#64748b]">
              Sort by:
              <select id="sortSelect" class="bg-[#13151a] border border-[#2a2f3a] rounded px-2 py-1 text-[#e5eaf2] text-xs outline-none" onchange="filterTable()">
                <option value="newest">Date Registered (Newest)</option>
                <option value="oldest">Date Registered (Oldest)</option>
                <option value="name">Name A-Z</option>
              </select>
            </div>
          </div>
          <table class="tbl w-full">
            <thead>
              <tr><th>Name</th><th>AFP Serial #</th><th>Pistol</th><th>Date Registered</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody id="inspTable">
              <tr><td colspan="6" class="text-center py-10 text-[#64748b]">Loading...</td></tr>
            </tbody>
          </table>
          <div class="flex items-center justify-between px-5 py-3 border-t border-[#2a2f3a]">
            <span class="text-xs text-[#64748b]" id="pagInfo">Showing 0 entries</span>
            <div class="flex gap-1" id="pagBtns"></div>
          </div>
        </div>
      </div>

      <!-- ===== CHECKLIST VIEW ===== -->
      <div id="viewChecklist" style="display:none;">

        <button onclick="showList()" class="flex items-center gap-2 text-[#d4a017] text-sm font-semibold mb-5 hover:underline">
          <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
          Back to List
        </button>

        <h1 class="text-2xl font-bold mb-1">Inspection / Renewal</h1>
        <p class="text-sm text-[#64748b] mb-5">Review and inspect firearms and documents of personnel. After inspection, update the status accordingly.</p>

        <!-- Personnel Info -->
        <div class="bg-[#23272f] rounded-lg p-5 mb-4 shadow shadow-black/10">
          <div class="flex items-center gap-2 mb-3">
            <svg fill="none" stroke="#d4a017" stroke-width="2" viewBox="0 0 24 24" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span class="font-bold text-sm force-light-text">Inspection Checklist</span>
          </div>
          <div class="grid grid-cols-3 gap-x-8 gap-y-2 text-sm">
            <div><span class="text-[#64748b] text-xs block">Personnel Name:</span><span class="font-semibold" id="cl_name">—</span></div>
            <div><span class="text-[#64748b] text-xs block">Nomenclature:</span><span class="font-semibold" id="cl_nomen">—</span></div>
            <div><span class="text-[#64748b] text-xs block">Unit:</span><span class="font-semibold" id="cl_unit">—</span></div>
            <div><span class="text-[#64748b] text-xs block">Made:</span><span class="font-semibold" id="cl_made">—</span></div>
            <div><span class="text-[#64748b] text-xs block">SN:</span><span class="font-semibold font-mono" id="cl_sn">—</span></div>
            <div><span class="text-[#64748b] text-xs block">Date:</span><span class="font-semibold" id="cl_date">—</span></div>
          </div>
        </div>

        <!-- Full Checklist -->
        <div class="bg-[#1e2430] border border-[#2a2f3a] rounded-xl p-5 mb-4">
          <div class="flex items-center gap-2 mb-2">
            <svg fill="none" stroke="#d4a017" stroke-width="2" viewBox="0 0 24 24" width="15" height="15"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="font-bold text-sm">Inspection Checklist</span>
          </div>
          <div class="text-xs text-[#64748b] mb-3 pb-2 border-b border-[#2a2f3a] flex flex-wrap gap-3">
            <span>Legend:</span>
            <span><span class="text-[#34d399] font-bold">(✓)</span> Serviceable</span>
            <span>(XX) Repair</span><span>(XXX) Replace</span><span>(U) Unserviceable</span>
            <span>(N/A) Not Applicable</span><span>(O) Missing</span><span>(D) Damaged</span>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <table class="cl-table">
              <thead><tr><th style="width:24px;">#</th><th style="text-align:left;padding-left:6px;">Items to Inspect</th><th>✓</th><th>XX</th><th>XXX</th><th>U</th><th>N/A</th><th>O</th><th>D</th></tr></thead>
              <tbody id="checklistLeft"></tbody>
            </table>
            <table class="cl-table">
              <thead><tr><th style="width:24px;">#</th><th style="text-align:left;padding-left:6px;">Items to Inspect</th><th>✓</th><th>XX</th><th>XXX</th><th>U</th><th>N/A</th><th>O</th><th>D</th></tr></thead>
              <tbody id="checklistRight"></tbody>
            </table>
          </div>
          <div class="mt-4">
            <label class="text-xs text-[#64748b] font-semibold block mb-1">Remarks</label>
            <textarea id="cl_remarks" rows="2" class="fi" style="resize:vertical;" placeholder="e.g. Firearm is Serviceable."></textarea>
          </div>
        </div>

        <!-- Signatories -->
        <div class="bg-[#1e2430] border border-[#2a2f3a] rounded-xl p-5 mb-4">
          <div class="grid grid-cols-4 gap-3">
            <div class="sig-block">
              <div class="text-xs text-[#64748b] font-semibold uppercase">Inspected By:</div>
              <img id="sig_insp_img" src="" class="sig-img hidden" alt="">
              <div class="sig-name text-xs mt-2" id="sig_insp_name">—</div>
              <div class="sig-pos" id="sig_insp_rank"></div>
              <div class="sig-pos" id="sig_insp_pos"></div>
              <button onclick="openEditSig('insp')" class="btn-edit">Edit info</button>
            </div>
            <div class="sig-block">
              <div class="text-xs text-[#64748b] font-semibold uppercase">Witnessed By:</div>
              <img id="sig_wit_img" src="" class="sig-img hidden" alt="">
              <div class="sig-name text-xs mt-2" id="sig_wit_name">—</div>
              <div class="sig-pos" id="sig_wit_rank"></div>
              <div class="sig-pos" id="sig_wit_pos"></div>
              <button onclick="openEditSig('wit')" class="btn-edit">Edit info</button>
            </div>
            <div class="sig-block">
              <div class="text-xs text-[#64748b] font-semibold uppercase">Approved By:</div>
              <img id="sig_app_img" src="" class="sig-img hidden" alt="">
              <div class="sig-name text-xs mt-2" id="sig_app_name">—</div>
              <div class="sig-pos" id="sig_app_rank"></div>
              <div class="sig-pos" id="sig_app_pos"></div>
              <button onclick="openEditSig('app')" class="btn-edit">Edit info</button>
            </div>
            <div class="sig-block">
              <div class="text-xs text-[#64748b] font-semibold uppercase">Noted By:</div>
              <img id="sig_not_img" src="" class="sig-img hidden" alt="">
              <div class="sig-name text-xs mt-2" id="sig_not_name">—</div>
              <div class="sig-pos" id="sig_not_rank"></div>
              <div class="sig-pos" id="sig_not_pos"></div>
              <button onclick="openEditSig('not')" class="btn-edit">Edit info</button>
            </div>
          </div>
        </div>

        <!-- Error / Success -->
        <div id="cl_error" class="hidden text-red-400 text-sm mb-3 p-3 rounded-lg bg-red-400/10 border border-red-400/30"></div>
        <div id="cl_success" class="hidden text-green-400 text-sm mb-3 p-3 rounded-lg bg-green-400/10 border border-green-400/30"></div>

      <!-- Action Buttons -->
        <div class="flex flex-wrap gap-3">
          <button onclick="saveInspection('approved')" class="btn-renew">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="14" height="14" style="display:inline;vertical-align:middle;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Mark For Renewal
          </button>
          <button onclick="printReport()" class="btn-print">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" width="14" height="14"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print Report
          </button>
        </div>

      </div><!-- end checklist view -->
    </main>
  </div>

  <!-- Edit Signatory Modal -->
  <div id="editSigModal" class="modal-overlay">
    <div class="modal-box">
      <h3 class="font-bold text-sm text-[#e5eaf2] mb-4" id="editSigTitle">Edit Signatory</h3>
      <input type="hidden" id="editSigKey">
      <div class="mb-3"><label class="text-xs text-[#64748b] font-semibold">Full Name</label><input type="text" id="editSigName" class="fi" placeholder="Full name"></div>
      <div class="mb-3"><label class="text-xs text-[#64748b] font-semibold">Rank</label><input type="text" id="editSigRank" class="fi" placeholder="e.g. Cpl (OS) PA"></div>
      <div class="mb-3"><label class="text-xs text-[#64748b] font-semibold">Position</label><input type="text" id="editSigPos" class="fi" placeholder="e.g. Armaments NCO"></div>
      <div class="mb-4"><label class="text-xs text-[#64748b] font-semibold">Signature Image (optional)</label><input type="file" id="editSigFile" accept="image/*" class="fi" style="padding:4px;"></div>
      <div class="flex gap-3">
        <button onclick="saveEditSig()" class="btn-save flex-1 justify-center">Save</button>
        <button onclick="closeEditSig()" class="btn-print flex-1 justify-center">Cancel</button>
      </div>
    </div>
  </div>

  <!-- Notify Staff Modal -->
  <div id="notifyModal" class="modal-overlay">
    <div class="modal-box-lg">
      <div class="flex items-center gap-3 mb-5">
        <div class="w-9 h-9 rounded-full bg-[#0d2e1a] flex items-center justify-center flex-shrink-0">
          <svg fill="none" stroke="#34d399" stroke-width="2" viewBox="0 0 24 24" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        </div>
        <div>
         <h3 class="font-bold text-[#e5eaf2] force-light-text">Notify Staff for ICS Renewal</h3>
          <p class="text-xs text-[#64748b]" id="notifyPersonnelName">—</p>
        </div>
      </div>

      <div class="bg-[#13151a] rounded-lg border border-[#2a2f3a] p-4 mb-4">
        <div class="text-xs font-semibold text-[#64748b] uppercase mb-2 tracking-wide force-light-text">ICS Details</div>
        <div class="grid grid-cols-2 gap-3 text-sm">
         <div><span class="text-[#64748b] text-xs block">AFP Serial #</span><span class="font-mono font-semibold force-light-text" id="notifySerial">—</span></div>
        <div><span class="text-[#64748b] text-xs block">Pistol Type</span><span class="font-semibold force-light-text" id="notifyPistol">—</span></div>
        </div>
      </div>

      <div class="mb-4">
        <label class="text-xs text-[#64748b] font-semibold block mb-1 force-light-text">Notification Message</label>
        <textarea id="notifyMessage" rows="4" class="fi" style="resize:vertical;" placeholder="Type your message to the staff..."></textarea>
      </div>

      <div id="notifyError" class="hidden text-red-400 text-xs mb-3 p-2 rounded bg-red-400/10 border border-red-400/20"></div>
      <div id="notifySuccess" class="hidden text-green-400 text-xs mb-3 p-2 rounded bg-green-400/10 border border-green-400/20"></div>

      <div class="flex gap-3">
        <button onclick="sendNotify()" class="btn-renew flex-1 text-center">
          Send for Renewal
        </button>
        <button onclick="closeNotifyModal()" class="btn-print flex-1 justify-center">Cancel</button>
      </div>
    </div>
  </div>

  <script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    const PARTS = [
      'barrel','slide','recoil_spring_assembly','firing_pin','spacer_sleeve',
      'firing_pin_spring','spring_cups','firing_pin_safety','firing_pin_safety_spring',
      'extractor','extractor_depressor_plunger','extractor_depressor_plunger_spring',
      'trigger_loaded_bearing','rear_sight','front_sight',
      'front_sight_screw','frame','magazine_catch_spring','magazine_catch','slide_lock',
      'slide_cover_plate','connector','trigger_mechanism_housing','trigger','trigger_spring',
      'trigger_with_trigger_bar','slide_stop_lever','trigger_pin','trigger_housing_pin','locking_block_pin'
    ];
    const PART_LABELS = [
      'Barrel','Slide','Recoil Spring Assembly','Firing Pin','Spacer Sleeve',
      'Firing Pin Spring','Spring Cups','Firing Pin Safety','Firing Pin Safety Spring',
      'Extractor','Extractor Depressor Plunger','Extractor Depressor Plunger Spring',
      'Spring-Loaded Bearing','Rear Sight','Front Sight',
      'Front Sight Screw','Frame','Magazine Catch Spring','Magazine Catch','Slide Lock',
      'Slide Cover Plate','Connector','Trigger Mechanism Housing w/ Ejector','Trigger','Trigger Spring',
      'Trigger with Trigger Bar','Slide Stop Lever','Trigger Pin','Trigger Housing Pin','Locking Block Pin'
    ];

    let allPersonnel = [], currentPage = 1, currentItemNumber = null, activeTab = 'pending';
    let currentInspectionStatus = 'under';
    let notifyItem = null;
    const PER_PAGE = 10;
    let sigData = {
      insp:{name:'',rank:'',pos:'',img:''},
      wit:{name:'',rank:'',pos:'',img:''},
      app:{name:'',rank:'',pos:'',img:''},
      not:{name:'',rank:'',pos:'',img:''}
    };

    // ── Tab switcher ──
    function setActiveTab(tab) {
      activeTab = tab;
      currentPage = 1;

      ['pending','renewal','under'].forEach(t => {
        const c = document.getElementById('card-'+t);
        c.classList.remove('active-pending','active-renewal','active-under');
      });
      document.getElementById('card-'+tab).classList.add('active-'+tab);

      const titleMap = {
        pending: { text:'Pending Inspection', badge:'Pending', cls:'tab-label-pending' },
        renewal: { text:'Ready for Renewal',  badge:'For Renewal', cls:'tab-label-renewal' },
        under:   { text:'Under Inspection',   badge:'Under Inspection', cls:'tab-label-under' },
      };
      const t = titleMap[tab];
      document.getElementById('tableTitle').innerHTML =
        `${t.text} <span class="tab-label ${t.cls}">${t.badge}</span>`;

      renderTable();
    }

    function tabStatuses() {
      if (activeTab === 'pending') return ['pending', null, undefined, ''];
      if (activeTab === 'renewal') return ['approved'];
      if (activeTab === 'under')   return ['under'];
      return [];
    }

    function actionBtn(p) {
      if (activeTab === 'pending') {
        return `<button onclick="openInspect(${p.itemNumber})" class="btn-inspect">Inspect</button>`;
      }
      if (activeTab === 'renewal') {
        return `<button onclick="openNotifyModal(${JSON.stringify(p).replace(/"/g,'&quot;')})" class="btn-notify">Notify Staff</button>`;
      }
      if (activeTab === 'under') {
        return `<button onclick="openInspect(${p.itemNumber})" class="btn-continue">Continue Inspection</button>`;
      }
      return '';
    }

    async function loadData() {
      const res  = await fetch('/admin/inspection-data');
      const data = await res.json();
      if (!data.success) return;
      allPersonnel = data.data;
      document.getElementById('statPending').textContent  = data.pending;
      document.getElementById('statUnder').textContent    = data.under;
      document.getElementById('statApproved').textContent = data.approved;
      document.getElementById('countPending').textContent  = data.pending;
      document.getElementById('countUnder').textContent    = data.under;
      document.getElementById('countRenewal').textContent  = data.approved;
      renderTable();
    }

    function filterTable() { currentPage = 1; renderTable(); }

    function renderTable() {
      const sort   = document.getElementById('sortSelect').value;
      const search = (document.getElementById('searchInput').value || '').toLowerCase();

      const validStatuses = tabStatuses();
      let rows = allPersonnel.filter(p => {
        const s = p.inspectionStatus || '';
        if (!validStatuses.includes(s)) return false;
        if (s === '' || s === 'pending' || s == null) {
          return p.icsStatus === 'under';
        }
        return true;
      });

      if (search) {
        rows = rows.filter(p =>
          (`${p.rank} ${p.lastName} ${p.firstName}`).toLowerCase().includes(search) ||
          (p.afpSerialNumber||'').toLowerCase().includes(search)
        );
      }

      if (sort==='oldest') rows.sort((a,b)=>new Date(a.dateRegistered)-new Date(b.dateRegistered));
      if (sort==='newest') rows.sort((a,b)=>new Date(b.dateRegistered)-new Date(a.dateRegistered));
      if (sort==='name')   rows.sort((a,b)=>a.lastName.localeCompare(b.lastName));

      const total = rows.length;
      const pages = Math.max(1, Math.ceil(total/PER_PAGE));
      const start = (currentPage-1)*PER_PAGE;
      const slice = rows.slice(start, start+PER_PAGE);

      const bc = {
        pending:'badge-pending',
        under:'badge-under',
        approved:'badge-renewal',
        needs_repair:'badge-needs_repair'
      };
      const bl = {
        pending:'Pending',
        under:'Under Inspection',
        approved:'For Renewal',
        needs_repair:'Needs Repair'
      };

      document.getElementById('inspTable').innerHTML = slice.length ? slice.map(p=>`
        <tr>
          <td class="font-semibold text-[#e5eaf2]">${p.rank} ${p.lastName}, ${p.firstName}${p.middleName?' '+p.middleName.charAt(0)+'.':''}</td>
          <td class="font-mono text-xs">${p.afpSerialNumber||'—'}</td>
          <td>${p.pistolType||'—'}</td>
          <td class="text-xs text-[#94a3b8]">${p.dateRegistered||'—'}</td>
          <td><span class="badge ${bc[p.inspectionStatus]||'badge-pending'}">${bl[p.inspectionStatus]||'Pending'}</span></td>
          <td>${actionBtn(p)}</td>
        </tr>`).join('') :
        `<tr><td colspan="6" class="text-center py-10 text-[#64748b]">No personnel found for this tab.</td></tr>`;

      document.getElementById('pagInfo').textContent =
        `Showing ${total ? start+1 : 0} to ${Math.min(start+PER_PAGE,total)} of ${total} entries`;

      const pd = document.getElementById('pagBtns');
      pd.innerHTML = '';
      if (pages > 1) {
        [['‹',()=>{if(currentPage>1){currentPage--;renderTable();}}],
         ...Array.from({length:pages},(_,i)=>[i+1,()=>{currentPage=i+1;renderTable();}]),
         ['›',()=>{if(currentPage<pages){currentPage++;renderTable();}}]
        ].forEach(([lbl,fn])=>{
          const b=document.createElement('button');
          b.className='pg-btn'+(lbl===currentPage?' active':'');
          b.textContent=lbl; b.onclick=fn;
          pd.appendChild(b);
        });
      }
    }

    async function openInspect(itemNumber) {
      currentItemNumber = itemNumber;

      if (activeTab === 'pending') {
        await fetch('/admin/inspection/save', {
          method: 'POST',
          headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
          body: JSON.stringify({ itemNumber, status: 'under' })
        });
        const rec = allPersonnel.find(p => p.itemNumber === itemNumber);
        if (rec) rec.inspectionStatus = 'under';
      }

      const res  = await fetch(`/admin/inspection/${itemNumber}/detail`);
      const data = await res.json();
      if (!data.success) return;
      const p=data.personnel, ins=data.inspection, ics=data.ics||{};

      document.getElementById('cl_name').textContent  = `${p.rank} ${p.lastName}, ${p.firstName}`;
      document.getElementById('cl_unit').textContent  = p.unit||'—';
      document.getElementById('cl_nomen').textContent = `Pistol 9mm, ${p.pistolType||'Glock 17'}`;
      document.getElementById('cl_made').textContent  = p.pistolType||'Glock 17';
      document.getElementById('cl_sn').textContent    = p.afpSerialNumber||'—';
      document.getElementById('cl_date').textContent  = new Date().toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});

      const opts = ['serviceable','repair','replace','unserviceable','na','missing','damaged'];
      function buildRows(parts, startIdx) {
        return parts.map((key,i)=>{
          const val = ins?(ins[key]||'serviceable'):'serviceable';
          return `<tr>
            <td class="text-center text-[#64748b] text-xs">${startIdx+i+1}.</td>
            <td style="padding-left:6px;">${PART_LABELS[startIdx+i]}</td>
            ${opts.map(o=>`<td class="text-center"><input type="radio" name="${key}" value="${o}" class="check-radio" ${val===o?'checked':''}></td>`).join('')}
          </tr>`;
        }).join('');
      }
      document.getElementById('checklistLeft').innerHTML  = buildRows(PARTS.slice(0,15),0);
      document.getElementById('checklistRight').innerHTML = buildRows(PARTS.slice(15,30),15);
      document.getElementById('cl_remarks').value = ins?.remarks||'';
      currentInspectionStatus = ins?.status || 'under';

      sigData = {
        insp:{name:ins?.inspectedByName||ics.chiefOfficerName||'',rank:ins?.inspectedByRank||'',pos:ins?.inspectedByPosition||ics.chiefOfficerPosition||'',img:ins?.inspectedBySig||''},
        wit: {name:ins?.witnessedByName||'',rank:ins?.witnessedByRank||'',pos:ins?.witnessedByPosition||'',img:ins?.witnessedBySig||''},
        app: {name:ins?.approvedByName||ics.issuedByName||'',rank:ins?.approvedByRank||'',pos:ins?.approvedByPosition||ics.issuedByPosition||'',img:ins?.approvedBySig||''},
        not: {name:ins?.notedByName||'',rank:ins?.notedByRank||'',pos:ins?.notedByPosition||'',img:ins?.notedBySig||''},
      };
      renderSigs();

      document.getElementById('viewList').style.display      = 'none';
      document.getElementById('viewChecklist').style.display = 'block';
      window.scrollTo(0,0);
    }

    function renderSigs() {
      ['insp','wit','app','not'].forEach(k=>{
        document.getElementById('sig_'+k+'_name').textContent = sigData[k].name||'—';
        document.getElementById('sig_'+k+'_rank').textContent = sigData[k].rank||'';
        document.getElementById('sig_'+k+'_pos').textContent  = sigData[k].pos||'';
        const img=document.getElementById('sig_'+k+'_img');
        if(sigData[k].img){img.src=sigData[k].img;img.classList.remove('hidden');}
        else img.classList.add('hidden');
      });
    }

    function showList() {
      document.getElementById('viewChecklist').style.display='none';
      document.getElementById('viewList').style.display='block';
      window.scrollTo(0,0);
    }

    async function saveInspection(status) {
      const errEl=document.getElementById('cl_error'), sucEl=document.getElementById('cl_success');
      errEl.classList.add('hidden'); sucEl.classList.add('hidden');
      const body={
        itemNumber:currentItemNumber, status,
        remarks:document.getElementById('cl_remarks').value,
        inspectedByName:sigData.insp.name, inspectedByRank:sigData.insp.rank, inspectedByPosition:sigData.insp.pos,
        inspectedBySig:sigData.insp.img,
        witnessedByName:sigData.wit.name,  witnessedByRank:sigData.wit.rank,  witnessedByPosition:sigData.wit.pos,
        witnessedBySig:sigData.wit.img,
        approvedByName:sigData.app.name,   approvedByRank:sigData.app.rank,   approvedByPosition:sigData.app.pos,
        approvedBySig:sigData.app.img,
        notedByName:sigData.not.name,      notedByRank:sigData.not.rank,      notedByPosition:sigData.not.pos,
        notedBySig:sigData.not.img,
      };
      PARTS.forEach(k=>{ const c=document.querySelector(`input[name="${k}"]:checked`); body[k]=c?c.value:'serviceable'; });
      const res  = await fetch('/admin/inspection/save',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},body:JSON.stringify(body)});
      const data = await res.json();
      if (data.success) {
        const lbl={under:'Saved as Under Inspection ✓',needs_repair:'Flagged for Repair ✓',approved:'Marked as Renewed ✓'};
        sucEl.textContent=lbl[status]||'Saved!'; sucEl.classList.remove('hidden');
        currentInspectionStatus = status;
        setTimeout(()=>{sucEl.classList.add('hidden'); loadData();},2000);
      } else {
        errEl.textContent=data.error||'Failed.'; errEl.classList.remove('hidden');
      }
    }

    // ── FIXED: opens the print tab synchronously (before the save request) so the
    //           browser's popup blocker doesn't kill it, then navigates that tab to
    //           the print URL (with a cache-busting param) once the save completes.
    //           Shows the REAL server error instead of a generic message. ──
    async function printReport() {
      if (!currentItemNumber) return;
      const errEl = document.getElementById('cl_error');
      errEl.classList.add('hidden');

      // Open the tab RIGHT AWAY, synchronously, so the popup blocker allows it.
      // We'll navigate it to the real print URL once saving finishes.
      const printWindow = window.open('', '_blank');
      if (printWindow) {
        printWindow.document.write(
          '<p style="font-family:sans-serif;padding:20px;">Saving inspection data, please wait…</p>'
        );
      }

      const body = {
        itemNumber: currentItemNumber,
        status: currentInspectionStatus || 'under',
        remarks: document.getElementById('cl_remarks').value,
        inspectedByName: sigData.insp.name, inspectedByRank: sigData.insp.rank, inspectedByPosition: sigData.insp.pos,
        inspectedBySig: sigData.insp.img,
        witnessedByName: sigData.wit.name,  witnessedByRank: sigData.wit.rank,  witnessedByPosition: sigData.wit.pos,
        witnessedBySig: sigData.wit.img,
        approvedByName: sigData.app.name,   approvedByRank: sigData.app.rank,   approvedByPosition: sigData.app.pos,
        approvedBySig: sigData.app.img,
        notedByName: sigData.not.name,      notedByRank: sigData.not.rank,      notedByPosition: sigData.not.pos,
        notedBySig: sigData.not.img,
      };
      PARTS.forEach(k => {
        const c = document.querySelector(`input[name="${k}"]:checked`);
        body[k] = c ? c.value : 'serviceable';
      });

      try {
        const res = await fetch('/admin/inspection/save', {
          method: 'POST',
          headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
          body: JSON.stringify(body)
        });

        const rawText = await res.text();
        let data;
        try {
          data = JSON.parse(rawText);
        } catch (parseErr) {
          console.error('Non-JSON response from /admin/inspection/save:', res.status, rawText);
          errEl.textContent = `Server error (status ${res.status}). Open the browser console for details.`;
          errEl.classList.remove('hidden');
          if (printWindow) printWindow.close();
          return;
        }

        if (!data.success) {
          console.error('Save failed:', data);
          errEl.textContent = data.error || 'Could not save before printing.';
          errEl.classList.remove('hidden');
          if (printWindow) printWindow.close();
          return;
        }

        // Navigate the tab we already opened, with a cache-buster so it can't show a stale copy.
        const printUrl = `/admin/inspection/${currentItemNumber}/print?_=${Date.now()}`;
        if (printWindow) {
          printWindow.location.href = printUrl;
        } else {
          // Fallback in case the browser blocked even the synchronous open
          window.open(printUrl, '_blank');
        }

      } catch (e) {
        console.error('Fetch failed on printReport:', e);
        errEl.textContent = 'Network error: ' + e.message;
        errEl.classList.remove('hidden');
        if (printWindow) printWindow.close();
      }
    }

    // ── Notify Staff Modal ──
    function openNotifyModal(p) {
      notifyItem = p;
      document.getElementById('notifyPersonnelName').textContent =
        `${p.rank} ${p.lastName}, ${p.firstName}${p.middleName?' '+p.middleName.charAt(0)+'.':''}`;
      document.getElementById('notifySerial').textContent = p.afpSerialNumber || '—';
      document.getElementById('notifyPistol').textContent = p.pistolType || '—';
      document.getElementById('notifyMessage').value =
        `Please process the ICS renewal for ${p.rank} ${p.lastName}, ${p.firstName} (AFP Serial: ${p.afpSerialNumber||'N/A'}). Firearm has been cleared for renewal.`;
      document.getElementById('notifyError').classList.add('hidden');
      document.getElementById('notifySuccess').classList.add('hidden');
      document.getElementById('notifyModal').classList.add('open');
    }
    function closeNotifyModal() {
      document.getElementById('notifyModal').classList.remove('open');
      notifyItem = null;
    }
    async function sendNotify() {
      const errEl = document.getElementById('notifyError');
      const sucEl = document.getElementById('notifySuccess');
      errEl.classList.add('hidden'); sucEl.classList.add('hidden');
      const msg = document.getElementById('notifyMessage').value.trim();
      if (!msg) { errEl.textContent='Please enter a message.'; errEl.classList.remove('hidden'); return; }
      try {
        const res  = await fetch('/admin/inspection/notify-staff', {
          method:'POST',
          headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF},
          body:JSON.stringify({ itemNumber: notifyItem?.itemNumber, message: msg })
        });
        const data = await res.json();
        if (data.success) {
          sucEl.textContent='Staff notified successfully ✓'; sucEl.classList.remove('hidden');
          setTimeout(()=>{ closeNotifyModal(); loadData(); }, 1800);
        } else {
          errEl.textContent = data.error || 'Failed to send renewal.'; errEl.classList.remove('hidden');
        }
      } catch(e) {
        errEl.textContent='Network error. Please try again.'; errEl.classList.remove('hidden');
      }
    }
    document.getElementById('notifyModal').addEventListener('click',function(e){if(e.target===this)closeNotifyModal();});

    // ── Edit signatory ──
    function openEditSig(key) {
      document.getElementById('editSigKey').value=key;
      document.getElementById('editSigTitle').textContent={insp:'Inspected By',wit:'Witnessed By',app:'Approved By',not:'Noted By'}[key];
      document.getElementById('editSigName').value=sigData[key].name;
      document.getElementById('editSigRank').value=sigData[key].rank;
      document.getElementById('editSigPos').value=sigData[key].pos;
      document.getElementById('editSigFile').value='';
      document.getElementById('editSigModal').classList.add('open');
    }
    function closeEditSig() { document.getElementById('editSigModal').classList.remove('open'); }
    function saveEditSig() {
      const key=document.getElementById('editSigKey').value;
      sigData[key].name=document.getElementById('editSigName').value;
      sigData[key].rank=document.getElementById('editSigRank').value;
      sigData[key].pos=document.getElementById('editSigPos').value;
      const file=document.getElementById('editSigFile').files[0];
      if(file){const r=new FileReader();r.onload=e=>{sigData[key].img=e.target.result;renderSigs();};r.readAsDataURL(file);}
      renderSigs(); closeEditSig();
    }
    document.getElementById('editSigModal').addEventListener('click',function(e){if(e.target===this)closeEditSig();});

    // Sidebar toggle
    const sidebar    = document.getElementById('sidebar');
    const toggleBtn  = document.getElementById('sidebarToggleBtn');
    const sbIconMenu = document.getElementById('sb-icon-menu');
    const sbIconClose= document.getElementById('sb-icon-close');
    if (localStorage.getItem('sb') === '1') {
      sidebar.classList.add('sidebar-collapsed');
      sbIconMenu.style.display  = '';
      sbIconClose.style.display = 'none';
    } else {
      sbIconMenu.style.display  = 'none';
      sbIconClose.style.display = '';
    }
    toggleBtn.addEventListener('click', function () {
      const collapsed = sidebar.classList.toggle('sidebar-collapsed');
      localStorage.setItem('sb', collapsed ? '1' : '0');
      sbIconMenu.style.display  = collapsed ? '' : 'none';
      sbIconClose.style.display = collapsed ? 'none' : '';
    });

    // Theme toggle
    const iconSun  = document.getElementById('icon-sun');
    const iconMoon = document.getElementById('icon-moon');
    function applyTheme(t) {
      document.body.classList.toggle('light-mode', t === 'light');
      iconSun.style.display  = t === 'light' ? 'none' : '';
      iconMoon.style.display = t === 'light' ? '' : 'none';
    }
    applyTheme(localStorage.getItem('theme') || 'dark');
    document.getElementById('themeToggle').addEventListener('click', function () {
      const next = localStorage.getItem('theme') === 'light' ? 'dark' : 'light';
      localStorage.setItem('theme', next);
      applyTheme(next);
    });

    loadData();
  </script>
</body>
</html>
