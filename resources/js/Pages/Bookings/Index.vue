<template>
  <AppLayout>
    <Head title="Bookings" />
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Bookings</h1>
      <Link v-if="$page.props.auth.user.permissions.includes('bookings.create')" href="/bookings/create"
        class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800">
        New Booking
      </Link>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 mb-6 dark:bg-gray-800 dark:border-gray-700">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Search</label>
          <input v-model="search" type="text" placeholder="Search tractor plate..." @input="debouncedFilter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500" />
        </div>
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Status</label>
          <select v-model="selectedStatus" @change="applyFilter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500 dark:focus:border-indigo-500">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
      <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
          <tr>
            <th scope="col" class="px-6 py-3">Tractor</th>
            <th scope="col" class="px-6 py-3">Booked By</th>
            <th scope="col" class="px-6 py-3">Date</th>
            <th scope="col" class="px-6 py-3">Purpose</th>
            <th scope="col" class="px-6 py-3">Status</th>
            <th scope="col" class="px-6 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="booking in bookings.data" :key="booking.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">{{ booking.tractor?.no_plate }}</td>
            <td class="px-6 py-4">{{ booking.booked_by?.name }}</td>
            <td class="px-6 py-4">{{ formatDate(booking.booking_date) }}</td>
            <td class="px-6 py-4 truncate max-w-xs">{{ booking.purpose }}</td>
            <td class="px-6 py-4"><StatusBadge :status="booking.status" /></td>
            <td class="px-6 py-4">
              <div class="flex items-center justify-end gap-1">
                <Link :href="`/bookings/${booking.id}`" class="p-1.5 rounded-lg text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:text-gray-400 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20 transition-colors" title="View">
                  <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                </Link>
                <Link v-if="booking.status === 'pending' && canApprove" :href="`/bookings/${booking.id}/approve`" method="post" as="button" class="p-1.5 rounded-lg text-gray-500 hover:text-green-600 hover:bg-green-50 dark:text-gray-400 dark:hover:text-green-400 dark:hover:bg-green-900/20 transition-colors" title="Approve">
                  <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </Link>
                <Link v-if="booking.status === 'pending' && canApprove" :href="`/bookings/${booking.id}/reject`" method="post" as="button" class="p-1.5 rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50 dark:text-gray-400 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors" title="Reject" @click.prevent="rejectBooking(booking)">
                  <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </Link>
                <button @click="confirmDelete(booking)" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors" title="Delete">
                  <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!bookings.data.length">
            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No bookings found.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <Pagination :links="bookings.links" class="mt-6" />

    <!-- Delete Confirmation Modal -->
    <Modal :show="showDeleteModal" max-width="sm" @close="closeDeleteModal">
      <template #header>
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Delete Booking</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">This action cannot be undone.</p>
          </div>
        </div>
      </template>

      <div class="text-sm text-gray-600 dark:text-gray-300 space-y-2">
        <p>Are you sure you want to delete this booking?</p>
        <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3 space-y-1">
          <p class="font-medium text-gray-900 dark:text-white">{{ bookingToDelete?.tractor?.no_plate || 'Unknown tractor' }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            #{{ bookingToDelete?.id }} &middot; {{ bookingToDelete?.booked_by?.name }} &middot; {{ formatDate(bookingToDelete?.booking_date) }}
          </p>
        </div>
      </div>

      <template #footer>
        <div class="flex items-center justify-end gap-3 w-full">
          <button @click="closeDeleteModal" type="button"
            class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 dark:focus:ring-gray-500 transition-colors">
            Cancel
          </button>
          <button @click="deleteBooking" type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:focus:ring-offset-gray-800 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Yes, Delete Booking
          </button>
        </div>
      </template>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Modal from '@/Components/Modal.vue';
import { formatDate } from '@/utils/dateFormat';

const props = defineProps({ bookings: Object, filters: Object });
const page = usePage();

const search = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || '');

const canApprove = computed(() => (page.props.auth?.user?.permissions || []).includes('bookings.approve'));

let timer;
const debouncedFilter = () => { clearTimeout(timer); timer = setTimeout(applyFilter, 300); };
const applyFilter = () => {
  router.get('/bookings', { search: search.value || undefined, status: selectedStatus.value || undefined }, { preserveState: true, replace: true });
};

const rejectBooking = (booking) => {
  const reason = prompt('Rejection reason:');
  if (reason) {
    router.post(`/bookings/${booking.id}/reject`, { reason });
  }
};

// ── Delete ──
const showDeleteModal = ref(false);
const bookingToDelete = ref(null);

function confirmDelete(booking) {
  bookingToDelete.value = booking;
  showDeleteModal.value = true;
}

function closeDeleteModal() {
  showDeleteModal.value = false;
  bookingToDelete.value = null;
}

function deleteBooking() {
  if (!bookingToDelete.value) return;
  router.delete(`/bookings/${bookingToDelete.value.id}`, {
    preserveState: true,
    replace: true,
    onSuccess: () => closeDeleteModal(),
    onError: () => closeDeleteModal(),
  });
}
</script>
