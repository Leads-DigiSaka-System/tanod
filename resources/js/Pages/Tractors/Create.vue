<template>
  <AppLayout>
    <Head title="Add Tractor" />

    <!-- Breadcrumb back link -->
    <div class="mb-6">
      <Link
        href="/tractors"
        class="inline-flex items-center text-sm text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors"
      >
        &larr; Back to Tractors
      </Link>
      <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Add Tractor</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Fill in the details below to register a new tractor.</p>
    </div>

    <!-- Form card -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
      <form @submit.prevent="submit" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <!-- IMEI -->
          <div>
            <label for="imei" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">IMEI *</label>
            <input
              id="imei"
              v-model="form.imei"
              type="text"
              required
              placeholder="Enter IMEI"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"
            />
            <p v-if="form.errors.imei" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.imei }}</p>
          </div>

          <!-- Plate Number -->
          <div>
            <label for="no_plate" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Plate Number *</label>
            <input
              id="no_plate"
              v-model="form.no_plate"
              type="text"
              required
              placeholder="Enter plate number"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"
            />
            <p v-if="form.errors.no_plate" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.no_plate }}</p>
          </div>

          <!-- ID No -->
          <div>
            <label for="id_no" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ID No *</label>
            <input
              id="id_no"
              v-model="form.id_no"
              type="text"
              required
              placeholder="Enter ID number"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"
            />
            <p v-if="form.errors.id_no" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.id_no }}</p>
          </div>

          <!-- Engine No -->
          <div>
            <label for="engine_no" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Engine No *</label>
            <input
              id="engine_no"
              v-model="form.engine_no"
              type="text"
              required
              placeholder="Enter engine number"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"
            />
            <p v-if="form.errors.engine_no" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.engine_no }}</p>
          </div>

          <!-- Chassis No -->
          <div>
            <label for="chassis_no" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Chassis No</label>
            <input
              id="chassis_no"
              v-model="form.chassis_no"
              type="text"
              placeholder="Enter chassis number"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"
            />
            <p v-if="form.errors.chassis_no" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.chassis_no }}</p>
          </div>

          <!-- Brand -->
          <div>
            <label for="brand" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Brand *</label>
            <input
              id="brand"
              v-model="form.brand"
              type="text"
              required
              placeholder="Enter brand"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"
            />
            <p v-if="form.errors.brand" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.brand }}</p>
          </div>

          <!-- Model -->
          <div>
            <label for="model" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Model *</label>
            <input
              id="model"
              v-model="form.model"
              type="text"
              required
              placeholder="Enter model"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"
            />
            <p v-if="form.errors.model" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.model }}</p>
          </div>

          <!-- Fuel Consumption -->
          <div>
            <label for="fuel_consumption" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Fuel Consumption (L/hr) *</label>
            <input
              id="fuel_consumption"
              v-model="form.fuel_consumption"
              type="number"
              step="0.01"
              required
              placeholder="0.00"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"
            />
            <p v-if="form.errors.fuel_consumption" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.fuel_consumption }}</p>
          </div>

          <!-- Installation Date -->
          <div>
            <label for="installation_time" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Installation Date *</label>
            <input
              id="installation_time"
              v-model="form.installation_time"
              type="date"
              required
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"
            />
            <p v-if="form.errors.installation_time" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.installation_time }}</p>
          </div>

          <!-- Installation Address -->
          <div>
            <label for="installation_address" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Installation Address *</label>
            <input
              id="installation_address"
              v-model="form.installation_address"
              type="text"
              required
              placeholder="Enter installation address"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"
            />
            <p v-if="form.errors.installation_address" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.installation_address }}</p>
          </div>

          <!-- Device -->
          <div>
            <label for="device_id" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Device</label>
            <select
              id="device_id"
              v-model="form.device_id"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"
            >
              <option :value="null">None</option>
              <option v-for="d in devices" :key="d.id" :value="d.id">{{ d.imei }} — {{ d.device_name }}</option>
            </select>
            <p v-if="form.errors.device_id" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.device_id }}</p>
          </div>

          <!-- Assign To -->
          <div>
            <label for="assigned_to" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Assign To (TPS)</label>
            <select
              id="assigned_to"
              v-model="form.assigned_to"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"
            >
              <option :value="null">None</option>
              <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
            <p v-if="form.errors.assigned_to" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.assigned_to }}</p>
          </div>

          <!-- Maintenance KM -->
          <div>
            <label for="maintenance_km" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Maintenance KM Threshold</label>
            <input
              id="maintenance_km"
              v-model="form.maintenance_km"
              type="number"
              placeholder="e.g. 5000"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"
            />
            <p v-if="form.errors.maintenance_km" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.maintenance_km }}</p>
          </div>

          <!-- Maintenance Hours -->
          <div>
            <label for="maintenance_hours" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Maintenance Hours Threshold</label>
            <input
              id="maintenance_hours"
              v-model="form.maintenance_hours"
              type="number"
              placeholder="e.g. 250"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500"
            />
            <p v-if="form.errors.maintenance_hours" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.maintenance_hours }}</p>
          </div>
        </div>

        <!-- Images -->
        <div>
          <label for="images" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Images (max 5)</label>
          <input
            id="images"
            type="file"
            multiple
            accept="image/*"
            @change="handleImages"
            class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none p-2 dark:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900 dark:file:text-indigo-300"
          />
          <p v-if="form.errors.images" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.images }}</p>
        </div>

        <!-- Footer buttons -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
          <Link
            href="/tractors"
            class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700"
          >
            Cancel
          </Link>
          <button
            type="submit"
            :disabled="form.processing"
            class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-300 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-indigo-500 dark:hover:bg-indigo-600 dark:focus:ring-indigo-800"
          >
            {{ form.processing ? 'Creating...' : 'Create Tractor' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ devices: Array, users: Array });

const form = useForm({
  imei: '',
  no_plate: '',
  id_no: '',
  engine_no: '',
  chassis_no: '',
  brand: '',
  model: '',
  fuel_consumption: '',
  installation_time: '',
  installation_address: '',
  device_id: null,
  assigned_to: null,
  maintenance_km: null,
  maintenance_hours: null,
  images: [],
});

const handleImages = (e) => {
  form.images = Array.from(e.target.files).slice(0, 5);
};

const submit = () => {
  form.post('/tractors', {
    forceFormData: true,
  });
};
</script>
