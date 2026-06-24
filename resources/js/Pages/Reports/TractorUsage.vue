<template>
  <AppLayout>
    <Head title="Tractor Usage Report" />

    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
      <div>
        <Link href="/reports" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
          Back to Reports
        </Link>
        <h1 class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">Tractor Usage Report</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Fleet utilization summary</p>
      </div>
      <a :href="exportUrl" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
        Export Excel
      </a>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
      <!-- Total Tractors -->
      <div class="relative overflow-hidden bg-white rounded-xl border border-gray-200 p-5 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center gap-4">
          <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/30">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Tractors</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.total_tractors?.toLocaleString() }}</p>
          </div>
        </div>
        <div class="mt-3 flex gap-2 text-xs">
          <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400">
            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>{{ onlineCount }} online
          </span>
          <span class="inline-flex items-center gap-1 text-red-500 dark:text-red-400">
            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>{{ offlineCount }} offline
          </span>
        </div>
      </div>

      <!-- Active With Data -->
      <div class="relative overflow-hidden bg-white rounded-xl border border-gray-200 p-5 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center gap-4">
          <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-900/30">
            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">With Usage Data</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ withDataCount }}</p>
          </div>
        </div>
        <div class="mt-3">
          <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700">
            <div class="bg-purple-500 h-1.5 rounded-full transition-all" :style="{ width: dataPercent + '%' }"></div>
          </div>
          <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ dataPercent }}% of fleet reporting</p>
        </div>
      </div>

      <!-- PMS Due -->
      <div class="relative overflow-hidden bg-white rounded-xl border border-gray-200 p-5 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center gap-4">
          <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-orange-50 dark:bg-orange-900/30">
            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
          </div>
          <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">PMS Due</p>
            <p class="text-2xl font-bold" :class="summary.pms_due > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-900 dark:text-white'">{{ summary.pms_due || 0 }}</p>
          </div>
        </div>
        <div class="mt-3 text-xs text-gray-400 dark:text-gray-500">
          {{ summary.total_maintenances || 0 }} total maintenance records
        </div>
      </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700 mb-4">
      <div class="flex flex-col sm:flex-row gap-3 items-end">
        <div class="flex-1">
          <label class="block mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">Search</label>
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <input v-model="search" type="text" placeholder="Search plate, brand, model, IMEI..."
              class="w-full pl-10 pr-3 py-2 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
          </div>
        </div>
        <div class="w-full sm:w-48">
          <label class="block mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">Group</label>
          <select v-model="filters.group_id" @change="applyFilter"
            class="w-full py-2 px-3 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">All Groups</option>
            <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
          </select>
        </div>
        <div class="w-full sm:w-40">
          <label class="block mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">Status</label>
          <select v-model="statusFilter"
            class="w-full py-2 px-3 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">All Status</option>
            <option value="online">Online</option>
            <option value="offline">Offline</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="w-full sm:w-40">
          <label class="block mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">PMS</label>
          <select v-model="pmsFilter"
            class="w-full py-2 px-3 text-sm bg-gray-50 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">All PMS</option>
            <option value="due">Due Now</option>
            <option value="ok">OK</option>
            <option value="nodata">No Data</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Results count -->
    <div class="flex items-center justify-between mb-2 px-1">
      <p class="text-xs text-gray-500 dark:text-gray-400">
        Showing <span class="font-semibold text-gray-700 dark:text-gray-300">{{ paginatedTractors.length }}</span>
        of <span class="font-semibold text-gray-700 dark:text-gray-300">{{ filteredTractors.length }}</span> tractors
      </p>
      <div class="flex items-center gap-2 text-xs text-gray-500">
        <span>Sort:</span>
        <button @click="toggleSort('no_plate')" :class="sortField === 'no_plate' ? 'text-indigo-600 font-semibold' : ''" class="hover:text-indigo-600">Name</button>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
          <thead class="text-xs text-gray-500 uppercase tracking-wider bg-gray-50/80 dark:bg-gray-700/50 dark:text-gray-400">
            <tr>
              <th class="px-5 py-3 font-semibold">#</th>
              <th class="px-5 py-3 font-semibold cursor-pointer select-none" @click="toggleSort('no_plate')">
                <span class="inline-flex items-center gap-1">Tractor <SortIcon :active="sortField === 'no_plate'" :asc="sortAsc" /></span>
              </th>
              <th class="px-5 py-3 font-semibold">Group</th>
              <th class="px-5 py-3 font-semibold">IMEI</th>
              <th class="px-5 py-3 font-semibold text-center">Last PMS</th>
              <th class="px-5 py-3 font-semibold text-center">PMS Status</th>
              <th class="px-5 py-3 font-semibold text-center">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <tr v-for="(t, idx) in paginatedTractors" :key="t.id"
                class="hover:bg-indigo-50/40 dark:hover:bg-gray-700/50 transition-colors">
              <td class="px-5 py-3 text-gray-400 text-xs">{{ (currentPage - 1) * perPage + idx + 1 }}</td>
              <td class="px-5 py-3">
                <Link :href="`/tractors/${t.id}`" class="font-medium text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                  {{ t.no_plate }}
                </Link>
                <p class="text-xs text-gray-400 mt-0.5">{{ t.brand }} {{ t.model }}</p>
              </td>
              <td class="px-5 py-3">
                <span v-if="t.group?.name" class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ t.group.name }}</span>
                <span v-else class="text-gray-300 dark:text-gray-600">—</span>
              </td>
              <td class="px-5 py-3 font-mono text-xs text-gray-400">{{ t.imei || '—' }}</td>
              <td class="px-5 py-3 text-center text-xs text-gray-500 dark:text-gray-400">
                {{ t.last_pms_date || 'Never' }}
              </td>
              <td class="px-5 py-3 text-center">
                <span v-if="t.pms_status === 'Due'" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                  Due Now
                </span>
                <span v-else-if="t.pms_status === 'No Data'" class="text-xs text-gray-400 dark:text-gray-500">—</span>
                <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                  <template v-if="t.pms_count > 0"><strong>{{ t.pms_count }}x</strong> · </template>{{ t.pms_status }}
                </span>
              </td>
              <td class="px-5 py-3 text-center">
                <StatusBadge :status="t.status" :show-dot="true" />
              </td>
            </tr>
            <tr v-if="!paginatedTractors.length">
              <td colspan="7" class="px-5 py-16">
                <div class="flex flex-col items-center justify-center text-center">
                  <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                  </svg>
                  <p class="text-sm text-gray-500 dark:text-gray-400">No tractors match your filters.</p>
                </div>
              </td>
            </tr>
          </tbody>
          <!-- Table Footer Totals -->
          <tfoot v-if="filteredTractors.length" class="bg-gray-50/80 dark:bg-gray-700/50">
            <tr class="font-semibold text-gray-700 dark:text-gray-200 text-sm">
              <td class="px-5 py-3" colspan="4">
                Totals ({{ filteredTractors.length }} tractors)
              </td>
              <td class="px-5 py-3 text-center text-orange-600 dark:text-orange-400">{{ filteredTotals.pmsDue }} due</td>
              <td class="px-5 py-3" colspan="2"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="flex items-center justify-between mt-4 px-1">
      <p class="text-xs text-gray-500 dark:text-gray-400">Page {{ currentPage }} of {{ totalPages }}</p>
      <div class="flex gap-1">
        <button @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage === 1"
          class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition">
          Prev
        </button>
        <template v-for="p in visiblePages" :key="p">
          <button v-if="p === '...'" disabled class="px-2 py-1.5 text-xs text-gray-400">...</button>
          <button v-else @click="currentPage = p"
            :class="[p === currentPage ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300', 'px-3 py-1.5 text-xs font-medium rounded-lg border transition']">
            {{ p }}
          </button>
        </template>
        <button @click="currentPage = Math.min(totalPages, currentPage + 1)" :disabled="currentPage === totalPages"
          class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition">
          Next
        </button>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { reactive, ref, computed, watch } from 'vue';
