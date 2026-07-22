<template>
  <AppLayout>
    <Head title="Distributions" />
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tractor Distributions</h1>
      <button @click="openSlideOver"
        class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800">
        New Distribution
      </button>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 mb-6 dark:bg-gray-800 dark:border-gray-700">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Search</label>
          <input v-model="search" type="text" placeholder="Search tractor, user, area..." @input="debouncedFilter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Status</label>
          <select v-model="statusFilter" @change="applyFilter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500">
            <option value="">All Status</option>
            <option value="distributed">Distributed</option>
            <option value="returned">Returned</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>
    </div>

    <DataTable>
      <template #head>
        <tr>
          <th scope="col" class="px-6 py-3 whitespace-nowrap">Tractor</th>
          <th scope="col" class="px-6 py-3 whitespace-nowrap">Distributed To</th>
          <th scope="col" class="px-6 py-3 whitespace-nowrap">Distributed By</th>
          <th scope="col" class="px-6 py-3 whitespace-nowrap">Area</th>
          <th scope="col" class="px-6 py-3 whitespace-nowrap">Status</th>
          <th scope="col" class="px-6 py-3 whitespace-nowrap">Date</th>
          <th scope="col" class="px-6 py-3 text-right whitespace-nowrap">Actions</th>
        </tr>
      </template>
      <template #body>
        <tr v-for="dist in distributions.data" :key="dist.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
          <td class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">
            {{ dist.tractor?.brand }} {{ dist.tractor?.model }} — {{ dist.tractor?.no_plate }}
          </td>
          <td class="px-6 py-4 whitespace-nowrap">{{ dist.distributed_to_user?.name || '—' }}</td>
          <td class="px-6 py-4 whitespace-nowrap">{{ dist.distributed_by_user?.name || '—' }}</td>
          <td class="px-6 py-4 whitespace-nowrap">{{ dist.area || '—' }}</td>
          <td class="px-6 py-4 whitespace-nowrap"><StatusBadge :status="dist.status" /></td>
          <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(dist.distributed_at || dist.created_at) }}</td>
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="flex items-center justify-end gap-1">
              <Link :href="`/distributions/${dist.id}`" class="p-1.5 rounded-lg text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:text-gray-400 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20 transition-colors" title="View">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
              </Link>
              <button v-if="dist.status === 'distributed'" @click="openEditSlideOver(dist)" class="p-1.5 rounded-lg text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:text-gray-400 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 transition-colors" title="Edit">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
              </button>
              <Link v-if="dist.status === 'distributed'" :href="`/distributions/${dist.id}/return`" method="post" as="button" class="p-1.5 rounded-lg text-gray-500 hover:text-amber-600 hover:bg-amber-50 dark:text-gray-400 dark:hover:text-amber-400 dark:hover:bg-amber-900/20 transition-colors" title="Return">
                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
              </Link>
            </div>
          </td>
        </tr>

        <!-- Empty state -->
        <tr v-if="!distributions.data?.length">
          <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No distributions found.</td>
        </tr>
      </template>
    </DataTable>

    <Pagination :links="distributions.links" class="mt-6" />

    <!-- Slide-over: New Distribution -->
    <SlideOver :show="showSlideOver" max-width="2xl" :title="editingDistribution ? 'Edit Distribution' : 'New Distribution'" :subtitle="editingDistribution ? 'Update tractor distribution details' : 'Record a new tractor distribution'" @close="closeSlideOver">
      <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto">
        <div class="p-6 space-y-5">
          <!-- Tractor multi-select -->
          <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
              Tractors <span class="text-red-500">*</span>
              <span v-if="form.tractor_ids.length" class="ml-1 text-emerald-600 dark:text-emerald-400 font-normal">({{ form.tractor_ids.length }} selected)</span>
            </label>
            <div class="relative mb-2">
              <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
              <input v-model="tractorSearch" type="text" placeholder="Search tractors..."
                class="w-full pl-9 pr-3 py-2 rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
            </div>
            <div class="flex items-center justify-between mb-2">
              <button type="button" @click="selectAllTractors" class="text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">Select all visible</button>
              <button type="button" @click="form.tractor_ids = []" class="text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">Clear</button>
            </div>
            <div class="max-h-48 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-600 divide-y divide-gray-100 dark:divide-gray-700">
              <label v-for="t in filteredTractors" :key="t.id"
                class="flex items-center gap-3 px-3 py-2.5 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                :class="{ 'bg-emerald-50/50 dark:bg-emerald-900/10': form.tractor_ids.includes(t.id) }">
                <input type="checkbox" :value="t.id" v-model="form.tractor_ids"
                  class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600" />
                <span class="relative flex h-2.5 w-2.5 shrink-0">
                  <span v-if="getOnlineStatus(t) === 'online'" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex h-2.5 w-2.5 rounded-full" :class="getOnlineStatus(t) === 'online' ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600'"></span>
                </span>
                <div class="flex-1 min-w-0">
                  <span class="text-sm font-medium text-gray-900 dark:text-white">{{ t.no_plate }}</span>
                  <span class="ml-1.5 text-xs text-gray-500 dark:text-gray-400">{{ t.brand }} {{ t.model }}</span>
                </div>
              </label>
              <div v-if="!filteredTractors.length" class="px-3 py-4 text-center text-sm text-gray-400 dark:text-gray-500">No tractors match your search.</div>
            </div>
            <p v-if="form.errors.tractor_ids" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.tractor_ids }}</p>
          </div>

          <!-- Distribute To (FCA) -->
          <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Distribute To (FCA) <span class="text-red-500">*</span></label>
            <select v-model="form.distributed_to"
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
              <option value="">Select FCA</option>
              <option v-for="u in fcaUsers" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
            </select>
            <p v-if="form.errors.distributed_to" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.distributed_to }}</p>
          </div>

          <!-- TSR -->
          <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Responsible TPS</label>
            <select v-model="form.tps_id"
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
              <option value="">Select responsible TSR (optional)</option>
              <option v-for="u in tpsUsers" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
            </select>
            <p v-if="form.errors.tps_id" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.tps_id }}</p>
          </div>

          <!-- Distribution Date -->
          <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Distribution Date <span class="text-red-500">*</span></label>
            <input v-model="form.distribution_date" type="date"
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
            <p v-if="form.errors.distribution_date" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.distribution_date }}</p>
          </div>

          <!-- Area -->
          <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Area <span class="text-red-500">*</span></label>
            <input v-model="form.area" type="text" placeholder="e.g. Tarlac, Pampanga"
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
            <p v-if="form.errors.area" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.area }}</p>
          </div>

          <!-- Notes -->
          <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
            <textarea v-model="form.notes" rows="3" placeholder="Any additional notes..."
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400"></textarea>
          </div>

          <!-- Proof Photo -->
          <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Proof of Distribution</label>
            <input ref="photoInput" type="file" accept="image/jpeg,image/png,image/webp" @change="handlePhotoChange"
              class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:text-gray-400 dark:file:bg-emerald-900/20 dark:file:text-emerald-400" />
            <img v-if="photoPreview" :src="photoPreview" alt="Preview" class="mt-2 h-32 w-auto rounded-lg border border-gray-200 dark:border-gray-600 object-cover" />
            <p v-if="form.errors.proof_photo" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.proof_photo }}</p>
          </div>

          <!-- Geolocation -->
          <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Geotag</label>
            <div class="flex items-center gap-3">
              <button type="button" @click="captureLocation" :disabled="geoLoading"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 disabled:opacity-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                {{ geoLoading ? 'Locating...' : 'Capture Location' }}
              </button>
              <span v-if="form.latitude && form.longitude" class="text-xs text-gray-500 dark:text-gray-400">
                {{ Number(form.latitude).toFixed(5) }}, {{ Number(form.longitude).toFixed(5) }}
              </span>
            </div>
            <p v-if="geoError" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ geoError }}</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="sticky bottom-0 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 px-6 py-4 flex justify-end gap-3">
          <button type="button" @click="closeSlideOver"
            class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">Cancel</button>
          <button type="submit" :disabled="form.processing"
            class="inline-flex items-center gap-1.5 rounded-lg px-5 py-2.5 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
            style="background-color: #007f3d;"
            @mouseenter="!form.processing && ($event.target.style.backgroundColor='#006631')"
            @mouseleave="!form.processing && ($event.target.style.backgroundColor='#007f3d')">
            <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
            {{ form.processing ? 'Saving...' : (editingDistribution ? 'Update' : 'Distribute') }}
          </button>
        </div>
      </form>
    </SlideOver>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import SlideOver from '@/Components/SlideOver.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import DataTable from '@/Components/DataTable.vue';
