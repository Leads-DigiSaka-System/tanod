<template>
  <AppLayout>
    <Head title="Collectibles" />

    <!-- Page Header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Collectibles</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage payments and collections from resolved tickets.</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
      <nav class="-mb-px flex gap-6">
        <button @click="switchTab('collectible')"
          class="whitespace-nowrap pb-3 px-1 border-b-2 text-sm font-medium transition-colors"
          :class="activeTab === 'collectible'
            ? 'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400'
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'">
          <span class="inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            Collectible
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ tabCounts.collectible }}</span>
          </span>
        </button>
        <button @click="switchTab('to_approve')"
          class="whitespace-nowrap pb-3 px-1 border-b-2 text-sm font-medium transition-colors"
          :class="activeTab === 'to_approve'
            ? 'border-amber-600 text-amber-600 dark:border-amber-400 dark:text-amber-400'
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'">
          <span class="inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            To Approve
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ tabCounts.to_approve }}</span>
          </span>
        </button>
        <button @click="switchTab('paid')"
          class="whitespace-nowrap pb-3 px-1 border-b-2 text-sm font-medium transition-colors"
          :class="activeTab === 'paid'
            ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'">
          <span class="inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            Paid
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">{{ tabCounts.paid }}</span>
          </span>
        </button>
      </nav>
    </div>

    <!-- ==================== COLLECTIBLE TAB ==================== -->
    <template v-if="activeTab === 'collectible'">
      <div class="bg-white rounded-xl border border-gray-200/60 shadow-sm overflow-hidden dark:bg-gray-800/60 dark:border-gray-700/50 relative">
        <!-- Loading overlay -->
        <div v-if="pageLoading" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/70 dark:bg-gray-800/70 backdrop-blur-[1px]">
          <div class="flex flex-col items-center gap-3">
            <svg class="animate-spin h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 animate-pulse">Loading...</p>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100 dark:border-gray-700/50">
                <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">FCA Name</th>
                <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Contact No.</th>
                <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Remaining Balance</th>
                <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Payment</th>
                <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Due Date</th>
                <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Last Payment</th>
                <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/30">
              <tr v-for="ticket in tickets.data" :key="ticket.id" class="group hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-5 py-4">
                  <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-bold text-white shrink-0" style="background-color: #007f3d;">
                      {{ (ticket.fca_name || '?').charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                      <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ ticket.fca_name || '—' }}</p>
                      <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ ticket.subject }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">{{ ticket.submitter?.phone || '—' }}</td>
                <td class="px-5 py-4">
                  <span class="text-sm font-semibold text-red-600 dark:text-red-400">₱{{ formatNumber(ticket.remaining_balance) }}</span>
                </td>
                <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">₱{{ formatNumber(ticket.total_paid) }}</td>
                <td class="px-5 py-4">
                  <div class="flex items-center gap-1.5">
                    <span v-if="ticket.installments > 0 && ticket.next_due_date" class="text-sm" :class="ticket.is_overdue ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-600 dark:text-gray-400'">
                      {{ formatDateOnly(ticket.next_due_date) }}
                    </span>
                    <span v-else class="text-sm text-gray-400 dark:text-gray-500">—</span>
                    <span v-if="ticket.monthly_amount > 0" class="text-[10px] text-gray-400 dark:text-gray-500">
                      (₱{{ formatNumber(ticket.monthly_amount) }}/mo)
                    </span>
                    <span v-if="ticket.is_overdue"
                      class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                      Overdue
                    </span>
                  </div>
                </td>
                <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">
                  {{ ticket.last_payment ? formatDate(ticket.last_payment.paid_at) : '—' }}
                </td>
                <td class="px-5 py-4 text-right">
                  <button @click="openDetailDrawer(ticket)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors"
                    :class="activeTab === 'collectible'
                      ? 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:text-emerald-400 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/30'
                      : activeTab === 'to_approve'
                      ? 'text-amber-700 bg-amber-50 hover:bg-amber-100 dark:text-amber-400 dark:bg-amber-900/20 dark:hover:bg-amber-900/30'
                      : 'text-blue-700 bg-blue-50 hover:bg-blue-100 dark:text-blue-400 dark:bg-blue-900/20 dark:hover:bg-blue-900/30'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    {{ activeTab === 'collectible' ? 'Collect' : activeTab === 'to_approve' ? 'Review' : 'View' }}
                  </button>
                </td>
              </tr>
              <tr v-if="!tickets.data?.length">
                <td colspan="7" class="px-5 py-16 text-center">
                  <div class="flex flex-col items-center gap-3">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800">
                      <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No collectibles found</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Resolved tickets will appear here.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <Pagination :links="tickets.links" class="mt-6" />
    </template>

    <!-- ==================== TO APPROVE TAB ==================== -->
    <template v-if="activeTab === 'to_approve'">
      <div class="bg-white rounded-xl border border-gray-200/60 shadow-sm overflow-hidden dark:bg-gray-800/60 dark:border-gray-700/50 relative">
        <!-- Loading overlay -->
        <div v-if="pageLoading" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/70 dark:bg-gray-800/70 backdrop-blur-[1px]">
          <div class="flex flex-col items-center gap-3">
            <svg class="animate-spin h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 animate-pulse">Loading...</p>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100 dark:border-gray-700/50">
                <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">FCA Name</th>
                <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Ticket</th>
                <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Amount</th>
                <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Resolved Date</th>
                <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/30">
              <tr v-for="ticket in tickets.data" :key="ticket.id" class="group hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-5 py-4">
                  <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-bold text-white shrink-0" style="background-color: #007f3d;">
                      {{ (ticket.fca_name || '?').charAt(0).toUpperCase() }}
                    </div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ ticket.fca_name || '—' }}</p>
                  </div>
                </td>
                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-[200px] truncate">{{ ticket.subject }}</td>
                <td class="px-5 py-4 text-sm font-semibold text-gray-700 dark:text-gray-300">₱{{ formatNumber(ticket.total_amount) }}</td>
                <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ ticket.resolved_at ? formatDate(ticket.resolved_at) : '—' }}</td>
                <td class="px-5 py-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button @click="approveTicket(ticket)"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:text-emerald-400 dark:bg-emerald-900/20 dark:hover:bg-emerald-900/30 transition-colors">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                      Approve
                    </button>
                    <button @click="openDetailDrawer(ticket)"
                      class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg text-gray-600 bg-gray-50 hover:bg-gray-100 dark:text-gray-400 dark:bg-gray-700 dark:hover:bg-gray-600 transition-colors">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                      View
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!tickets.data?.length">
                <td colspan="5" class="px-5 py-16 text-center">
                  <div class="flex flex-col items-center gap-3">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800">
                      <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No pending approvals</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">All resolved tickets have been reviewed.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <Pagination :links="tickets.links" class="mt-6" />
    </template>

    <!-- ==================== PAID TAB ==================== -->
    <template v-if="activeTab === 'paid'">
      <div class="bg-white rounded-xl border border-gray-200/60 shadow-sm overflow-hidden dark:bg-gray-800/60 dark:border-gray-700/50 relative">
        <!-- Loading overlay -->
        <div v-if="pageLoading" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/70 dark:bg-gray-800/70 backdrop-blur-[1px]">
          <div class="flex flex-col items-center gap-3">
            <svg class="animate-spin h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400 animate-pulse">Loading...</p>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-100 dark:border-gray-700/50">
                <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">FCA Name</th>
                <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Contact No.</th>
                <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Amount Paid</th>
                <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Date Fully Paid</th>
                <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/30">
              <tr v-for="ticket in tickets.data" :key="ticket.id" class="group hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors">
                <td class="px-5 py-4">
                  <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-9 h-9 rounded-full text-sm font-bold text-white shrink-0" style="background-color: #007f3d;">
                      {{ (ticket.fca_name || '?').charAt(0).toUpperCase() }}
                    </div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ ticket.fca_name || '—' }}</p>
                  </div>
                </td>
                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">{{ ticket.submitter?.phone || '—' }}</td>
                <td class="px-5 py-4 text-sm font-semibold text-emerald-600 dark:text-emerald-400">₱{{ formatNumber(ticket.total_paid) }}</td>
                <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ ticket.last_payment ? formatDate(ticket.last_payment.paid_at) : '—' }}</td>
                <td class="px-5 py-4 text-right">
                  <button @click="openDetailDrawer(ticket)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg text-blue-700 bg-blue-50 hover:bg-blue-100 dark:text-blue-400 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                    View
                  </button>
                </td>
              </tr>
              <tr v-if="!tickets.data?.length">
                <td colspan="5" class="px-5 py-16 text-center">
                  <div class="flex flex-col items-center gap-3">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800">
                      <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No paid records yet</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Fully paid collectibles will appear here.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <Pagination :links="tickets.links" class="mt-6" />
    </template>

    <!-- ==================== DETAIL DRAWER (Slide-over) ==================== -->
    <Transition
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0">
      <div v-if="drawerOpen" class="fixed inset-0 z-40 bg-gray-900/50" @click="closeDrawer"></div>
    </Transition>

    <Transition
      enter-active-class="transition-transform duration-300 ease-out"
      enter-from-class="translate-x-full"
      enter-to-class="translate-x-0"
      leave-active-class="transition-transform duration-200 ease-in"
      leave-from-class="translate-x-0"
      leave-to-class="translate-x-full">
      <div v-if="drawerOpen" class="fixed inset-y-0 right-0 z-50 w-full max-w-2xl">
        <div class="flex h-full flex-col bg-white shadow-2xl dark:bg-gray-800">
          <!-- Drawer Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 shrink-0" style="background-color: #007f3d;">
            <div>
              <h2 class="text-lg font-semibold text-white">Collectible Details</h2>
              <p class="text-sm text-green-100">{{ selectedTicket?.fca_name }} &middot; #{{ selectedTicket?.id }}</p>
            </div>
            <button @click="closeDrawer" class="rounded-lg p-1.5 text-green-100 hover:text-white hover:bg-white/20 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>

          <!-- Drawer Body -->
          <div class="flex-1 overflow-y-auto">
            <div class="p-6 space-y-6">
              <!-- Loading state (always visible while fetching) -->
              <div v-if="loadingDetail" class="flex flex-col items-center justify-center py-20">
                <div class="relative">
                  <svg class="animate-spin h-10 w-10 text-emerald-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                </div>
                <p class="mt-4 text-sm font-medium text-gray-500 dark:text-gray-400 animate-pulse">Loading details...</p>
              </div>

              <template v-if="!loadingDetail && ticketDetail">
                <!-- FCA Info -->
                <div class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                  <div class="flex items-center justify-center w-12 h-12 rounded-full text-lg font-bold text-white shrink-0" style="background-color: #007f3d;">
                    {{ (ticketDetail.fca_name || '?').charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ ticketDetail.fca_name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ ticketDetail.submitter?.phone || '—' }} &middot; {{ ticketDetail.submitter?.email || '—' }}</p>
                    <p v-if="ticketDetail.tractor" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                      {{ ticketDetail.tractor.brand }} {{ ticketDetail.tractor.model }} &middot; {{ ticketDetail.tractor.no_plate }}
                    </p>
                    <!-- Installment Info -->
                    <div v-if="ticketDetail.installments > 0" class="flex items-center gap-2 mt-2 pt-2 border-t border-gray-200 dark:border-gray-600 flex-wrap">
                      <span class="text-xs font-medium text-gray-500 dark:text-gray-400">₱{{ formatNumber(ticketDetail.monthly_amount) }}/mo × {{ ticketDetail.installments }} months</span>
                      <span v-if="ticketDetail.down_payment > 0" class="text-[10px] text-gray-400 dark:text-gray-500">
                        (₱{{ formatNumber(ticketDetail.installment_base) }} ÷ {{ ticketDetail.installments }})
                      </span>
                      <span v-if="ticketDetail.is_overdue"
                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                        Overdue
                      </span>
                      <span v-else class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                        Month {{ ticketDetail.current_month }} of {{ ticketDetail.installments }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Parts Used -->
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    Parts Used
                  </h3>
                  <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <table class="w-full text-sm">
                      <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700/50">
                          <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Part Name</th>
                          <th class="px-4 py-2.5 text-center text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Qty</th>
                          <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Price</th>
                          <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Subtotal</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-for="part in ticketDetail.tractor_parts" :key="part.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30">
                          <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">{{ part.name }}</td>
                          <td class="px-4 py-2.5 text-center text-gray-600 dark:text-gray-400">{{ part.quantity }}</td>
                          <td class="px-4 py-2.5 text-right text-gray-600 dark:text-gray-400">₱{{ formatNumber(part.amount) }}</td>
                          <td class="px-4 py-2.5 text-right font-medium text-gray-900 dark:text-white">₱{{ formatNumber(part.subtotal) }}</td>
                        </tr>
                      </tbody>
                      <tfoot>
                        <tr class="bg-gray-50/80 dark:bg-gray-700/30 font-medium">
                          <td colspan="3" class="px-4 py-2.5 text-right text-sm text-gray-600 dark:text-gray-400">Total Parts</td>
                          <td class="px-4 py-2.5 text-right text-sm text-gray-900 dark:text-white">₱{{ formatNumber(ticketDetail.total_parts) }}</td>
                        </tr>
                        <tr class="bg-gray-50/80 dark:bg-gray-700/30">
                          <td colspan="3" class="px-4 py-2.5 text-right text-sm text-gray-600 dark:text-gray-400">Service Charge</td>
                          <td class="px-4 py-2.5 text-right text-sm text-gray-900 dark:text-white">₱{{ formatNumber(ticketDetail.service_charge) }}</td>
                        </tr>
                        <tr class="bg-gray-50/80 dark:bg-gray-700/30 border-t-2 border-gray-200 dark:border-gray-600">
                          <td colspan="3" class="px-4 py-2.5 text-right text-sm font-bold text-gray-900 dark:text-white">Total Amount to Pay</td>
                          <td class="px-4 py-2.5 text-right text-sm font-bold text-gray-900 dark:text-white">₱{{ formatNumber(ticketDetail.total_amount) }}</td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>

                <!-- Payment Summary Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                  <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-700/30">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Down Payment</p>
                    <p class="text-lg font-bold text-amber-600 dark:text-amber-400">₱{{ formatNumber(ticketDetail.down_payment) }}</p>
                  </div>
                  <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-700/30">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">To Pay in Installments</p>
                    <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400">₱{{ formatNumber(ticketDetail.installment_base) }}</p>
                    <p v-if="ticketDetail.installments > 0" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ ticketDetail.installments }} mo × ₱{{ formatNumber(ticketDetail.monthly_amount) }}</p>
                  </div>
                  <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-700/30">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Total Paid</p>
                    <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">₱{{ formatNumber(ticketDetail.total_paid) }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ ticketDetail.payments.length + (ticketDetail.down_payment > 0 ? 1 : 0) }} payment(s)</p>
                  </div>
                  <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-700/30">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Remaining Balance</p>
                    <p class="text-lg font-bold" :class="ticketDetail.remaining_balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400'">
                      ₱{{ formatNumber(ticketDetail.remaining_balance) }}
                    </p>
                    <p v-if="ticketDetail.remaining_balance <= 0" class="text-xs text-emerald-500 mt-0.5">Fully Paid ✓</p>
                  </div>
                </div>

                <!-- Installment Schedule -->
                <div v-if="ticketDetail.installment_schedule?.length">
                  <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    Monthly Installment Schedule
                  </h3>
                  <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <table class="w-full text-sm">
                      <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700/50">
                          <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Month</th>
                          <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Due Date</th>
                          <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Amount</th>
                          <th class="px-4 py-2.5 text-center text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Status</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-for="inst in ticketDetail.installment_schedule" :key="inst.month"
                          class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors"
                          :class="inst.status === 'overdue' ? 'bg-red-50/50 dark:bg-red-900/10' : inst.status === 'paid' ? 'bg-emerald-50/50 dark:bg-emerald-900/10' : ''">
                          <td class="px-4 py-2.5">
                            <span class="text-sm font-medium" :class="inst.status === 'paid' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-700 dark:text-gray-300'">
                              Month {{ inst.month }}
                            </span>
                          </td>
                          <td class="px-4 py-2.5 text-sm" :class="inst.status === 'overdue' ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-600 dark:text-gray-400'">
                            {{ formatDateOnly(inst.due_date) }}
                          </td>
                          <td class="px-4 py-2.5 text-right text-sm font-medium text-gray-700 dark:text-gray-300">₱{{ formatNumber(inst.amount) }}</td>
                          <td class="px-4 py-2.5 text-center">
                            <span v-if="inst.status === 'paid'" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                              Paid
                            </span>
                            <span v-else-if="inst.status === 'overdue'" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                              Overdue
                            </span>
                            <span v-else-if="inst.status === 'pending'" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                              Pending
                            </span>
                            <span v-else class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                              Upcoming
                            </span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <!-- Payment History -->
                <div>
                  <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    Payment History
                  </h3>
                  <div class="space-y-2">
                    <!-- Down Payment Entry -->
                    <div v-if="ticketDetail.down_payment > 0" class="flex items-center justify-between p-3 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/30">
                      <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/40">
                          <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                        <div>
                          <p class="text-sm font-medium text-gray-900 dark:text-white">Down Payment</p>
                          <p class="text-xs text-gray-500 dark:text-gray-400">Initial payment &middot; {{ ticketDetail.resolved_at ? formatDate(ticketDetail.resolved_at) : '—' }}</p>
                        </div>
                      </div>
                      <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">₱{{ formatNumber(ticketDetail.down_payment) }}</p>
                    </div>

                    <!-- Subsequent Payments -->
                    <div v-for="pm in ticketDetail.payments" :key="pm.id" class="flex items-center justify-between p-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-800/30">
                      <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                          <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div>
                          <p class="text-sm font-medium text-gray-900 dark:text-white">Payment #{{ ticketDetail.payments.length - ticketDetail.payments.indexOf(pm) }}</p>
                          <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ pm.paid_at ? formatDate(pm.paid_at) : '—' }}
                            <span v-if="pm.collected_by"> &middot; {{ pm.collected_by }}</span>
                          </p>
                          <p v-if="pm.notes" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ pm.notes }}</p>
                        </div>
                      </div>
                      <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">₱{{ formatNumber(pm.amount) }}</p>
                    </div>

                    <div v-if="ticketDetail.payments.length === 0 && ticketDetail.down_payment <= 0" class="text-center py-6 text-sm text-gray-400 dark:text-gray-500">
                      No payments recorded yet.
                    </div>
                  </div>
                </div>

                <!-- Add Payment Form -->
                <div v-if="ticketDetail.remaining_balance > 0" class="rounded-xl border-2 border-dashed border-emerald-200 dark:border-emerald-800 p-5 bg-emerald-50/50 dark:bg-emerald-900/10">
                  <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    Record Payment
                  </h3>
                  <form @submit.prevent="submitPayment">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                      <div>
                        <label class="block mb-1.5 text-xs font-medium text-gray-600 dark:text-gray-400">Amount <span class="text-red-500">*</span></label>
                        <div class="relative">
                          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-500 dark:text-gray-400">₱</span>
                          <input v-model="paymentForm.amount" type="number" step="0.01" min="0.01" :max="ticketDetail.remaining_balance" placeholder="0.00"
                            class="w-full rounded-lg border-gray-300 pl-8 pr-3 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                        </div>
                        <p v-if="paymentForm.errors.amount" class="mt-1 text-xs text-red-600">{{ paymentForm.errors.amount }}</p>
                        <button v-if="ticketDetail.monthly_amount > 0" type="button" @click="paymentForm.amount = ticketDetail.monthly_amount"
                          class="mt-1 text-[11px] text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 font-medium">
                          ⚡ Set monthly amount (₱{{ formatNumber(ticketDetail.monthly_amount) }})
                        </button>
                      </div>
                      <div>
                        <label class="block mb-1.5 text-xs font-medium text-gray-600 dark:text-gray-400">Payment Date</label>
                        <input v-model="paymentForm.paid_at" type="date"
                          class="w-full rounded-lg border-gray-300 py-2.5 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
                      </div>
                    </div>
                    <div class="mb-4">
                      <label class="block mb-1.5 text-xs font-medium text-gray-600 dark:text-gray-400">Notes (optional)</label>
                      <textarea v-model="paymentForm.notes" rows="2" placeholder="Add notes about this payment..."
                        class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400"></textarea>
                    </div>
                    <div class="flex items-center justify-between">
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        Remaining: <span class="font-semibold text-red-600 dark:text-red-400">₱{{ formatNumber(ticketDetail.remaining_balance) }}</span>
                      </p>
                      <button type="submit" :disabled="paymentForm.processing"
                        class="inline-flex items-center gap-1.5 rounded-lg px-5 py-2.5 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        style="background-color: #007f3d;"
                        @mouseenter="!paymentForm.processing && ($event.target.style.backgroundColor='#006631')"
                        @mouseleave="!paymentForm.processing && ($event.target.style.backgroundColor='#007f3d')">
                        <svg v-if="paymentForm.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        Record Payment
                      </button>
                    </div>
                  </form>
                </div>

                <!-- Fully Paid Message -->
                <div v-else class="rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 p-5 text-center">
                  <svg class="w-10 h-10 text-emerald-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                  <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-400">Fully Paid</p>
                  <p class="text-xs text-emerald-600 dark:text-emerald-500 mt-1">This collectible has been fully paid.</p>
                </div>
              </template>
            </div>
          </div>

          <!-- Drawer Footer -->
          <div class="shrink-0 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 px-6 py-4 flex justify-end">
            <button @click="closeDrawer"
              class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
              Close
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import axios from 'axios';
import { formatDate, formatDateOnly } from '@/utils/dateFormat';

