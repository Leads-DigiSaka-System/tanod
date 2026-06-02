<template>
  <AppLayout>
    <Head title="Dashboard" />

    <!-- Page header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Welcome back, {{ $page.props.auth?.user?.name }} · {{ todayDate }}</p>
      </div>
      <div class="flex items-center gap-3">
        <Link href="/reports/tractor-usage" class="inline-flex items-center gap-1.5 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 px-3 py-2 text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Reports
        </Link>
        <Link href="/live-view" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500 transition-colors shadow-sm">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v4m0 12v4M2 12h4m12 0h4"/></svg>
          Live View
        </Link>
      </div>
    </div>

    <!-- ═══════════ ADMIN / SUB-ADMIN DASHBOARD ═══════════ -->
    <template v-if="charts">

      <!-- KPI Row 1: Tractor Usage Summary (pill cards) -->
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5 mb-4">
        <!-- Total Tractors -->
        <div class="flex items-center gap-3 rounded-2xl bg-indigo-50 dark:bg-indigo-950/30 px-4 py-3.5 border border-indigo-200/60 dark:border-indigo-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Tractors</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ Number(stats.totalTractors).toLocaleString() }}</p>
            <p class="text-[11px] font-medium mt-0.5"><span class="text-green-600 dark:text-green-400">{{ stats.onlineTractors }} online</span><span class="text-gray-300 dark:text-gray-600 mx-1.5">·</span><span class="text-red-500 dark:text-red-400">{{ stats.offlineTractors }} offline</span></p>
          </div>
        </div>
        <!-- Total Distance -->
        <div class="flex items-center gap-3 rounded-2xl bg-blue-50 dark:bg-blue-950/30 px-4 py-3.5 border border-blue-200/60 dark:border-blue-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Distance</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ Number(stats.totalDistance || 0).toLocaleString(undefined, { maximumFractionDigits: 1 }) }} <span class="text-sm font-normal text-gray-400">km</span></p>
            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">Avg {{ Number(stats.avgDistancePerTractor || 0).toLocaleString(undefined, { maximumFractionDigits: 1 }) }} km / tractor</p>
          </div>
        </div>
        <!-- Running Hours -->
        <div class="flex items-center gap-3 rounded-2xl bg-cyan-50 dark:bg-cyan-950/30 px-4 py-3.5 border border-cyan-200/60 dark:border-cyan-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-cyan-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Running Hours</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ Number(stats.totalRunningHours || 0).toLocaleString(undefined, { maximumFractionDigits: 2 }) }} <span class="text-sm font-normal text-gray-400">hrs</span></p>
            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">Avg {{ Number(stats.avgHoursPerTractor || 0).toLocaleString(undefined, { maximumFractionDigits: 1 }) }} hrs / tractor · {{ Number(stats.totalMachineHours || 0).toLocaleString(undefined, { maximumFractionDigits: 0 }) }} total fleet</p>
          </div>
        </div>
        <!-- With Usage Data -->
        <div class="flex items-center gap-3 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 px-4 py-3.5 border border-emerald-200/60 dark:border-emerald-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">W/ Usage Data</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ Number(stats.tractorsWithUsageData || 0).toLocaleString() }} <span class="text-sm font-normal text-emerald-600 dark:text-emerald-400">{{ stats.usageDataPercent }}%</span></p>
            <div class="mt-1 h-1 w-full rounded-full bg-emerald-200 dark:bg-emerald-800"><div class="h-1 rounded-full bg-emerald-500 transition-all duration-500" :style="{ width: stats.usageDataPercent + '%' }"></div></div>
          </div>
        </div>
        <!-- PMS Due -->
        <div class="flex items-center gap-3 rounded-2xl bg-orange-50 dark:bg-orange-950/30 px-4 py-3.5 border border-orange-200/60 dark:border-orange-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-orange-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">PMS Due</p>
            <p class="text-xl font-bold text-orange-600 dark:text-orange-400">{{ stats.pmsDue }}</p>
            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">{{ Number(stats.totalMaintenanceRecords || 0).toLocaleString() }} maintenance records</p>
          </div>
        </div>
      </div>

      <!-- KPI Row 2: Operational Stats (pill cards) -->
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-7 mb-6">
        <div class="flex items-center gap-2.5 rounded-2xl bg-indigo-50 dark:bg-indigo-950/30 px-3.5 py-3 border border-indigo-200/60 dark:border-indigo-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
          <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Offline</p>
            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ stats.offlineTractors }} <span class="text-[10px] font-medium text-gray-400 dark:text-gray-500">tractors</span></p>
            <div class="flex flex-wrap gap-x-2 gap-y-0.5 mt-0.5">
              <span v-if="stats.offlineLessThanDay" class="text-[10px] font-semibold text-red-500 dark:text-red-400">{{ stats.offlineLessThanDay }} &lt;1d</span>
              <span v-if="stats.offline1to7Days" class="text-[10px] font-semibold text-amber-500 dark:text-amber-400">{{ stats.offline1to7Days }} &lt;7d</span>
              <span v-if="stats.offline7to30Days" class="text-[10px] font-semibold text-yellow-600 dark:text-yellow-400">{{ stats.offline7to30Days }} &lt;30d</span>
              <span v-if="stats.offline30to100Days" class="text-[10px] font-semibold text-orange-500 dark:text-orange-400">{{ stats.offline30to100Days }} &lt;100d</span>
              <span v-if="stats.offlineMoreThan100Days" class="text-[10px] font-semibold text-gray-400 dark:text-gray-500">{{ stats.offlineMoreThan100Days }} 100d+</span>
            </div>
          </div>
        </div>
        <div class="flex items-center gap-2.5 rounded-2xl bg-green-50 dark:bg-green-950/30 px-3.5 py-3 border border-green-200/60 dark:border-green-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
          <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 21h8M12 17v4"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Devices</p>
            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ stats.totalDevices }} <span class="text-xs font-medium text-green-600 dark:text-green-400">{{ stats.onlineDevices }} on</span></p>
          </div>
        </div>
        <div class="flex items-center gap-2.5 rounded-2xl bg-violet-50 dark:bg-violet-950/30 px-3.5 py-3 border border-violet-200/60 dark:border-violet-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
          <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-violet-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Users</p>
            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ Number(stats.totalUsers).toLocaleString() }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2.5 rounded-2xl bg-yellow-50 dark:bg-yellow-950/30 px-3.5 py-3 border border-yellow-200/60 dark:border-yellow-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
          <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-yellow-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Bookings</p>
            <p class="text-lg font-bold text-yellow-600 dark:text-yellow-400">{{ stats.pendingBookings }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2.5 rounded-2xl bg-orange-50 dark:bg-orange-950/30 px-3.5 py-3 border border-orange-200/60 dark:border-orange-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
          <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">PMS</p>
            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ stats.pmsDue }} <span class="text-[10px] font-medium text-gray-400 dark:text-gray-500"><span class="text-orange-500">due</span> · {{ stats.pmsOk }} ok</span></p>
          </div>
        </div>
        <div class="flex items-center gap-2.5 rounded-2xl bg-red-50 dark:bg-red-950/30 px-3.5 py-3 border border-red-200/60 dark:border-red-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
          <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Alerts</p>
            <p class="text-lg font-bold text-red-600 dark:text-red-400">{{ stats.unacknowledgedAlerts }} <span class="text-[10px] font-medium text-gray-400 dark:text-gray-500">/ {{ stats.totalAlerts }}</span></p>
          </div>
        </div>
        <div class="flex items-center gap-2.5 rounded-2xl bg-sky-50 dark:bg-sky-950/30 px-3.5 py-3 border border-sky-200/60 dark:border-sky-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
          <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-sky-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Geo-Fences</p>
            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ stats.totalGeoFences }} <span class="text-[10px] font-medium text-green-600 dark:text-green-400">{{ stats.activeGeoFences }} active</span></p>
          </div>
        </div>
      </div>

      <!-- Charts Row 1: Tractor Status Donut + Alerts Trend -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
        <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 border border-gray-200/70 dark:border-gray-700/50 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)]">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Tractor Status</h3>
            <span class="text-xs text-gray-400 dark:text-gray-500">{{ stats.totalTractors }} total</span>
          </div>
          <div class="flex gap-5">
            <!-- Donut chart: 2/3 -->
            <div class="w-2/3">
              <apexchart type="donut" height="280" :options="tractorDonutOptions" :series="tractorDonutSeries" />
            </div>
            <!-- Offline breakdown: 1/3 -->
            <div v-if="stats.offlineTractors > 0" class="w-1/3 border-l border-gray-100 dark:border-gray-700 pl-5 flex flex-col justify-center">
              <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3">Offline by Duration</p>
              <div class="space-y-2.5">
                <div v-if="stats.offlineLessThanDay" class="flex items-center justify-between text-xs">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500 shrink-0"></span>
                    <span class="text-gray-500 dark:text-gray-400 truncate">&lt; 1 day</span>
                  </div>
                  <span class="font-semibold text-red-600 dark:text-red-400 ml-2 shrink-0">{{ stats.offlineLessThanDay }}</span>
                </div>
                <div v-if="stats.offline1to7Days" class="flex items-center justify-between text-xs">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                    <span class="text-gray-500 dark:text-gray-400 truncate">1 – 7 days</span>
                  </div>
                  <span class="font-semibold text-amber-600 dark:text-amber-400 ml-2 shrink-0">{{ stats.offline1to7Days }}</span>
                </div>
                <div v-if="stats.offline7to30Days" class="flex items-center justify-between text-xs">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="w-2.5 h-2.5 rounded-full bg-yellow-600 shrink-0"></span>
                    <span class="text-gray-500 dark:text-gray-400 truncate">7 – 30 days</span>
                  </div>
                  <span class="font-semibold text-yellow-700 dark:text-yellow-400 ml-2 shrink-0">{{ stats.offline7to30Days }}</span>
                </div>
                <div v-if="stats.offline30to100Days" class="flex items-center justify-between text-xs">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="w-2.5 h-2.5 rounded-full bg-orange-400 shrink-0"></span>
                    <span class="text-gray-500 dark:text-gray-400 truncate">30 – 100 days</span>
                  </div>
                  <span class="font-semibold text-orange-600 dark:text-orange-400 ml-2 shrink-0">{{ stats.offline30to100Days }}</span>
                </div>
                <div v-if="stats.offlineMoreThan100Days" class="flex items-center justify-between text-xs">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="w-2.5 h-2.5 rounded-full bg-gray-400 shrink-0"></span>
                    <span class="text-gray-500 dark:text-gray-400 truncate">&gt; 100 days</span>
                  </div>
                  <span class="font-semibold text-gray-500 dark:text-gray-400 ml-2 shrink-0">{{ stats.offlineMoreThan100Days }}</span>
                </div>
              </div>
            </div>
            <!-- No offline: empty 1/3 spacer -->
            <div v-else class="w-1/3 flex items-center justify-center">
              <p class="text-xs text-gray-400 dark:text-gray-500">All tractors online</p>
            </div>
          </div>
        </div>
        <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 border border-gray-200/70 dark:border-gray-700/50 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)]">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Alerts — Last 7 Days</h3>
            <span class="text-xs text-red-500 font-medium">{{ charts.alertsTrend?.reduce((s, d) => s + d.count, 0) || 0 }} total</span>
          </div>
          <apexchart type="area" height="280" :options="alertsTrendOptions" :series="alertsTrendSeries" />
        </div>
      </div>

      <!-- Charts Row 2: Bookings Bar + Bookings Trend -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
        <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 border border-gray-200/70 dark:border-gray-700/50 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)]">
          <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Bookings by Status</h3>
          <apexchart type="bar" height="280" :options="bookingBarOptions" :series="bookingBarSeries" />
        </div>
        <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 border border-gray-200/70 dark:border-gray-700/50 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)]">
          <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Bookings — Last 7 Days</h3>
          <apexchart type="line" height="280" :options="bookingsTrendOptions" :series="bookingsTrendSeries" />
        </div>
      </div>

      <!-- Charts Row 3: PMS Breakdown + Alerts by Type + Tractors by Group -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-6">
        <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 border border-gray-200/70 dark:border-gray-700/50 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)]">
          <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">PMS Schedule</h3>
          <apexchart v-if="pmsBreakdownTotal" type="donut" height="260" :options="pmsBreakdownOptions" :series="pmsBreakdownSeries" />
          <div v-else class="flex items-center justify-center h-48 text-sm text-gray-400 dark:text-gray-500">No usage data available</div>
        </div>
        <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 border border-gray-200/70 dark:border-gray-700/50 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)]">
          <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Alerts by Type</h3>
          <apexchart v-if="alertTypePieSeries.length" type="pie" height="260" :options="alertTypePieOptions" :series="alertTypePieSeries" />
          <div v-else class="flex items-center justify-center h-48 text-sm text-gray-400 dark:text-gray-500">No alert data</div>
        </div>
        <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 border border-gray-200/70 dark:border-gray-700/50 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)]">
          <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Tractors by Group</h3>
          <apexchart v-if="charts.tractorsByGroup?.length" type="bar" height="260" :options="groupBarOptions" :series="groupBarSeries" />
          <div v-else class="flex items-center justify-center h-48 text-sm text-gray-400 dark:text-gray-500">No group data</div>
        </div>
      </div>

      <!-- Data Tables Row -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Recent Alerts -->
        <div v-if="recentAlerts?.length" class="rounded-2xl bg-white dark:bg-gray-800 overflow-hidden border border-gray-200/70 dark:border-gray-700/50 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)]">
          <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-red-50/50 dark:bg-red-950/20">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-red-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]"></span>
              Unacknowledged Alerts
            </h3>
            <Link href="/alerts" class="text-xs font-semibold text-red-600 hover:text-red-500 dark:text-red-400 transition-colors">View all →</Link>
          </div>
          <div class="p-4 space-y-2">
            <div v-for="alert in recentAlerts" :key="alert.id" class="flex items-start gap-3 p-3 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/20 transition-colors cursor-pointer">
              <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/40">
                <svg class="h-4 w-4 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ alert.title }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ alert.device?.device_name || alert.tractor?.no_plate }} · {{ timeAgo(alert.created_at) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Bookings -->
        <div v-if="recentBookings?.length" class="rounded-2xl bg-white dark:bg-gray-800 overflow-hidden border border-gray-200/70 dark:border-gray-700/50 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)]">
          <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-indigo-50/50 dark:bg-indigo-950/20">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-indigo-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]"></span>
              Recent Bookings
            </h3>
            <Link href="/bookings" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 transition-colors">View all →</Link>
          </div>
          <div class="p-4 space-y-2">
            <div v-for="booking in recentBookings" :key="booking.id" class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer">
              <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ booking.tractor?.no_plate }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ booking.booked_by?.name }} · {{ booking.booking_date }}</p>
              </div>
              <StatusBadge :status="booking.status" />
            </div>
          </div>
        </div>

        <!-- PMS Due List -->
        <div v-if="maintenanceDueList?.length" class="rounded-2xl bg-white dark:bg-gray-800 overflow-hidden border border-gray-200/70 dark:border-gray-700/50 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] lg:col-span-2">
          <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-orange-50/50 dark:bg-orange-950/20">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-orange-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]"></span>
              PMS Due — {{ maintenanceDueList.length }} tractors
            </h3>
            <Link href="/maintenance" class="text-xs font-semibold text-orange-600 hover:text-orange-500 dark:text-orange-400 transition-colors">View all →</Link>
          </div>
          <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <div v-for="tractor in maintenanceDueList" :key="tractor.id" class="flex items-center justify-between p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20 hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-colors cursor-pointer">
              <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ tractor.no_plate }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ tractor.brand }} {{ tractor.model }} · {{ Number(tractor.total_distance || 0).toLocaleString() }} km</p>
              </div>
              <span class="inline-flex items-center rounded-full bg-orange-200 dark:bg-orange-800 px-2.5 py-0.5 text-xs font-semibold text-orange-800 dark:text-orange-200">Due</span>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- ═══════════ NON-ADMIN FALLBACK (TPS / FCA / Farmer) ═══════════ -->
    <template v-else>
      <!-- Stats Grid -->
      <div v-if="stats" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
        <StatCard v-for="(value, key) in stats" :key="key" :title="formatTitle(key)" :value="value" :color="getStatColor(key)" />
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Pending Bookings (FCA) -->
        <div v-if="pendingBookings?.length" class="bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
              Pending Bookings
            </h3>
          </div>
          <div class="p-5 space-y-3">
            <div v-for="booking in pendingBookings" :key="booking.id" class="flex items-center justify-between p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20">
              <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ booking.tractor?.no_plate }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ booking.booked_by?.name }} &mdash; {{ booking.booking_date }}</p>
              </div>
              <Link :href="`/bookings/${booking.id}`" class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">Review</Link>
            </div>
          </div>
        </div>

        <!-- My TPS Responsibilities -->
        <div v-if="myTractors?.length" class="bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
          <div class="p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">My TPS Responsibilities</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Your assigned tractors for coordination. The full fleet remains available in the Tractors module.</p>
          </div>
          <div class="p-5 space-y-3">
            <div v-for="tractor in myTractors" :key="tractor.id" class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700">
              <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ tractor.no_plate }} — {{ tractor.brand }} {{ tractor.model }}</p>
                <StatusBadge :status="tractor.device?.latest_location ? 'online' : 'offline'" :show-dot="true" />
              </div>
              <Link :href="`/tractors/${tractor.id}`" class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">View</Link>
            </div>
          </div>
        </div>

        <!-- My Bookings (Farmer) -->
        <div v-if="myBookings?.length" class="bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">My Bookings</h3>
          </div>
          <div class="p-5 space-y-3">
            <div v-for="booking in myBookings" :key="booking.id" class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700">
              <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ booking.tractor?.no_plate }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ booking.booking_date }} &mdash; {{ booking.purpose }}</p>
              </div>
              <StatusBadge :status="booking.status" />
            </div>
          </div>
        </div>

        <!-- Available Tractors (Farmer) -->
        <div v-if="availableTractors?.length" class="bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Available Tractors</h3>
          </div>
          <div class="p-5 space-y-3">
            <div v-for="tractor in availableTractors" :key="tractor.id" class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700">
              <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ tractor.no_plate }} — {{ tractor.brand }} {{ tractor.model }}</p>
              </div>
              <Link :href="`/bookings/create?tractor=${tractor.id}`" class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">Book</Link>
            </div>
          </div>
        </div>
      </div>
    </template>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import VueApexCharts from 'vue3-apexcharts';
