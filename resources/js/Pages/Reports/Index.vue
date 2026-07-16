<template>
  <AppLayout>
    <Head title="Reports" />

    <!-- Page Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Reports</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Access detailed reports and analytics for your fleet</p>
    </div>

    <!-- Report Cards — neumorphic style matching Dashboard -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-6">
      <!-- Tractor Usage -->
      <Link href="/reports/tractor-usage" class="group flex items-center gap-3 rounded-2xl bg-blue-50 dark:bg-blue-950/30 px-4 py-4 border border-blue-200/60 dark:border-blue-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-blue-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </div>
        <div class="min-w-0">
          <p class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Tractor Usage</p>
          <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">Distance, hours, and utilization</p>
        </div>
      </Link>

      <!-- Maintenance Summary -->
      <Link href="/reports/maintenance-summary" class="group flex items-center gap-3 rounded-2xl bg-amber-50 dark:bg-amber-950/30 px-4 py-4 border border-amber-200/60 dark:border-amber-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <div class="min-w-0">
          <p class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">Maintenance Summary</p>
          <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">Costs, frequency, and overdue</p>
        </div>
      </Link>

      <!-- Booking Summary -->
      <Link href="/reports/booking-summary" class="group flex items-center gap-3 rounded-2xl bg-green-50 dark:bg-green-950/30 px-4 py-4 border border-green-200/60 dark:border-green-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-green-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div class="min-w-0">
          <p class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">Booking Summary</p>
          <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">Bookings by status and tractor</p>
        </div>
      </Link>

      <!-- Device Status -->
      <Link href="/reports/device-status" class="group flex items-center gap-3 rounded-2xl bg-purple-50 dark:bg-purple-950/30 px-4 py-4 border border-purple-200/60 dark:border-purple-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-purple-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        </div>
        <div class="min-w-0">
          <p class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">Device Status</p>
          <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">Online/offline and sync stats</p>
        </div>
      </Link>

      <!-- Alerts History -->
      <Link href="/reports/alerts-history" class="group flex items-center gap-3 rounded-2xl bg-red-50 dark:bg-red-950/30 px-4 py-4 border border-red-200/60 dark:border-red-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-red-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        </div>
        <div class="min-w-0">
          <p class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">Alerts History</p>
          <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">Filter by type, status, and date</p>
        </div>
      </Link>

      <!-- Ticket Summary -->
      <Link href="/reports/ticket-summary" class="group flex items-center gap-3 rounded-2xl bg-sky-50 dark:bg-sky-950/30 px-4 py-4 border border-sky-200/60 dark:border-sky-800/30 shadow-[3px_3px_8px_rgba(0,0,0,0.04),-2px_-2px_6px_rgba(255,255,255,0.9)] dark:shadow-[3px_3px_8px_rgba(0,0,0,0.2),-1px_-1px_4px_rgba(255,255,255,0.02)] hover:shadow-[4px_4px_12px_rgba(0,0,0,0.06),-2px_-2px_8px_rgba(255,255,255,1)] dark:hover:shadow-[4px_4px_12px_rgba(0,0,0,0.3),-1px_-1px_4px_rgba(255,255,255,0.03)] transition-shadow">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-sky-500 shadow-[inset_0_2px_4px_rgba(0,0,0,0.12)]">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
        </div>
        <div class="min-w-0">
          <p class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-sky-600 dark:group-hover:text-sky-400 transition-colors">Ticket Summary</p>
          <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">Resolution times and priorities</p>
        </div>
      </Link>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- Auto-Report Subscriptions -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="mt-10">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h2 class="text-lg font-bold text-gray-900 dark:text-white">Auto-Report Subscriptions</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Schedule Excel reports to be emailed automatically to FCA, TPS, and Admins</p>
        </div>
        <button @click="openModal" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500 transition-colors shadow-sm">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Add Subscription
        </button>
      </div>

      <!-- Subscriptions Table — one row per user -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700" v-if="groupedSubscriptions?.length">
        <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
          <div v-for="group in groupedSubscriptions" :key="group.user_id" class="p-5 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
            <!-- User header row -->
            <div class="flex items-start justify-between gap-4 mb-3">
              <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-sm font-bold text-indigo-600 dark:text-indigo-400 shrink-0">
                  {{ group.user?.name?.charAt(0) || '?' }}
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ group.user?.name }}</p>
                  <p class="text-xs text-gray-400">{{ group.user?.email }}</p>
                </div>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ group.subscriptions.length }} report{{ group.subscriptions.length > 1 ? 's' : '' }}</span>
                <button
                  @click="removeUserSubscriptions(group.user_id)"
                  class="text-xs font-medium text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition px-2 py-1 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/20"
                  title="Remove all subscriptions for this user"
                >Remove all</button>
              </div>
            </div>

            <!-- Report chips -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
              <div
                v-for="sub in group.subscriptions"
                :key="sub.id"
                class="flex items-center justify-between gap-3 px-3 py-2.5 rounded-xl border transition-colors"
                :class="sub.is_active
                  ? 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800'
                  : 'border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/40 opacity-60'"
              >
                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                  <span class="text-base shrink-0">{{ reportIcon(sub.report_type) }}</span>
                  <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate">{{ sub.report_type_label }}</p>

                    <!-- Inline edit -->
                    <div v-if="editingSub?.id === sub.id" class="mt-1.5 flex items-center gap-2 flex-wrap">
                      <select v-model="editForm.interval" class="py-1 px-2 text-[10px] rounded-md border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 focus:ring-1 focus:ring-indigo-500">
                        <option v-for="(label, key) in intervals" :key="key" :value="key">{{ label }}</option>
                      </select>
                      <select v-if="editForm.interval === 'weekly'" v-model="editForm.day_of_week" class="py-1 px-2 text-[10px] rounded-md border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200 capitalize">
                        <option v-for="d in daysOfWeek" :key="d" :value="d">{{ d }}</option>
                      </select>
                      <select v-else-if="editForm.interval === 'monthly'" v-model="editForm.day_of_month" class="py-1 px-2 text-[10px] rounded-md border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                        <option v-for="d in 28" :key="d" :value="d">{{ ordinal(d) }}</option>
                      </select>
                      <select v-model="editForm.time" class="py-1 px-2 text-[10px] rounded-md border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-200">
                        <option v-for="t in timeOptions" :key="t" :value="t">{{ t }}</option>
                      </select>
                      <button @click="saveEdit(sub)" class="py-1 px-2 text-[10px] font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-md transition">Save</button>
                      <button @click="editingSub = null" class="py-1 px-2 text-[10px] font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition">Cancel</button>
                    </div>

                    <!-- Display -->
                    <button
                      v-else
                      @click="startEdit(sub)"
                      class="text-[10px] text-gray-400 dark:text-gray-500 flex items-center gap-1 hover:text-indigo-600 dark:hover:text-indigo-400 transition cursor-pointer group"
                      title="Click to edit interval"
                    >
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                      <span>{{ sub.interval_label }} at {{ sub.time }}</span>
                      <svg class="w-2.5 h-2.5 opacity-0 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                  </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                  <!-- Next send tooltip -->
                  <span class="text-[10px] text-gray-400 dark:text-gray-500 hidden sm:inline" :title="sub.next_scheduled_at ? formatDate(sub.next_scheduled_at) : ''">
                    {{ sub.next_scheduled_at ? relativeTime(sub.next_scheduled_at) : '—' }}
                  </span>
                  <!-- Active toggle -->
                  <button
                    @click="toggleActive(sub)"
                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200"
                    :class="sub.is_active ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600'"
                    role="switch"
                  >
                    <span class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow-sm transform ring-0 transition duration-200" :class="sub.is_active ? 'translate-x-4' : 'translate-x-0'" />
                  </button>
                  <button
                    @click="deleteSubscription(sub.id)"
                    class="p-1 rounded-md text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/20 transition"
                    title="Remove"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 px-5 py-16 text-center">
        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        <p class="text-sm text-gray-500 dark:text-gray-400">No report subscriptions yet.</p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Click "Add Subscription" to schedule automated report emails.</p>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- Slide Modal -->
    <!-- ═══════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-hidden">
          <!-- Backdrop -->
          <div @click="closeModal" class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>
          <!-- Panel -->
          <div class="absolute inset-y-0 right-0 w-full max-w-xl bg-white dark:bg-gray-900 shadow-2xl flex flex-col transition-transform duration-300 ease-out">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100 dark:border-gray-800 shrink-0 bg-linear-to-r from-indigo-50 to-white dark:from-indigo-950/20 dark:to-gray-900">
              <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-900/40">
                  <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                </div>
                <div>
                  <h3 class="text-lg font-bold text-gray-900 dark:text-white">New Subscription</h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Schedule automated Excel report emails</p>
                </div>
              </div>
              <button @click="closeModal" class="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>

            <!-- Body -->
            <form @submit.prevent="createSubscription" class="flex-1 overflow-y-auto p-6 space-y-6 bg-gray-50/50 dark:bg-gray-900/50">

              <!-- ── Step 1: Select Users ── -->
              <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                  <div class="flex items-center gap-2">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-xs font-bold text-indigo-600 dark:text-indigo-400">1</span>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Recipients</h4>
                    <span class="text-xs text-red-400">*</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <button type="button" @click="selectAllUsers" class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 transition">Select all</button>
                    <span class="text-gray-300 dark:text-gray-600">|</span>
                    <button type="button" @click="form.user_ids = []" class="text-xs font-medium text-gray-400 hover:text-gray-500 transition">Clear</button>
                  </div>
                </div>

                <!-- Search -->
                <div class="relative mb-3">
                  <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                  <input
                    v-model="userSearch"
                    type="text"
                    placeholder="Search by name or email..."
                    class="w-full pl-10 pr-3 py-2.5 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white dark:bg-gray-700/50 dark:border-gray-600 dark:text-white dark:focus:bg-gray-700 transition"
                  />
                </div>

                <!-- Filtered user list -->
                <div class="max-h-52 overflow-y-auto rounded-lg border border-gray-100 dark:border-gray-700 divide-y divide-gray-50 dark:divide-gray-700/50">
                  <label
                    v-for="u in filteredUsers"
                    :key="u.id"
                    class="flex items-center gap-3 px-3 py-2.5 hover:bg-indigo-50/50 dark:hover:bg-indigo-950/20 cursor-pointer transition text-sm"
                    :class="{ 'bg-indigo-50/30 dark:bg-indigo-950/10': form.user_ids.includes(u.id) }"
                  >
                    <input type="checkbox" :value="u.id" v-model="form.user_ids" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 shrink-0" />
                    <div class="min-w-0 flex-1">
                      <p class="text-gray-800 dark:text-gray-200 font-medium truncate">{{ u.name }}</p>
                      <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ u.email }}</p>
                    </div>
                  </label>
                  <div v-if="filteredUsers.length === 0" class="px-3 py-6 text-center text-sm text-gray-400">
                    No users match "{{ userSearch }}"
                  </div>
                </div>

                <div class="flex items-center justify-between mt-3">
                  <p v-if="form.errors.user_ids" class="text-xs text-red-500">{{ form.errors.user_ids }}</p>
                  <p class="text-xs text-gray-400 ml-auto">
                    <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ form.user_ids.length }}</span> of {{ allUsers.length }} selected
                  </p>
                </div>
              </div>

              <!-- ── Step 2: Select Reports ── -->
              <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                  <div class="flex items-center gap-2">
                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-xs font-bold text-indigo-600 dark:text-indigo-400">2</span>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Reports</h4>
                    <span class="text-xs text-red-400">*</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <button type="button" @click="selectAllReports" class="text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 transition">Select all</button>
                    <span class="text-gray-300 dark:text-gray-600">|</span>
                    <button type="button" @click="form.report_types = []" class="text-xs font-medium text-gray-400 hover:text-gray-500 transition">Clear</button>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <label
                    v-for="(label, key) in reportTypes"
                    :key="key"
                    class="flex items-center gap-3 px-3 py-3 rounded-lg border cursor-pointer transition-all text-sm"
                    :class="form.report_types.includes(key)
                      ? 'border-indigo-300 bg-indigo-50 dark:border-indigo-700 dark:bg-indigo-950/30'
                      : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600'"
                  >
                    <input type="checkbox" :value="key" v-model="form.report_types" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 shrink-0" />
                    <div class="flex items-center gap-2 min-w-0">
                      <span class="text-lg shrink-0">{{ reportIcon(key) }}</span>
                      <span class="text-gray-700 dark:text-gray-200 font-medium truncate">{{ label }}</span>
                    </div>
                  </label>
                </div>

                <div class="flex items-center justify-between mt-3">
                  <p v-if="form.errors.report_types" class="text-xs text-red-500">{{ form.errors.report_types }}</p>
                  <p class="text-xs text-gray-400 ml-auto">
                    <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ form.report_types.length }}</span> of {{ Object.keys(reportTypes).length }} selected
                  </p>
                </div>
              </div>

              <!-- ── Step 3: Schedule ── -->
              <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
                <div class="flex items-center gap-2 mb-4">
                  <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-xs font-bold text-indigo-600 dark:text-indigo-400">3</span>
                  <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Schedule</h4>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                  <div>
                    <label class="block mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">Frequency</label>
                    <select v-model="form.interval" required @change="onIntervalChange" class="w-full py-2.5 px-3 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:bg-gray-700/50 dark:border-gray-600 dark:text-white transition">
                      <option value="">Choose...</option>
                      <option v-for="(label, key) in intervals" :key="key" :value="key">{{ label }}</option>
                    </select>
                  </div>
                  <div v-if="form.interval === 'weekly'">
                    <label class="block mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">Day of Week</label>
                    <select v-model="form.day_of_week" class="w-full py-2.5 px-3 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:bg-gray-700/50 dark:border-gray-600 dark:text-white transition capitalize">
                      <option v-for="d in daysOfWeek" :key="d" :value="d">{{ d }}</option>
                    </select>
                  </div>
                  <div v-else-if="form.interval === 'monthly'">
                    <label class="block mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">Day of Month</label>
                    <select v-model="form.day_of_month" class="w-full py-2.5 px-3 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:bg-gray-700/50 dark:border-gray-600 dark:text-white transition">
                      <option v-for="d in 28" :key="d" :value="d">{{ ordinal(d) }}</option>
                    </select>
                  </div>
                  <div>
                    <label class="block mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">Time (24h)</label>
                    <select v-model="form.time" class="w-full py-2.5 px-3 text-sm bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:bg-gray-700/50 dark:border-gray-600 dark:text-white transition">
                      <option v-for="t in timeOptions" :key="t" :value="t">{{ t }}</option>
                    </select>
                  </div>
                </div>

                <!-- Visual schedule pill -->
                <div v-if="form.interval" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-indigo-50 dark:bg-indigo-950/30 text-sm">
                  <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  <span class="text-indigo-700 dark:text-indigo-300 font-medium">{{ scheduleLabel }}</span>
                </div>
              </div>

              <!-- ── Summary Preview ── -->
              <div v-if="form.user_ids.length && form.report_types.length && form.interval" class="bg-linear-to-r from-indigo-500 to-purple-600 rounded-xl p-5 text-white shadow-lg">
                <div class="flex items-center gap-2 mb-3">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  <p class="text-sm font-semibold">Ready to create</p>
                </div>
                <div class="grid grid-cols-3 gap-4 text-center">
                  <div>
                    <p class="text-2xl font-bold">{{ form.user_ids.length }}</p>
                    <p class="text-xs text-indigo-100">Recipients</p>
                  </div>
                  <div>
                    <p class="text-2xl font-bold">× {{ form.report_types.length }}</p>
                    <p class="text-xs text-indigo-100">Reports</p>
                  </div>
                  <div>
                    <p class="text-2xl font-bold">= {{ form.user_ids.length * form.report_types.length }}</p>
                    <p class="text-xs text-indigo-100">Subscriptions</p>
                  </div>
                </div>
              </div>

              <!-- ── Submit ── -->
              <div class="sticky bottom-0 -mx-6 -mb-6 mt-6 px-6 py-4 border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 rounded-b-xl">
                <div class="flex items-center justify-between">
                  <p class="text-xs text-gray-400">{{ form.user_ids.length * form.report_types.length || 0 }} subscription(s) will be created</p>
                  <div class="flex items-center gap-3">
                    <button type="button" @click="closeModal" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition">Cancel</button>
                    <button
                      type="submit"
                      :disabled="form.processing || !form.user_ids.length || !form.report_types.length || !form.interval"
                      class="px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg shadow-sm transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                      <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                      {{ form.processing ? 'Creating...' : 'Create Subscriptions' }}
                    </button>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ═══════════════════════════════════════════ -->
    <!-- Ticket Service Reports -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="mt-10">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h2 class="text-lg font-bold text-gray-900 dark:text-white">TPS Service Reports</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Auto-generated service reports from resolved tickets</p>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-800/80">
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Report #</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subject</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">TPS</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Generated</th>
                <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
              <tr v-for="report in ticketReports?.data" :key="report.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-4 py-3.5 text-sm font-semibold text-gray-900 dark:text-white">#{{ report.id }}</td>
                <td class="px-4 py-3.5 text-sm text-gray-700 dark:text-gray-300 max-w-xs truncate">{{ report.subject }}</td>
                <td class="px-4 py-3.5 text-sm text-gray-600 dark:text-gray-400">{{ report.tps?.name || '—' }}</td>
                <td class="px-4 py-3.5">
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                    :class="report.status === 'finalized'
                      ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                      : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'"
                  >
                    {{ report.status === 'finalized' ? 'Finalized' : 'Draft' }}
                  </span>
                </td>
                <td class="px-4 py-3.5 text-sm text-gray-500 dark:text-gray-400">{{ report.generated_at ? new Date(report.generated_at).toLocaleDateString() : '—' }}</td>
                <td class="px-4 py-3.5 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <a
                      v-if="report.report_pdf_path"
                      :href="`/reports/ticket-service-reports/${report.id}/download`"
                      class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 transition-colors"
                      title="Download PDF"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </a>
                    <span v-else class="text-xs text-gray-400 dark:text-gray-500 italic">No PDF</span>
                    <button
                      @click="deleteReport(report)"
                      class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors"
                      title="Delete"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="ticketReports?.data?.length" class="flex items-center justify-between px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
          <p class="text-xs text-gray-500 dark:text-gray-400">
            Showing {{ ticketReports.from }}–{{ ticketReports.to }} of {{ ticketReports.total }} reports
          </p>
          <div class="flex items-center gap-2">
            <Link
              v-if="ticketReports.prev_page_url"
              :href="ticketReports.prev_page_url"
              class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition"
            >
              Previous
            </Link>
            <Link
              v-if="ticketReports.next_page_url"
              :href="ticketReports.next_page_url"
              class="px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-500 transition"
            >
              Next
            </Link>
          </div>
        </div>

        <!-- Empty state -->
        <div v-if="!ticketReports?.data?.length" class="px-5 py-16 text-center">
          <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          <p class="text-sm text-gray-500 dark:text-gray-400">No service reports yet.</p>
          <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Reports are auto-generated when a TPS resolves a ticket.</p>
        </div>
      </div>
    </div>

    <!-- Global Confirm Dialog -->
    <ConfirmDialog
      ref="confirmDialog"
      :title="confirmState.title"
      :message="confirmState.message"
      :confirmLabel="confirmState.confirmLabel"
      :variant="confirmState.variant"
      @confirm="confirmState.onConfirm()"
      @cancel="confirmState.onCancel"
    />
  </AppLayout>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps({
  groupedSubscriptions: Array,
  allUsers: Array,
  reportTypes: Object,
  intervals: Object,
  daysOfWeek: Array,
  timeOptions: Array,
  ticketReports: Object,
});

