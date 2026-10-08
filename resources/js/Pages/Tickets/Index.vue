<template>
  <AppLayout>
    <Head title="Support Tickets" />

    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Support Tickets</h1>
      <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ tickets.total || 0 }} tickets</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200/60 shadow-sm p-5 mb-6 dark:bg-gray-800/60 dark:border-gray-700/50">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
          <label class="block mb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Search</label>
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input v-model="search" type="text" placeholder="Search subject..." @input="debouncedFilter"
              class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 block w-full pl-10 pr-3 py-2.5 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500/20 dark:focus:border-indigo-500 transition-shadow" />
          </div>
        </div>
        <div>
          <label class="block mb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</label>
          <select v-model="statusFilter" @change="applyFilter"
            class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-800 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 dark:focus:ring-indigo-500/20 dark:focus:border-indigo-500 transition-shadow">
            <option value="">All Status</option>
            <option value="open">Open</option>
            <option value="in_progress">In Progress</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
          </select>
        </div>
        <div>
          <label class="block mb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Priority</label>
          <select v-model="priorityFilter" @change="applyFilter"
            class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-800 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 dark:focus:ring-indigo-500/20 dark:focus:border-indigo-500 transition-shadow">
            <option value="">All Priority</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex items-center gap-1 mb-6">
      <button @click="activeTab = 'current'"
        :class="activeTab === 'current'
          ? 'bg-white text-gray-900 shadow-sm border border-gray-200/60 dark:bg-gray-800 dark:text-white dark:border-gray-700/50'
          : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
        class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors">
        Current
      </button>
      <button @click="activeTab = 'old'"
        :class="activeTab === 'old'
          ? 'bg-white text-gray-900 shadow-sm border border-gray-200/60 dark:bg-gray-800 dark:text-white dark:border-gray-700/50'
          : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
        class="px-4 py-2 text-sm font-semibold rounded-lg transition-colors">
        Old Data
        <span v-if="oldTickets?.total" class="ml-1.5 inline-flex items-center justify-center w-5 h-5 text-[11px] font-bold rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ oldTickets.total }}</span>
      </button>
    </div>

    <!-- Current Tab -->
    <template v-if="activeTab === 'current'">
    <!-- Table -->
    <div class="mb-6">
      <DataTable>
        <template #head>
          <tr class="border-b border-gray-100 dark:border-gray-700/50">
            <th scope="col" class="px-4 py-3.5 w-10">
              <input type="checkbox" :checked="isAllSelected" @click="toggleSelectAll($event)"
                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
            </th>
            <th scope="col" class="px-5 py-3.5 w-16 whitespace-nowrap">
              <button @click="toggleSort('id')" class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors group">
                #
                <svg v-if="sort === 'id'" :class="direction === 'asc' ? 'rotate-0' : 'rotate-180'" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"/></svg>
                <svg v-else class="w-3 h-3 text-gray-300 dark:text-gray-600 group-hover:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
              </button>
            </th>
            <th v-for="col in [
              { label: 'Type', field: 'category' },
              { label: 'Name', field: 'tractor_name' },
              { label: 'Subject', field: 'subject' },
              { label: 'Action Taken', field: 'description' },
              { label: 'Service Charge', field: 'service_charge' },
              { label: 'Status', field: 'status' },
              { label: 'Reported', field: 'reported_date' },
            ]" :key="col.field" scope="col" class="px-5 py-3.5 whitespace-nowrap">
              <button @click="toggleSort(col.field)" class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors group">
                {{ col.label }}
                <svg v-if="sort === col.field" :class="direction === 'asc' ? 'rotate-0' : 'rotate-180'" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"/></svg>
                <svg v-else class="w-3 h-3 text-gray-300 dark:text-gray-600 group-hover:text-gray-400 opacity-0 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
              </button>
            </th>
            <th scope="col" class="px-5 py-3.5 text-right w-16 whitespace-nowrap">
              <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</span>
            </th>
          </tr>
        </template>
        <template #body>
          <tr v-for="ticket in tickets.data" :key="ticket.id" class="group hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors border-b border-gray-100 dark:border-gray-700/50"
            :class="{ 'bg-indigo-50/50 dark:bg-indigo-900/10': selectedIsChecked(ticket.id) }">
            <td class="px-4 py-3.5">
              <input type="checkbox" :checked="selectedIsChecked(ticket.id)" @click="toggleSelect(ticket.id, $event)"
                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600" />
            </td>
            <td class="px-5 py-3.5 whitespace-nowrap">
              <span class="text-xs font-mono text-gray-400 dark:text-gray-500">#{{ ticket.id }}</span>
            </td>
            <td class="px-5 py-3.5 whitespace-nowrap">
              <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/50 dark:bg-indigo-900/20 dark:text-indigo-400 dark:ring-indigo-800/30">
                {{ ticket.category || 'repair' }}
              </span>
            </td>
            <td class="px-5 py-3.5 whitespace-nowrap">
              <p class="text-sm text-gray-700 dark:text-gray-300 truncate max-w-50" :title="ticket.organization_name || ticket.fca_name">{{ ticket.organization_name || ticket.fca_name || '—' }}</p>
            </td>
            <td class="px-5 py-3.5 max-w-70 whitespace-nowrap">
              <p class="text-sm font-medium text-gray-900 dark:text-white truncate" :title="ticket.subject">{{ ticket.subject }}</p>
            </td>
            <td class="px-5 py-3.5 max-w-55 whitespace-nowrap">
              <p class="text-xs text-gray-500 dark:text-gray-400 truncate" :title="ticket.description">{{ ticket.description || '—' }}</p>
            </td>
            <td class="px-5 py-3.5 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
              {{ ticket.service_charge ? `₱${Number(ticket.service_charge).toLocaleString()}` : '—' }}
            </td>
            <td class="px-5 py-3.5 whitespace-nowrap">
              <span :class="statusBadgeClass(ticket.status)" class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold">
                <span :class="statusDotClass(ticket.status)" class="w-1.5 h-1.5 rounded-full"></span>
                {{ statusLabel(ticket.status) }}
              </span>
            </td>
            <td class="px-5 py-3.5 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">{{ formatDateOnly(ticket.reported_date) || formatDate(ticket.created_at) }}</td>
            <td class="px-5 py-3.5 whitespace-nowrap">
              <div class="flex items-center justify-end gap-0.5">
                <Link :href="`/tickets/${ticket.id}`" class="p-2 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 transition-colors" title="View">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </Link>
                <button @click="confirmDelete(ticket)" class="p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors" title="Delete">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </div>
            </td>
          </tr>

          <!-- Empty state -->
          <tr v-if="!tickets.data?.length">
            <td colspan="10" class="px-5 py-16 text-center">
              <div class="flex flex-col items-center gap-3">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800">
                  <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No tickets found</p>
                <p class="text-xs text-gray-400 dark:text-gray-500">Create a new ticket to get started.</p>
              </div>
            </td>
          </tr>
        </template>
      </DataTable>
    </div>

    <div class="flex items-center justify-between mt-6">
      <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <span>Show</span>
        <select v-model="perPage" @change="applyFilter"
          class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 px-2 py-1.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
          <option :value="15">15</option>
          <option :value="50">50</option>
          <option :value="100">100</option>
          <option :value="200">200</option>
          <option :value="500">500</option>
          <option :value="1000">1000</option>
        </select>
        <span>entries</span>
      </div>
      <Pagination :links="tickets.links" />
    </div>

    <!-- Floating multi-select bar -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="translate-y-4 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-4 opacity-0">
      <div v-if="selectedCount > 0" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-30 flex items-center gap-4 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 px-5 py-3">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
          <span class="text-indigo-600 dark:text-indigo-400 font-bold">{{ selectedCount }}</span> ticket{{ selectedCount !== 1 ? 's' : '' }} selected
        </span>
        <div class="w-px h-6 bg-gray-200 dark:bg-gray-700"></div>
        <button @click="clearSelection" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
          Clear
        </button>
        <button @click="exportSelected"
          class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors bg-emerald-600 hover:bg-emerald-700">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Export XLSX
        </button>
      </div>
    </Transition>
    </template>

    <!-- Old Data Tab -->
    <template v-if="activeTab === 'old'">
    <div class="bg-white rounded-xl border border-gray-200/60 shadow-sm overflow-hidden mb-6 dark:bg-gray-800/60 dark:border-gray-700/50">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 dark:border-gray-700/50">
              <th scope="col" class="px-5 py-3.5 w-16">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">#</span>
              </th>
              <th v-for="col in [
                { label: 'Type', field: 'category' },
                { label: 'Name', field: 'tractor_name' },
                { label: 'Subject', field: 'subject' },
                { label: 'Action Taken', field: 'description' },
                { label: 'Service Charge', field: 'service_charge' },
                { label: 'Status', field: 'status' },
                { label: 'Reported', field: 'reported_date' },
              ]" :key="col.field" scope="col" class="px-5 py-3.5">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ col.label }}</span>
              </th>
              <th scope="col" class="px-5 py-3.5 text-right w-16">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-700/30">
            <tr v-for="ticket in oldTickets.data" :key="ticket.id" class="group hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors">
              <td class="px-5 py-3.5">
                <span class="text-xs font-mono text-gray-400 dark:text-gray-500">#{{ ticket.id }}</span>
              </td>
              <td class="px-5 py-3.5">
                <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium bg-amber-50 text-amber-700 ring-1 ring-amber-200/50 dark:bg-amber-900/20 dark:text-amber-400 dark:ring-amber-800/30">
                  {{ ticket.category || 'repair' }}
                </span>
              </td>
              <td class="px-5 py-3.5">
                <p class="text-sm text-gray-700 dark:text-gray-300 truncate max-w-50" :title="ticket.organization_name || ticket.fca_name">{{ ticket.organization_name || ticket.fca_name || '—' }}</p>
              </td>
              <td class="px-5 py-3.5 max-w-70">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate" :title="ticket.subject">{{ ticket.subject }}</p>
              </td>
              <td class="px-5 py-3.5 max-w-55">
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate" :title="ticket.description">{{ ticket.description || '—' }}</p>
              </td>
              <td class="px-5 py-3.5 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                  {{ ticket.service_charge ? `₱${Number(ticket.service_charge).toLocaleString()}` : '—' }}
                </td>
                <td class="px-5 py-3.5">
                  <span :class="statusBadgeClass(ticket.status)" class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold">
                    <span :class="statusDotClass(ticket.status)" class="w-1.5 h-1.5 rounded-full"></span>
                    {{ statusLabel(ticket.status) }}
                  </span>
                </td>
                <td class="px-5 py-3.5 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">{{ formatDateOnly(ticket.reported_date) || formatDate(ticket.created_at) }}</td>
                <td class="px-5 py-3.5">
                  <div class="flex items-center justify-end gap-0.5">
                    <Link :href="`/tickets/${ticket.id}`" class="p-2 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 transition-colors" title="View">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </Link>
                    <button @click="confirmDelete(ticket)" class="p-2 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors" title="Delete">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!oldTickets.data?.length">
                <td colspan="9" class="px-5 py-16 text-center">
                  <div class="flex flex-col items-center gap-3">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800">
                      <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No old tickets found</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <Pagination :links="oldTickets.links" class="mt-4" />
    </template>

    <!-- Delete Confirmation Modal -->
    <Modal :show="showDeleteModal" max-width="sm" @close="closeDeleteModal">
      <template #header>
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Delete Ticket</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">This action cannot be undone.</p>
          </div>
        </div>
      </template>

      <div class="text-sm text-gray-600 dark:text-gray-300 space-y-2">
        <p>Are you sure you want to delete this ticket?</p>
        <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3 space-y-1">
          <p class="font-medium text-gray-900 dark:text-white truncate">{{ ticketToDelete?.subject }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            #{{ ticketToDelete?.id }} &middot; {{ statusLabel(ticketToDelete?.status) }}
          </p>
        </div>
      </div>

      <template #footer>
        <div class="flex items-center justify-end gap-3 w-full">
          <button @click="closeDeleteModal" type="button"
            class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 dark:focus:ring-gray-500 transition-colors">
            Cancel
          </button>
          <button @click="deleteTicket" type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:focus:ring-offset-gray-800 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Yes, Delete Ticket
          </button>
        </div>
      </template>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import DataTable from '@/Components/DataTable.vue';
import { formatDate, formatDateOnly } from '@/utils/dateFormat';

const props = defineProps({ tickets: Object, oldTickets: Object, filters: Object });

const activeTab = ref('current');

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const priorityFilter = ref(props.filters?.priority || '');
const sort = ref(props.filters?.sort || 'created_at');
const direction = ref(props.filters?.direction || 'desc');
const perPage = ref(props.filters?.per_page || 15);

// ── Multi-Select ──
const STORAGE_KEY = 'ticket_selected_ids';
const saved = sessionStorage.getItem(STORAGE_KEY);
const selectedIds = ref(saved ? JSON.parse(saved) : {});

const persistSelection = () => {
  sessionStorage.setItem(STORAGE_KEY, JSON.stringify(selectedIds.value));
};

const selectedCount = computed(() => Object.keys(selectedIds.value).length);

const selectedIsChecked = (id) => !!selectedIds.value[id];

const toggleSelect = (id, event) => {
  const next = { ...selectedIds.value };
  if (next[id]) {
    delete next[id];
  } else {
    next[id] = true;
  }
  selectedIds.value = next;
  persistSelection();
};

const isAllSelected = computed(() => {
  const data = props.tickets?.data;
  return data && data.length > 0 && data.every(d => selectedIds.value[d.id]);
});

const toggleSelectAll = (event) => {
  const data = props.tickets?.data;
  if (!data) return;
  const next = { ...selectedIds.value };
  if (isAllSelected.value) {
    data.forEach(d => delete next[d.id]);
  } else {
    data.forEach(d => next[d.id] = true);
  }
  selectedIds.value = next;
  persistSelection();
};

const clearSelection = () => {
  selectedIds.value = {};
  sessionStorage.removeItem(STORAGE_KEY);
};

const exportSelected = () => {
  const ids = Object.keys(selectedIds.value).map(Number);
  if (ids.length === 0) return;

  const exportForm = document.createElement('form');
  exportForm.method = 'POST';
  exportForm.action = '/tickets/export';

  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (csrf) {
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrf;
    exportForm.appendChild(csrfInput);
  }

  ids.forEach(id => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'ticket_ids[]';
    input.value = id;
    exportForm.appendChild(input);
  });

  document.body.appendChild(exportForm);
  exportForm.submit();
  document.body.removeChild(exportForm);
};

