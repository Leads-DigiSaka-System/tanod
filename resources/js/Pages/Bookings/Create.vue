<template>
  <AppLayout>
    <Head title="New Booking" />
    <div class="mb-6">
      <Link href="/bookings" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-500">&larr; Back to Bookings</Link>
      <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">New Booking</h1>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 max-w-2xl dark:bg-gray-800 dark:border-gray-700">
      <form @submit.prevent="submit" class="space-y-6">

        <!-- Step 1: Member / Non-member -->
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Booking Type *</label>
          <div class="flex gap-3">
            <button type="button" @click="form.is_member = true"
              :class="['flex-1 py-3 px-4 rounded-lg border-2 text-sm font-medium transition-all',
                form.is_member === true
                  ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:border-indigo-400 dark:bg-indigo-900/20 dark:text-indigo-300'
                  : 'border-gray-200 text-gray-500 hover:border-gray-300 dark:border-gray-600 dark:text-gray-400']">
              <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
              Member
            </button>
            <button type="button" @click="form.is_member = false"
              :class="['flex-1 py-3 px-4 rounded-lg border-2 text-sm font-medium transition-all',
                form.is_member === false
                  ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:border-indigo-400 dark:bg-indigo-900/20 dark:text-indigo-300'
                  : 'border-gray-200 text-gray-500 hover:border-gray-300 dark:border-gray-600 dark:text-gray-400']">
              <svg class="w-5 h-5 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
              Non-member
            </button>
          </div>
          <p v-if="form.errors.is_member" class="mt-1 text-sm text-red-600">{{ form.errors.is_member }}</p>
        </div>

        <!-- Step 2: FCA -->
        <div v-if="form.is_member !== null">
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">FCA *</label>
          <div class="relative">
            <input v-model="fcaSearch" type="text" placeholder="Search FCA by name..."
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
              @focus="showFcaDropdown = true" @blur="hideFcaDropdown" />
            <div v-if="showFcaDropdown && filteredFcas.length" class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto">
              <button type="button" v-for="fca in filteredFcas" :key="fca.id"
                @mousedown.prevent="selectFca(fca)"
                class="w-full text-left px-4 py-2.5 text-sm hover:bg-indigo-50 dark:hover:bg-indigo-900/20"
                :class="{'bg-indigo-50 dark:bg-indigo-900/20': form.fca_id === fca.id}">
                <span class="font-medium text-gray-900 dark:text-white">{{ fca.name }}</span>
                <span v-if="fca.organization_name" class="ml-2 text-xs text-gray-500 dark:text-gray-400">{{ fca.organization_name }}</span>
              </button>
            </div>
          </div>
          <p v-if="selectedFca" class="mt-1 text-xs text-indigo-600 dark:text-indigo-400">
            Selected: {{ selectedFca.name }}{{ selectedFca.organization_name ? ' — ' + selectedFca.organization_name : '' }}
          </p>
          <p v-if="form.errors.fca_id" class="mt-1 text-sm text-red-600">{{ form.errors.fca_id }}</p>
        </div>

        <!-- Step 3: Tractor (filtered by FCA) -->
        <div v-if="form.fca_id">
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tractor *</label>
          <select v-model="form.tractor_id" required :disabled="tractorsLoading"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:opacity-50">
            <option :value="null" disabled>{{ tractorsLoading ? 'Loading...' : 'Select a tractor' }}</option>
            <option v-for="t in fcaTractors" :key="t.id" :value="t.id">{{ t.no_plate }} — {{ t.brand }} {{ t.model }} ({{ t.imei }})</option>
          </select>
          <p v-if="!tractorsLoading && !fcaTractors.length && form.fca_id" class="mt-1 text-xs text-amber-600 dark:text-amber-400">No tractors are currently distributed to this FCA.</p>
          <p v-if="form.errors.tractor_id" class="mt-1 text-sm text-red-600">{{ form.errors.tractor_id }}</p>
        </div>

        <!-- Step 4a: Farmer (Member) -->
        <div v-if="form.is_member && form.tractor_id">
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Farmer *</label>
          <select v-model="form.farmer_id" required :disabled="farmersLoading"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:opacity-50">
            <option :value="null" disabled>{{ farmersLoading ? 'Loading...' : 'Select a farmer' }}</option>
            <option v-for="f in fcaFarmers" :key="f.id" :value="f.id">{{ f.name }}</option>
          </select>
          <p v-if="!farmersLoading && !fcaFarmers.length && form.fca_id" class="mt-1 text-xs text-amber-600 dark:text-amber-400">No farmers registered under this FCA.</p>
          <p v-if="form.errors.farmer_id" class="mt-1 text-sm text-red-600">{{ form.errors.farmer_id }}</p>
        </div>

        <!-- Step 4b: Contact Info (Non-member) -->
        <div v-if="form.is_member === false && form.tractor_id" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Contact Name *</label>
            <input v-model="form.contact_name" type="text" required placeholder="Full name"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
            <p v-if="form.errors.contact_name" class="mt-1 text-sm text-red-600">{{ form.errors.contact_name }}</p>
          </div>
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Contact Phone *</label>
            <input v-model="form.contact_phone" type="text" required placeholder="09xxxxxxxxx"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
            <p v-if="form.errors.contact_phone" class="mt-1 text-sm text-red-600">{{ form.errors.contact_phone }}</p>
          </div>
        </div>

        <!-- Step 5: Date Range -->
        <div v-if="canShowDates" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Start Date *</label>
            <input v-model="form.start_date" type="date" required :min="today"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
            <p v-if="form.errors.start_date" class="mt-1 text-sm text-red-600">{{ form.errors.start_date }}</p>
          </div>
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">End Date *</label>
            <input v-model="form.end_date" type="date" required :min="form.start_date || today"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
            <p v-if="form.errors.end_date" class="mt-1 text-sm text-red-600">{{ form.errors.end_date }}</p>
          </div>
        </div>

        <!-- Step 6: Farm Area + Step 7: Cost -->
        <div v-if="canShowDates" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Farm Area (hectares) <span class="text-gray-400 font-normal">(optional)</span></label>
            <input v-model="form.farm_area_hectares" type="number" step="0.01" min="0" placeholder="e.g. 2.5"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
          </div>
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Cost (₱) <span class="text-gray-400 font-normal">(optional)</span></label>
            <input v-model="form.cost" type="number" step="0.01" min="0" placeholder="0.00"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
          </div>
        </div>

        <!-- Step 8: Purpose + Notes -->
        <div v-if="canShowDates">
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Purpose *</label>
          <textarea v-model="form.purpose" rows="3" required placeholder="Describe the purpose of this booking..."
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
          <p v-if="form.errors.purpose" class="mt-1 text-sm text-red-600">{{ form.errors.purpose }}</p>
        </div>
        <div v-if="canShowDates">
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
          <textarea v-model="form.notes" rows="2" placeholder="Any additional notes..."
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
        </div>

        <!-- Submit -->
        <div v-if="canShowDates" class="flex justify-end gap-3 pt-2">
          <Link href="/bookings"
            class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700">Cancel</Link>
          <button type="submit" :disabled="form.processing"
            class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800 disabled:opacity-50">
            {{ form.processing ? 'Submitting...' : 'Submit Booking' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

const props = defineProps({
  fcas: Array,
});

const today = new Date().toISOString().split('T')[0];

const form = useForm({
  is_member: null,
  fca_id: null,
  tractor_id: null,
  farmer_id: null,
  contact_name: '',
  contact_phone: '',
  start_date: '',
  end_date: '',
  purpose: '',
  farm_area_hectares: null,
  cost: null,
  notes: '',
});

// FCA search
const fcaSearch = ref('');
const showFcaDropdown = ref(false);
const selectedFca = ref(null);

const filteredFcas = computed(() => {
  if (!fcaSearch.value) return props.fcas || [];
  const q = fcaSearch.value.toLowerCase();
  return (props.fcas || []).filter(f =>
    f.name?.toLowerCase().includes(q) ||
    f.organization_name?.toLowerCase().includes(q)
  );
});

const hideFcaDropdown = () => {
  setTimeout(() => { showFcaDropdown.value = false; }, 200);
};

const selectFca = (fca) => {
  form.fca_id = fca.id;
  selectedFca.value = fca;
  fcaSearch.value = fca.name;
  showFcaDropdown.value = false;
  // Reset downstream fields
  form.tractor_id = null;
  form.farmer_id = null;
};

// Tractors by FCA
const fcaTractors = ref([]);
const tractorsLoading = ref(false);

watch(() => form.fca_id, async (fcaId) => {
  if (!fcaId) { fcaTractors.value = []; return; }
  tractorsLoading.value = true;
  try {
    const { data } = await axios.get(`/api/v1/bookings/fcas/${fcaId}/tractors`);
    fcaTractors.value = data;
  } catch { fcaTractors.value = []; }
  tractorsLoading.value = false;
});

// Farmers by FCA (for member)
const fcaFarmers = ref([]);
const farmersLoading = ref(false);

watch(() => form.tractor_id, async (tractorId) => {
  if (!tractorId || !form.is_member) { fcaFarmers.value = []; return; }
  farmersLoading.value = true;
  try {
    const { data } = await axios.get(`/api/v1/bookings/fcas/${form.fca_id}/farmers`);
    fcaFarmers.value = data;
  } catch { fcaFarmers.value = []; }
  farmersLoading.value = false;
});

const canShowDates = computed(() => {
  if (form.is_member === null) return false;
  if (!form.fca_id || !form.tractor_id) return false;
  if (form.is_member && !form.farmer_id) return false;
  if (form.is_member === false && (!form.contact_name || !form.contact_phone)) return false;
  return true;
});

const submit = () => form.post('/bookings');
</script>