const showModal = ref(false);
const userSearch = ref('');
const confirmDialog = ref(null);
const editingSub = ref(null); // the subscription being edited
const editForm = reactive({
  interval: 'daily',
  day_of_week: 'monday',
  day_of_month: 1,
  time: '08:00',
});

const confirmState = reactive({
  title: '',
  message: '',
  confirmLabel: 'Delete',
  variant: 'danger',
  onConfirm: () => {},
  onCancel: () => {},
});

const showConfirm = ({ title, message, confirmLabel = 'Delete', variant = 'danger', onConfirm }) => {
  confirmState.title = title;
  confirmState.message = message;
  confirmState.confirmLabel = confirmLabel;
  confirmState.variant = variant;
  confirmState.onConfirm = onConfirm;
  confirmDialog.value?.show();
};

const openModal = () => { showModal.value = true; };
const closeModal = () => { showModal.value = false; userSearch.value = ''; };

const filteredUsers = computed(() => {
  if (!userSearch.value) return props.allUsers;
  const q = userSearch.value.toLowerCase();
  return props.allUsers.filter(u =>
    u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
  );
});

const form = useForm({
  user_ids: [],
  report_types: [],
  interval: '',
  day_of_week: 'monday',
  day_of_month: 1,
  time: '08:00',
});

