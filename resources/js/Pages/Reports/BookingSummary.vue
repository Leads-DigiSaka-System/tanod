<template>
  <AppLayout>
    <Head title="Booking Summary" />

    <!-- Page Header -->
    <div class="mb-6">
      <Link href="/reports" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        Back to Reports
      </Link>
      <div class="mt-3 flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Booking Summary</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Overview of tractor bookings by status and usage</p>
        </div>
        <a :href="exportUrl" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition shrink-0">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
          Export Excel
        </a>
      </div>
    </div>

    <!-- Filters Card -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700 mb-6">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">From Date</label>
          <input v-model="filters.from" type="date" @change="applyFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">To Date</label>
          <input v-model="filters.to" type="date" @change="applyFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Status</label>
          <select v-model="filters.status" @change="applyFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">All</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 mb-6">
      <StatCard title="Total Bookings" :value="summary.total" color="blue" icon="calendar" />
      <StatCard title="Approved" :value="summary.approved" color="green" icon="check" />
      <StatCard title="Pending" :value="summary.pending" color="amber" icon="clock" />
      <StatCard title="Rejected" :value="summary.rejected" color="red" icon="x" />
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
      <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
          <tr>
            <th class="px-6 py-3">Booking #</th>
            <th class="px-6 py-3">Tractor</th>
            <th class="px-6 py-3">Booked By</th>
            <th class="px-6 py-3">Status</th>
            <th class="px-6 py-3">Date</th>
            <th class="px-6 py-3">Duration</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="b in bookings.data" :key="b.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
              <Link :href="`/bookings/${b.id}`" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">#{{ b.id }}</Link>
            </td>
            <td class="px-6 py-4 text-gray-900 dark:text-white">{{ b.tractor?.brand }} {{ b.tractor?.model }} — {{ b.tractor?.no_plate }}</td>
            <td class="px-6 py-4">{{ b.booked_by?.name || '—' }}</td>
            <td class="px-6 py-4"><StatusBadge :status="b.status" /></td>
            <td class="px-6 py-4">{{ b.start_date ? formatDate(b.start_date) : formatDate(b.created_at) }}</td>
            <td class="px-6 py-4">{{ b.start_date && b.end_date ? Math.ceil((new Date(b.end_date) - new Date(b.start_date)) / 86400000) + 'd' : '—' }}</td>
          </tr>
          <tr v-if="!bookings?.data?.length">
            <td colspan="6" class="px-6 py-12">
              <div class="flex flex-col items-center justify-center text-center">
                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-sm text-gray-500 dark:text-gray-400">No booking data available for the selected filters.</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/utils/dateFormat';

const props = defineProps({ bookings: Object, summary: Object, filterData: Object });

const filters = reactive({
  from: props.filterData?.from || '',
  to: props.filterData?.to || '',
  status: props.filterData?.status || '',
});

const applyFilter = () => {
  router.get('/reports/booking-summary', {
    from: filters.from || undefined,
    to: filters.to || undefined,
    status: filters.status || undefined,
  }, { preserveState: true, replace: true });
};

const exportUrl = computed(() => {
  const params = new URLSearchParams();
  if (filters.from) params.set('from', filters.from);
  if (filters.to) params.set('to', filters.to);
  const qs = params.toString();
  return '/reports/booking-summary/export' + (qs ? '?' + qs : '');
});
</script>
