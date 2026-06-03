<template>
  <AppLayout>
    <Head title="Ticket Summary" />

    <div class="mb-6">
      <Link href="/reports" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        Back to Reports
      </Link>
      <div class="mt-3 flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Ticket Summary</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Track support tickets, resolution times, and priorities</p>
        </div>
        <a :href="exportUrl" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition shrink-0">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
          Export Excel
        </a>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700 mb-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
        <div>
          <label class="block mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">From Date</label>
          <input v-model="filters.from" type="date" @change="applyFilter" class="w-full py-2 px-3 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
        </div>
        <div>
          <label class="block mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">To Date</label>
          <input v-model="filters.to" type="date" @change="applyFilter" class="w-full py-2 px-3 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
        </div>
        <div>
          <label class="block mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">Status</label>
          <select v-model="filters.status" @change="applyFilter" class="w-full py-2 px-3 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">All Status</option>
            <option value="open">Open</option>
            <option value="in_progress">In Progress</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
          </select>
        </div>
        <div>
          <label class="block mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">Priority</label>
          <select v-model="filters.priority" @change="applyFilter" class="w-full py-2 px-3 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">All Priorities</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
      <div class="bg-white rounded-xl border border-gray-200 p-4 dark:bg-gray-800 dark:border-gray-700">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ summary.total?.toLocaleString() }}</p>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4 dark:bg-gray-800 dark:border-gray-700">
        <p class="text-xs font-medium text-red-500 dark:text-red-400 uppercase tracking-wider">Open</p>
        <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ summary.open?.toLocaleString() }}</p>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4 dark:bg-gray-800 dark:border-gray-700">
        <p class="text-xs font-medium text-amber-500 dark:text-amber-400 uppercase tracking-wider">In Progress</p>
        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ summary.in_progress?.toLocaleString() }}</p>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4 dark:bg-gray-800 dark:border-gray-700">
        <p class="text-xs font-medium text-green-500 dark:text-green-400 uppercase tracking-wider">Resolved</p>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ summary.resolved?.toLocaleString() }}</p>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4 dark:bg-gray-800 dark:border-gray-700">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Avg Resolution</p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ summary.avg_resolution_hours ? summary.avg_resolution_hours + 'h' : '—' }}</p>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
          <thead class="text-xs text-gray-500 uppercase tracking-wider bg-gray-50/80 dark:bg-gray-700/50 dark:text-gray-400">
            <tr>
              <th class="px-5 py-3 font-semibold">Ticket</th>
              <th class="px-5 py-3 font-semibold">Subject</th>
              <th class="px-5 py-3 font-semibold">Tractor</th>
              <th class="px-5 py-3 font-semibold">Priority</th>
              <th class="px-5 py-3 font-semibold">Status</th>
              <th class="px-5 py-3 font-semibold">Submitted By</th>
              <th class="px-5 py-3 font-semibold">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-for="t in tickets.data" :key="t.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
              <td class="px-5 py-3">
                <Link :href="`/tickets/${t.id}`" class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">#{{ t.id }}</Link>
              </td>
              <td class="px-5 py-3 font-medium text-gray-900 dark:text-white max-w-xs truncate">{{ t.subject }}</td>
              <td class="px-5 py-3 text-gray-900 dark:text-white">{{ t.tractor?.no_plate || '—' }}</td>
              <td class="px-5 py-3">
                <span :class="priorityClass(t.priority)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold capitalize">
                  {{ t.priority }}
                </span>
              </td>
              <td class="px-5 py-3">
                <span :class="statusClass(t.status)" class="inline-flex items-center gap-1 text-xs font-semibold">
                  <span class="w-1.5 h-1.5 rounded-full" :class="statusDot(t.status)"></span>
                  {{ t.status.replace('_', ' ') }}
                </span>
              </td>
              <td class="px-5 py-3 text-xs text-gray-500">{{ t.submitter?.name || '—' }}</td>
              <td class="px-5 py-3 text-xs text-gray-500 whitespace-nowrap">{{ formatDate(t.created_at) }}</td>
            </tr>
            <tr v-if="!tickets.data?.length">
              <td colspan="7" class="px-5 py-16 text-center">
                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                <p class="text-sm text-gray-500 dark:text-gray-400">No tickets found for the selected filters.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatDate } from '@/utils/dateFormat';

const props = defineProps({ tickets: Object, summary: Object, filterData: Object });

const filters = reactive({
  from: props.filterData?.from || '',
  to: props.filterData?.to || '',
  status: props.filterData?.status || '',
  priority: props.filterData?.priority || '',
});

const applyFilter = () => {
  router.get('/reports/ticket-summary', {
    from: filters.from || undefined,
    to: filters.to || undefined,
    status: filters.status || undefined,
    priority: filters.priority || undefined,
  }, { preserveState: true, replace: true });
};

const exportUrl = computed(() => {
  const params = new URLSearchParams();
  if (filters.from) params.set('from', filters.from);
  if (filters.to) params.set('to', filters.to);
  if (filters.status) params.set('status', filters.status);
  if (filters.priority) params.set('priority', filters.priority);
  const qs = params.toString();
  return '/reports/ticket-summary/export' + (qs ? '?' + qs : '');
});

const priorityClass = (p) => ({
  urgent: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
  high: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
  medium: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
  low: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
}[p] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300');

const statusClass = (s) => ({
  open: 'text-red-600 dark:text-red-400',
  in_progress: 'text-amber-600 dark:text-amber-400',
  resolved: 'text-green-600 dark:text-green-400',
  closed: 'text-gray-400 dark:text-gray-500',
}[s] || 'text-gray-500');

const statusDot = (s) => ({
  open: 'bg-red-500',
  in_progress: 'bg-amber-500',
  resolved: 'bg-green-500',
  closed: 'bg-gray-400',
}[s] || 'bg-gray-400');
</script>