const selectAllUsers = () => {
  form.user_ids = filteredUsers.value.map(u => u.id);
};
const selectAllReports = () => {
  form.report_types = Object.keys(props.reportTypes);
};

const onIntervalChange = () => {
  if (form.interval !== 'weekly') form.day_of_week = 'monday';
  if (form.interval !== 'monthly') form.day_of_month = 1;
};

const scheduleLabel = computed(() => {
  const freq = props.intervals[form.interval] || '';
  if (form.interval === 'weekly') return `Every ${form.day_of_week} at ${form.time}`;
  if (form.interval === 'monthly') return `Monthly on day ${form.day_of_month} at ${form.time}`;
  return `${freq} at ${form.time}`;
});

const reportIcon = (key) => ({
  'tractor-usage': '🚜', 'maintenance-summary': '🔧', 'booking-summary': '📅',
  'device-status': '📡', 'alerts-history': '🔔', 'ticket-summary': '🎫',
}[key] || '📄');

const ordinal = (n) => {
  const s = ['th', 'st', 'nd', 'rd'];
  const v = n % 100;
  return n + (s[(v - 20) % 10] || s[v] || s[0]);
};

const createSubscription = () => {
  form.post('/reports/subscriptions', {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      form.user_ids = [];
      form.report_types = [];
      userSearch.value = '';
      closeModal();
    },
  });
};

