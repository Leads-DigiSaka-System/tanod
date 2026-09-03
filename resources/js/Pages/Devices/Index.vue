<template>
  <AppLayout>
    <Head title="Devices" />

    <!-- Page Header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Devices</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage and monitor all tracked devices.</p>
      </div>
      <div v-if="$page.props.auth.user.permissions.includes('devices.sync')" class="mt-4 sm:mt-0 flex flex-wrap gap-2">
        <Link
          href="/devices/sync"
          method="post"
          as="button"
          class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800"
        >
          <svg class="w-4 h-4 inline-block mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
          Sync Devices
        </Link>
        <Link
          href="/devices/sync-locations"
          method="post"
          as="button"
          class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800"
        >
          <svg class="w-4 h-4 inline-block mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
          Sync Locations
        </Link>
      </div>
    </div>

    <!-- Filters Card -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 sm:p-6 mb-6 dark:bg-gray-800 dark:border-gray-700">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label for="device-search" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Search</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
              <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </div>
            <input
              id="device-search"
              v-model="search"
              type="text"
              placeholder="Search IMEI, name..."
              @input="debouncedFilter"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"
            />
          </div>
        </div>
        <div>
          <label for="device-status" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Status</label>
          <select
            id="device-status"
            v-model="selectedStatus"
            @change="filter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"
          >
            <option value="">All Statuses</option>
            <option value="online">Online</option>
            <option value="offline">Offline</option>
            <option value="unassigned">Unassigned</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Table -->
    <DataTable>
      <template #head>
        <tr>
          <th scope="col" class="px-6 py-3 whitespace-nowrap">IMEI</th>
          <th scope="col" class="px-6 py-3 whitespace-nowrap">Name</th>
          <th scope="col" class="px-6 py-3 whitespace-nowrap">Tractor</th>
          <th scope="col" class="px-6 py-3 whitespace-nowrap">Status</th>
          <th scope="col" class="px-6 py-3 whitespace-nowrap">Last Heartbeat</th>
          <th scope="col" class="px-6 py-3 text-right whitespace-nowrap">Actions</th>
        </tr>
      </template>
      <template #body>
        <tr
          v-for="device in devices.data"
          :key="device.id"
          class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
        >
          <td class="px-6 py-4 font-mono font-medium text-gray-900 whitespace-nowrap dark:text-white">
            {{ device.imei }}
          </td>
          <td class="px-6 py-4 whitespace-nowrap">{{ device.device_name || '—' }}</td>
          <td class="px-6 py-4 whitespace-nowrap">
            <span v-if="device.tractor" class="font-medium text-gray-900 dark:text-white">{{ device.tractor.no_plate }}</span>
            <span v-else class="text-gray-400 italic">Unassigned</span>
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <StatusBadge :status="getStatus(device)" />
          </td>
          <td class="px-6 py-4 whitespace-nowrap">{{ device.latest_location?.heartbeat_at || 'Never' }}</td>
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center justify-end">
              <Link :href="`/devices/${device.id}`" class="p-1.5 rounded-lg text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:text-gray-400 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20 transition-colors" title="View">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
              </Link>
            </div>
          </td>
        </tr>

        <!-- Empty state -->
        <tr v-if="!devices.data.length">
          <td colspan="6" class="px-6 py-16 text-center">
            <div class="flex flex-col items-center justify-center">
              <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              <h3 class="text-sm font-medium text-gray-900 dark:text-white">No devices found</h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your search or filter criteria.</p>
            </div>
          </td>
        </tr>
      </template>
    </DataTable>

    <!-- Pagination -->
    <Pagination :links="devices.links" class="mt-6" />
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import DataTable from '@/Components/DataTable.vue';

const props = defineProps({ devices: Object, filters: Object });

const search = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || '');

let debounceTimer;
const debouncedFilter = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(filter, 300);
};

const filter = () => {
  router.get('/devices', {
    search: search.value || undefined,
    status: selectedStatus.value || undefined,
  }, { preserveState: true, replace: true });
};

const getStatus = (device) => {
  // Prefer JIMI's own status field (same as Live View uses)
  if (device.jimi_status) return device.jimi_status;

  // Fallback: live heartbeat from JIMI API
  if (device.live_heartbeat_at) {
    return (Date.now() - new Date(device.live_heartbeat_at).getTime()) < 600000 ? 'online' : 'offline';
  }

  // Last resort: DB-stored heartbeat
  const loc = device.latest_location || device.latestLocation || {};
  if (loc.heartbeat_at) {
    return (Date.now() - new Date(loc.heartbeat_at).getTime()) < 600000 ? 'online' : 'offline';
  }

  return 'offline';
};
</script>
