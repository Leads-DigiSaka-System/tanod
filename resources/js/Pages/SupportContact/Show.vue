<template>
  <AppLayout>
    <Head :title="user.name" />

    <!-- Breadcrumb + header -->
    <div class="mb-6">
      <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <Link href="/support-contact" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">Support Contact</Link>
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
        <span class="text-gray-700 dark:text-gray-300">{{ user.name }}</span>
      </div>
      <div class="sm:flex sm:items-center sm:justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ user.name }}</h1>
        <div class="mt-3 sm:mt-0 flex items-center space-x-3">
          <Link :href="`/support-contact/${user.id}/assign`"
            class="text-white bg-emerald-700 hover:bg-emerald-800 focus:ring-4 focus:ring-emerald-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-emerald-600 dark:hover:bg-emerald-700 focus:outline-none dark:focus:ring-emerald-800">
            Assign Provinces
          </Link>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- Profile card -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 lg:col-span-1 text-center dark:bg-gray-800 dark:border-gray-700">
        <img v-if="user.profile_photo_path" :src="`/storage/${user.profile_photo_path}`" class="mx-auto h-32 w-32 rounded-full object-cover ring-4 ring-gray-100 dark:ring-gray-700" />
        <div v-else class="mx-auto h-32 w-32 rounded-full bg-emerald-100 dark:bg-emerald-900 flex items-center justify-center ring-4 ring-gray-100 dark:ring-gray-700">
          <span class="text-4xl font-bold text-emerald-600 dark:text-emerald-300">{{ user.name?.charAt(0) }}</span>
        </div>
        <h2 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">{{ user.name }}</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 capitalize">{{ user.roles?.[0]?.name || '—' }}</p>
        <StatusBadge :status="user.is_active ? 'online' : 'offline'" :label="user.is_active ? 'Active' : 'Inactive'" class="mt-2" />
      </div>

      <!-- Details card -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 lg:col-span-2 dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Contact Details</h3>
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
        </dl>

        <!-- Assigned Provinces -->
        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Assigned Provinces</h3>
          <div v-if="provinces.length" class="flex flex-wrap gap-2">
            <span v-for="province in provinces" :key="province.province_code"
              class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              {{ province.province_description }}
            </span>
          </div>
          <p v-else class="text-sm text-gray-500 dark:text-gray-400">No provinces assigned yet.</p>
        </div>
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

defineProps({
  user: Object,
  provinces: Array,
});
</script>