const toggleActive = (sub) => {
  router.put(`/reports/subscriptions/${sub.id}`, {
    interval: sub.interval,
    day_of_week: sub.day_of_week,
    day_of_month: sub.day_of_month,
    time: sub.time,
    is_active: !sub.is_active,
  }, { preserveScroll: true });
};

const deleteSubscription = (id) => {
  showConfirm({
    title: 'Remove subscription?',
    message: 'This report will no longer be sent to the user.',
    confirmLabel: 'Remove',
    variant: 'danger',
    onConfirm: () => {
      router.delete(`/reports/subscriptions/${id}`, { preserveScroll: true });
    },
  });
};

const deleteReport = (report) => {
  showConfirm({
    title: 'Delete service report?',
    message: `Are you sure you want to delete report #${report.id}${report.subject ? ' (' + report.subject + ')' : ''}? This action cannot be undone.`,
    confirmLabel: 'Delete',
    variant: 'danger',
    onConfirm: () => {
      router.delete(`/reports/ticket-service-reports/${report.id}`, { preserveScroll: true });
    },
  });
};

const startEdit = (sub) => {
  editingSub.value = sub;
  editForm.interval = sub.interval;
  editForm.day_of_week = sub.day_of_week || 'monday';
  editForm.day_of_month = sub.day_of_month || 1;
  editForm.time = sub.time;
};

