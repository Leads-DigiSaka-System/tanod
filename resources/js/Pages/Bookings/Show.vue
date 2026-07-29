<template>
  <AppLayout>
    <Head title="Booking Details" />
    <div class="mb-6">
      <Link href="/bookings" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-500">&larr; Back to Bookings</Link>
      <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Booking #{{ booking.id }}</h1>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <!-- Left Column: Details + Actions -->
      <div class="space-y-6">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 mb-4 dark:text-white">Booking Details</h2>
          <dl class="space-y-4">
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
              <dd class="mt-1"><StatusBadge :status="booking.status" /></dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tractor</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ booking.tractor?.no_plate }} — {{ booking.tractor?.brand }} {{ booking.tractor?.model }}</dd>
            </div>
            <div v-if="booking.farmer">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Farmer</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ booking.farmer?.name }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Date Range</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                {{ formatDate(booking.start_date || booking.booking_date) }}
                <span v-if="booking.end_date && booking.end_date !== booking.start_date"> — {{ formatDate(booking.end_date) }}</span>
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Purpose</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ booking.purpose }}</dd>
            </div>
            <div v-if="booking.farm_area_hectares">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Farm Area</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ booking.farm_area_hectares }} hectares</dd>
            </div>
            <div v-if="booking.cost != null">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cost</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">₱{{ Number(booking.cost).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Booked By</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ booking.booked_by?.name }}</dd>
            </div>
            <div v-if="booking.approved_by">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Reviewed By</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ booking.approved_by?.name }}</dd>
            </div>
            <div v-if="booking.notes">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Notes</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ booking.notes }}</dd>
            </div>
          </dl>
        </div>

        <!-- Actions -->
        <div v-if="booking.status === 'pending' && canApprove" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <h2 class="text-lg font-semibold text-gray-900 mb-4 dark:text-white">Review Actions</h2>
          <div class="flex gap-3">
            <Link :href="`/bookings/${booking.id}/approve`" method="post" as="button"
              class="text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
              Approve
            </Link>
            <button @click="rejectBooking"
              class="text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900">
              Reject
            </button>
          </div>
        </div>

        <div v-if="canCancel" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <Link :href="`/bookings/${booking.id}/cancel`" method="post" as="button"
            class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700">
            Cancel Booking
          </Link>
        </div>
      </div>

      <!-- Right Column: GPS Track Image -->
      <div v-if="trackImageUrl" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700 h-fit lg:sticky lg:top-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-4 dark:text-white">GPS Track</h2>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
          Route taken by {{ booking.tractor?.no_plate }} from {{ formatDate(booking.start_date || booking.booking_date) }}
          <span v-if="booking.end_date && booking.end_date !== booking.start_date"> to {{ formatDate(booking.end_date) }}</span>
        </p>
        <img :src="trackImageUrl" alt="GPS Track" class="w-full rounded-lg border border-gray-100 dark:border-gray-700" />
        <p class="mt-2 text-[11px] text-gray-400 dark:text-gray-500">
          <span class="inline-flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-full bg-green-500"></span> Start</span>
          <span class="ml-3 inline-flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-full bg-red-500"></span> End</span>
        </p>

        <!-- Track Stats -->
        <div v-if="trackStats" class="mt-4 grid grid-cols-2 gap-3">
          <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 p-3 text-center">
            <p class="text-[11px] text-gray-500 dark:text-gray-400 uppercase tracking-wider">Distance</p>
            <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ trackStats.distance }} <span class="text-xs font-normal text-gray-400">km</span></p>
          </div>
          <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 p-3 text-center">
            <p class="text-[11px] text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Time</p>
            <p class="text-lg font-bold text-gray-800 dark:text-gray-200">{{ formatDuration(trackStats.totalDuration) }}</p>
          </div>
          <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 p-3 text-center">
            <p class="text-[11px] text-emerald-600 dark:text-emerald-400 uppercase tracking-wider font-medium">Moving</p>
            <p class="text-base font-bold text-emerald-700 dark:text-emerald-300">{{ formatDuration(trackStats.movingDuration) }}</p>
          </div>
          <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 p-3 text-center">
            <p class="text-[11px] text-amber-600 dark:text-amber-400 uppercase tracking-wider font-medium">Idle</p>
            <p class="text-base font-bold text-amber-700 dark:text-amber-300">{{ formatDuration(trackStats.idleDuration) }}</p>
          </div>
          <div class="rounded-lg bg-sky-50 dark:bg-sky-900/20 p-3 text-center">
            <p class="text-[11px] text-sky-600 dark:text-sky-400 uppercase tracking-wider font-medium">Parked</p>
            <p class="text-base font-bold text-sky-700 dark:text-sky-300">{{ formatDuration(trackStats.parkedDuration) }}</p>
          </div>
          <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 p-3 text-center">
            <p class="text-[11px] text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stops</p>
            <p class="text-base font-bold text-gray-800 dark:text-gray-200">{{ trackStats.stopCount }}</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/utils/dateFormat';

const props = defineProps({
  booking: Object,
  trackImageUrl: String,
  trackStats: Object,
});
const page = usePage();

const canApprove = computed(() => (page.props.auth?.user?.permissions || []).includes('bookings.approve'));
const canCancel = computed(() => {
  return ['pending', 'approved'].includes(props.booking.status) && (
    props.booking.booked_by?.id === page.props.auth?.user?.id ||
    (page.props.auth?.user?.roles || []).some(r => ['super-admin', 'sub-admin'].includes(r))
  );
});

const rejectBooking = () => {
  const reason = prompt('Reason for rejection:');
  if (reason) {
    router.post(`/bookings/${props.booking.id}/reject`, { reason });
  }
};

function formatDuration(seconds) {
  if (!seconds || seconds <= 0) return '0m';
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  if (h > 0) return `${h}h ${m}m`;
  return `${m}m`;
}
</script>