// ── Delete state ──
const showDeleteModal = ref(false);
const ticketToDelete = ref(null);

function confirmDelete(ticket) {
  ticketToDelete.value = ticket;
  showDeleteModal.value = true;
}

function closeDeleteModal() {
  showDeleteModal.value = false;
  ticketToDelete.value = null;
}

function deleteTicket() {
  if (!ticketToDelete.value) return;
  router.delete(`/tickets/${ticketToDelete.value.id}`, {
    preserveState: true,
    replace: true,
    onSuccess: () => closeDeleteModal(),
    onError: () => closeDeleteModal(),
  });
}

let timer;
const debouncedFilter = () => { clearTimeout(timer); timer = setTimeout(applyFilter, 300); };
const applyFilter = () => {
  router.get('/tickets', {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
    priority: priorityFilter.value || undefined,
    sort: sort.value,
    direction: direction.value,
    per_page: perPage.value,
  }, { preserveState: true, replace: true });
};

function toggleSort(field) {
  if (sort.value === field) {
    direction.value = direction.value === 'asc' ? 'desc' : 'asc';
  } else {
    sort.value = field;
    direction.value = 'asc';
  }
  applyFilter();
}

// ── Status helpers ──
function statusLabel(s) {
  const map = { open: 'Open', in_progress: 'In Progress', resolved: 'Completed', closed: 'Closed' };
  return map[s] || s || '—';
}
function statusBadgeClass(s) {
  const map = {
    open: 'bg-red-50 text-red-700 ring-1 ring-red-200/50 dark:bg-red-900/20 dark:text-red-400 dark:ring-red-800/30',
    in_progress: 'bg-blue-50 text-blue-700 ring-1 ring-blue-200/50 dark:bg-blue-900/20 dark:text-blue-400 dark:ring-blue-800/30',
    resolved: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/50 dark:bg-emerald-900/20 dark:text-emerald-400 dark:ring-emerald-800/30',
    closed: 'bg-gray-50 text-gray-500 ring-1 ring-gray-200/50 dark:bg-gray-700/50 dark:text-gray-400 dark:ring-gray-600/30',
  };
  return map[s] || 'bg-gray-50 text-gray-500';
}
function statusDotClass(s) {
  const map = { open: 'bg-red-500', in_progress: 'bg-blue-500', resolved: 'bg-emerald-500', closed: 'bg-gray-400' };
  return map[s] || 'bg-gray-400';
}
</script>
