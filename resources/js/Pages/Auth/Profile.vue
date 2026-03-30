<template>
  <AppLayout>
    <Head title="Profile" />

    <div class="max-w-2xl mx-auto space-y-6">
      <!-- Page Header -->
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Profile Settings</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your account information and security settings</p>
      </div>

      <!-- Update Profile Card -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Profile Information</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Update your personal details and contact information.</p>
        <form @submit.prevent="updateProfile" class="space-y-5">
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Name</label>
            <input v-model="profileForm.name" type="text" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" />
            <p v-if="profileForm.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ profileForm.errors.name }}</p>
          </div>
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email</label>
            <input v-model="profileForm.email" type="email" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" />
            <p v-if="profileForm.errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ profileForm.errors.email }}</p>
          </div>
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Phone</label>
            <input v-model="profileForm.phone" type="text" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" />
            <p v-if="profileForm.errors.phone" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ profileForm.errors.phone }}</p>
          </div>
          <button type="submit" :disabled="profileForm.processing"
            class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800 disabled:opacity-50">
            Save Changes
          </button>
        </form>
      </div>

      <!-- Change Password Card -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Change Password</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Ensure your account uses a strong, unique password for security.</p>
        <form @submit.prevent="changePassword" class="space-y-5">
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Current Password</label>
            <input v-model="passwordForm.current_password" type="password" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" />
            <p v-if="passwordForm.errors.current_password" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ passwordForm.errors.current_password }}</p>
          </div>
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">New Password</label>
            <input v-model="passwordForm.password" type="password" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" />
          </div>
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Confirm New Password</label>
            <input v-model="passwordForm.password_confirmation" type="password" required class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" />
          </div>
          <button type="submit" :disabled="passwordForm.processing"
            class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800 disabled:opacity-50">
            Update Password
          </button>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ user: Object });

const profileForm = useForm({
  name: props.user.name,
  email: props.user.email,
  phone: props.user.phone || '',
});

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const updateProfile = () => profileForm.put('/profile');
const changePassword = () => {
  passwordForm.put('/profile/password', {
    onSuccess: () => passwordForm.reset(),
  });
};
</script>
