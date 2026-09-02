<template>
  <AppLayout>
    <Head title="Logs" />

    <!-- Page header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Logs</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Monitor every action performed across the system.</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 mb-6 dark:bg-gray-800 dark:border-gray-700">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <input v-model="search" type="text" placeholder="Search user, action, module..." @input="debouncedFilter"
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" />
        <select v-model="selectedAction" @change="applyFilter"
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
          <option value="">All Actions</option>
          <option v-for="a in actions" :key="a" :value="a">{{ formatAction(a) }}</option>
        </select>
        <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
          {{ logs.total }} total record{{ logs.total !== 1 ? 's' : '' }}
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
      <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
          <tr>
            <th scope="col" class="px-6 py-3">Date &amp; Time</th>
            <th scope="col" class="px-6 py-3">User</th>
            <th scope="col" class="px-6 py-3">Action</th>
            <th scope="col" class="px-6 py-3">Module</th>
            <th scope="col" class="px-6 py-3">Details</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in logs.data" :key="log.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
            <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full text-white flex items-center justify-center text-sm font-bold shrink-0" style="background-color: #007f3d;">
                  {{ log.performer?.name?.charAt(0)?.toUpperCase() || 'S' }}
                </div>
                <div class="min-w-0">
                  <p class="font-medium text-gray-900 dark:text-white whitespace-nowrap">{{ log.performer?.name || 'System' }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">{{ log.performer?.email || '—' }}</p>
                </div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize" :class="actionBadgeClass(log.action)">
                {{ formatAction(log.action) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">{{ log.model_type }}</td>
            <td class="px-6 py-4 max-w-md">
              <span class="text-gray-600 dark:text-gray-300">{{ formatChanges(log.changes) }}</span>
            </td>
          </tr>
          <tr v-if="!logs.data?.length">
            <td colspan="5" class="px-6 py-16 text-center">
              <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <p class="mt-4 text-sm font-medium text-gray-900 dark:text-white">No logs found</p>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Actions performed in the system will appear here.</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <Pagination :links="logs.links" class="mt-6" />
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatDate } from '@/utils/dateFormat';

const props = defineProps({
  logs: Object,
  filters: Object,
  actions: Array,
});

const search = ref(props.filters?.search || '');
const selectedAction = ref(props.filters?.action || '');

let timer;
const debouncedFilter = () => { clearTimeout(timer); timer = setTimeout(applyFilter, 300); };

const applyFilter = () => {
  router.get('/logs', {
    search: search.value || undefined,
    action: selectedAction.value || undefined,
  }, { preserveState: true, replace: true });
};

const formatAction = (action) => (action ? action.replace(/_/g, ' ') : action);

const actionBadgeClass = (action) => {
  const map = {
    created: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    updated: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    deleted: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    force_deleted: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    distributed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    returned: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    restored: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
    image_deleted: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
    comment_added: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
    status_updated: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
    assigned: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300',
    approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    cancelled: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
    reviewed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    activated: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
    deactivated: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
    acknowledged: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    acknowledged_all: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    payment_added: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    collection_approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    permissions_updated: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    profile_updated: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    password_changed: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    reports_sent: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    subscription_created: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    subscription_updated: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    subscription_deleted: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    share_created: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    synced: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    synced_locations: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
  };
  return map[action] || 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
};

const formatChanges = (changes) => {
  if (!changes || typeof changes !== 'object') return '—';
  const entries = Object.entries(changes);
  if (!entries.length) return '—';
  return entries
    .map(([key, value]) => `${key.replace(/_/g, ' ')}: ${Array.isArray(value) ? value.join(', ') : (value ?? '—')}`)
    .join(' · ');
};
</script>
