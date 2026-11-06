<template>
  <AppLayout>
    <Head :title="group.name" />

    <!-- Back link + header -->
    <div class="mb-6">
      <Link href="/groups" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        Back to Groups
      </Link>
      <div class="mt-2 sm:flex sm:items-center sm:justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ group.name }}</h1>
        <div class="mt-3 sm:mt-0">
          <Link :href="`/groups/${group.id}/edit`"
            class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 focus:outline-none dark:focus:ring-indigo-800">Edit</Link>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- Details card -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Details</h3>
        <dl class="space-y-4">
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Area</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ group.area || '—' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
            <dd class="mt-1"><StatusBadge :status="group.is_active ? 'online' : 'offline'" :label="group.is_active ? 'Active' : 'Inactive'" /></dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ group.description || '—' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(group.created_at) }}</dd>
          </div>
        </dl>
      </div>

      <!-- Tractors card -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Tractors ({{ group.tractors?.length || 0 }})</h3>
        <ul v-if="group.tractors?.length" class="divide-y divide-gray-200 dark:divide-gray-700">
          <li v-for="t in group.tractors" :key="t.id" class="py-3 flex justify-between items-center">
            <Link :href="`/tractors/${t.id}`" class="text-sm font-medium text-indigo-600 dark:text-indigo-500 hover:underline">{{ t.name }} — {{ t.no_plate }}</Link>
            <StatusBadge :status="t.status" />
          </li>
        </ul>
        <div v-else class="flex flex-col items-center py-6">
          <svg class="w-10 h-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
          </svg>
          <p class="mt-2 text-sm text-gray-400 dark:text-gray-500">No tractors assigned.</p>
        </div>
      </div>

      <!-- TSR Users card -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Assigned TSR ({{ group.tps_users?.length || 0 }})</h3>
        <div v-if="group.tps_users?.length" class="space-y-3">
          <div v-for="u in group.tps_users" :key="u.id"
            class="flex items-center gap-3 rounded-lg border border-gray-200 dark:border-gray-700 p-3 bg-gray-50 dark:bg-gray-700">
            <div class="h-9 w-9 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center flex-shrink-0">
              <span class="text-sm font-semibold text-indigo-600 dark:text-indigo-300">{{ u.name?.charAt(0) }}</span>
            </div>
            <div class="min-w-0">
              <Link :href="`/users/${u.id}`" class="text-sm font-medium text-indigo-600 dark:text-indigo-500 hover:underline truncate block">{{ u.name }}</Link>
              <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ u.email }}</p>
            </div>
          </div>
        </div>
        <div v-else class="flex flex-col items-center py-6">
          <svg class="w-10 h-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          <p class="mt-2 text-sm text-gray-400 dark:text-gray-500">No TSR assigned.</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/utils/dateFormat';

defineProps({ group: Object });
</script>