const apexchart = VueApexCharts;

const props = defineProps({
  stats: Object,
  charts: Object,
  recentAlerts: Array,
  recentBookings: Array,
  maintenanceDueList: Array,
  pendingBookings: Array,
  myTractors: Array,
  pendingTasks: Array,
  myBookings: Array,
  availableTractors: Array,
  recentFeedback: Array,
});

// ── Helper ──
const todayDate = computed(() => new Date().toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' }));
const timeAgo = (dateStr) => {
  if (!dateStr) return '';
  const seconds = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
  if (seconds < 60) return 'just now';
  if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
  if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
  return Math.floor(seconds / 86400) + 'd ago';
};

const formatTitle = (key) => key.replace(/([A-Z])/g, ' $1').replace(/^./, s => s.toUpperCase()).trim();
const getStatColor = (key) => {
  if (key.includes('pending') || key.includes('Pending')) return 'yellow';
  if (key.includes('active') || key.includes('Active') || key.includes('online') || key.includes('Online')) return 'green';
  if (key.includes('alert') || key.includes('Alert')) return 'red';
  return 'indigo';
};

// ── Shared chart theme ──
const chartForeColor = '#9ca3af';

// ── Tractor Status Donut ──
const tractorDonutSeries = computed(() => {
  const ts = props.charts?.tractorStatus || {};
  return [ts.online || 0, ts.offline || 0, ts.inactive || 0];
});
const tractorDonutOptions = computed(() => ({
  chart: { type: 'donut', foreColor: chartForeColor },
  labels: ['Online', 'Offline', 'Inactive'],
  colors: ['#10b981', '#ef4444', '#9ca3af'],
  plotOptions: { pie: { donut: { size: '60%', labels: { show: true, name: { show: true }, value: { show: true, fontSize: '22px', fontWeight: 700 }, total: { show: true, label: 'Total', fontSize: '14px' } } } } },
  legend: { position: 'bottom', labels: { colors: chartForeColor } },
  stroke: { width: 0 },
  dataLabels: { enabled: false },
}));

// ── Alerts Trend (Area) ──
const alertsTrendSeries = computed(() => [{
  name: 'Alerts',
  data: (props.charts?.alertsTrend || []).map(d => d.count),
}]);
const alertsTrendOptions = computed(() => ({
  chart: { type: 'area', foreColor: chartForeColor, toolbar: { show: false }, sparkline: { enabled: false }, fontFamily: 'inherit' },
  xaxis: { categories: (props.charts?.alertsTrend || []).map(d => d.date), labels: { style: { fontSize: '11px' } } },
  yaxis: { min: 0, forceNiceScale: true, labels: { style: { fontSize: '11px' } } },
  colors: ['#ef4444'],
  fill: { type: 'gradient', gradient: { shade: 'dark', type: 'vertical', shadeIntensity: 0.4, gradientToColors: ['#f87171'], inverseColors: false, opacityFrom: 0.5, opacityTo: 0.05, stops: [0, 100] } },
  stroke: { curve: 'smooth', width: 2.5 },
  dataLabels: { enabled: false },
  grid: { borderColor: '#e5e7eb', strokeDashArray: 4, padding: { left: 0, right: 0 } },
  tooltip: { theme: 'light', fillSeriesColor: false, marker: { show: true } },
  markers: { size: 0, strokeWidth: 0, hover: { size: 5 } },
}));

// ── Bookings by Status (Bar) ──
const bookingBarSeries = computed(() => {
  const bs = props.charts?.bookingsByStatus || {};
  return [{ name: 'Bookings', data: Object.values(bs) }];
});
const bookingStatusColors = { pending: '#f59e0b', approved: '#10b981', rejected: '#ef4444', cancelled: '#6b7280', completed: '#6366f1', in_use: '#8b5cf6' };
const bookingBarOptions = computed(() => {
  const bs = props.charts?.bookingsByStatus || {};
  const labels = Object.keys(bs).map(k => k.replace(/_/g, ' '));
  const colors = Object.keys(bs).map(k => bookingStatusColors[k] || '#6366f1');
  return {
    chart: { type: 'bar', foreColor: chartForeColor, toolbar: { show: false }, fontFamily: 'inherit' },
    plotOptions: { bar: { borderRadius: 6, horizontal: false, distributed: true, columnWidth: '55%' } },
    xaxis: { categories: labels, labels: { style: { fontSize: '11px' } } },
    yaxis: { min: 0, forceNiceScale: true, labels: { style: { fontSize: '11px' } } },
    colors: colors,
    legend: { show: false },
    dataLabels: { enabled: true, style: { fontSize: '11px', fontWeight: 600 } },
    grid: { borderColor: '#e5e7eb', strokeDashArray: 4, padding: { left: 0, right: 0 } },
    tooltip: { theme: 'light' },
  };
});

// ── Bookings Trend (Line) ──
const bookingsTrendSeries = computed(() => [{
  name: 'Bookings',
  data: (props.charts?.bookingsTrend || []).map(d => d.count),
}]);
const bookingsTrendOptions = computed(() => ({
  chart: { type: 'line', foreColor: chartForeColor, toolbar: { show: false }, fontFamily: 'inherit' },
  xaxis: { categories: (props.charts?.bookingsTrend || []).map(d => d.date), labels: { style: { fontSize: '11px' } } },
  yaxis: { min: 0, forceNiceScale: true, labels: { style: { fontSize: '11px' } } },
  colors: ['#6366f1'],
  stroke: { curve: 'smooth', width: 3 },
  fill: { type: 'gradient', gradient: { shade: 'dark', type: 'vertical', gradientToColors: ['#818cf8'], opacityFrom: 0.2, opacityTo: 0, stops: [0, 100] } },
  markers: { size: 5, colors: ['#6366f1'], strokeColors: '#fff', strokeWidth: 2, hover: { size: 7 } },
  dataLabels: { enabled: false },
  grid: { borderColor: '#e5e7eb', strokeDashArray: 4, padding: { left: 0, right: 0 } },
  tooltip: { theme: 'light', fillSeriesColor: false },
}));

// ── PMS Breakdown (100‑hr schedule — due / ok / no data) ──
const pmsBreakdownTotal = computed(() => (props.charts?.pmsBreakdown?.due || 0) + (props.charts?.pmsBreakdown?.ok || 0) + (props.charts?.pmsBreakdown?.noData || 0));
const pmsBreakdownSeries = computed(() => {
  const b = props.charts?.pmsBreakdown || {};
  return [b.due || 0, b.ok || 0, b.noData || 0];
});
const pmsBreakdownOptions = computed(() => ({
  chart: { type: 'donut', foreColor: chartForeColor },
  labels: ['Due', 'OK', 'No Data'],
  colors: ['#f97316', '#10b981', '#9ca3af'],
  plotOptions: { pie: { donut: { size: '60%', labels: { show: true, name: { show: true }, value: { show: true, fontSize: '18px', fontWeight: 700 }, total: { show: true, label: 'Tractors', fontSize: '13px' } } } } },
  legend: { position: 'bottom', labels: { colors: chartForeColor } },
  stroke: { width: 0 },
  dataLabels: { enabled: false },
}));

// ── PMS by Status (Pie) ──
const maintenancePieSeries = computed(() => Object.values(props.charts?.maintenanceByStatus || {}));
const maintenanceStatusColors = { documentation: '#f59e0b', scheduled: '#3b82f6', in_progress: '#8b5cf6', completed: '#10b981', cancelled: '#6b7280' };
const maintenancePieOptions = computed(() => {
  const ms = props.charts?.maintenanceByStatus || {};
  return {
    chart: { type: 'pie', foreColor: chartForeColor },
    labels: Object.keys(ms).map(k => k.replace(/_/g, ' ')),
    colors: Object.keys(ms).map(k => maintenanceStatusColors[k] || '#6366f1'),
    legend: { position: 'bottom', labels: { colors: chartForeColor } },
    stroke: { width: 0 },
    dataLabels: { enabled: true, formatter: (val) => val.toFixed(0) + '%' },
  };
});

// ── Alerts by Type (Pie) ──
const alertTypePieSeries = computed(() => Object.values(props.charts?.alertsByType || {}));
const alertTypePieOptions = computed(() => {
  const at = props.charts?.alertsByType || {};
  return {
    chart: { type: 'pie', foreColor: chartForeColor },
    labels: Object.keys(at).map(k => k.replace(/_/g, ' ')),
    colors: ['#ef4444', '#f59e0b', '#3b82f6', '#8b5cf6', '#10b981', '#ec4899'],
    legend: { position: 'bottom', labels: { colors: chartForeColor } },
    stroke: { width: 0 },
    dataLabels: { enabled: true, formatter: (val) => val.toFixed(0) + '%' },
  };
});

// ── Tractors by Group (Horizontal Bar) ──
const groupBarSeries = computed(() => [{
  name: 'Tractors',
  data: (props.charts?.tractorsByGroup || []).map(g => g.count),
}]);
const groupBarOptions = computed(() => ({
  chart: { type: 'bar', foreColor: chartForeColor, toolbar: { show: false }, fontFamily: 'inherit' },
  plotOptions: { bar: { borderRadius: 4, horizontal: true, barHeight: '70%' } },
  xaxis: { categories: (props.charts?.tractorsByGroup || []).map(g => g.name.length > 18 ? g.name.substring(0, 18) + '...' : g.name), labels: { style: { fontSize: '10px' } } },
  yaxis: { labels: { style: { fontSize: '10px' } } },
  colors: ['#818cf8'],
  dataLabels: { enabled: true, style: { fontSize: '10px', fontWeight: 600 } },
  grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
  tooltip: { theme: 'light' },
}));
</script>
