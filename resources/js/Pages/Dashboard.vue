<template>
  <AppLayout>
    <Head title="Dashboard" />

    <!-- Page header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Welcome back, {{ $page.props.auth?.user?.name }} &middot; {{ todayDate }}</p>
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

    <!-- Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â ADMIN / SUB-ADMIN DASHBOARD Ã¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢ÂÃ¢â€¢Â -->
    <template v-if="charts">

      <!-- KPI Row: Fleet Metrics (Neumorphic + Gradient) -->
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5 mb-6">

        <!-- Total Tractors -->
        <div class="xl:col-span-2 xl:row-span-2 flex flex-col rounded-2xl bg-linear-to-br from-indigo-50 to-purple-50 dark:from-indigo-950/40 dark:to-purple-950/30 px-4 py-3.5 border border-indigo-200/60 dark:border-indigo-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)]">
          <div class="flex items-start gap-3 mb-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10"/></svg>
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Tractors</p>
              <p class="text-xl font-bold text-gray-900 dark:text-white">{{ Number(stats.totalTractors).toLocaleString() }}</p>
            </div>
          </div>
          <div class="flex-1 flex flex-col justify-center gap-1.5 text-xs">
            <div class="flex items-center justify-between py-0.5">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                <span class="font-medium text-gray-600 dark:text-gray-300">Online now</span>
              </div>
              <span class="font-bold text-green-600 dark:text-green-400">{{ stats.onlineTractors }}</span>
            </div>
            <div class="flex items-center justify-between py-0.5">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-red-400"></span>
                <span class="font-medium text-gray-600 dark:text-gray-300">&lt; 1 day offline</span>
              </div>
              <span class="font-bold text-red-500 dark:text-red-400">{{ stats.offlineLessThanDay }}</span>
            </div>
            <div class="flex items-center justify-between py-0.5">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                <span class="font-medium text-gray-600 dark:text-gray-300">1 &ndash; 7 days</span>
              </div>
              <span class="font-bold text-amber-500 dark:text-amber-400">{{ stats.offline1to7Days }}</span>
            </div>
            <div class="flex items-center justify-between py-0.5">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                <span class="font-medium text-gray-600 dark:text-gray-300">1 week &ndash; 1 month</span>
              </div>
              <span class="font-bold text-yellow-600 dark:text-yellow-400">{{ stats.offline7to30Days }}</span>
            </div>
            <div class="flex items-center justify-between py-0.5">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-orange-400"></span>
                <span class="font-medium text-gray-600 dark:text-gray-300">1 &ndash; 3 months</span>
              </div>
              <span class="font-bold text-orange-500 dark:text-orange-400">{{ stats.offline30to100Days }}</span>
            </div>
            <div class="flex items-center justify-between py-0.5">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                <span class="font-medium text-gray-600 dark:text-gray-300">3+ months</span>
              </div>
              <span class="font-bold text-gray-500">{{ stats.offlineMoreThan100Days }}</span>
            </div>
          </div>
        </div>

        <!-- Usage (HW Data) -->
        <div class="flex items-center gap-3 rounded-2xl bg-linear-to-br from-emerald-50 to-green-50 dark:from-emerald-950/40 dark:to-green-950/30 px-4 py-3.5 border border-emerald-200/60 dark:border-emerald-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Usage (HW Data)</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ Number(stats.usageHwData || 0).toLocaleString() }} <span :class="['text-sm font-semibold', stats.usageGrowthPercent >= 0 ? 'text-green-500' : 'text-red-500']">{{ stats.usageGrowthPercent >= 0 ? '+' : '' }}{{ stats.usageGrowthPercent }}%</span></p>
            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">tractors with data</p>
          </div>
        </div>

        <!-- PMS Due -->
        <div class="flex items-center gap-3 rounded-2xl bg-linear-to-br from-orange-50 to-amber-50 dark:from-orange-950/40 dark:to-amber-950/30 px-4 py-3.5 border border-orange-200/60 dark:border-orange-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-orange-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">PMS Due</p>
            <p class="text-xl font-bold text-orange-600 dark:text-orange-400">{{ stats.pmsDue }}</p>
            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">Due within schedule</p>
          </div>
        </div>

        <!-- Alerts -->
        <div class="flex items-center gap-3 rounded-2xl bg-linear-to-br from-red-50 to-rose-50 dark:from-red-950/40 dark:to-rose-950/30 px-4 py-3.5 border border-red-200/60 dark:border-red-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Alerts</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ Number(stats.totalAlertsLast7Days || 0).toLocaleString() }} <span class="text-sm font-normal text-gray-400">7d</span></p>
            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">Last 7 days</p>
          </div>
        </div>

        <!-- Groups -->
        <div class="flex items-center gap-3 rounded-2xl bg-linear-to-br from-purple-50 to-violet-50 dark:from-purple-950/40 dark:to-violet-950/30 px-4 py-3.5 border border-purple-200/60 dark:border-purple-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-purple-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Groups</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ Number(stats.activeGroups || 0).toLocaleString() }} <span class="text-sm font-normal text-gray-400">active</span></p>
            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">Active groups</p>
          </div>
        </div>

        <!-- Tickets -->
        <div class="flex items-center gap-3 rounded-2xl bg-linear-to-br from-amber-50 to-yellow-50 dark:from-amber-950/40 dark:to-yellow-950/30 px-4 py-3.5 border border-amber-200/60 dark:border-amber-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tickets</p>
            <p class="text-xl font-bold text-amber-600 dark:text-amber-400">{{ Number(stats.openTickets ?? 0).toLocaleString() }} <span class="text-sm font-normal text-gray-400">open</span></p>
            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">of {{ Number(stats.totalTickets ?? 0).toLocaleString() }} total</p>
          </div>
        </div>

        <!-- Users -->
        <div class="flex items-center gap-3 rounded-2xl bg-linear-to-br from-sky-50 to-blue-50 dark:from-sky-950/40 dark:to-blue-950/30 px-4 py-3.5 border border-sky-200/60 dark:border-sky-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sky-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          </div>
          <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Users</p>
            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ Number(stats.totalUsers || 0).toLocaleString() }} <span class="text-sm font-normal text-gray-400">total</span></p>
            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">Registered users</p>
          </div>
        </div>
      </div>

      <!-- Status Row: Activation + PMS Schedule (Neumorphic cards) -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

        <!-- Tractor Status -->
        <div class="rounded-2xl bg-gray-100 dark:bg-gray-800/60 p-6 border border-gray-200/70 dark:border-gray-700/50 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)]">
          <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-6">Tractor Status</h3>
          <div class="flex flex-col sm:flex-row items-stretch gap-4 sm:gap-0 sm:divide-x divide-gray-200 dark:divide-gray-700">
            <!-- Activated -->
            <div class="flex-1 flex flex-col items-center px-3">
              <span class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-3">Activated</span>
              <div class="relative w-full max-w-[160px] mx-auto flex justify-center mb-3">
                <apexchart type="donut" height="120" :options="activatedDonutOptions" :series="activatedDonutSeries" />
              </div>
              <div class="flex items-center gap-2 mt-1">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Activated {{ deviceActivation.activated.toLocaleString() }}</span>
              </div>
            </div>
            <!-- Inactive -->
            <div class="flex-1 flex flex-col items-center px-3">
              <span class="text-sm font-semibold text-gray-600 dark:text-gray-300 mb-3">Inactive</span>
              <div class="relative w-full max-w-[160px] mx-auto flex justify-center mb-3">
                <apexchart type="donut" height="120" :options="inactivatedDonutOptions" :series="inactivatedDonutSeries" />
              </div>
              <div class="flex items-center gap-2 mt-1">
                <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Inactive {{ deviceActivation.inactivated.toLocaleString() }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- PMS Schedule -->
        <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 border border-gray-200/70 dark:border-gray-700/50 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)]">
          <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-6">PMS Schedule</h3>
          <div class="flex items-start divide-x divide-gray-200 dark:divide-gray-700">
            <!-- Finished / For Checking -->
            <div class="flex-1 flex flex-col items-center px-3">
              <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/40 mb-3">
                <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
              </div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ Number(pmsScheduleItems[0].count).toLocaleString() }}</p>
              <p class="text-sm font-semibold text-green-600 dark:text-green-400 mt-0.5">{{ pmsScheduleItems[0].percent }}%</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 text-center leading-tight">Finished /<br>For Checking</p>
            </div>
            <!-- Upcoming -->
            <div class="flex-1 flex flex-col items-center px-3">
              <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/40 mb-3">
                <svg class="h-6 w-6 text-orange-500 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2"/></svg>
              </div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ Number(pmsScheduleItems[1].count).toLocaleString() }}</p>
              <p class="text-sm font-semibold text-orange-500 dark:text-orange-400 mt-0.5">{{ pmsScheduleItems[1].percent }}%</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Upcoming</p>
            </div>
            <!-- Due -->
            <div class="flex-1 flex flex-col items-center px-3">
              <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/40 mb-3">
                <svg class="h-6 w-6 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              </div>
              <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ Number(pmsScheduleItems[2].count).toLocaleString() }}</p>
              <p class="text-sm font-semibold text-red-500 dark:text-red-400 mt-0.5">{{ pmsScheduleItems[2].percent }}%</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Due</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Activation per Month (Full Width) -->
      <div class="mb-6">
        <div class="rounded-2xl bg-white dark:bg-gray-800 p-6 border border-gray-200/70 dark:border-gray-700/50 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)]">
          <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">Activation per Month</h3>
            <div v-if="charts.activationByMonth?.length" class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
              <span class="w-2 h-2 rounded-full bg-blue-600"></span>
              {{ activationDateRange }}
            </div>
          </div>
          <div v-if="charts.activationByMonth?.length" style="height: 350px">
            <canvas ref="activationChartRef"></canvas>
          </div>
          <div v-else class="flex items-center justify-center h-48 text-sm text-gray-400">No activation data</div>
        </div>
      </div>

    </template>

    <!-- Non-admin fallback -->
    <template v-else>
      <div v-if="stats" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
        <StatCard v-for="(value, key) in stats" :key="key" :title="formatTitle(key)" :value="value" :color="getStatColor(key)" />
      </div>
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div v-if="pendingBookings?.length" class="bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold">Pending Bookings</h3></div>
          <div class="p-5 space-y-3"><div v-for="b in pendingBookings" :key="b.id" class="flex justify-between p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20"><div><p class="text-sm font-medium">{{ b.tractor?.no_plate }}</p><p class="text-xs text-gray-500">{{ b.booked_by?.name }} &mdash; {{ b.booking_date }}</p></div><Link :href="`/bookings/${b.id}`" class="text-sm font-medium text-indigo-600 hover:underline">Review</Link></div></div>
        </div>
        <div v-if="myTractors?.length" class="bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
          <div class="p-5 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold">My TSR Responsibilities</h3></div>
          <div class="p-5 space-y-3"><div v-for="t in myTractors" :key="t.id" class="flex justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700"><div><p class="text-sm font-medium">{{ t.no_plate }} &mdash; {{ t.brand }} {{ t.model }}</p><StatusBadge :status="t.device?.latest_location ? 'online' : 'offline'" :show-dot="true" /></div><Link :href="`/tractors/${t.id}`" class="text-sm font-medium text-indigo-600 hover:underline">View</Link></div></div>
        </div>
        <div v-if="myBookings?.length" class="bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
          <div class="p-5 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold">My Bookings</h3></div>
          <div class="p-5 space-y-3"><div v-for="b in myBookings" :key="b.id" class="flex justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700"><div><p class="text-sm font-medium">{{ b.tractor?.no_plate }}</p><p class="text-xs text-gray-500">{{ b.booking_date }} &mdash; {{ b.purpose }}</p></div><StatusBadge :status="b.status" /></div></div>
        </div>
        <div v-if="availableTractors?.length" class="bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
          <div class="p-5 border-b border-gray-200 dark:border-gray-700"><h3 class="text-lg font-semibold">Available Tractors</h3></div>
          <div class="p-5 space-y-3"><div v-for="t in availableTractors" :key="t.id" class="flex justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700"><div><p class="text-sm font-medium">{{ t.no_plate }} &mdash; {{ t.brand }} {{ t.model }}</p></div><Link :href="`/bookings/create?tractor=${t.id}`" class="text-sm font-medium text-indigo-600 hover:underline">Book</Link></div></div>
        </div>
      </div>
    </template>
  </AppLayout>