const saveEdit = (sub) => {
  router.put(`/reports/subscriptions/${sub.id}`, {
    interval: editForm.interval,
    day_of_week: editForm.interval === 'weekly' ? editForm.day_of_week : undefined,
    day_of_month: editForm.interval === 'monthly' ? editForm.day_of_month : undefined,
    time: editForm.time,
    is_active: sub.is_active,
  }, {
    preserveScroll: true,
    onSuccess: () => { editingSub.value = null; },
  });
};

const removeUserSubscriptions = (userId) => {
  const group = props.groupedSubscriptions.find(g => g.user_id === userId);
  const count = group?.subscriptions?.length || 0;
  const userName = group?.user?.name || 'this user';

  showConfirm({
    title: `Remove all from ${userName}?`,
    message: `This will permanently delete ${count} report subscription${count > 1 ? 's' : ''}.`,
    confirmLabel: 'Remove All',
    variant: 'danger',
    onConfirm: () => {
      group.subscriptions.forEach(sub => {
        router.delete(`/reports/subscriptions/${sub.id}`, { preserveScroll: true });
      });
    },
  });
};

const relativeTime = (dateStr) => {
  if (!dateStr) return '—';
  const diff = new Date(dateStr) - Date.now();
  const mins = Math.round(diff / 60000);
  if (mins <= 0) return 'Now';
  if (mins < 60) return `${mins}m`;
  const hrs = Math.round(mins / 60);
  if (hrs < 24) return `${hrs}h`;
  return `${Math.round(hrs/24)}d`;
};

const formatDate = (d) => {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>
