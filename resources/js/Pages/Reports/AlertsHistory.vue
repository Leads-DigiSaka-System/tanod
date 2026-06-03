<template>
  <AppLayout>
    <Head title="Alerts History" />

    <div class="mb-6">
      <Link href="/reports" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        Back to Reports
      </Link>
      <div class="mt-3 flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Alerts History</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">View and filter all alerts by type, status, and date range</p>
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
          <label class="block mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">Type</label>
          <select v-model="filters.type" @change="applyFilter" class="w-full py-2 px-3 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">All Types</option>
            <option v-for="(count, type) in summary.by_type" :key="type" :value="type">{{ type.replace(/_/g, ' ') }} ({{ count }})</option>
          </select>
        </div>
        <div>
          <label class="block mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">Status</label>
          <select v-model="filters.acknowledged" @change="applyFilter" class="w-full py-2 px-3 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">All</option>
            <option value="0">Unacknowledged</option>
            <option value="1">Acknowledged</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="bg-white rounded-xl border border-gray-200 p-5 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center gap-4">
          <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700">
            <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Alerts</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.total?.toLocaleString() }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-5 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center gap-4">
          <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-red-50 dark:bg-red-900/30">
            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Unacknowledged</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ summary.unacknowledged?.toLocaleString() }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-5 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center gap-4">
          <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-green-50 dark:bg-green-900/30">
            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Acknowledged</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ summary.acknowledged?.toLocaleString() }}</p>
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
              <th class="px-5 py-3 font-semibold">Alert</th>
              <th class="px-5 py-3 font-semibold">Type</th>
              <th class="px-5 py-3 font-semibold">Tractor</th>
              <th class="px-5 py-3 font-semibold">Device</th>
              <th class="px-5 py-3 font-semibold">Status</th>
              <th class="px-5 py-3 font-semibold">Date</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-for="a in alerts.data" :key="a.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
              <td class="px-5 py-3">
                <p class="font-medium text-gray-900 dark:text-white">{{ a.title }}</p>
                <p class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ a.message }}</p>
              </td>
              <td class="px-5 py-3">
                <span :class="typeBadgeClass(a.type)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold">
                  {{ a.type.replace(/_/g, ' ') }}
                </span>
              </td>
              <td class="px-5 py-3 text-gray-900 dark:text-white">{{ a.tractor?.no_plate || '—' }}</td>
              <td class="px-5 py-3 text-xs text-gray-500">{{ a.device?.device_name || a.device?.imei || '—' }}</td>
              <td class="px-5 py-3">
                <span v-if="a.is_acknowledged" class="inline-flex items-center gap-1 text-xs font-medium text-green-600 dark:text-green-400">
                  <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Ack'd
                </span>
                <span v-else class="inline-flex items-center gap-1 text-xs font-medium text-red-600 dark:text-red-400">
                  <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Pending
                </span>
              </td>
              <td class="px-5 py-3 text-xs text-gray-500 whitespace-nowrap">{{ formatDate(a.created_at) }}</td>
            </tr>
            <tr v-if="!alerts.data?.length">
              <td colspan="6" class="px-5 py-16 text-center">
                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                <p class="text-sm text-gray-500 dark:text-gray-400">No alerts found for the selected filters.</p>
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

const props = defineProps({ alerts: Object, summary: Object, filterData: Object });

const filters = reactive({
  from: props.filterData?.from || '',
  to: props.filterData?.to || '',
  type: props.filterData?.type || '',
  acknowledged: props.filterData?.acknowledged ?? '',
});

const applyFilter = () => {
  router.get('/reports/alerts-history', {
    from: filters.from || undefined,
    to: filters.to || undefined,
    type: filters.type || undefined,
    acknowledged: filters.acknowledged !== '' ? filters.acknowledged : undefined,
  }, { preserveState: true, replace: true });
};

const exportUrl = computed(() => {
  const params = new URLSearchParams();
  if (filters.from) params.set('from', filters.from);
  if (filters.to) params.set('to', filters.to);
  if (filters.type) params.set('type', filters.type);
  if (filters.acknowledged !== '') params.set('acknowledged', filters.acknowledged);
  const qs = params.toString();
  return '/reports/alerts-history/export' + (qs ? '?' + qs : '');
});

const typeBadgeClass = (type) => {
  const map = {
    geofence_breach: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    speed_exceeded: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    device_offline: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    low_battery: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    sos: 'bg-red-200 text-red-800 dark:bg-red-900/50 dark:text-red-300',
  };
  return map[type] || 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400';
};
</script>