</template>

<script setup>
import { computed, ref, onMounted, watch, nextTick } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatCard from '@/Components/StatCard.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import VueApexCharts from 'vue3-apexcharts'
import { Chart, registerables } from 'chart.js'
Chart.register(...registerables)
const apexchart = VueApexCharts

const props = defineProps({
  stats: Object, charts: Object, recentAlerts: Array, recentBookings: Array,
  maintenanceDueList: Array, pendingBookings: Array, myTractors: Array,
  pendingTasks: Array, myBookings: Array, availableTractors: Array, recentFeedback: Array,
})

const todayDate = computed(() => new Date().toLocaleDateString('en-US', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' }))
const formatTitle = (key) => key.replace(/([A-Z])/g, ' $1').replace(/^./, s => s.toUpperCase()).trim()
const getStatColor = (key) => {
  if (key.includes('pending')) return 'yellow'
  if (key.includes('active') || key.includes('online')) return 'green'
  if (key.includes('alert')) return 'red'
  return 'indigo'
}


// Activation
const deviceActivation = computed(() => props.charts?.deviceActivation || { total: 0, activated: 0, inactivated: 0 })
const activationActivatedPercent = computed(() => deviceActivation.value.total > 0 ? Math.round((deviceActivation.value.activated / deviceActivation.value.total) * 100 * 100) / 100 : 0)
const activationInactivatedPercent = computed(() => deviceActivation.value.total > 0 ? Math.round((deviceActivation.value.inactivated / deviceActivation.value.total) * 100 * 100) / 100 : 0)

// Tractor Status donut charts
const buildStatusDonut = (percent, color) => ({
  chart: { type: 'donut', sparkline: { enabled: true } },
  plotOptions: { pie: { donut: { size: '70%', labels: { show: true, name: { show: false }, value: { show: true, fontSize: '16px', fontWeight: 700, color, offsetY: -2 }, total: { show: false } } } } },
  colors: [color, '#e5e7eb'],
  stroke: { width: 0 },
  dataLabels: { enabled: false },
  legend: { show: false },
  tooltip: { enabled: false },
})
const activatedDonutSeries = computed(() => [activationActivatedPercent.value, 100 - activationActivatedPercent.value])
const activatedDonutOptions = computed(() => buildStatusDonut(activationActivatedPercent.value, '#3b82f6'))
const inactivatedDonutSeries = computed(() => [activationInactivatedPercent.value, 100 - activationInactivatedPercent.value])
const inactivatedDonutOptions = computed(() => buildStatusDonut(activationInactivatedPercent.value, '#f43f5e'))

// PMS Schedule
const pmsSchedule = computed(() => props.charts?.pmsScheduleBreakdown || { finished: 0, upcoming: 0, due: 0 })
const pmsScheduleTotal = computed(() => pmsSchedule.value.finished + pmsSchedule.value.upcoming + pmsSchedule.value.due)
const pmsScheduleItems = computed(() => {
  const t = pmsScheduleTotal.value || 1
  return [
    { label: 'Finished / For Checking', count: pmsSchedule.value.finished, percent: Math.round((pmsSchedule.value.finished / t) * 1000) / 10, color: '#10b981' },
    { label: 'Upcoming', count: pmsSchedule.value.upcoming, percent: Math.round((pmsSchedule.value.upcoming / t) * 1000) / 10, color: '#f59e0b' },
    { label: 'Due', count: pmsSchedule.value.due, percent: Math.round((pmsSchedule.value.due / t) * 1000) / 10, color: '#ef4444' },
  ]
})

// Alerts sparkline
const alertsSparkSeries = computed(() => [{ data: (props.charts?.alertsTrend || []).map(d => d.count) }])
const alertsSparkOptions = computed(() => ({
  chart: { type: 'area', sparkline: { enabled: true } },
  stroke: { curve: 'smooth', width: 1.5 },
  fill: { type: 'gradient', gradient: { shade: 'dark', type: 'vertical', gradientToColors: ['#fca5a5'], opacityFrom: 0.3, opacityTo: 0, stops: [0, 100] } },
  colors: ['#ef4444'],
}))

// Groups sparkline
const groupSparkSeries = computed(() => [{ data: (props.charts?.tractorsByGroup || []).map(g => g.count) }])
const groupSparkOptions = computed(() => ({
  chart: { type: 'bar', sparkline: { enabled: true } },
  plotOptions: { bar: { columnWidth: '60%', borderRadius: 2 } },
  colors: ['#a78bfa'],
}))

// Activation per Month — Chart.js
const activationChartRef = ref(null)
let activationChartInstance = null

const activationDateRange = computed(() => {
  const data = props.charts?.activationByMonth || []
  if (!data.length) return ''
  return data[0].month + ' – ' + data[data.length - 1].month
})

function buildActivationChart() {
  if (!activationChartRef.value) return
  const data = props.charts?.activationByMonth || []
  if (!data.length) return

  if (activationChartInstance) activationChartInstance.destroy()

  const labels = data.map(d => d.month)
  const values = data.map(d => d.count)
  const maxVal = Math.max(...values, 1)
  const yMax = Math.ceil(maxVal / 10) * 10 + 10

  const isDark = document.documentElement.classList.contains('dark')
  const gridColor = isDark ? 'rgba(75,85,99,0.3)' : 'rgba(229,231,235,0.8)'
  const textColor = isDark ? '#9ca3af' : '#9ca3af'

  activationChartInstance = new Chart(activationChartRef.value, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Activations',
        data: values,
        borderColor: '#2563eb',
        backgroundColor: (ctx) => {
          const gradient = ctx.chart.ctx.createLinearGradient(0, 0, 0, ctx.chart.height)
          gradient.addColorStop(0, 'rgba(37,99,235,0.25)')
          gradient.addColorStop(1, 'rgba(37,99,235,0.01)')
          return gradient
        },
        fill: true,
        tension: 0.3,
        borderWidth: 3,
        pointRadius: 6,
        pointBackgroundColor: '#2563eb',
        pointBorderColor: '#fff',
        pointBorderWidth: 3,
        pointHoverRadius: 9,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { intersect: false, mode: 'index' },
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#fff',
          titleColor: '#1f2937',
          bodyColor: '#1f2937',
          borderColor: '#e5e7eb',
          borderWidth: 1,
          padding: 10,
          cornerRadius: 8,
          displayColors: false,
        },
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: textColor, font: { size: 10 }, maxRotation: 45, autoSkip: true, maxTicksLimit: 20 },
        },
        y: {
          min: 0, max: yMax,
          grid: { color: gridColor },
          ticks: { color: textColor, font: { size: 11 }, stepSize: Math.ceil(yMax / 5) },
        },
      },
    },
  })
}

onMounted(() => { nextTick(() => buildActivationChart()) })
watch(() => props.charts?.activationByMonth, () => { nextTick(() => buildActivationChart()) }, { deep: true })

</script>