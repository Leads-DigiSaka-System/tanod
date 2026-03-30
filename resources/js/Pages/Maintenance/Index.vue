<template>
  <AppLayout>
    <Head title="Maintenance" />
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Maintenance Records</h1>
      <Link href="/maintenance/create"
        class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800">
        New Maintenance
      </Link>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 mb-6 dark:bg-gray-800 dark:border-gray-700">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Search</label>
          <input v-model="search" type="text" placeholder="Search tractor, description..." @input="debouncedFilter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Status</label>
          <select v-model="statusFilter" @change="applyFilter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tractor</label>
          <select v-model="tractorFilter" @change="applyFilter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500">
            <option value="">All Tractors</option>
            <option v-for="t in tractors" :key="t.id" :value="t.id">{{ t.brand }} {{ t.model }} — {{ t.no_plate }}</option>
          </select>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
      <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
          <tr>
            <th scope="col" class="px-6 py-3">Tractor</th>
            <th scope="col" class="px-6 py-3">Issue Type</th>
            <th scope="col" class="px-6 py-3">Description</th>
            <th scope="col" class="px-6 py-3">Status</th>
            <th scope="col" class="px-6 py-3">Cost</th>
            <th scope="col" class="px-6 py-3">Date</th>
            <th scope="col" class="px-6 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in maintenances.data" :key="m.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">
              {{ m.tractor?.brand }} {{ m.tractor?.model }} — {{ m.tractor?.no_plate }}
            </td>
            <td class="px-6 py-4">{{ m.issue_type?.name || '—' }}</td>
            <td class="px-6 py-4 max-w-xs truncate">{{ m.description }}</td>
            <td class="px-6 py-4"><StatusBadge :status="m.status" /></td>
            <td class="px-6 py-4">{{ m.cost ? `₱${Number(m.cost).toLocaleString()}` : '—' }}</td>
            <td class="px-6 py-4">{{ formatDate(m.scheduled_date || m.created_at) }}</td>
            <td class="px-6 py-4">
              <div class="flex items-center justify-end gap-1">
                <Link :href="`/maintenance/${m.id}`" class="p-1.5 rounded-lg text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:text-gray-400 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20 transition-colors" title="View">
                  <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                </Link>
                <Link :href="`/maintenance/${m.id}/edit`" class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:text-gray-400 dark:hover:text-blue-400 dark:hover:bg-blue-900/20 transition-colors" title="Edit">
                  <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                </Link>
              </div>
            </td>
          </tr>
          <tr v-if="!maintenances.data?.length">
            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No maintenance records found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <Pagination :links="maintenances.links" class="mt-6" />
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/utils/dateFormat';

const props = defineProps({ maintenances: Object, filters: Object, tractors: Array });

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const tractorFilter = ref(props.filters?.tractor_id || '');

let timer;
const debouncedFilter = () => { clearTimeout(timer); timer = setTimeout(applyFilter, 300); };
const applyFilter = () => {
  router.get('/maintenance', {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
    tractor_id: tractorFilter.value || undefined,
  }, { preserveState: true, replace: true });
};
</script>
