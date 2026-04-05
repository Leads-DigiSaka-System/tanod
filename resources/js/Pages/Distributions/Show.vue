<template>
  <AppLayout>
    <Head :title="`Distribution #${distribution.id}`" />

    <!-- Page Header -->
    <div class="mb-6">
      <Link href="/distributions" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Distributions
      </Link>
      <div class="mt-2 sm:flex sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Distribution #{{ distribution.id }}</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">View distribution details and associated information.</p>
        </div>
        <div v-if="distribution.status === 'distributed'" class="mt-3 sm:mt-0 flex gap-2">
          <Link :href="`/distributions/${distribution.id}/edit`"
            class="text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-500 dark:hover:bg-indigo-600 dark:focus:ring-indigo-800">
            Edit
          </Link>
          <Link :href="`/distributions/${distribution.id}/return`" method="post" as="button"
            class="text-white bg-amber-600 hover:bg-amber-700 focus:ring-4 focus:ring-amber-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-amber-500 dark:hover:bg-amber-600 dark:focus:ring-amber-800">
            Mark as Returned
          </Link>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <!-- Distribution Info Card -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Distribution Info</h3>
        <dl class="space-y-4">
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
            <dd class="mt-1"><StatusBadge :status="distribution.status" /></dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Area</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ distribution.area || '—' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Distributed Date</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ distribution.distributed_at ? formatDate(distribution.distributed_at) : formatDate(distribution.created_at) }}</dd>
          </div>
          <div v-if="distribution.returned_at">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Returned Date</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(distribution.returned_at) }}</dd>
          </div>
          <div v-if="distribution.notes">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Notes</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ distribution.notes }}</dd>
          </div>
        </dl>
      </div>

      <div class="space-y-6">
        <!-- Tractor Card -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tractor</h3>
          <div v-if="distribution.tractor">
            <Link :href="`/tractors/${distribution.tractor.id}`" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium">
              {{ distribution.tractor.brand }} {{ distribution.tractor.model }}
            </Link>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Plate: {{ distribution.tractor.no_plate }}</p>
          </div>
          <div v-else class="flex flex-col items-center justify-center py-4">
            <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0H3m10 0h2m4 0h1a1 1 0 001-1v-4l-3-5h-4v10"/></svg>
            <p class="text-sm text-gray-400 dark:text-gray-500">No tractor assigned</p>
          </div>
        </div>

        <!-- People Card -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">People</h3>
          <dl class="space-y-4">
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Distributed To</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                <Link v-if="distribution.distributed_to_user" :href="`/users/${distribution.distributed_to_user.id}`" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">{{ distribution.distributed_to_user.name }}</Link>
                <span v-else class="text-gray-400 dark:text-gray-500">—</span>
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Distributed By</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                <Link v-if="distribution.distributed_by_user" :href="`/users/${distribution.distributed_by_user.id}`" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">{{ distribution.distributed_by_user.name }}</Link>
                <span v-else class="text-gray-400 dark:text-gray-500">—</span>
              </dd>
            </div>
          </dl>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/utils/dateFormat';

defineProps({ distribution: Object });
</script>
