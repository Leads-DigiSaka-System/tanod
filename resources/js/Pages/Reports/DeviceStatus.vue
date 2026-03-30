<template>
  <AppLayout>
    <Head title="Device Status Report" />

    <!-- Page Header -->
    <div class="mb-6">
      <Link href="/reports" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        Back to Reports
      </Link>
      <div class="mt-3">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Device Status Report</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Monitor device connectivity, heartbeats, and SIM expiration</p>
      </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 mb-6">
      <StatCard title="Total Devices" :value="summary.total" color="blue" icon="device" />
      <StatCard title="Online" :value="summary.online" color="green" icon="wifi" />
      <StatCard title="Offline" :value="summary.offline" color="red" icon="wifi-off" />
      <StatCard title="Active" :value="summary.active" color="amber" icon="power" />
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
      <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
          <tr>
            <th class="px-6 py-3">Device</th>
            <th class="px-6 py-3">IMEI</th>
            <th class="px-6 py-3">Tractor</th>
            <th class="px-6 py-3">Status</th>
            <th class="px-6 py-3">Last Heartbeat</th>
            <th class="px-6 py-3">SIM</th>
            <th class="px-6 py-3">Expiration</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="d in devices" :key="d.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">
              <Link :href="`/devices/${d.id}`" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">{{ d.device_name || d.imei }}</Link>
            </td>
            <td class="px-6 py-4 font-mono text-xs">{{ d.imei }}</td>
            <td class="px-6 py-4">{{ d.tractor ? `${d.tractor.brand} ${d.tractor.model}` : '—' }}</td>
            <td class="px-6 py-4"><StatusBadge :status="d.is_online ? 'online' : 'offline'" :label="d.is_online ? 'Online' : 'Offline'" /></td>
            <td class="px-6 py-4">{{ formatDate(d.latest_location?.heartbeat_at) }}</td>
            <td class="px-6 py-4">{{ d.sim || '—' }}</td>
            <td class="px-6 py-4" :class="isExpired(d.expiration_date) ? 'text-red-600 dark:text-red-400 font-medium' : ''">
              {{ d.expiration_date ? formatDate(d.expiration_date) : '—' }}
            </td>
          </tr>
          <tr v-if="!devices?.length">
            <td colspan="7" class="px-6 py-12">
              <div class="flex flex-col items-center justify-center text-center">
                <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <p class="text-sm text-gray-500 dark:text-gray-400">No devices found.</p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/utils/dateFormat';

defineProps({ devices: Array, summary: Object });

const isExpired = (date) => date && new Date(date) < new Date();
</script>
