<template>
  <AppLayout>
    <Head title="New Maintenance" />
    <div class="mb-6">
      <Link href="/maintenance" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-500">&larr; Back to Maintenance</Link>
      <h1 class="text-2xl font-bold text-gray-900 mt-2 dark:text-white">New Maintenance Record</h1>
    </div>

    <form @submit.prevent="submit" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 max-w-2xl dark:bg-gray-800 dark:border-gray-700">
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tractor *</label>
          <select v-model="form.tractor_id"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500">
            <option value="">Select Tractor</option>
            <option v-for="t in tractors" :key="t.id" :value="t.id">{{ t.brand }} {{ t.model }} — {{ t.no_plate }}</option>
          </select>
          <p v-if="form.errors.tractor_id" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.tractor_id }}</p>
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Issue Type *</label>
          <select v-model="form.issue_type_id"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500">
            <option value="">Select Type</option>
            <option v-for="it in issueTypes" :key="it.id" :value="it.id">{{ it.name }}</option>
          </select>
          <p v-if="form.errors.issue_type_id" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.issue_type_id }}</p>
        </div>
        <div class="sm:col-span-2">
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Description *</label>
          <textarea v-model="form.description" rows="3"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"></textarea>
          <p v-if="form.errors.description" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.description }}</p>
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Status</label>
          <select v-model="form.status"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500">
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
          </select>
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Priority</label>
          <select v-model="form.priority"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500">
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Scheduled Date</label>
          <input v-model="form.scheduled_date" type="date"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Cost (₱)</label>
          <input v-model="form.cost" type="number" step="0.01" min="0"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
        </div>
        <div class="sm:col-span-2">
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Notes</label>
          <textarea v-model="form.notes" rows="2"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"></textarea>
        </div>
        <div class="sm:col-span-2">
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Images</label>
          <input type="file" accept="image/*" multiple @change="form.images = Array.from($event.target.files)"
            class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900 dark:file:text-indigo-300" />
          <p v-if="form.errors.images" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.images }}</p>
        </div>
      </div>

      <div class="mt-6 flex justify-end space-x-3">
        <Link href="/maintenance"
          class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700">Cancel</Link>
        <button type="submit" :disabled="form.processing"
          class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800 disabled:opacity-50">Create</button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ tractors: Array, issueTypes: Array });

const form = useForm({
  tractor_id: '', issue_type_id: '', description: '',
  status: 'pending', priority: 'medium', scheduled_date: '',
  cost: '', notes: '', images: [],
});

const submit = () => { form.post('/maintenance', { forceFormData: true }); };
</script>
