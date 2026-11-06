<template>
  <AppLayout>
    <Head title="Miscellaneous" />

    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Miscellaneous</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Tractor Parts &amp; Pricing</p>
      </div>
    </div>

    <!-- Single card -->
    <div class="max-w-2xl bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/40 dark:border-gray-700/30 shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-50 dark:border-gray-700/30 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Tractor Parts</h2>
          <span class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">{{ parts.total }}</span>
        </div>
        <button @click="openAdd" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Add Part
        </button>
      </div>

      <!-- Search -->
      <div class="px-6 py-3 border-b border-gray-50 dark:border-gray-700/20">
        <form method="GET" class="relative">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <input
            name="search"
            :value="filters.search"
            placeholder="Search parts..."
            class="w-full pl-9 pr-4 py-2 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:text-white"
          />
        </form>
      </div>

      <!-- Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            <tr>
              <th class="px-6 py-3 text-left font-semibold">Part Name</th>
              <th class="px-6 py-3 text-right font-semibold w-36">Amount</th>
              <th class="px-6 py-3 text-right font-semibold w-20">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700/30">
            <tr v-for="part in parts.data" :key="part.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
              <td class="px-6 py-3">
                <p class="font-medium text-gray-900 dark:text-white">{{ part.name }}</p>
                <p v-if="part.description" class="text-xs text-gray-400 mt-0.5 truncate max-w-xs">{{ part.description }}</p>
              </td>
              <td class="px-6 py-3 text-right tabular-nums text-gray-700 dark:text-gray-200 font-medium">{{ part.amount != null ? '₱' + Number(part.amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—' }}</td>
              <td class="px-6 py-3 text-right">
                <div class="flex items-center justify-end gap-1">
                  <button @click="openEdit(part)" class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors" title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                  </button>
                  <button @click="deletePart(part)" class="p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" title="Delete">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="parts.data.length === 0">
              <td colspan="3" class="px-6 py-12 text-center text-gray-400 text-sm">No parts found. Click "Add Part" to create one.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="parts.last_page > 1" class="px-6 py-3 border-t border-gray-50 dark:border-gray-700/30">
        <div class="flex items-center justify-between">
          <span class="text-xs text-gray-500">Page {{ parts.current_page }} of {{ parts.last_page }}</span>
          <div class="flex gap-1">
            <Link v-for="link in parts.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
              class="px-2.5 py-1 text-xs rounded-md"
              :class="link.active ? 'bg-indigo-600 text-white' : (link.url ? 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' : 'text-gray-300 dark:text-gray-600 cursor-default')"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Slide-in modal backdrop -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex justify-end" @click.self="closeModal">
      <div class="absolute inset-0 bg-black/30"></div>
      <div class="relative w-full max-w-md bg-white dark:bg-gray-800 h-full shadow-2xl overflow-y-auto animate-slide-in">
        <div class="sticky top-0 bg-white dark:bg-gray-800 px-6 py-4 border-b border-gray-100 dark:border-gray-700/30 flex items-center justify-between z-10">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ editing ? 'Edit Part' : 'Add Part' }}</h2>
          <button @click="closeModal" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <form @submit.prevent="submit" class="px-6 py-5 space-y-5">
          <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Part Name</label>
            <input v-model="form.name" required maxlength="255"
              class="w-full px-3 py-2.5 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:text-white" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Amount (₱) <span class="text-gray-400 font-normal">(optional)</span></label>
            <input v-model="form.amount" type="number" step="0.01" min="0" placeholder="0.00"
              class="w-full px-3 py-2.5 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:text-white" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Description <span class="text-gray-400 font-normal">(optional)</span></label>
            <textarea v-model="form.description" maxlength="1000" rows="3"
              class="w-full px-3 py-2.5 text-sm bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:text-white resize-none"></textarea>
          </div>
          <div class="flex gap-3 pt-2">
            <button type="button" @click="closeModal"
              class="flex-1 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition-colors">
              Cancel
            </button>
            <button type="submit" :disabled="form.processing"
              class="flex-1 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors">
              {{ editing ? 'Update' : 'Add Part' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation -->
    <div v-if="deleting" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30">
      <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-sm w-full mx-4 shadow-xl border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Delete Part</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Are you sure you want to delete "{{ deleting.name }}"? This cannot be undone.</p>
        <div class="flex gap-3 justify-end">
          <button @click="deleting = null" class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300">Cancel</button>
          <button @click="confirmDelete" :disabled="form.processing"
            class="px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50">Delete</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm, Link, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';

const props = defineProps({
  parts: Object,
  filters: Object,
});

const showModal = ref(false);
const editing = ref(null);
const deleting = ref(null);

const form = useForm({
  name: '',
  amount: '',
  description: '',
});

function openAdd() {
  editing.value = null;
  form.reset();
  showModal.value = true;
}

function openEdit(part) {
  editing.value = part;
  form.name = part.name;
  form.amount = part.amount;
  form.description = part.description || '';
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  editing.value = null;
  form.reset();
}

function submit() {
  if (editing.value) {
    form.put(`/miscellaneous/parts/${editing.value.id}`, {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/miscellaneous/parts', {
      onSuccess: () => closeModal(),
    });
  }
}

function deletePart(part) {
  deleting.value = part;
}

function confirmDelete() {
  form.delete(`/miscellaneous/parts/${deleting.value.id}`, {
    onSuccess: () => {
      deleting.value = null;
      form.reset();
    },
  });
}
</script>

<style scoped>
@keyframes slide-in {
  from { transform: translateX(100%); }
  to { transform: translateX(0); }
}
.animate-slide-in {
  animation: slide-in 0.25s ease-out;
}
</style>