import { formatDate } from '@/utils/dateFormat';

const props = defineProps({
  distributions: Object,
  filters: Object,
  tractors: Array,
  fcaUsers: Array,
  tpsUsers: Array,
  editDistribution: { type: Object, default: null },
});

// --- Filters ---
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');

// --- Online status (heartbeat within 10 minutes) ---
const getOnlineStatus = (tractor) => {
  if (!tractor.device?.latest_location?.heartbeat_at) return 'offline';
  const heartbeat = new Date(tractor.device.latest_location.heartbeat_at);
  return (Date.now() - heartbeat.getTime()) < 600000 ? 'online' : 'offline';
};

let timer;
const debouncedFilter = () => { clearTimeout(timer); timer = setTimeout(applyFilter, 300); };
const applyFilter = () => {
  router.get('/distributions', {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
  }, { preserveState: true, replace: true });
};

// --- Slide-over ---
const showSlideOver = ref(false);
const tractorSearch = ref('');
const photoPreview = ref(null);
const photoInput = ref(null);
const geoLoading = ref(false);
const geoError = ref('');
const editingDistribution = ref(null);

const filteredTractors = computed(() => {
  const q = tractorSearch.value.toLowerCase().trim();
  if (!q) return props.tractors;
  return props.tractors.filter(t =>
    `${t.no_plate} ${t.brand} ${t.model}`.toLowerCase().includes(q)
  );
});

