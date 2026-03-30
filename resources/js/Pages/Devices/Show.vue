<template>
  <AppLayout>
    <Head title="Device Details" />

    <!-- Page Header -->
    <div class="mb-8">
      <Link href="/devices" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Devices
      </Link>
      <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ device.imei }}</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Device details and current location information</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

      <!-- Device Info Card -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center mb-5">
          <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900 mr-3">
            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
          </div>
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Device Info</h2>
        </div>
        <dl class="space-y-3">
          <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">IMEI</dt>
            <dd class="mt-1 text-sm font-mono font-semibold text-gray-900 dark:text-white">{{ device.imei }}</dd>
          </div>
          <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Name</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ device.device_name || '—' }}</dd>
          </div>
          <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Model</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ device.device_model || '—' }}</dd>
          </div>
          <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">SIM</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ device.sim || '—' }}</dd>
          </div>
          <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Tractor</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
              <span v-if="device.tractor" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300">{{ device.tractor.no_plate }}</span>
              <span v-else class="text-gray-400 dark:text-gray-500">Unassigned</span>
            </dd>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Activation</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ device.activation_time ? formatDate(device.activation_time) : '—' }}</dd>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Expiration</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ device.expiration_date ? formatDate(device.expiration_date) : '—' }}</dd>
            </div>
          </div>
        </dl>
      </div>

      <!-- Current Location Card -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center mb-5">
          <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900 mr-3">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </div>
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Current Location</h2>
        </div>
        <div v-if="device.latest_location">
          <div class="mb-4">
            <StatusBadge :status="isOnline ? 'online' : 'offline'" />
          </div>
          <dl class="space-y-3">
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Lat / Lng</dt>
              <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ device.latest_location.lat }}, {{ device.latest_location.lng }}</dd>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Speed</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                <span class="inline-flex items-center">
                  <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                  {{ device.latest_location.speed }} km/h
                </span>
              </dd>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Last Heartbeat</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(device.latest_location.heartbeat_at) }}</dd>
            </div>
          </dl>
        </div>
        <div v-else class="flex flex-col items-center justify-center py-10">
          <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No location data available</p>
          <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">The device has not reported any location yet</p>
        </div>
      </div>
    </div>

    <!-- Recent Location Trail -->
    <div class="mt-6 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
      <div class="flex items-center p-6 pb-4">
        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900 mr-3">
          <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
        </div>
        <div>
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Location Trail</h2>
          <p v-if="recentLocations?.length" class="text-sm text-gray-500 dark:text-gray-400">{{ recentLocations.length }} point(s) recorded</p>
        </div>
      </div>

      <div v-if="recentLocations?.length" class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
          <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
              <th scope="col" class="px-6 py-3">Time</th>
              <th scope="col" class="px-6 py-3">Lat</th>
              <th scope="col" class="px-6 py-3">Lng</th>
              <th scope="col" class="px-6 py-3">Speed</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="loc in recentLocations.slice(0, 20)" :key="loc.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
              <td class="px-6 py-4 text-gray-900 dark:text-white">{{ formatDate(loc.heartbeat_at) }}</td>
              <td class="px-6 py-4 font-mono">{{ loc.lat }}</td>
              <td class="px-6 py-4 font-mono">{{ loc.lng }}</td>
              <td class="px-6 py-4">{{ loc.speed }} km/h</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="flex flex-col items-center justify-center py-12 px-6">
        <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No trail data recorded</p>
        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Location trail will appear here once the device starts reporting</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/utils/dateFormat';

const props = defineProps({ device: Object, recentLocations: Array });

const isOnline = computed(() => {
  if (!props.device.latest_location?.heartbeat_at) return false;
  return (Date.now() - new Date(props.device.latest_location.heartbeat_at).getTime()) < 600000;
});
</script>
