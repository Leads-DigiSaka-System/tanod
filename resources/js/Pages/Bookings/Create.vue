<template>
  <AppLayout>
    <Head title="New Booking" />
    <div class="mb-6">
      <Link href="/bookings" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-500">&larr; Back to Bookings</Link>
      <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">New Booking</h1>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 max-w-2xl dark:bg-gray-800 dark:border-gray-700">
      <form @submit.prevent="submit" class="space-y-6">
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Tractor *</label>
          <select v-model="form.tractor_id" required
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500">
            <option :value="null" disabled>Select a tractor</option>
            <option v-for="t in tractors" :key="t.id" :value="t.id">{{ t.no_plate }} — {{ t.brand }} {{ t.model }}</option>
          </select>
          <p v-if="form.errors.tractor_id" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.tractor_id }}</p>
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Booking Date *</label>
          <input v-model="form.booking_date" type="date" required
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Purpose *</label>
          <textarea v-model="form.purpose" rows="3" required
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"></textarea>
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Farm Area (hectares)</label>
          <input v-model="form.farm_area_hectares" type="number" step="0.01"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Notes</label>
          <textarea v-model="form.notes" rows="2"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"></textarea>
        </div>
        <div class="flex justify-end gap-3">
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
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ tractors: Array });

const form = useForm({
  tractor_id: null,
  booking_date: '',
  purpose: '',
  farm_area_hectares: null,
  notes: '',
});

const submit = () => form.post('/bookings');
</script>
