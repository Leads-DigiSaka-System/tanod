<template>
  <AppLayout>
    <Head :title="`Assign Provinces - ${tpsUser.name}`" />

    <!-- Page header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
          <Link href="/support-contact" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Support Contact</Link>
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
          <span class="text-gray-700 dark:text-gray-300">Assign Provinces</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Assign Provinces</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Select provinces to assign to <span class="font-semibold text-gray-700 dark:text-gray-300">{{ tpsUser.name }}</span>
        </p>
      </div>
    </div>
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 mb-6 dark:bg-gray-800 dark:border-gray-700">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <input v-model="search" type="text" placeholder="Search provinces..."
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" />
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-500 dark:text-gray-400">
            <strong>{{ selectedProvinces.length }}</strong> of <strong>{{ filteredProvinces.length }}</strong> selected
          </span>
          <button v-if="filteredProvinces.length" @click="selectAll" class="text-xs text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 font-medium transition-colors">
            Select All
          </button>
          <button v-if="selectedProvinces.length" @click="deselectAll" class="text-xs text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-medium transition-colors">
            Deselect All
          </button>
        </div>
      </div>
    </div>

    <!-- Provinces grid -->
    <form @submit.prevent="submit">
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
        <div v-if="filteredProvinces.length" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-px bg-gray-200 dark:bg-gray-700">
          <label v-for="province in filteredProvinces" :key="province.province_code"
            class="flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors"
            :class="{ 'bg-emerald-50 dark:bg-emerald-900/20': isSelected(province.province_code) }">
            <input type="checkbox" :value="province.province_code" v-model="selectedProvinces"
              class="w-4 h-4 text-emerald-600 bg-gray-100 border-gray-300 rounded focus:ring-emerald-500 dark:focus:ring-emerald-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600" />
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ province.province_description }}</span>
          </label>
        </div>
        <div v-else class="py-16 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <p class="mt-4 text-sm font-medium text-gray-900 dark:text-white">No provinces found</p>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try a different search term.</p>
        </div>
      </div>

      <!-- Submit -->
      <div class="mt-6 flex items-center justify-end gap-3">
        <Link href="/support-contact"
          class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 transition-colors">
          Cancel
        </Link>
        <button type="submit" :disabled="form.processing"
          class="inline-flex items-center gap-1.5 rounded-lg px-5 py-2.5 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          style="background-color: #007f3d;"
          @mouseenter="!form.processing && ($event.target.style.backgroundColor='#006631')"
          @mouseleave="!form.processing && ($event.target.style.backgroundColor='#007f3d')">
          <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          {{ form.processing ? 'Saving...' : 'Save Assignments' }}
        </button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  tpsUser: Object,
  provinces: Array,
  assignedProvinces: Array,
});

const search = ref('');
const selectedProvinces = ref([...props.assignedProvinces]);

const filteredProvinces = computed(() => {
  if (!search.value) return props.provinces;
  const q = search.value.toLowerCase();
  return props.provinces.filter(p =>
    p.province_description.toLowerCase().includes(q)
  );
});

const isSelected = (code) => selectedProvinces.value.includes(code);

const selectAll = () => {
  selectedProvinces.value = filteredProvinces.value.map(p => p.province_code);
};

const deselectAll = () => {
  const filteredCodes = filteredProvinces.value.map(p => p.province_code);
  selectedProvinces.value = selectedProvinces.value.filter(
    c => !filteredCodes.includes(c)
  );
};

const form = useForm({
  provinces: selectedProvinces,
});

const submit = () => {
  form.provinces = selectedProvinces.value;
  form.post(`/support-contact/${props.tpsUser.id}/assign`, {
    preserveScroll: true,
    onSuccess: () => {
      window.location.href = '/support-contact';
    },
  });
};
</script>
