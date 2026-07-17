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
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Distribution Date</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ distribution.distribution_date ? formatDate(distribution.distribution_date) : '—' }}</dd>
          </div>
          <div v-if="distribution.return_date">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Return Date</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(distribution.return_date) }}</dd>
          </div>
          <div v-if="distribution.latitude && distribution.longitude">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Geotag</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ Number(distribution.latitude).toFixed(5) }}, {{ Number(distribution.longitude).toFixed(5) }}</dd>
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
          <div class="flex items-center gap-2 mb-4">
            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tractor Information</h3>
          </div>
          <div v-if="distribution.tractor">
            <dl class="space-y-3">
              <div class="flex justify-between">
                <dt class="text-sm text-gray-500 dark:text-gray-400">Name</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ distribution.tractor.name || '—' }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-sm text-gray-500 dark:text-gray-400">Brand & Model</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-white">
                  <Link :href="`/tractors/${distribution.tractor.id}`" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                    {{ distribution.tractor.brand }} {{ distribution.tractor.model }}
                  </Link>
                </dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-sm text-gray-500 dark:text-gray-400">Plate No.</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ distribution.tractor.no_plate || '—' }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-sm text-gray-500 dark:text-gray-400">Engine No.</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ distribution.tractor.engine_no || '—' }}</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-sm text-gray-500 dark:text-gray-400">Chassis No.</dt>
                <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ distribution.tractor.chassis_no || '—' }}</dd>
              </div>

              <!-- Implements Section -->
              <div class="border-t border-gray-100 dark:border-gray-700 pt-3 mt-3">
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Implements</p>
                <div class="flex justify-between">
                  <dt class="text-sm text-gray-500 dark:text-gray-400">Front Loader SN</dt>
                  <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ distribution.tractor.front_loader_sn || '—' }}</dd>
                </div>
                <div class="flex justify-between mt-2">
                  <dt class="text-sm text-gray-500 dark:text-gray-400">Rotary Tiller SN</dt>
                  <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ distribution.tractor.rotary_tiller_sn || '—' }}</dd>
                </div>
                <div class="flex justify-between mt-2">
                  <dt class="text-sm text-gray-500 dark:text-gray-400">Disc Plow SN</dt>
                  <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ distribution.tractor.disc_plow_sn || '—' }}</dd>
                </div>
              </div>
            </dl>
          </div>
          <div v-else class="flex flex-col items-center justify-center py-4">
            <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0H3m10 0h2m4 0h1a1 1 0 001-1v-4l-3-5h-4v10"/></svg>
            <p class="text-sm text-gray-400 dark:text-gray-500">No tractor assigned</p>
          </div>
        </div>

        <!-- People Card -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center gap-2 mb-4">
            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">People</h3>
          </div>
          <dl class="space-y-4">
            <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 p-3">
              <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Distributed To (FCA)</dt>
              <dd class="text-sm text-gray-900 dark:text-white">
                <div v-if="distribution.distributed_to_user">
                  <Link :href="`/users/${distribution.distributed_to_user.id}`" class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                    {{ distribution.distributed_to_user.name }}
                  </Link>
                  <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ distribution.distributed_to_user.email }}</p>
                  <p v-if="distribution.distributed_to_user.phone" class="text-xs text-gray-500 dark:text-gray-400">{{ distribution.distributed_to_user.phone }}</p>
                  <p v-if="distribution.distributed_to_user.organization_name" class="mt-0.5 text-xs font-medium text-gray-600 dark:text-gray-300">{{ distribution.distributed_to_user.organization_name }}</p>
                  <div v-if="distribution.distributed_to_user.province || distribution.distributed_to_user.city" class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                    {{ [distribution.distributed_to_user.city, distribution.distributed_to_user.province].filter(Boolean).join(', ') }}
                  </div>
                </div>
                <span v-else class="text-gray-400 dark:text-gray-500">—</span>
              </dd>
            </div>
            <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 p-3">
              <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Distributed By</dt>
              <dd class="text-sm text-gray-900 dark:text-white">
                <div v-if="distribution.distributed_by_user">
                  <Link :href="`/users/${distribution.distributed_by_user.id}`" class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                    {{ distribution.distributed_by_user.name }}
                  </Link>
                  <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ distribution.distributed_by_user.email }}</p>
                  <p v-if="distribution.distributed_by_user.phone" class="text-xs text-gray-500 dark:text-gray-400">{{ distribution.distributed_by_user.phone }}</p>
                </div>
                <span v-else class="text-gray-400 dark:text-gray-500">—</span>
              </dd>
            </div>
            <div v-if="distribution.tps_user" class="rounded-lg bg-gray-50 dark:bg-gray-700/50 p-3">
              <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Responsible TPS</dt>
              <dd class="text-sm text-gray-900 dark:text-white">
                <Link :href="`/users/${distribution.tps_user.id}`" class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                  {{ distribution.tps_user.name }}
                </Link>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ distribution.tps_user.email }}</p>
                <p v-if="distribution.tps_user.phone" class="text-xs text-gray-500 dark:text-gray-400">{{ distribution.tps_user.phone }}</p>
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
