<template>
  <AppLayout>
    <Head title="New Distribution" />

    <!-- Page Header -->
    <div class="mb-6">
      <Link href="/distributions" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Distributions
      </Link>
      <div class="mt-2">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">New Tractor Distribution</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Fill in the details below to create a new distribution record.</p>
      </div>
    </div>

    <form @submit.prevent="submit" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700 max-w-2xl">
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tractor *</label>
          <select v-model="form.tractor_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400">
            <option value="">Select Tractor</option>
            <option v-for="t in tractors" :key="t.id" :value="t.id">{{ t.brand }} {{ t.model }} — {{ t.no_plate }}</option>
          </select>
          <p v-if="form.errors.tractor_id" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.tractor_id }}</p>
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Distribute To *</label>
          <select v-model="form.distributed_to" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400">
            <option value="">Select User</option>
            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
          </select>
          <p v-if="form.errors.distributed_to" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.distributed_to }}</p>
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Area</label>
          <input v-model="form.area" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Distribution Date</label>
          <input v-model="form.distributed_at" type="date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
        </div>
        <div class="sm:col-span-2">
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Notes</label>
          <textarea v-model="form.notes" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400"></textarea>
        </div>
      </div>

      <div class="mt-6 flex justify-end space-x-3">
        <Link href="/distributions" class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-700">Cancel</Link>
        <button type="submit" :disabled="form.processing" class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 disabled:opacity-50 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800">Create</button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ tractors: Array, users: Array });

const form = useForm({
  tractor_id: '', distributed_to: '', area: '', distributed_at: '', notes: '',
});

const submit = () => { form.post('/distributions'); };
</script>