const selectAllTractors = () => {
  const visibleIds = filteredTractors.value.map(t => t.id);
  const merged = new Set([...form.tractor_ids, ...visibleIds]);
  form.tractor_ids = [...merged];
};

const form = useForm({
  tractor_ids: [],
  distributed_to: '',
  tps_id: '',
  distribution_date: new Date().toISOString().slice(0, 10),
  area: '',
  notes: '',
  proof_photo: null,
  latitude: null,
  longitude: null,
});

const openSlideOver = () => {
  editingDistribution.value = null;
  form.reset();
  form.clearErrors();
  form.distribution_date = new Date().toISOString().slice(0, 10);
  tractorSearch.value = '';
  photoPreview.value = null;
  geoError.value = '';
  showSlideOver.value = true;
};

const openEditSlideOver = (dist) => {
  editingDistribution.value = dist;
  form.clearErrors();
  form.tractor_ids = dist.tractor_ids ?? (dist.tractor_id ? [dist.tractor_id] : []);
  form.distributed_to = dist.distributed_to ?? '';
  form.tps_id = dist.tps_id ?? '';
  form.distribution_date = dist.distribution_date ? dist.distribution_date.slice(0, 10) : '';
  form.area = dist.area ?? '';
  form.notes = dist.notes ?? '';
  form.proof_photo = null;
  form.latitude = dist.latitude ?? null;
  form.longitude = dist.longitude ?? null;
  photoPreview.value = dist.proof_photo ? `/storage/${dist.proof_photo}` : null;
  tractorSearch.value = '';
  geoError.value = '';
  showSlideOver.value = true;
};

const closeSlideOver = () => {
  showSlideOver.value = false;
  editingDistribution.value = null;
};

const handlePhotoChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    form.proof_photo = file;
    photoPreview.value = URL.createObjectURL(file);
  }
};

const captureLocation = () => {
  if (!navigator.geolocation) {
    geoError.value = 'Geolocation is not supported by your browser.';
    return;
  }
  geoLoading.value = true;
  geoError.value = '';
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      form.latitude = pos.coords.latitude;
      form.longitude = pos.coords.longitude;
      geoLoading.value = false;
    },
    (err) => {
      geoError.value = err.message;
      geoLoading.value = false;
    },
    { enableHighAccuracy: true, timeout: 15000 }
  );
};

const submitForm = () => {
  if (editingDistribution.value) {
    form.post(`/distributions/${editingDistribution.value.id}`, {
      forceFormData: true,
      headers: { 'X-HTTP-Method-Override': 'PUT' },
      onSuccess: () => closeSlideOver(),
    });
  } else {
    form.post('/distributions', {
      forceFormData: true,
      onSuccess: () => closeSlideOver(),
    });
  }
};

// Auto-open slide-over for edit route
if (props.editDistribution) {
  openEditSlideOver(props.editDistribution);
}
</script>
