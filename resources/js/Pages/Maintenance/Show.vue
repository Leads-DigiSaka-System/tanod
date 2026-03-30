<template>
  <AppLayout>
    <Head :title="`Maintenance #${maintenance.id}`" />
    <div class="mb-6">
      <Link href="/maintenance" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-500">&larr; Back to Maintenance</Link>
      <div class="mt-2 sm:flex sm:items-center sm:justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Maintenance #{{ maintenance.id }}</h1>
        <Link :href="`/maintenance/${maintenance.id}/edit`"
          class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800">Edit</Link>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 lg:col-span-2 dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 dark:text-white">Details</h3>
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tractor</dt>
            <dd class="mt-1">
              <Link v-if="maintenance.tractor" :href="`/tractors/${maintenance.tractor.id}`" class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-500">
                {{ maintenance.tractor.brand }} {{ maintenance.tractor.model }} — {{ maintenance.tractor.no_plate }}
              </Link>
              <span v-else class="text-sm text-gray-400 dark:text-gray-500">—</span>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Issue Type</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ maintenance.issue_type?.name || '—' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
            <dd class="mt-1"><StatusBadge :status="maintenance.status" /></dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Priority</dt>
            <dd class="mt-1"><StatusBadge :status="maintenance.priority" /></dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cost</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ maintenance.cost ? `₱${Number(maintenance.cost).toLocaleString()}` : '—' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Scheduled Date</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ maintenance.scheduled_date ? formatDate(maintenance.scheduled_date) : '—' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed Date</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ maintenance.completed_date ? formatDate(maintenance.completed_date) : '—' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Reported By</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ maintenance.reporter?.name || '—' }}</dd>
          </div>
        </dl>
        <div class="mt-4">
          <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
          <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap dark:text-white">{{ maintenance.description }}</dd>
        </div>
        <div v-if="maintenance.notes" class="mt-4">
          <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Notes</dt>
          <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap dark:text-white">{{ maintenance.notes }}</dd>
        </div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 dark:text-white">Images</h3>
        <div v-if="maintenance.images?.length" class="grid grid-cols-2 gap-2">
          <img v-for="img in maintenance.images" :key="img.id" :src="`/storage/${img.path}`" class="rounded-lg object-cover h-32 w-full cursor-pointer hover:opacity-80 border border-gray-200 dark:border-gray-600" @click="openImage(img.path)" />
        </div>
        <p v-else class="text-sm text-gray-400 dark:text-gray-500">No images uploaded.</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/utils/dateFormat';

defineProps({ maintenance: Object });

const openImage = (path) => { window.open(`/storage/${path}`, '_blank'); };
</script>
