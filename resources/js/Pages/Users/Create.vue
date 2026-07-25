<template>
  <AppLayout>
    <Head title="Create User" />

    <!-- Back link + header -->
    <div class="mb-6">
      <Link href="/users" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        Back to Users
      </Link>
      <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Create User</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Fill in the details below to add a new user.</p>
    </div>

    <form @submit.prevent="submit" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 max-w-2xl dark:bg-gray-800 dark:border-gray-700">
      <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div class="sm:col-span-2">
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Full Name *</label>
          <input v-model="form.name" type="text"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
          <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email *</label>
          <input v-model="form.email" type="email"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
          <p v-if="form.errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.email }}</p>
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Password *</label>
          <input v-model="form.password" type="password"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
          <p v-if="form.errors.password" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.password }}</p>
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Password Confirmation *</label>
          <input v-model="form.password_confirmation" type="password"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Phone</label>
          <input v-model="form.phone" type="text"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
          <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.phone }}</p>
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Gender</label>
          <select v-model="form.gender"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500">
            <option value="">Select</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
          </select>
          <p v-if="form.errors.gender" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.gender }}</p>
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Role *</label>
          <select v-model="form.role"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500">
            <option value="">Select Role</option>
            <option v-for="r in roles" :key="r.id" :value="r.name">{{ formatRoleName(r) }}</option>
          </select>
          <p v-if="form.errors.role" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.role }}</p>
        </div>
        <div v-if="form.role === 'tsr'" class="md:col-span-2 rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
          <label class="flex items-start gap-3">
            <input v-model="form.tsr_assign_all_tractors" type="checkbox"
              class="mt-1 h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700" />
            <div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">Assign this TSR to all tractors</p>
              <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">When enabled, this TSR user can see the full tractor fleet in the mobile app. Leave it off to manage their tractor visibility through Group assignments.</p>
            </div>
          </label>
          <p v-if="form.errors.tsr_assign_all_tractors" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.tsr_assign_all_tractors }}</p>
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Profile Photo</label>
          <input type="file" accept="image/*" @change="form.profile_photo = $event.target.files[0]"
            class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900 dark:file:text-indigo-300" />
          <p v-if="form.errors.profile_photo" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.profile_photo }}</p>
        </div>
      </div>

      <div class="mt-6 flex justify-end space-x-3">
        <Link href="/users"
          class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700">Cancel</Link>
        <button type="submit" :disabled="form.processing"
          class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 focus:outline-none dark:focus:ring-indigo-800 disabled:opacity-50">Create</button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatRoleName } from '@/utils/roleFormat';

defineProps({ roles: Array });

const form = useForm({
  name: '', email: '', password: '', password_confirmation: '',
  phone: '', gender: '', role: '', tsr_assign_all_tractors: false, profile_photo: null,
});

const submit = () => {
  form.post('/users', { forceFormData: true });
};
</script>
