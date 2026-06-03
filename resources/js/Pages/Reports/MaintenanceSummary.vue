<template>
  <AppLayout>
    <Head title="Maintenance Summary" />

    <!-- Page Header -->
    <div class="mb-6">
      <Link href="/reports" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        Back to Reports
      </Link>
      <div class="mt-3 flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Maintenance Summary</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Review maintenance records, costs, and status</p>
        </div>
        <a :href="exportUrl" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition shrink-0">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
          Export Excel
        </a>
      </div>
    </div>

    <!-- Filters Card -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700 mb-6">
      <div class="flex flex-col sm:flex-row gap-3 items-end">
        <div class="flex-1">
          <label class="block mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">From Date</label>
          <input v-model="filters.from" type="date" @change="applyFilter" class="w-full py-2 px-3 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
        </div>
        <div class="flex-1">
          <label class="block mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">To Date</label>
          <input v-model="filters.to" type="date" @change="applyFilter" class="w-full py-2 px-3 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
        </div>
        <div class="w-full sm:w-48">
          <label class="block mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">Status</label>
          <select v-model="filters.status" @change="applyFilter" class="w-full py-2 px-3 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">All Status</option>
            <option value="documentation">Documentation</option>
            <option value="scheduled">Scheduled</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div class="relative overflow-hidden bg-white rounded-xl border border-gray-200 p-5 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center gap-4">
          <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/30">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Records</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.total?.toLocaleString() }}</p>
          </div>
        </div>
      </div>
      <div class="relative overflow-hidden bg-white rounded-xl border border-gray-200 p-5 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center gap-4">
          <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-green-50 dark:bg-green-900/30">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.completed?.toLocaleString() }}</p>
          </div>
        </div>
      </div>
      <div class="relative overflow-hidden bg-white rounded-xl border border-gray-200 p-5 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center gap-4">
          <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-900/30">
            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.pending?.toLocaleString() }}</p>
          </div>
        </div>
      </div>
      <div class="relative overflow-hidden bg-white rounded-xl border border-gray-200 p-5 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center gap-4">
          <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-red-50 dark:bg-red-900/30">
            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Cost</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">₱{{ Number(summary.total_cost || 0).toLocaleString() }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
          <thead class="text-xs text-gray-500 uppercase tracking-wider bg-gray-50/80 dark:bg-gray-700/50 dark:text-gray-400">
            <tr>
              <th class="px-5 py-3 font-semibold">#</th>
              <th class="px-5 py-3 font-semibold">Tractor</th>
              <th class="px-5 py-3 font-semibold">Issue Type</th>
              <th class="px-5 py-3 font-semibold">Date</th>
              <th class="px-5 py-3 font-semibold">Status</th>
              <th class="px-5 py-3 font-semibold text-right">Cost</th>
              <th class="px-5 py-3 font-semibold">Technician</th>
              <th class="px-5 py-3 font-semibold">Performed By</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-for="(m, idx) in rows" :key="m.id" class="hover:bg-indigo-50/40 dark:hover:bg-gray-700/50 transition-colors">
              <td class="px-5 py-3 text-gray-400 text-xs">{{ (maintenances.current_page - 1) * maintenances.per_page + idx + 1 }}</td>
              <td class="px-5 py-3">
                <span class="font-medium text-gray-900 dark:text-white">{{ m.tractor?.no_plate }}</span>
                <p class="text-xs text-gray-400 mt-0.5">{{ m.tractor?.brand }} {{ m.tractor?.model }}</p>
              </td>
              <td class="px-5 py-3">
                <span v-if="m.issue_type?.name" class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ m.issue_type.name }}</span>
                <span v-else class="text-gray-300 dark:text-gray-600">—</span>
              </td>
              <td class="px-5 py-3 text-xs text-gray-500">{{ m.maintenance_date ? formatDate(m.maintenance_date) : '—' }}</td>
              <td class="px-5 py-3">
                <span :class="statusClass(m.status)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold">
                  {{ statusLabel(m.status) }}
                </span>
              </td>
              <td class="px-5 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ m.cost ? `₱${Number(m.cost).toLocaleString()}` : '—' }}</td>
              <td class="px-5 py-3 text-xs text-gray-500">{{ m.tech_name || '—' }}</td>
              <td class="px-5 py-3 text-xs text-gray-500">{{ m.performer?.name || '—' }}</td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="8" class="px-5 py-16">
                <div class="flex flex-col items-center justify-center text-center">
                  <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <p class="text-sm text-gray-500 dark:text-gray-400">No maintenance records found.</p>
                  <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Try adjusting your date filters or status.</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="maintenances.last_page > 1" class="flex items-center justify-between mt-4 px-1">
      <p class="text-xs text-gray-500 dark:text-gray-400">
        Showing {{ maintenances.from }}–{{ maintenances.to }} of {{ maintenances.total }}
      </p>
      <div class="flex gap-1">
        <template v-for="link in maintenances.links" :key="link.label">
          <Link v-if="link.url" :href="link.url" preserve-state replace
            :class="[link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300', 'px-3 py-1.5 text-xs font-medium rounded-lg border transition']"
            v-html="link.label" />
          <span v-else class="px-3 py-1.5 text-xs text-gray-400 opacity-40" v-html="link.label" />
        </template>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { router, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ maintenances: Object, summary: Object, filterData: Object });

const rows = computed(() => props.maintenances?.data || []);

const filters = reactive({
  from: props.filterData?.from || '',
  to: props.filterData?.to || '',
  status: props.filterData?.status || '',
});

const applyFilter = () => {
  router.get('/reports/maintenance-summary', {
    from: filters.from || undefined,
    to: filters.to || undefined,
    status: filters.status || undefined,
  }, { preserveState: true, replace: true });
};

const exportUrl = computed(() => {
  const params = new URLSearchParams();
  if (filters.from) params.set('from', filters.from);
  if (filters.to) params.set('to', filters.to);
  if (filters.status) params.set('status', filters.status);
  const qs = params.toString();
  return '/reports/maintenance-summary/export' + (qs ? '?' + qs : '');
});

function formatDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function statusLabel(s) {
  return { documentation: 'Documentation', scheduled: 'Scheduled', in_progress: 'In Progress', completed: 'Completed', cancelled: 'Cancelled' }[s] || s;
}

function statusClass(s) {
  return {
    documentation: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    scheduled: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    in_progress: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    completed: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
  }[s] || 'bg-gray-100 text-gray-700';
}
</script>
