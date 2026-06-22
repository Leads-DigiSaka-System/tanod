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

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200/60 shadow-sm overflow-hidden mb-6 dark:bg-gray-800/60 dark:border-gray-700/50">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 dark:border-gray-700/50">
              <th scope="col" class="px-5 py-3.5 w-16">
                <button @click="toggleSort('id')" class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors group">
                  #
                  <svg v-if="sort === 'id'" :class="direction === 'asc' ? 'rotate-0' : 'rotate-180'" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"/></svg>
                  <svg v-else class="w-3 h-3 text-gray-300 dark:text-gray-600 group-hover:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                </button>
              </th>
              <th v-for="col in [
                { label: 'Type', field: 'category' },
                { label: 'FCA Name', field: 'fca_name' },
                { label: 'Subject', field: 'subject' },
                { label: 'Action Taken', field: 'description' },
                { label: 'Service Charge', field: 'service_charge' },
                { label: 'Status', field: 'status' },
                { label: 'Reported', field: 'reported_date' },
              ]" :key="col.field" scope="col" class="px-5 py-3.5">
                <button @click="toggleSort(col.field)" class="flex items-center gap-1 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors group">
                  {{ col.label }}
                  <svg v-if="sort === col.field" :class="direction === 'asc' ? 'rotate-0' : 'rotate-180'" class="w-3 h-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7"/></svg>
                  <svg v-else class="w-3 h-3 text-gray-300 dark:text-gray-600 group-hover:text-gray-400 opacity-0 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                </button>
              </th>
              <th scope="col" class="px-5 py-3.5 text-right w-16">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-700/30">
            <tr v-for="ticket in tickets.data" :key="ticket.id" class="group hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors">
              <td class="px-5 py-3.5">
                <span class="text-xs font-mono text-gray-400 dark:text-gray-500">#{{ ticket.id }}</span>
              </td>
              <td class="px-5 py-3.5">
                <span class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200/50 dark:bg-indigo-900/20 dark:text-indigo-400 dark:ring-indigo-800/30">
                  {{ ticket.category || 'repair' }}
                </span>
              </td>
              <td class="px-5 py-3.5">
                <p class="text-sm text-gray-700 dark:text-gray-300 truncate max-w-[200px]" :title="ticket.fca_name">{{ ticket.fca_name || '—' }}</p>
              </td>
              <td class="px-5 py-3.5 max-w-[280px]">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate" :title="ticket.subject">{{ ticket.subject }}</p>
              </td>
              <td class="px-5 py-3.5 max-w-[220px]">
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
                <div class="flex items-center justify-end">
                  <Link :href="`/tickets/${ticket.id}`" class="p-2 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 transition-colors" title="View">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  </Link>
                </div>
              </td>
            </tr>
            <tr v-if="!tickets.data?.length">
              <td colspan="9" class="px-5 py-16 text-center">
                <div class="flex flex-col items-center gap-3">
                  <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800">
                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                  </div>
                  <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No tickets found</p>
                  <p class="text-xs text-gray-400 dark:text-gray-500">Create a new ticket to get started.</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Pagination :links="tickets.links" class="mt-6" />
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatDate, formatDateOnly } from '@/utils/dateFormat';

const props = defineProps({ tickets: Object, filters: Object });

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const priorityFilter = ref(props.filters?.priority || '');
const sort = ref(props.filters?.sort || 'created_at');
const direction = ref(props.filters?.direction || 'desc');

let timer;
const debouncedFilter = () => { clearTimeout(timer); timer = setTimeout(applyFilter, 300); };
const applyFilter = () => {
  router.get('/tickets', {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
    priority: priorityFilter.value || undefined,
    sort: sort.value,
    direction: direction.value,
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
