<template>
  <AppLayout>
    <Head title="Create Group" />

    <div class="mb-6">
      <Link href="/groups" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        Back to Groups
      </Link>
      <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Create Group</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Set up a new tractor group and assign resources.</p>
    </div>

    <form @submit.prevent="submit" class="space-y-6 max-w-4xl">
      <!-- Group Details Card -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Group Details</h2>
        </div>
        <div class="p-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
          <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Group Name <span class="text-red-500">*</span></label>
            <input v-model="form.name" type="text" placeholder="e.g. Northern Region Fleet"
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
          </div>
          <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Area</label>
            <input v-model="form.area" type="text" placeholder="e.g. Tarlac, Pampanga"
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
            <p v-if="form.errors.area" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.area }}</p>
          </div>
          <div class="sm:col-span-2">
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
            <textarea v-model="form.description" rows="3" placeholder="Brief description of this group..."
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400"></textarea>
          </div>
          <div class="sm:col-span-2">
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="form.is_active" type="checkbox" class="sr-only peer" />
              <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:after:border-gray-500 peer-checked:bg-indigo-600"></div>
              <span class="ms-3 text-sm font-medium text-gray-700 dark:text-gray-300">Active</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Assign Tractors Card -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Assign Tractors</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ form.tractor_ids.length }} selected</p>
          </div>
          <div class="flex items-center gap-3">
            <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
              <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span> Online
              <span class="inline-block w-2 h-2 rounded-full bg-gray-300 dark:bg-gray-500 ml-2"></span> Offline
            </span>
          </div>
        </div>
        <div class="p-4">
          <input v-model="tractorSearch" type="text" placeholder="Search by plate, brand, model, or IMEI..."
            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 mb-3" />
          <div class="max-h-64 overflow-y-auto space-y-2">
            <label v-for="t in filteredTractors" :key="t.id"
              class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all"
              :class="form.tractor_ids.includes(t.id)
                ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 dark:border-indigo-400'
                : 'border-gray-200 hover:border-gray-300 dark:border-gray-600 dark:hover:border-gray-500'">
              <input type="checkbox" :value="t.id" v-model="form.tractor_ids" class="sr-only" />
              <span class="relative shrink-0 flex items-center justify-center w-10 h-10 rounded-lg text-lg"
                :class="t.is_online ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-gray-100 dark:bg-gray-700'">
                <svg class="w-5 h-5" :class="t.is_online ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0H21M3.375 14.25h3.75L9 7.5h6l1.875 6.75h3.75" />
                </svg>
                <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-white dark:border-gray-800"
                  :class="t.is_online ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-500'"></span>
              </span>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <span class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ t.brand }} {{ t.model }}</span>
                  <span class="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="t.is_online
                      ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400'
                      : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'">
                    {{ t.is_online ? 'Online' : 'Offline' }}
                  </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ t.no_plate }} &middot; {{ t.imei || 'No IMEI' }}</p>
              </div>
              <div class="shrink-0">
                <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                  :class="form.tractor_ids.includes(t.id)
                    ? 'bg-indigo-600 border-indigo-600'
                    : 'border-gray-300 dark:border-gray-500'">
                  <svg v-if="form.tractor_ids.includes(t.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
              </div>
            </label>
            <p v-if="!filteredTractors.length" class="text-center py-6 text-sm text-gray-400 dark:text-gray-500">
              {{ tractorSearch ? 'No tractors match your search.' : 'No tractors available.' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Assign TPS Card -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Assign TPS Users</h2>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ form.tps_user_ids.length }} selected</p>
        </div>
        <div class="p-4">
          <input v-model="tpsSearch" type="text" placeholder="Search by name or email..."
            class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 mb-3" />
          <div class="max-h-64 overflow-y-auto space-y-2">
            <label v-for="u in filteredTpsUsers" :key="u.id"
              class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all"
              :class="form.tps_user_ids.includes(u.id)
                ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 dark:border-indigo-400'
                : 'border-gray-200 hover:border-gray-300 dark:border-gray-600 dark:hover:border-gray-500'">
              <input type="checkbox" :value="u.id" v-model="form.tps_user_ids" class="sr-only" />
              <span class="shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-sm font-semibold text-indigo-700 dark:text-indigo-300">
                {{ u.name.charAt(0).toUpperCase() }}
              </span>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ u.name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ u.email }}</p>
              </div>
              <div class="shrink-0">
                <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                  :class="form.tps_user_ids.includes(u.id)
                    ? 'bg-indigo-600 border-indigo-600'
                    : 'border-gray-300 dark:border-gray-500'">
                  <svg v-if="form.tps_user_ids.includes(u.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
              </div>
            </label>
            <p v-if="!filteredTpsUsers.length" class="text-center py-6 text-sm text-gray-400 dark:text-gray-500">
              {{ tpsSearch ? 'No TPS users match your search.' : 'No TPS users available.' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-end gap-3 pt-2">
        <Link href="/groups"
          class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">Cancel</Link>
        <button type="submit" :disabled="form.processing"
          class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 disabled:opacity-50 disabled:cursor-not-allowed">
          <span v-if="form.processing" class="inline-flex items-center gap-1.5">
            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
            Creating...
          </span>
          <span v-else>Create Group</span>
        </button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ tractors: Array, tpsUsers: Array });

const tractorSearch = ref('');
const tpsSearch = ref('');

const filteredTractors = computed(() => {
  let list = props.tractors;
  if (tractorSearch.value) {
    const q = tractorSearch.value.toLowerCase();
    list = list.filter(t =>
      t.no_plate?.toLowerCase().includes(q) ||
      t.brand?.toLowerCase().includes(q) ||
      t.model?.toLowerCase().includes(q) ||
      t.imei?.toLowerCase().includes(q)
    );
  }
  return [...list].sort((a, b) => {
    const aSelected = form.tractor_ids.includes(a.id) ? 0 : 1;
    const bSelected = form.tractor_ids.includes(b.id) ? 0 : 1;
    return aSelected - bSelected;
  });
});

const filteredTpsUsers = computed(() => {
  if (!tpsSearch.value) return props.tpsUsers;
  const q = tpsSearch.value.toLowerCase();
  return props.tpsUsers.filter(u =>
    u.name?.toLowerCase().includes(q) ||
    u.email?.toLowerCase().includes(q)
  );
});

const form = useForm({
  name: '', area: '', description: '', is_active: true,
  tractor_ids: [], tps_user_ids: [],
});

const submit = () => { form.post('/groups'); };
</script>
