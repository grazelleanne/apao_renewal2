<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Admin | Reports</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: { sidebarBg: '#181c21', sidebarDark: '#23272f', accent: '#3ec6ff', mainBg: '#1a2025' },
          fontFamily: { inter: ['Inter', 'system-ui', 'sans-serif'] }
        }
      }
    }
  </script>
  <style>
    /* ===== SIDEBAR ===== */
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
    .nav-item{display:flex;align-items:center;gap:10px;padding:9px 10px;border-radius:8px;color:#94a3b8;text-decoration:none;font-size:0.85rem;font-weight:500;white-space:nowrap;transition:background 0.15s,color 0.15s;overflow:hidden;}
    .nav-item:hover{background:#23272f;color:#e5eaf2;}
    .nav-item.active{background:#23272f;color:#e5eaf2;}
    .nav-item svg{width:20px;height:20px;flex-shrink:0;}
    .nav-label{transition:opacity 0.15s,width 0.15s;overflow:hidden;white-space:nowrap;}
    #sidebar.sidebar-collapsed .nav-label{opacity:0;width:0;}
    #sidebar.sidebar-collapsed .nav-item{justify-content:center;padding:9px 0;gap:0;}
    .sb-bottom{padding-top:12px;border-top:1px solid #23272f;display:flex;justify-content:center;}
    .theme-btn{background:#23272f;border:none;color:#94a3b8;cursor:pointer;padding:8px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:background 0.15s,color 0.15s;}
    .theme-btn:hover{background:#2d3340;color:#e5eaf2;}

    /* ===== LIGHT MODE ===== */
    body.light-mode{background:#f1f5fa;color:#222;}
    body.light-mode .main-bg{background:#f1f5fa !important;}
    body.light-mode .bg-\[\#1a2025\]{background-color:#f1f5fa !important;}
    body.light-mode .bg-\[\#23272f\]{background-color:#e6eaf4 !important;}
    body.light-mode #sidebar{background:#f7fafc;border-color:#e2e8f0;}
    body.light-mode .nav-item{color:#64748b;}
    body.light-mode .nav-item:hover,body.light-mode .nav-item.active{background:#e8edf5;color:#1e293b;}
    body.light-mode .sb-logo-text{color:#1e293b;}
    body.light-mode .sb-bottom{border-color:#e2e8f0;}
    body.light-mode .theme-btn{background:#e8edf5;color:#64748b;}
    body.light-mode .card-main-text{color:#176146 !important;}
    body.light-mode .card-label{color:#36a380 !important;}
    body.light-mode .card-desc{color:#25775c !important;}
    body.light-mode .force-light-text{color:#222 !important;}
    body.light-mode input,body.light-mode select{background:#f3f7fa !important;color:#232634 !important;border-color:#cdd5ea !important;}
    body.light-mode .rounded-lg{background:#f8fafc !important;}
   body.light-mode .badge-renewed{color:#176146!important;}
    body.light-mode .badge-expired{color:#c53030!important;}
    body.light-mode .badge-within{color:#92400e!important;}

    /* ===== CARDS ===== */
    .card-main-text{color:#33b481 !important;font-weight:bold;}
    .card-label{color:#228b68 !important;font-weight:600;}
    .card-desc{color:#89e2bc !important;}
    .force-light-text{color:#e5eaf2;}

    /* ===== NOTIFICATION BELL ===== */
    .notification-bell{position:relative;cursor:pointer;outline:none;transition:color 0.2s ease;}
    .notification-badge{position:absolute;top:-4px;right:-4px;background:#ef4444;color:white;font-weight:bold;border-radius:50%;font-size:0.62rem;min-width:17px;height:17px;display:none;align-items:center;justify-content:center;border:2px solid #1a2025;animation:bell-pop 0.3s ease-out;}
    .notification-bell.has-unread .notification-badge{display:flex;}
    @keyframes bell-pop{0%{transform:scale(0);opacity:0}50%{transform:scale(1.2)}100%{transform:scale(1);opacity:1}}
    #adminNotifDropdown{display:none;position:absolute;top:calc(100% + 10px);right:0;width:320px;background:#23272f;border:1px solid #363b48;border-radius:12px;box-shadow:0 12px 32px rgba(0,0,0,0.4);z-index:200;overflow:hidden;}
    #adminNotifDropdown.open{display:block;animation:fadeDown 0.18s ease;}
    @keyframes fadeDown{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
    .notif-header{padding:12px 16px;border-bottom:1px solid #363b48;display:flex;justify-content:space-between;align-items:center;font-size:0.8rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;}
    .notif-mark-read{font-size:0.72rem;color:#3ec6ff;cursor:pointer;font-weight:600;text-transform:none;letter-spacing:0;}
    .notif-mark-read:hover{text-decoration:underline;}
    .notif-list{max-height:340px;overflow-y:auto;}
    .notif-list::-webkit-scrollbar{width:4px;}
    .notif-list::-webkit-scrollbar-track{background:#1a2025;}
    .notif-list::-webkit-scrollbar-thumb{background:#363b48;border-radius:4px;}
    .notif-item{padding:12px 16px;border-bottom:1px solid #2e333d;display:flex;gap:10px;align-items:flex-start;font-size:0.8rem;color:#cbd5e0;transition:background 0.15s;}
    .notif-item:last-child{border-bottom:none;}
    .notif-item.unread{background:#1c2631;}
    .notif-item:hover{background:#1a2025;}
    .notif-icon{flex-shrink:0;margin-top:2px;}
    .notif-content{flex:1;min-width:0;}
    .notif-title{font-weight:700;font-size:0.78rem;color:#e5eaf2;margin-bottom:2px;}
    .notif-message{color:#94a3b8;line-height:1.4;font-size:0.75rem;}
    .notif-time{color:#4b5563;font-size:0.7rem;margin-top:4px;}
    .notif-dot{width:7px;height:7px;border-radius:50%;background:#3ec6ff;flex-shrink:0;margin-top:5px;}
    .notif-empty{padding:24px 16px;text-align:center;color:#4b5563;font-size:0.8rem;}
    .notif-footer{padding:10px 16px;border-top:1px solid #363b48;text-align:center;font-size:0.72rem;color:#4b5563;}
    body.light-mode .notification-bell{color:#0284c7 !important;}
    body.light-mode #adminNotifDropdown{background:#fff;border-color:#d0d7e4;}
    body.light-mode .notif-title{color:#1e293b!important;}
    body.light-mode .notif-message{color:#475569!important;}
    body.light-mode .notif-time{color:#94a3b8!important;}
    body.light-mode .notif-empty{color:#94a3b8!important;}
    body.light-mode .notif-footer{color:#64748b!important;}
    body.light-mode .notif-header{color:#475569!important;}
   body.light-mode .notif-item{color:#1e293b!important;background:#ffffff!important;}
    body.light-mode .notif-item.unread{background:#eff6ff!important;}
    body.light-mode .notif-item:hover{background:#f8fafc!important;}
    body.light-mode .notif-item.unread:hover{background:#e0f0ff!important;}
    body.light-mode #adminNotifDropdown .notif-list::-webkit-scrollbar-track{background:#f1f5f9!important;}

    /* ===== STATUS BADGES ===== */
  .badge-renewed{color:#33b481;font-weight:700;}
    .badge-expired{color:#e53e3e;font-weight:700;}
    .badge-within{color:#ecc94b;font-weight:700;}
   .report-summary-row td{border-bottom:1px solid #2a2f3a;}
    body.light-mode .report-summary-row td{border-bottom:1px solid #d1d9e6;}
    body.light-mode .border-\[\#2a2f3a\]{border-color:#e2e8f0!important;}
    body.light-mode .hover\:bg-\[\#1e2329\]:hover{background:#f8fafc!important;}
  </style>
</head>
<body class="min-h-screen font-inter main-bg bg-[#1a2025]">
<div class="flex min-h-screen">

  <!-- SIDEBAR -->
  <aside id="sidebar">
    <div class="sb-top">
      <div class="sb-logo">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" onerror="this.src=''">
        <span class="sb-logo-text">Admin</span>
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
      <a href="{{ route('admin.inspection') }}" class="nav-item">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="nav-label">Inspection/Renewal</span>
      </a>
      <a href="{{ route('admin.reports') }}" class="nav-item active">
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
  <main class="flex-1 main-bg bg-[#1a2025] p-7 overflow-y-auto">

    <header class="flex flex-wrap justify-between mb-8 items-center gap-4">
      <h1 class="text-2xl font-bold tracking-tight force-light-text">Reports</h1>
      <div class="flex items-center gap-4">
        <span class="text-sm force-light-text opacity-70">Welcome, <strong>{{ $user->name ?? 'Admin' }}</strong></span>

        <!-- NOTIFICATION BELL -->
        <div class="relative" id="adminNotifWrapper">
          <button id="notificationBell" class="notification-bell text-cyan-400 focus:outline-none" aria-label="Notifications" type="button">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11c0-3.074-1.64-5.64-5-5.996V5a2 2 0 10-4 0v.004C6.64 5.36 5 7.926 5 11v3.159c0 .538-.214 1.055-.595 1.436L3 17h5m7 0v1a3 3 0 01-6 0v-1m7 0H8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="notification-badge" id="notificationBadge"></span>
          </button>
          <div id="adminNotifDropdown">
            <div class="notif-header">
              <span>Notifications</span>
              <span class="notif-mark-read" id="adminMarkAllRead">Mark all read</span>
            </div>
            <div class="notif-list" id="adminNotifList"><div class="notif-empty">Loading...</div></div>
            <div class="notif-footer" id="adminNotifFooter">Auto-refreshes every 30 seconds</div>
          </div>
        </div>

        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="text-red-400 hover:underline text-base font-semibold tracking-tight">Logout</button>
        </form>
      </div>
    </header>

    <!-- Report Filter -->
    <section class="mb-6">
      <div class="bg-[#23272f] rounded-lg p-6 shadow shadow-black/10 flex flex-col gap-3">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <p class="text-lg font-semibold force-light-text mb-2">Generate &amp; Export Personnel Renewal Reports</p>
          <form class="flex flex-wrap gap-2 items-center" id="reportFilterForm" onsubmit="return false;">
            <label for="reportPeriod" class="text-xs force-light-text mr-2">Period:</label>
            <select id="reportPeriod" class="bg-[#23272f] text-white border border-[#363b48] rounded px-3 py-1 text-xs force-light-text">
              <option value="this-year">This Year</option>
              <option value="last-year">Last Year</option>
              <option value="custom">Custom</option>
            </select>
            <input type="date" id="customStart" class="hidden bg-[#23272f] text-white border border-[#363b48] rounded px-3 py-1 text-xs" />
            <span id="customDash" class="hidden text-gray-400">–</span>
            <input type="date" id="customEnd" class="hidden bg-[#23272f] text-white border border-[#363b48] rounded px-3 py-1 text-xs" />
            <button type="submit" class="bg-accent hover:bg-cyan-500 text-white rounded px-4 py-1 ml-2 text-sm font-semibold">Preview</button>
            <button type="button" id="downloadReportBtn" class="bg-green-500 hover:bg-green-600 text-white rounded px-4 py-1 ml-2 text-sm font-semibold">Download PDF</button>
            <button type="button" id="exportExcelBtn" class="bg-amber-500 hover:bg-amber-600 text-white rounded px-4 py-1 ml-2 text-sm font-semibold">Export Excel</button>
          </form>
        </div>
        <div class="text-xs text-[#b0bac7] force-light-text">Choose the period and click "Preview" to filter the table, or download/export for sharing.</div>
      </div>
    </section>

    <!-- Metrics -->
    <div class="grid lg:grid-cols-4 md:grid-cols-2 grid-cols-1 gap-6 mb-7">
      <div class="bg-[#23272f] rounded-lg p-6 shadow shadow-black/10 flex flex-col gap-2">
        <div class="text-xs uppercase font-semibold card-label tracking-wide mb-1">Total Personnel</div>
        <p id="totalUsers" class="text-3xl font-extrabold card-main-text">0</p>
        <p class="text-xs card-desc">Personnel managed by the platform.</p>
      </div>
      <div class="bg-[#23272f] rounded-lg p-6 shadow shadow-black/10 flex flex-col gap-2">
        <div class="text-xs uppercase font-semibold card-label tracking-wide mb-1">Renewed</div>
        <p id="totalRenewed" class="text-3xl font-extrabold card-main-text">--</p>
        <p class="text-xs card-desc">Personnel with up-to-date renewal.</p>
      </div>
      <div class="bg-[#23272f] rounded-lg p-6 shadow shadow-black/10 flex flex-col gap-2">
        <div class="text-xs uppercase font-semibold card-label tracking-wide mb-1">Within Renewal</div>
        <p id="withinRenewal" class="text-3xl font-extrabold card-main-text">--</p>
        <p class="text-xs card-desc">Personnel within current renewal window.</p>
      </div>
      <div class="bg-[#23272f] rounded-lg p-6 shadow shadow-black/10 flex flex-col gap-2">
        <div class="text-xs uppercase font-semibold card-label tracking-wide mb-1">Expired</div>
        <p id="expired" class="text-3xl font-extrabold card-main-text">--</p>
        <p class="text-xs card-desc">Personnel with expired renewal.</p>
      </div>
    </div>

    <!-- Report Summary -->
    <div class="bg-[#23272f] rounded-lg p-6 shadow shadow-black/10 mb-7">
      <h3 class="font-semibold text-base force-light-text tracking-tight mb-4">Report Summary</h3>
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead>
            <tr class="border-b border-[#363b48]">
              <th class="py-2 px-4 font-semibold force-light-text">Status</th>
              <th class="py-2 px-4 font-semibold force-light-text">Count</th>
              <th class="py-2 px-4 font-semibold force-light-text">Percentage</th>
              <th class="py-2 px-4 font-semibold force-light-text">Description</th>
            </tr>
          </thead>
          <tbody>
            <tr class="report-summary-row">
              <td class="py-3 px-4"><span class="badge-renewed text-xs font-semibold">● Renewed</span></td>
              <td class="py-3 px-4 font-bold card-main-text" id="summaryRenewed">--</td>
              <td class="py-3 px-4 force-light-text" id="summaryRenewedPct">--%</td>
              <td class="py-3 px-4 text-xs card-desc">Validity date is more than 90 days away.</td>
            </tr>
            <tr class="report-summary-row">
              <td class="py-3 px-4"><span class="badge-within text-xs font-semibold">⏱ Within Renewal</span></td>
              <td class="py-3 px-4 font-bold" style="color:#ecc94b;" id="summaryWithin">--</td>
              <td class="py-3 px-4 force-light-text" id="summaryWithinPct">--%</td>
              <td class="py-3 px-4 text-xs card-desc">Validity date is within 0–90 days.</td>
            </tr>
            <tr>
             <td class="py-3 px-4"><span class="badge-expired text-xs font-semibold">✕ Expired</span></td>
              <td class="py-3 px-4 font-bold" style="color:#e53e3e;" id="summaryExpired">--</td>
              <td class="py-3 px-4 force-light-text" id="summaryExpiredPct">--%</td>
              <td class="py-3 px-4 text-xs card-desc">Validity date has already passed.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Personnel Table -->
    <div class="bg-[#23272f] rounded-lg p-6 shadow shadow-black/10 mb-10">
      <div class="flex flex-wrap items-center justify-between mb-4 gap-2">
        <h2 class="font-semibold text-base force-light-text tracking-tight">Personnel Renewal List</h2>
        <div class="flex flex-wrap gap-2 items-center ml-auto">
          <label for="sortSelect" class="text-[#b0bac7] text-xs force-light-text">Sort by:</label>
          <select id="sortSelect" class="bg-[#23272f] text-white border border-[#363b48] rounded px-2 py-1 text-xs force-light-text">
            <option value="itemNumber-asc">Item # (Asc)</option>
            <option value="itemNumber-desc">Item # (Desc)</option>
            <option value="lastName-asc">Last Name (A-Z)</option>
            <option value="lastName-desc">Last Name (Z-A)</option>
            <option value="dateOfValidity-asc">Date of Validity (Earliest)</option>
            <option value="dateOfValidity-desc">Date of Validity (Latest)</option>
          </select>
          <label for="searchInput" class="text-[#b0bac7] text-xs force-light-text">Search:</label>
          <input id="searchInput" type="text" placeholder="Enter name..." class="bg-[#23272f] text-white border border-[#363b48] rounded px-2 py-1 text-xs force-light-text" />
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-xs text-left force-light-text">
          <thead>
            <tr class="border-b border-[#252b32] text-[#b0bac7]">
              <th class="py-2 px-2 font-semibold force-light-text">Item #</th>
              <th class="py-2 px-2 font-semibold force-light-text">Date of Validity</th>
              <th class="py-2 px-2 font-semibold force-light-text">Status</th>
              <th class="py-2 px-2 font-semibold force-light-text">Last Name</th>
              <th class="py-2 px-2 font-semibold force-light-text">First Name</th>
              <th class="py-2 px-2 font-semibold force-light-text">Middle Name</th>
              <th class="py-2 px-2 font-semibold force-light-text">AFP Serial #</th>
              <th class="py-2 px-2 font-semibold force-light-text">Date of Birth</th>
              <th class="py-2 px-2 font-semibold force-light-text">Nomenclature of Pistol</th>
              <th class="py-2 px-2 font-semibold force-light-text">Pistol Serial #</th>
              <th class="py-2 px-2 font-semibold force-light-text">Qty Ammo</th>
            </tr>
          </thead>
          <tbody id="personnelTableBody"></tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const ROUTES = {
  personnelData: "{{ route('admin.personnel.data') }}",
  login:         "{{ route('login') }}",
};

document.addEventListener("DOMContentLoaded", function () {

  // ===== SIDEBAR =====
  const sidebar     = document.getElementById('sidebar');
  const toggleBtn   = document.getElementById('sidebarToggleBtn');
  const sbIconMenu  = document.getElementById('sb-icon-menu');
  const sbIconClose = document.getElementById('sb-icon-close');
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

  // ===== THEME =====
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

  // ===== NOTIFICATION BELL =====
  const bell        = document.getElementById('notificationBell');
  const badge       = document.getElementById('notificationBadge');
  const dropdown    = document.getElementById('adminNotifDropdown');
  const notifList   = document.getElementById('adminNotifList');
  const notifFooter = document.getElementById('adminNotifFooter');
  const markAllRead = document.getElementById('adminMarkAllRead');
  const ADMIN_NOTIF_URL      = "{{ route('admin.notifications') }}";
  const ADMIN_NOTIF_READ_URL = "{{ route('admin.notifications.read') }}";

  function timeAgo(dateStr) {
    const diff = Math.floor((new Date() - new Date(dateStr)) / 1000);
    if (diff < 60)    return "Just now";
    if (diff < 3600)  return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return new Date(dateStr).toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
  }

  function getNotifIcon(type) {
    const icons = {
      approval_changed:   `<svg class="w-4 h-4" style="color:#3ec6ff" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
      email_sent:         `<svg class="w-4 h-4" style="color:#33b481" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>`,
      personnel_added:    `<svg class="w-4 h-4" style="color:#33b481" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M12 4v16m8-8H4"/></svg>`,
      personnel_updated:  `<svg class="w-4 h-4" style="color:#ecc94b" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>`,
      personnel_archived: `<svg class="w-4 h-4" style="color:#fc8181" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>`,
    };
    return icons[type] || `<svg class="w-4 h-4" style="color:#94a3b8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/></svg>`;
  }

  async function loadNotifications() {
    try {
      const res  = await fetch(ADMIN_NOTIF_URL, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
      const json = await res.json();
      if (!json.success) return;
      const count = json.unreadCount || 0;
      if (count > 0) {
        bell.classList.add('has-unread');
        badge.style.display = 'flex';
        badge.textContent   = count > 99 ? '99+' : String(count);
      } else {
        bell.classList.remove('has-unread');
        badge.style.display = 'none';
      }
      notifFooter.textContent = count > 0 ? `${count} unread notification${count > 1 ? 's' : ''}` : 'All caught up!';
      if (!json.notifications || !json.notifications.length) {
        notifList.innerHTML = `<div class="notif-empty">No notifications yet.</div>`;
        return;
      }
      notifList.innerHTML = json.notifications.map(n => `
        <div class="notif-item ${!n.read ? 'unread' : ''}">
          <div class="notif-icon">${getNotifIcon(n.type)}</div>
          <div class="notif-content">
            <div class="notif-title">${n.title}</div>
            <div class="notif-message">${n.message}</div>
            <div class="notif-time">${timeAgo(n.createdAt)}</div>
          </div>
          ${!n.read ? `<div class="notif-dot"></div>` : ''}
        </div>`).join('');
    } catch (e) {
      notifList.innerHTML = `<div class="notif-empty" style="color:#fc8181;">Failed to load notifications.</div>`;
    }
  }

  bell.addEventListener('click', function (e) {
    e.stopPropagation();
    const isOpen = dropdown.classList.contains('open');
    dropdown.classList.toggle('open');
    if (!isOpen) {
      bell.classList.remove('has-unread');
      badge.style.display = 'none';
      badge.textContent = '';
      fetch(ADMIN_NOTIF_READ_URL, { method:'POST', headers:{ 'X-CSRF-TOKEN':CSRF, 'Accept':'application/json' } }).finally(loadNotifications);
    }
  });
  document.addEventListener('click', function (e) {
    if (!document.getElementById('adminNotifWrapper').contains(e.target)) {
      dropdown.classList.remove('open');
    }
  });
  markAllRead.addEventListener('click', async function () {
    try {
      await fetch(ADMIN_NOTIF_READ_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
      await loadNotifications();
      dropdown.classList.remove('open');
    } catch (e) {}
  });
  loadNotifications();
  setInterval(loadNotifications, 30000);

  // ===== REPORT PERIOD CONTROLS =====
  const periodSelect = document.getElementById("reportPeriod");
  const customStart  = document.getElementById("customStart");
  const customEnd    = document.getElementById("customEnd");
  const customDash   = document.getElementById("customDash");
  periodSelect.addEventListener("change", function () {
    if (this.value === "custom") {
      customStart.classList.remove("hidden"); customEnd.classList.remove("hidden"); customDash.classList.remove("hidden");
    } else {
      customStart.classList.add("hidden"); customEnd.classList.add("hidden"); customDash.classList.add("hidden");
      customStart.value = ""; customEnd.value = "";
    }
  });

  // ===== STATUS HELPERS (matches admin personnel page logic) =====

  function getValidityStatus(dateOfValidity) {
    if (!dateOfValidity) return 'pending';
    const currDate     = new Date();
    const validityDate = new Date(dateOfValidity);
    const diffDays     = Math.ceil((validityDate - currDate) / (1000 * 60 * 60 * 24));
    if (diffDays > 90) return 'renewed';
    if (diffDays >= 0) return 'within';
    return 'expired';
  }

  // Manual approvedStatus overrides date-based status (unless it's 'pending' or empty)
  function resolveStatus(p) {
    const manual = (p.approvedStatus || '').trim().toLowerCase();
    if (manual && manual !== 'pending') return manual;
    return getValidityStatus(p.dateOfValidity);
  }

 function statusBadgeHtml(p) {
    const s = resolveStatus(p);
    if (s === 'renewed') return `<span class="badge-renewed text-xs font-semibold">● Renewed</span>`;
    if (s === 'within')  return `<span class="badge-within text-xs font-semibold">⏱ Within Renewal</span>`;
    if (s === 'expired') return `<span class="badge-expired text-xs font-semibold">✕ Expired</span>`;
    return `<span class="text-xs font-semibold" style="color:#64748b;">— Pending —</span>`;
  }

  // ===== PERSONNEL DATA =====
  let allPersonnel = [];
  let currentSort  = "itemNumber-asc";

  async function loadPersonnel() {
    try {
      const res  = await fetch(ROUTES.personnelData);
      const json = await res.json();
      allPersonnel = (json.success && Array.isArray(json.data)) ? json.data : [];
    } catch (e) { allPersonnel = []; }
    renderPersonnelTable(allPersonnel);
  }

  function updateMetrics(filtered) {
    let renewed = 0, withinRenewal = 0, expired = 0;
    filtered.forEach(p => {
      const s = resolveStatus(p);
      if (s === 'renewed') renewed++;
      else if (s === 'within') withinRenewal++;
      else if (s === 'expired') expired++;
    });
    const total = filtered.length;
    document.getElementById("totalUsers").innerText    = total;
    document.getElementById("totalRenewed").innerText  = renewed;
    document.getElementById("withinRenewal").innerText = withinRenewal;
    document.getElementById("expired").innerText       = expired;
    const pct = (n) => total ? Math.round((n / total) * 100) + '%' : '--';
    document.getElementById("summaryRenewed").innerText    = renewed;
    document.getElementById("summaryWithin").innerText     = withinRenewal;
    document.getElementById("summaryExpired").innerText    = expired;
    document.getElementById("summaryRenewedPct").innerText = pct(renewed);
    document.getElementById("summaryWithinPct").innerText  = pct(withinRenewal);
    document.getElementById("summaryExpiredPct").innerText = pct(expired);
  }

  function sortPersonnel(list, sortBy) {
    const [key, dir] = sortBy.split("-");
    return list.slice().sort((a, b) => {
      let aVal = a[key], bVal = b[key];
      if (key === "itemNumber" || key === "qtyAmmo") { aVal = Number(aVal); bVal = Number(bVal); }
      else if (key !== "dateOfValidity" && key !== "dateOfBirth") { aVal = (aVal||"").toString().toLowerCase(); bVal = (bVal||"").toString().toLowerCase(); }
      if (aVal < bVal) return dir === "asc" ? -1 : 1;
      if (aVal > bVal) return dir === "asc" ? 1 : -1;
      return 0;
    });
  }

  function renderPersonnelTable(data) {
    let displayed = (data || allPersonnel).slice();
    const val = (document.getElementById("searchInput")?.value?.trim().toLowerCase()) || "";
    if (val) displayed = displayed.filter(p =>
      p.firstName?.toLowerCase().includes(val) ||
      p.middleName?.toLowerCase().includes(val) ||
      p.lastName?.toLowerCase().includes(val)
    );
    displayed = sortPersonnel(displayed, currentSort);
    const tbody = document.getElementById("personnelTableBody");
    tbody.innerHTML = "";
    if (!displayed.length) {
      tbody.innerHTML = `<tr><td colspan="11" class="text-center py-4 text-gray-400 force-light-text">No data found.</td></tr>`;
      updateMetrics([]);
      return;
    }
    displayed.forEach(row => {
      tbody.innerHTML += `<tr class="border-b border-[#2a2f3a] hover:bg-[#1e2329] transition-colors">
        <td class="py-2 px-2 force-light-text">${row.itemNumber||''}</td>
        <td class="py-2 px-2 force-light-text">${row.dateOfValidity||''}</td>
        <td class="py-2 px-2">${statusBadgeHtml(row)}</td>
        <td class="py-2 px-2 force-light-text">${row.lastName||''}</td>
        <td class="py-2 px-2 force-light-text">${row.firstName||''}</td>
        <td class="py-2 px-2 force-light-text">${row.middleName||''}</td>
        <td class="py-2 px-2 force-light-text">${row.afpSerialNumber||''}</td>
        <td class="py-2 px-2 force-light-text">${row.dateOfBirth||''}</td>
        <td class="py-2 px-2 force-light-text">${row.pistolNomenclature||''}</td>
        <td class="py-2 px-2 force-light-text">${row.pistolSerialNumber||''}</td>
        <td class="py-2 px-2 force-light-text">${row.qtyAmmo||0}</td>
      </tr>`;
    });
    updateMetrics(displayed);
  }

  document.getElementById("searchInput").addEventListener("input", () => renderPersonnelTable());
  document.getElementById("sortSelect").addEventListener("change", function (e) { currentSort = e.target.value; renderPersonnelTable(); });

  // ===== PERIOD FILTER =====
  document.getElementById("reportFilterForm").addEventListener("submit", function (e) {
    e.preventDefault();
    let filtered = allPersonnel.slice();
    const period = periodSelect.value;
    const today  = new Date();
    if (period === "this-year") {
      filtered = filtered.filter(p => (new Date(p.dateOfValidity)).getFullYear() === today.getFullYear());
    } else if (period === "last-year") {
      filtered = filtered.filter(p => (new Date(p.dateOfValidity)).getFullYear() === today.getFullYear() - 1);
    } else if (period === "custom") {
      const from = customStart.value ? new Date(customStart.value) : null;
      const to   = customEnd.value   ? new Date(customEnd.value)   : null;
      filtered = filtered.filter(p => {
        const d = new Date(p.dateOfValidity);
        let ok = true;
        if (from) ok = ok && (d >= from);
        if (to)   ok = ok && (d <= to);
        return ok;
      });
    }
    renderPersonnelTable(filtered);
  });

  document.getElementById("downloadReportBtn").onclick = function () { const rows = document.querySelectorAll("#personnelTableBody tr"); if (!rows.length || (rows.length === 1 && rows[0].querySelector("td[colspan]"))) { alert("No data. Click Preview first."); return; } const headers = ["Item #","Date of Validity","Status","Last Name","First Name","Middle Name","AFP Serial #","Date of Birth","Nomenclature of Pistol","Pistol Serial #","Qty Ammo"]; let tableRows = ""; rows.forEach(row => { const cells = row.querySelectorAll("td"); if (!cells.length) return; let r = ""; cells.forEach(cell => { r += `<td style="border:1px solid #ccc;padding:5px 8px;font-size:11px;">${cell.innerText.trim()}</td>`; }); tableRows += `<tr>${r}</tr>`; }); const today = new Date().toLocaleDateString(); const win = window.open("","_blank"); win.document.write(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>APAO Report</title><style>body{font-family:Arial;margin:30px;}h2{text-align:center;}table{width:100%;border-collapse:collapse;}th{background:#1a3a2a;color:#fff;padding:6px;font-size:11px;border:1px solid #ccc;text-align:left;}</style></head><body><h2>ARMY PROPERTY ACCOUNTABILITY OFFICE</h2><h2>Personnel Renewal Report</h2><p style="text-align:center;font-size:12px;color:#555;">Generated: ${today}</p><table><thead><tr>${headers.map(h=>"<th>"+h+"</th>").join("")}</tr></thead><tbody>${tableRows}</tbody></table></body></html>`); win.document.close(); setTimeout(()=>{win.focus();win.print();},400); };
  document.getElementById("exportExcelBtn").onclick = function () { const rows = document.querySelectorAll("#personnelTableBody tr"); if (!rows.length || (rows.length === 1 && rows[0].querySelector("td[colspan]"))) { alert("No data. Click Preview first."); return; } const headers = ["Item #","Date of Validity","Status","Last Name","First Name","Middle Name","AFP Serial #","Date of Birth","Nomenclature of Pistol","Pistol Serial #","Qty Ammo"]; let csv = headers.map(h => `"${h}"`).join(",") + "\n"; rows.forEach(row => { const cells = row.querySelectorAll("td"); if (!cells.length) return; csv += Array.from(cells).map(c => `"${c.innerText.trim().replace(/"/g,"\"\"")}"` ).join(",") + "\n"; }); const blob = new Blob(["\uFEFF"+csv],{type:"text/csv;charset=utf-8;"}); const url = URL.createObjectURL(blob); const a = document.createElement("a"); a.href=url; a.download="APAO_Report_"+new Date().toISOString().slice(0,10)+".csv"; a.click(); URL.revokeObjectURL(url); };

  loadPersonnel();
});
</script>
</body>
</html>

