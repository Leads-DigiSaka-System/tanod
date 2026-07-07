<template>
  <AppLayout>
    <Head :title="user.name" />

    <!-- Back link + header -->
    <div class="mb-6">
      <Link href="/users" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        Back to Users
      </Link>
      <div class="mt-2 sm:flex sm:items-center sm:justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ user.name }}</h1>
        <div class="mt-3 sm:mt-0 flex items-center space-x-3">
          <Link :href="`/users/${user.id}/edit`"
            class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 focus:outline-none dark:focus:ring-indigo-800">Edit</Link>
          <Link :href="`/users/${user.id}/toggle-active`" method="post" as="button"
            :class="user.is_active
              ? 'text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-red-600 dark:hover:bg-red-700 focus:outline-none dark:focus:ring-red-800'
              : 'text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-green-600 dark:hover:bg-green-700 focus:outline-none dark:focus:ring-green-800'">
            {{ user.is_active ? 'Deactivate' : 'Activate' }}
          </Link>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- Profile card -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 lg:col-span-1 text-center dark:bg-gray-800 dark:border-gray-700">
        <img v-if="user.profile_photo_path" :src="`/storage/${user.profile_photo_path}`" class="mx-auto h-32 w-32 rounded-full object-cover ring-4 ring-gray-100 dark:ring-gray-700" />
        <div v-else class="mx-auto h-32 w-32 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center ring-4 ring-gray-100 dark:ring-gray-700">
          <span class="text-4xl font-bold text-indigo-600 dark:text-indigo-300">{{ user.name?.charAt(0) }}</span>
        </div>
        <h2 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">{{ user.name }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 capitalize">{{ user.roles?.[0]?.name || '—' }}</p>
        <StatusBadge :status="user.is_active ? 'online' : 'offline'" :label="user.is_active ? 'Active' : 'Inactive'" class="mt-2" />
      </div>

      <!-- Details card -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 lg:col-span-2 dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">User Details</h3>
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ user.email }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ user.phone || '—' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Gender</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white capitalize">{{ user.gender || '—' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Joined</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(user.created_at) }}</dd>
          </div>
          <div v-if="user.roles?.[0]?.name === 'tsr'">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tractor Access</dt>
            <dd class="mt-1">
              <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                :class="user.tsr_assign_all_tractors
                  ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                  : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'">
                {{ user.tsr_assign_all_tractors ? 'Full fleet access' : 'Group responsibilities only' }}
              </span>
            </dd>
          </div>
        </dl>
      </div>
    </div>

    <!-- Related Data -->
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2" v-if="user.tractors?.length || user.bookings?.length">
      <div v-if="user.tractors?.length" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Assigned Tractors</h3>
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
          <li v-for="t in user.tractors" :key="t.id" class="py-3 flex justify-between items-center">
            <Link :href="`/tractors/${t.id}`" class="font-medium text-sm text-indigo-600 dark:text-indigo-500 hover:underline">{{ t.brand }} {{ t.model }} — {{ t.no_plate }}</Link>
            <StatusBadge :status="t.status" />
          </li>
        </ul>
      </div>
      <div v-if="user.bookings?.length" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Bookings</h3>
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
          <li v-for="b in user.bookings" :key="b.id" class="py-3 flex justify-between items-center">
            <Link :href="`/bookings/${b.id}`" class="font-medium text-sm text-indigo-600 dark:text-indigo-500 hover:underline">Booking #{{ b.id }}</Link>
            <StatusBadge :status="b.status" />
          </li>
        </ul>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/utils/dateFormat';

defineProps({ user: Object });
</script>
