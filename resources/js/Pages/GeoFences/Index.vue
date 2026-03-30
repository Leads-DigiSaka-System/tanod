<template>
  <AppLayout>
    <Head title="Geo-Fences" />

    <!-- Page Header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Geo-Fences</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage geographic boundaries for your tracked devices.</p>
      </div>
      <Link href="/geofences/create" class="mt-3 sm:mt-0 text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800">
        Create Geo-Fence
      </Link>
    </div>

    <!-- Search Filter -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 mb-6 dark:bg-gray-800 dark:border-gray-700">
      <input v-model="search" type="text" placeholder="Search geo-fence name..." @input="debouncedFilter"
        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:w-80 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
    </div>

    <!-- Geo-Fence Cards Grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="gf in geoFences.data" :key="gf.id" class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 hover:shadow-md transition dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ gf.name }}</h3>
          <span class="text-xs font-medium px-2.5 py-1 rounded-full" :class="gf.shape === 'circle' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300'">
            {{ gf.shape }}
          </span>
        </div>
        <div v-if="gf.devices && gf.devices.length" class="flex flex-wrap gap-1 mb-1">
          <span v-for="dev in gf.devices" :key="dev.id"
            class="inline-flex items-center bg-gray-100 text-gray-800 text-xs font-medium px-2 py-0.5 rounded dark:bg-gray-700 dark:text-gray-300">
            {{ dev.device_name || dev.imei }}
          </span>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Alert on: <span class="capitalize">{{ gf.alert_on?.replace(/_/g, ' ') }}</span></p>
        <p v-if="gf.radius" class="text-sm text-gray-500 dark:text-gray-400 mb-3">Radius: {{ gf.radius }}m</p>
        <div class="flex items-center justify-end gap-1 pt-3 border-t border-gray-100 dark:border-gray-700">
          <Link :href="`/geofences/${gf.id}`" class="p-1.5 rounded-lg text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:text-gray-400 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20 transition-colors" title="View">
            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
          </Link>
          <button @click="confirmDelete(gf)" class="p-1.5 rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50 dark:text-gray-400 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors" title="Delete">
            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
          </button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!geoFences.data?.length" class="sm:col-span-2 lg:col-span-3 bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <div class="flex flex-col items-center justify-center py-16">
          <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
          </svg>
          <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No geo-fences found</p>
          <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Create a new geo-fence to get started.</p>
        </div>
      </div>
    </div>

    <Pagination :links="geoFences.links" class="mt-6" />

    <!-- Delete Confirmation Modal -->
    <Modal :show="showDeleteModal" @close="showDeleteModal = false" maxWidth="sm">
      <div class="p-6 dark:bg-gray-800">
        <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900">
          <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white text-center">Delete Geo-Fence</h3>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 text-center">Are you sure you want to delete "{{ deleteTarget?.name }}"? This will also remove it from JIMI.</p>
        <div class="mt-5 flex justify-center space-x-3">
          <button @click="showDeleteModal = false" class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-700">Cancel</button>
          <Link :href="`/geofences/${deleteTarget?.id}`" method="delete" as="button" class="text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900">Delete</Link>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({ geoFences: Object, filters: Object });

const search = ref(props.filters?.search || '');
const showDeleteModal = ref(false);
const deleteTarget = ref(null);

let timer;
const debouncedFilter = () => { clearTimeout(timer); timer = setTimeout(() => {
  router.get('/geofences', { search: search.value || undefined }, { preserveState: true, replace: true });
}, 300); };

const confirmDelete = (gf) => { deleteTarget.value = gf; showDeleteModal.value = true; };
</script>
