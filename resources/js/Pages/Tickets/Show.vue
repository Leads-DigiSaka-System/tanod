<template>
  <AppLayout>
    <Head :title="`Ticket #${ticket.id}`" />

    <!-- Page Header -->
    <div class="mb-6">
      <Link href="/tickets" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        Back to Tickets
      </Link>
      <div class="mt-3">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Ticket #{{ ticket.id }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ ticket.subject }}</p>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- Ticket Content -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Description Card -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Description</h3>
          <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ ticket.description }}</p>
        </div>

        <!-- Comments Card -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Comments ({{ ticket.comments?.length || 0 }})</h3>

          <div v-if="ticket.comments?.length" class="space-y-4 mb-6">
            <div v-for="comment in ticket.comments" :key="comment.id" class="flex gap-3">
              <div class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center flex-shrink-0">
                <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-300">{{ comment.user?.name?.charAt(0) || '?' }}</span>
              </div>
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ comment.user?.name || 'Unknown' }}</span>
                  <span class="text-xs text-gray-400 dark:text-gray-500">{{ formatDate(comment.created_at) }}</span>
                </div>
                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ comment.body }}</p>
              </div>
            </div>
          </div>

          <!-- Empty comments state -->
          <div v-else class="flex flex-col items-center justify-center py-8 mb-6">
            <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <p class="text-sm text-gray-500 dark:text-gray-400">No comments yet. Be the first to comment.</p>
          </div>

          <form @submit.prevent="addComment">
            <textarea v-model="commentForm.body" rows="3" placeholder="Write a comment..."
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"></textarea>
            <p v-if="commentForm.errors.body" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ commentForm.errors.body }}</p>
            <div class="mt-3 flex justify-end">
              <button type="submit" :disabled="commentForm.processing"
                class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800 disabled:opacity-50">
                Add Comment
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Details Card -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Details</h3>
          <dl class="space-y-4">
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
              <dd class="mt-1"><StatusBadge :status="ticket.status" /></dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Priority</dt>
              <dd class="mt-1"><StatusBadge :status="ticket.priority" /></dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white capitalize">{{ ticket.category || '—' }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Reporter</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ ticket.reporter?.name || '—' }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Assigned To</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ ticket.assignee?.name || 'Unassigned' }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(ticket.created_at) }}</dd>
            </div>
          </dl>
        </div>

        <!-- Actions Card -->
        <div v-if="canManage" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700 space-y-5">
          <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Actions</h3>

          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Update Status</label>
            <div class="flex gap-2">
              <select v-model="statusForm.status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="open">Open</option>
                <option value="in_progress">In Progress</option>
                <option value="resolved">Resolved</option>
                <option value="closed">Closed</option>
              </select>
              <button @click="updateStatus" :disabled="statusForm.processing"
                class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800 disabled:opacity-50 whitespace-nowrap">Update</button>
            </div>
          </div>

          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Assign To</label>
            <div class="flex gap-2">
              <select v-model="assignForm.assigned_to" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="">Unassigned</option>
                <option v-for="u in admins" :key="u.id" :value="u.id">{{ u.name }}</option>
              </select>
              <button @click="assignTicket" :disabled="assignForm.processing"
                class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800 disabled:opacity-50 whitespace-nowrap">Assign</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/utils/dateFormat';

const props = defineProps({ ticket: Object, admins: Array });

const page = usePage();
const canManage = computed(() => {
  const perms = page.props.auth?.user?.permissions || [];
  return perms.includes('tickets.manage') || perms.includes('tickets.assign');
});

const commentForm = useForm({ body: '' });
const addComment = () => {
  commentForm.post(`/tickets/${props.ticket.id}/comments`, {
    preserveScroll: true,
    onSuccess: () => commentForm.reset(),
  });
};

const statusForm = useForm({ status: props.ticket.status });
const updateStatus = () => {
  statusForm.post(`/tickets/${props.ticket.id}/status`, { preserveScroll: true });
};

const assignForm = useForm({ assigned_to: props.ticket.assigned_to || '' });
const assignTicket = () => {
  assignForm.post(`/tickets/${props.ticket.id}/assign`, { preserveScroll: true });
};
</script>
