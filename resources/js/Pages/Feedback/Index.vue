<template>
  <AppLayout>
    <Head title="Feedback" />

    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Farmer Feedback</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Feedback submitted from the mobile app by FCAs and farmers</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 gap-4 mb-6 sm:grid-cols-3 lg:grid-cols-5">
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Total</div>
        <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</div>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
        <div class="text-xs font-medium text-yellow-600 uppercase tracking-wider dark:text-yellow-400">Pending</div>
        <div class="mt-1 text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ stats.pending }}</div>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
        <div class="text-xs font-medium text-blue-600 uppercase tracking-wider dark:text-blue-400">Reviewed</div>
        <div class="mt-1 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ stats.reviewed }}</div>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
        <div class="text-xs font-medium text-green-600 uppercase tracking-wider dark:text-green-400">Resolved</div>
        <div class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">{{ stats.resolved }}</div>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Avg Rating</div>
        <div class="mt-1 flex items-baseline gap-1">
          <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.avg_rating }}</span>
          <span class="text-yellow-400 text-lg">&#9733;</span>
        </div>
      </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700 mb-6">
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Search -->
        <div class="relative">
          <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </div>
          <input v-model="filterForm.search" type="text" placeholder="Search feedback, user..."
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" />
        </div>
        <!-- Status -->
        <select v-model="filterForm.status"
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="reviewed">Reviewed</option>
          <option value="resolved">Resolved</option>
        </select>
        <!-- Category -->
        <select v-model="filterForm.category"
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
          <option value="">All Categories</option>
          <option value="service">Service</option>
          <option value="tractor">Tractor</option>
          <option value="operator">Operator</option>
          <option value="general">General</option>
        </select>
        <!-- Rating -->
        <select v-model="filterForm.rating"
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
          <option value="">All Ratings</option>
          <option v-for="r in 5" :key="r" :value="r">{{ r }} Star{{ r > 1 ? 's' : '' }}</option>
        </select>
      </div>
      <div class="mt-3 flex items-center gap-2">
        <button @click="applyFilters"
          class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800">
          Apply Filters
        </button>
        <button v-if="hasActiveFilters" @click="clearFilters"
          class="text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-4 py-2 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
          Clear
        </button>
      </div>
    </div>

    <!-- Feedback Table -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
          <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
              <th scope="col" class="px-6 py-3">Submitted By</th>
              <th scope="col" class="px-6 py-3">Rating</th>
              <th scope="col" class="px-6 py-3">Feedback</th>
              <th scope="col" class="px-6 py-3">Tractor</th>
              <th scope="col" class="px-6 py-3">Category</th>
              <th scope="col" class="px-6 py-3">Status</th>
              <th scope="col" class="px-6 py-3">Date</th>
              <th scope="col" class="px-6 py-3 text-right">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="fb in feedback.data" :key="fb.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
              <!-- Submitted By -->
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 shrink-0">
                    <span class="text-xs font-bold text-indigo-700 dark:text-indigo-300">{{ getInitials(fb.submitter?.name) }}</span>
                  </div>
                  <div class="min-w-0">
                    <div class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ fb.submitter?.name || 'Unknown' }}</div>
                    <div class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ fb.submitter?.email }}</div>
                  </div>
                </div>
              </td>
              <!-- Rating -->
              <td class="px-6 py-4">
                <div v-if="fb.rating" class="flex items-center gap-0.5">
                  <span v-for="star in 5" :key="star" :class="['text-sm', star <= fb.rating ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600']">&#9733;</span>
                </div>
                <span v-else class="text-gray-400">—</span>
              </td>
              <!-- Feedback excerpt -->
              <td class="px-6 py-4 max-w-xs">
                <p class="text-sm text-gray-900 dark:text-white truncate">{{ fb.feedback || '—' }}</p>
              </td>
              <!-- Tractor -->
              <td class="px-6 py-4">
                <span v-if="fb.tractor" class="inline-flex items-center bg-gray-100 text-gray-800 text-xs font-medium px-2 py-0.5 rounded dark:bg-gray-700 dark:text-gray-300">
                  {{ fb.tractor.no_plate }}
                </span>
                <span v-else class="text-gray-400">—</span>
              </td>
              <!-- Category -->
              <td class="px-6 py-4">
                <span v-if="fb.category" class="capitalize text-xs font-medium px-2 py-0.5 rounded"
                  :class="categoryClass(fb.category)">
                  {{ fb.category }}
                </span>
                <span v-else class="text-gray-400">—</span>
              </td>
              <!-- Status -->
              <td class="px-6 py-4">
                <span class="text-xs font-medium px-2.5 py-1 rounded-full" :class="statusClass(fb.status)">
                  {{ fb.status }}
                </span>
              </td>
              <!-- Date -->
              <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                {{ formatDate(fb.created_at) }}
              </td>
              <!-- Action -->
              <td class="px-6 py-4 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button @click="openDetail(fb)" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300" title="View details">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  </button>
                  <button v-if="canReview && fb.status === 'pending'" @click="openReview(fb)"
                    class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300" title="Review">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  </button>
                  <button @click="confirmDelete(fb)" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors" title="Delete">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="!feedback.data?.length" class="flex flex-col items-center justify-center py-16 px-6">
        <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">No feedback found</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Feedback submitted by FCAs and farmers from the mobile app will appear here.</p>
      </div>
    </div>

    <Pagination :links="feedback.links" class="mt-6" />

    <!-- Detail Modal -->
    <Modal :show="showDetailModal" @close="showDetailModal = false" maxWidth="2xl">
      <div v-if="detailItem" class="p-6">
        <div class="flex items-start justify-between mb-5">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Feedback Details</h3>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">ID #{{ detailItem.id }} &middot; {{ formatDate(detailItem.created_at) }}</p>
          </div>
          <span class="text-xs font-medium px-2.5 py-1 rounded-full" :class="statusClass(detailItem.status)">
            {{ detailItem.status }}
          </span>
        </div>

        <div class="space-y-4">
          <!-- Submitter -->
          <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900">
              <span class="text-sm font-bold text-indigo-700 dark:text-indigo-300">{{ getInitials(detailItem.submitter?.name) }}</span>
            </div>
            <div>
              <div class="text-sm font-medium text-gray-900 dark:text-white">{{ detailItem.submitter?.name || 'Unknown' }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400">{{ detailItem.submitter?.email }}</div>
            </div>
          </div>

          <!-- Rating + Category -->
          <div class="grid grid-cols-2 gap-3">
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <div class="text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Rating</div>
              <div v-if="detailItem.rating" class="mt-1 flex items-center gap-0.5">
                <span v-for="star in 5" :key="star" :class="['text-lg', star <= detailItem.rating ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600']">&#9733;</span>
                <span class="ml-1 text-sm font-medium text-gray-700 dark:text-gray-300">{{ detailItem.rating }}/5</span>
              </div>
              <div v-else class="mt-1 text-sm text-gray-400">No rating</div>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <div class="text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Category</div>
              <div class="mt-1">
                <span v-if="detailItem.category" class="capitalize text-xs font-medium px-2 py-0.5 rounded" :class="categoryClass(detailItem.category)">{{ detailItem.category }}</span>
                <span v-else class="text-sm text-gray-400">Uncategorized</span>
              </div>
            </div>
          </div>

          <!-- Tractor + Booking -->
          <div class="grid grid-cols-2 gap-3">
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <div class="text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Tractor</div>
              <div class="mt-1 text-sm text-gray-900 dark:text-white">
                {{ detailItem.tractor ? `${detailItem.tractor.brand} ${detailItem.tractor.model} — ${detailItem.tractor.no_plate}` : '—' }}
              </div>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <div class="text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Booking</div>
              <div class="mt-1 text-sm text-gray-900 dark:text-white">
                {{ detailItem.booking ? `#${detailItem.booking.id}` : '—' }}
              </div>
            </div>
          </div>

          <!-- Feedback Text -->
          <div class="p-4 bg-gray-50 rounded-lg dark:bg-gray-700 border border-gray-200 dark:border-gray-600">
            <div class="text-xs font-medium text-gray-500 uppercase dark:text-gray-400 mb-2">Feedback</div>
            <p class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap leading-relaxed">{{ detailItem.feedback || 'No feedback text provided.' }}</p>
          </div>

          <!-- Conclusion -->
          <div v-if="detailItem.conclusion" class="p-4 bg-blue-50 rounded-lg dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800">
            <div class="text-xs font-medium text-blue-600 uppercase dark:text-blue-400 mb-2">Conclusion</div>
            <p class="text-sm text-blue-900 dark:text-blue-200 whitespace-pre-wrap">{{ detailItem.conclusion }}</p>
          </div>

          <!-- Admin Response -->
          <div v-if="detailItem.admin_response" class="p-4 bg-emerald-50 rounded-lg dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800">
            <div class="flex items-center gap-2 mb-2">
              <div class="text-xs font-medium text-emerald-600 uppercase dark:text-emerald-400">Admin Response</div>
              <span v-if="detailItem.reviewer" class="text-xs text-gray-400 dark:text-gray-500">by {{ detailItem.reviewer.name }}</span>
            </div>
            <p class="text-sm text-emerald-900 dark:text-emerald-200 whitespace-pre-wrap">{{ detailItem.admin_response }}</p>
            <p v-if="detailItem.reviewed_at" class="mt-2 text-xs text-gray-400 dark:text-gray-500">{{ formatDate(detailItem.reviewed_at) }}</p>
          </div>
        </div>

        <div class="mt-5 flex justify-end">
          <button @click="showDetailModal = false"
            class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600">
            Close
          </button>
        </div>
      </div>
    </Modal>

    <!-- Review Modal -->
    <Modal :show="showReviewModal" @close="showReviewModal = false" maxWidth="lg">
      <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Review Feedback</h3>
        <p v-if="reviewTarget" class="text-sm text-gray-500 dark:text-gray-400 mb-4">
          From <strong>{{ reviewTarget.submitter?.name }}</strong> — "{{ truncate(reviewTarget.feedback, 80) }}"
        </p>
        <div class="space-y-4">
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Status *</label>
            <select v-model="reviewForm.status"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
              <option value="reviewed">Reviewed</option>
              <option value="resolved">Resolved</option>
              <option value="dismissed">Dismissed</option>
            </select>
          </div>
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Conclusion</label>
            <textarea v-model="reviewForm.conclusion" rows="2" placeholder="Internal conclusion..."
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"></textarea>
          </div>
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Admin Response</label>
            <textarea v-model="reviewForm.admin_response" rows="3" placeholder="Response visible to the mobile user..."
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"></textarea>
          </div>
        </div>
        <div class="mt-5 flex justify-end space-x-3">
          <button @click="showReviewModal = false"
            class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600 dark:focus:ring-gray-700">
            Cancel
          </button>
          <button @click="submitReview" :disabled="reviewForm.processing"
            class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800 disabled:opacity-50">
            Submit Review
          </button>
        </div>
      </div>
    </Modal>

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
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Delete Feedback</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">This action cannot be undone.</p>
          </div>
        </div>
      </template>

      <div class="text-sm text-gray-600 dark:text-gray-300 space-y-2">
        <p>Are you sure you want to delete this feedback?</p>
        <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3 space-y-1">
          <p class="font-medium text-gray-900 dark:text-white truncate">{{ feedbackToDelete?.feedback || 'No feedback text' }}</p>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            #{{ feedbackToDelete?.id }} &middot; by {{ feedbackToDelete?.submitter?.name || 'Unknown' }}
          </p>
        </div>
      </div>

      <template #footer>
        <div class="flex items-center justify-end gap-3 w-full">
          <button @click="closeDeleteModal" type="button"
            class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 dark:focus:ring-gray-500 transition-colors">
            Cancel
          </button>
          <button @click="deleteFeedback" type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:focus:ring-offset-gray-800 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Yes, Delete Feedback
          </button>
        </div>
      </template>
    </Modal>
  </AppLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import { formatDate } from '@/utils/dateFormat';

const props = defineProps({
  feedback: Object,
  stats: Object,
  filters: Object,
});

const page = usePage();
const roles = computed(() => page.props.auth?.user?.roles?.map(r => r.name) || []);
const canReview = computed(() => ['super-admin', 'sub-admin'].some(r => roles.value.includes(r)));

// Filters
const filterForm = reactive({
  search: props.filters?.search || '',
  status: props.filters?.status || '',
  category: props.filters?.category || '',
  rating: props.filters?.rating || '',
});

const hasActiveFilters = computed(() => Object.values(filterForm).some(v => v));

function applyFilters() {
  const params = {};
  if (filterForm.search) params.search = filterForm.search;
  if (filterForm.status) params.status = filterForm.status;
  if (filterForm.category) params.category = filterForm.category;
  if (filterForm.rating) params.rating = filterForm.rating;
  router.get('/feedback', params, { preserveState: true, replace: true });
}

function clearFilters() {
  filterForm.search = '';
  filterForm.status = '';
  filterForm.category = '';
  filterForm.rating = '';
  router.get('/feedback', {}, { preserveState: true, replace: true });
}

// Detail modal
const showDetailModal = ref(false);
const detailItem = ref(null);
function openDetail(fb) {
  detailItem.value = fb;
  showDetailModal.value = true;
}

// Review modal
const showReviewModal = ref(false);
const reviewTarget = ref(null);
const reviewForm = useForm({
  status: 'reviewed',
  conclusion: '',
  admin_response: '',
});

function openReview(fb) {
  reviewTarget.value = fb;
  reviewForm.status = 'reviewed';
  reviewForm.conclusion = fb.conclusion || '';
  reviewForm.admin_response = fb.admin_response || '';
  showReviewModal.value = true;
}

function submitReview() {
  reviewForm.put(`/feedback/${reviewTarget.value.id}/review`, {
    preserveScroll: true,
    onSuccess: () => { showReviewModal.value = false; },
  });
}

// ── Delete ──
const showDeleteModal = ref(false);
const feedbackToDelete = ref(null);

function confirmDelete(fb) {
  feedbackToDelete.value = fb;
  showDeleteModal.value = true;
}

function closeDeleteModal() {
  showDeleteModal.value = false;
  feedbackToDelete.value = null;
}

function deleteFeedback() {
  if (!feedbackToDelete.value) return;
  router.delete(`/feedback/${feedbackToDelete.value.id}`, {
    preserveState: true,
    replace: true,
    onSuccess: () => closeDeleteModal(),
    onError: () => closeDeleteModal(),
  });
}

// Helpers
function getInitials(name) {
  if (!name) return '?';
  return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase();
}

function truncate(str, len) {
  if (!str) return '';
  return str.length > len ? str.substring(0, len) + '...' : str;
}

function statusClass(status) {
  const map = {
    pending:  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    reviewed: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    resolved: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    dismissed:'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
  };
  return map[status] || map.pending;
}

function categoryClass(cat) {
  const map = {
    service:  'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
    tractor:  'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
    operator: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-300',
    general:  'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
  };
  return map[cat] || map.general;
}
</script>
