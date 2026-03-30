<template>
  <AppLayout>
    <Head title="Booking Details" />
    <div class="mb-6">
      <Link href="/bookings" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-500">&larr; Back to Bookings</Link>
      <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">Booking #{{ booking.id }}</h1>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
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
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Booking Date</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(booking.booking_date) }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Purpose</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ booking.purpose }}</dd>
          </div>
          <div v-if="booking.farm_area_hectares">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Farm Area</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ booking.farm_area_hectares }} hectares</dd>
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
      <div class="space-y-4">
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
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/utils/dateFormat';

const props = defineProps({ booking: Object });
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
</script>
