<template>
  <AppLayout>
    <Head title="Location History" />

    <!-- Page Header -->
    <div class="mb-8">
      <Link :href="`/devices/${device.id}`" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Device
      </Link>
      <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Location History</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ device.device_name || device.imei }} — Historical location data and trail</p>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 mb-6 dark:bg-gray-800 dark:border-gray-700">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 items-end">
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">From Date</label>
          <input v-model="filters.from" type="date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">To Date</label>
          <input v-model="filters.to" type="date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
        </div>
        <div>
          <button @click="applyFilter" class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 w-full dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800 inline-flex items-center justify-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            Search
          </button>
        </div>
        <div>
          <button @click="clearFilter" class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 w-full dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700 inline-flex items-center justify-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            Clear
          </button>
        </div>
      </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 mb-6">
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center">
          <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900 mr-3">
            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </div>
          <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Total Points</p>
            <p class="mt-0.5 text-2xl font-bold text-gray-900 dark:text-white">{{ locations.data?.length || 0 }}</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center">
          <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900 mr-3">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          </div>
          <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Max Speed</p>
            <p class="mt-0.5 text-2xl font-bold text-gray-900 dark:text-white">{{ maxSpeed }} <span class="text-sm font-normal text-gray-500">km/h</span></p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center">
          <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900 mr-3">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
          </div>
          <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Avg Speed</p>
            <p class="mt-0.5 text-2xl font-bold text-gray-900 dark:text-white">{{ avgSpeed }} <span class="text-sm font-normal text-gray-500">km/h</span></p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center">
          <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900 mr-3">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Time Range</p>
            <p class="mt-0.5 text-sm font-semibold text-gray-900 dark:text-white">{{ timeRange }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Location Table -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
          <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
              <th scope="col" class="px-6 py-3">#</th>
              <th scope="col" class="px-6 py-3">Latitude</th>
              <th scope="col" class="px-6 py-3">Longitude</th>
              <th scope="col" class="px-6 py-3">Speed</th>
              <th scope="col" class="px-6 py-3">Direction</th>
              <th scope="col" class="px-6 py-3">ACC</th>
              <th scope="col" class="px-6 py-3">Heartbeat</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(loc, i) in locations.data" :key="loc.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
              <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ i + 1 }}</td>
              <td class="px-6 py-4 font-mono">{{ loc.lat?.toFixed(6) }}</td>
              <td class="px-6 py-4 font-mono">{{ loc.lng?.toFixed(6) }}</td>
              <td class="px-6 py-4 text-gray-900 dark:text-white">{{ loc.speed ?? 0 }} km/h</td>
              <td class="px-6 py-4">{{ loc.direction ?? 0 }}°</td>
              <td class="px-6 py-4">
                <span v-if="loc.acc_status" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">ON</span>
                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">OFF</span>
              </td>
              <td class="px-6 py-4">{{ loc.heartbeat_at || '—' }}</td>
            </tr>
            <tr v-if="!locations.data?.length">
              <td colspan="7" class="px-6 py-16 text-center">
                <div class="flex flex-col items-center justify-center">
                  <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No location data found</p>
                  <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Try adjusting your date filters or check back later</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Pagination :links="locations.links" class="mt-6" />
  </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({ device: Object, locations: Object, filters: Object });

const filters = reactive({
  from: props.filters?.from || '',
  to: props.filters?.to || '',
});

const applyFilter = () => {
  router.get(`/devices/${props.device.id}/history`, {
    from: filters.from || undefined,
    to: filters.to || undefined,
  }, { preserveState: true, replace: true });
};

const clearFilter = () => {
  filters.from = '';
  filters.to = '';
  router.get(`/devices/${props.device.id}/history`, {}, { preserveState: true, replace: true });
};

const maxSpeed = computed(() => {
  const data = props.locations?.data || [];
  return data.length ? Math.max(...data.map(l => l.speed || 0)) : 0;
});

const avgSpeed = computed(() => {
  const data = props.locations?.data || [];
  if (!data.length) return 0;
  return (data.reduce((sum, l) => sum + (l.speed || 0), 0) / data.length).toFixed(1);
});

const timeRange = computed(() => {
  const data = props.locations?.data || [];
  if (!data.length) return '—';
  const first = data[data.length - 1]?.heartbeat_at;
  const last = data[0]?.heartbeat_at;
  return `${first || '?'} → ${last || '?'}`;
});
</script>