const props = defineProps({
  tickets: Object,
  filters: Object,
  tabCounts: Object,
});

// --- Tabs ---
const activeTab = ref(props.filters?.tab || 'collectible');
const pageLoading = ref(false);

const switchTab = (tab) => {
  activeTab.value = tab;
  pageLoading.value = true;
  router.get('/collectibles', { tab }, {
    preserveState: true,
    replace: true,
    onFinish: () => { pageLoading.value = false; },
  });
};

// --- Helper: Format numbers ---
function formatNumber(val) {
  if (val === null || val === undefined) return '0.00';
  return Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// --- Detail Drawer ---
const drawerOpen = ref(false);
const loadingDetail = ref(false);
const selectedTicket = ref(null);
const ticketDetail = ref(null);

const paymentForm = useForm({
  amount: '',
  paid_at: new Date().toISOString().split('T')[0],
  notes: '',
});

async function openDetailDrawer(ticket) {
  selectedTicket.value = ticket;
  drawerOpen.value = true;
  loadingDetail.value = true;
  ticketDetail.value = null;
  paymentForm.reset();
  paymentForm.paid_at = new Date().toISOString().split('T')[0];

  try {
    const response = await axios.get(`/collectibles/${ticket.id}`);
    ticketDetail.value = response.data.ticket;
  } catch (err) {
    console.error('Failed to load ticket details', err);
  } finally {
    loadingDetail.value = false;
  }
}

function closeDrawer() {
  drawerOpen.value = false;
  selectedTicket.value = null;
  ticketDetail.value = null;
}

function submitPayment() {
  if (!selectedTicket.value) return;

  paymentForm.post(`/collectibles/${selectedTicket.value.id}/payment`, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      // Reload the drawer with updated data
      openDetailDrawer(selectedTicket.value);
      // Reload the page to update counts
      router.reload({ preserveState: true, preserveScroll: true });
    },
  });
}

function approveTicket(ticket) {
  router.post(`/collectibles/${ticket.id}/approve`, {}, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      router.reload({ preserveState: true });
    },
  });
}
</script>
