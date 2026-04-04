<template>
  <AppLayout>
    <Head title="Geo-Fences" />

    <!-- Page Header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Geo-Fences</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage geographic boundaries for your tracked devices.</p>
      </div>
      <Link href="/geofences/create" class="mt-3 sm:mt-0 inline-flex items-center gap-2 text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Create Geo-Fence
      </Link>
    </div>

    <!-- Summary Stats -->
    <div v-if="geoFences.data?.length" class="grid grid-cols-3 gap-4 mb-6">
      <div class="bg-white rounded-xl border border-gray-200 p-4 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/40">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ geoFences.total ?? geoFences.data.length }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Total Fences</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/40">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2"/></svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ circleCount }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Circles</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/40">
            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2l8.5 5v10L12 22l-8.5-5V7L12 2z"/></svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ polygonCount }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Polygons</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Search Filter -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 mb-6 dark:bg-gray-800 dark:border-gray-700">
      <div class="relative">
        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
          <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <input v-model="search" type="text" placeholder="Search geo-fence name..." @input="debouncedFilter"
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:w-80 ps-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
      </div>
    </div>

    <!-- Geo-Fence Cards Grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
      <Link v-for="gf in geoFences.data" :key="gf.id" :href="`/geofences/${gf.id}`"
        class="group bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden hover:shadow-lg hover:border-gray-300 transition-all duration-200 dark:bg-gray-800 dark:border-gray-700 dark:hover:border-gray-600">

        <!-- Card Top Accent -->
        <div class="h-1" :class="gf.shape === 'circle' ? 'bg-gradient-to-r from-blue-400 to-blue-600' : 'bg-gradient-to-r from-purple-400 to-purple-600'"></div>

        <div class="p-5">
          <!-- Header -->
          <div class="flex items-start justify-between gap-3 mb-4">
            <div class="flex items-center gap-3 min-w-0">
              <div class="shrink-0 flex items-center justify-center w-10 h-10 rounded-lg" :class="gf.shape === 'circle' ? 'bg-blue-50 dark:bg-blue-900/30' : 'bg-purple-50 dark:bg-purple-900/30'">
                <svg v-if="gf.shape === 'circle'" class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2"/></svg>
                <svg v-else class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2l8.5 5v10L12 22l-8.5-5V7L12 2z"/></svg>
              </div>
              <div class="min-w-0">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ gf.name }}</h3>
                <span class="text-xs capitalize" :class="gf.shape === 'circle' ? 'text-blue-600 dark:text-blue-400' : 'text-purple-600 dark:text-purple-400'">{{ gf.shape }}</span>
              </div>
            </div>
            <div class="shrink-0 flex items-center gap-0.5" @click.prevent>
              <button @click="confirmDelete(gf)" class="p-1.5 rounded-lg text-gray-400 opacity-0 group-hover:opacity-100 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-all" title="Delete">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
              </button>
            </div>
          </div>

          <!-- Devices -->
          <div v-if="gf.devices && gf.devices.length" class="flex flex-wrap gap-1.5 mb-4">
            <span v-for="dev in gf.devices.slice(0, 3)" :key="dev.id"
              class="inline-flex items-center gap-1 bg-gray-50 text-gray-700 text-xs font-medium px-2 py-1 rounded-md border border-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
              <svg class="w-3 h-3 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              {{ dev.device_name || dev.imei }}
            </span>
            <span v-if="gf.devices.length > 3" class="inline-flex items-center text-xs font-medium text-gray-500 dark:text-gray-400 px-2 py-1">
              +{{ gf.devices.length - 3 }} more
            </span>
          </div>
          <div v-else class="mb-4">
            <span class="text-xs text-gray-400 dark:text-gray-500 italic">No devices assigned</span>
          </div>

          <!-- Metadata -->
          <div class="space-y-2">
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
              <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
              <span>Alert on <span class="capitalize font-medium text-gray-700 dark:text-gray-300">{{ gf.alert_on?.replace(/_/g, ' ') }}</span></span>
            </div>
            <div v-if="gf.radius" class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
              <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
              <span>Radius <span class="font-medium text-gray-700 dark:text-gray-300">{{ Number(gf.radius).toLocaleString() }}m</span></span>
            </div>
            <div v-if="gf.creator" class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
              <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              <span class="truncate">{{ gf.creator.name }}</span>
            </div>
          </div>
        </div>
      </Link>

      <!-- Empty State -->
      <div v-if="!geoFences.data?.length" class="sm:col-span-2 lg:col-span-3 bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <div class="flex flex-col items-center justify-center py-20">
          <div class="flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
            <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
          </div>
          <p class="text-sm font-semibold text-gray-900 dark:text-white">No geo-fences found</p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-4">Create your first geo-fence to start monitoring boundaries.</p>
          <Link href="/geofences/create" class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Geo-Fence
          </Link>
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
        <div class="mt-5 flex justify-center gap-3">
          <button @click="showDeleteModal = false" class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-700">Cancel</button>
          <Link :href="`/geofences/${deleteTarget?.id}`" method="delete" as="button" class="text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900">Delete</Link>
        </div>
      </div>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({ geoFences: Object, filters: Object });

const search = ref(props.filters?.search || '');
const showDeleteModal = ref(false);
const deleteTarget = ref(null);

const circleCount = computed(() => props.geoFences.data?.filter(g => g.shape === 'circle').length ?? 0);
const polygonCount = computed(() => props.geoFences.data?.filter(g => g.shape === 'polygon').length ?? 0);

let timer;
const debouncedFilter = () => { clearTimeout(timer); timer = setTimeout(() => {
  router.get('/geofences', { search: search.value || undefined }, { preserveState: true, replace: true });
}, 300); };

const confirmDelete = (gf) => { deleteTarget.value = gf; showDeleteModal.value = true; };
</script>
