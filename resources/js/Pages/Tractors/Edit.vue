<template>
  <AppLayout>
    <Head title="Edit Tractor" />

    <!-- Page Header -->
    <div class="mb-8">
      <Link :href="`/tractors/${tractor.id}`" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Tractor
      </Link>
      <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Edit {{ tractor.no_plate }}</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Update the tractor details below. Fields marked with * are required.</p>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
      <form @submit.prevent="submit" class="space-y-8">
        <!-- Identification Section -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Identification</h3>
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">IMEI *</label>
              <input v-model="form.imei" type="text" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" placeholder="Enter IMEI" />
              <p v-if="form.errors.imei" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.imei }}</p>
            </div>
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Plate Number *</label>
              <input v-model="form.no_plate" type="text" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" placeholder="e.g. ABC-1234" />
              <p v-if="form.errors.no_plate" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.no_plate }}</p>
            </div>
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">ID No *</label>
              <input v-model="form.id_no" type="text" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" placeholder="Enter ID number" />
              <p v-if="form.errors.id_no" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.id_no }}</p>
            </div>
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Engine No *</label>
              <input v-model="form.engine_no" type="text" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" placeholder="Enter engine number" />
              <p v-if="form.errors.engine_no" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.engine_no }}</p>
            </div>
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Chassis No</label>
              <input v-model="form.chassis_no" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" placeholder="Enter chassis number" />
              <p v-if="form.errors.chassis_no" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.chassis_no }}</p>
            </div>
          </div>
        </div>

        <hr class="border-gray-200 dark:border-gray-700" />

        <!-- Specifications Section -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Specifications</h3>
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Brand *</label>
              <input v-model="form.brand" type="text" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" placeholder="e.g. John Deere" />
              <p v-if="form.errors.brand" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.brand }}</p>
            </div>
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Model *</label>
              <input v-model="form.model" type="text" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" placeholder="e.g. 5075E" />
              <p v-if="form.errors.model" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.model }}</p>
            </div>
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Fuel Consumption (L/hr)</label>
              <input v-model="form.fuel_consumption" type="number" step="0.01" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" placeholder="e.g. 5.5" />
              <p v-if="form.errors.fuel_consumption" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.fuel_consumption }}</p>
            </div>
          </div>
        </div>

        <hr class="border-gray-200 dark:border-gray-700" />

        <!-- Installation Section -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Installation</h3>
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Installation Date</label>
              <input v-model="form.installation_time" type="date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
              <p v-if="form.errors.installation_time" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.installation_time }}</p>
            </div>
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Installation Address</label>
              <input v-model="form.installation_address" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" placeholder="Enter installation address" />
              <p v-if="form.errors.installation_address" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.installation_address }}</p>
            </div>
          </div>
        </div>

        <hr class="border-gray-200 dark:border-gray-700" />

        <!-- Assignment Section -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Assignment</h3>
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Device</label>
              <select v-model="form.device_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500">
                <option :value="null">None</option>
                <option v-for="d in devices" :key="d.id" :value="d.id">{{ d.imei }} — {{ d.device_name }}</option>
              </select>
              <p v-if="form.errors.device_id" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.device_id }}</p>
            </div>
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Assign To (TPS)</label>
              <select v-model="form.assigned_to" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500">
                <option :value="null">None</option>
                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
              </select>
              <p v-if="form.errors.assigned_to" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.assigned_to }}</p>
            </div>
          </div>
        </div>

        <hr class="border-gray-200 dark:border-gray-700" />

        <!-- Maintenance Section -->
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Maintenance Thresholds</h3>
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Maintenance KM Threshold</label>
              <input v-model="form.maintenance_km" type="number" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" placeholder="e.g. 5000" />
              <p v-if="form.errors.maintenance_km" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.maintenance_km }}</p>
            </div>
            <div>
              <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Maintenance Hours Threshold</label>
              <input v-model="form.maintenance_hours" type="number" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" placeholder="e.g. 500" />
              <p v-if="form.errors.maintenance_hours" class="mt-2 text-sm text-red-600 dark:text-red-500">{{ form.errors.maintenance_hours }}</p>
            </div>
          </div>
        </div>

        <hr class="border-gray-200 dark:border-gray-700" />

        <!-- Existing Images -->
        <div v-if="tractor.images?.length">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Current Images</h3>
          <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            <div v-for="img in tractor.images" :key="img.id" class="relative group rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
              <img :src="`/storage/${img.path}`" class="h-36 w-full object-cover" />
              <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                <button type="button" @click="deleteImage(img.id)" class="text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-xs px-3 py-1.5 dark:bg-red-500 dark:hover:bg-red-600 dark:focus:ring-red-800">
                  <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  Remove
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Upload New Images -->
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Add More Images</label>
          <div class="flex items-center justify-center w-full">
            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600">
              <div class="flex flex-col items-center justify-center pt-5 pb-6">
                <svg class="w-8 h-8 mb-2 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                <p class="mb-1 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Click to upload</span> images</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG or WEBP (max 5 files)</p>
              </div>
              <input type="file" multiple accept="image/*" @change="handleImages" class="hidden" />
            </label>
          </div>
          <p v-if="form.images.length" class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ form.images.length }} file(s) selected</p>
        </div>

        <!-- Form Actions -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
          <Link :href="`/tractors/${tractor.id}`" class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700">
            Cancel
          </Link>
          <button type="submit" :disabled="form.processing" class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800 disabled:opacity-50 disabled:cursor-not-allowed">
            <svg v-if="form.processing" class="inline w-4 h-4 mr-2 animate-spin" viewBox="0 0 100 101" fill="none"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="#E5E7EB"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentColor"/></svg>
            {{ form.processing ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ tractor: Object, devices: Array, users: Array });

const form = useForm({
  _method: 'PUT',
  imei: props.tractor.imei,
  no_plate: props.tractor.no_plate,
  id_no: props.tractor.id_no,
  engine_no: props.tractor.engine_no,
  chassis_no: props.tractor.chassis_no || '',
  brand: props.tractor.brand,
  model: props.tractor.model,
  fuel_consumption: props.tractor.fuel_consumption,
  installation_time: props.tractor.installation_time,
  installation_address: props.tractor.installation_address,
  device_id: props.tractor.device_id,
  assigned_to: props.tractor.assigned_to,
  maintenance_km: props.tractor.maintenance_km,
  maintenance_hours: props.tractor.maintenance_hours,
  images: [],
});

const handleImages = (e) => {
  form.images = Array.from(e.target.files).slice(0, 5);
};

const submit = () => {
  form.post(`/tractors/${props.tractor.id}`, { forceFormData: true });
};

const deleteImage = (imageId) => {
  if (confirm('Remove this image?')) {
    router.delete(`/tractors/${props.tractor.id}/images/${imageId}`);
  }
};
</script>