import { router, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

// --- Inline sort icon ---
const SortIcon = {
  props: { active: Boolean, asc: Boolean },
  template: `<svg class="w-3 h-3 inline" :class="active ? 'text-indigo-500' : 'text-gray-300'" viewBox="0 0 10 14" fill="currentColor">
    <path v-if="!active || asc" d="M5 0L9.33 5H0.67L5 0Z" />
    <path v-if="!active || !asc" d="M5 14L0.67 9H9.33L5 14Z" />
  </svg>`,
};

const props = defineProps({ tractors: Array, groups: Array, summary: Object, filterData: Object });

const filters = reactive({
  group_id: props.filterData?.group_id || '',
});
const search = ref('');
const statusFilter = ref('');
const pmsFilter = ref('');
const sortField = ref('no_plate');
const sortAsc = ref(false);
const currentPage = ref(1);
const perPage = 25;

// Computed
const onlineCount = computed(() => (props.tractors || []).filter(t => t.status === 'online').length);
const offlineCount = computed(() => (props.tractors || []).filter(t => t.status === 'offline').length);
const withDataCount = computed(() => (props.tractors || []).filter(t => (t.total_distance || 0) > 0 || (t.running_hours || 0) > 0).length);
const dataPercent = computed(() => props.summary?.total_tractors ? Math.round(withDataCount.value / props.summary.total_tractors * 100) : 0);

const exportUrl = computed(() => {
  const params = new URLSearchParams();
  if (filters.group_id) params.set('group_id', filters.group_id);
  const qs = params.toString();
  return '/reports/tractor-usage/export' + (qs ? '?' + qs : '');
});

const filteredTractors = computed(() => {
  let list = props.tractors || [];
  const q = search.value.toLowerCase().trim();
  if (q) {
    list = list.filter(t =>
      (t.no_plate || '').toLowerCase().includes(q) ||
      (t.brand || '').toLowerCase().includes(q) ||
      (t.model || '').toLowerCase().includes(q) ||
      (t.imei || '').includes(q) ||
      (t.group?.name || '').toLowerCase().includes(q)
    );
  }
  if (statusFilter.value) {
    list = list.filter(t => t.status === statusFilter.value);
  }
  if (pmsFilter.value) {
    if (pmsFilter.value === 'due') list = list.filter(t => t.pms_status === 'Due');
    else if (pmsFilter.value === 'nodata') list = list.filter(t => t.pms_status === 'No Data');
    else if (pmsFilter.value === 'ok') list = list.filter(t => t.pms_status !== 'Due' && t.pms_status !== 'No Data');
  }
  // Sort
  const field = sortField.value;
  const asc = sortAsc.value;
  list = [...list].sort((a, b) => {
    let va = a[field] ?? '';
    let vb = b[field] ?? '';
    if (typeof va === 'number' || typeof vb === 'number') {
      return asc ? (va - vb) : (vb - va);
    }
    return asc ? String(va).localeCompare(String(vb)) : String(vb).localeCompare(String(va));
  });
  return list;
});

const filteredTotals = computed(() => ({
  pmsDue: filteredTractors.value.filter(t => t.pms_status === 'Due').length,
}));

const totalPages = computed(() => Math.ceil(filteredTractors.value.length / perPage));
const paginatedTractors = computed(() => {
  const start = (currentPage.value - 1) * perPage;
  return filteredTractors.value.slice(start, start + perPage);
});

const visiblePages = computed(() => {
  const total = totalPages.value;
  const cur = currentPage.value;
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
  const pages = [];
  pages.push(1);
  if (cur > 3) pages.push('...');
  for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i);
  if (cur < total - 2) pages.push('...');
  pages.push(total);
  return pages;
});

// Watchers — reset page on filter/search change
watch([search, statusFilter, pmsFilter], () => { currentPage.value = 1; });

function toggleSort(field) {
  if (sortField.value === field) {
    sortAsc.value = !sortAsc.value;
  } else {
    sortField.value = field;
    sortAsc.value = field === 'no_plate'; // alpha asc, numeric desc by default
  }
}

function formatNumber(val) {
  return Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

const applyFilter = () => {
  router.get('/reports/tractor-usage', {
    group_id: filters.group_id || undefined,
  }, { preserveState: true, replace: true });
};
</script>
