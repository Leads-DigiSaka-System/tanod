<template>
  <AppLayout>
    <Head title="Notifications" />

    <!-- Page Header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Notifications</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Stay up to date with important alerts and updates</p>
      </div>
      <div class="flex items-center gap-3 mt-4 sm:mt-0">
        <Link v-if="filters.type || filters.unread" href="/notifications"
          class="text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-4 py-2.5 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
          Clear Filters
        </Link>
        <Link href="/notifications/read-all" method="post" as="button"
          class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800">
          Mark All as Read
        </Link>
      </div>
    </div>

    <!-- Type Tabs -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 mb-6">
      <div class="flex border-b border-gray-200 dark:border-gray-700 px-2 overflow-x-auto scrollbar-hide">
        <Link
          v-for="tab in tabs"
          :key="tab.key"
          :href="tab.key === 'all' ? '/notifications' : `/notifications?type=${tab.key}`"
          :class="[
            'relative flex items-center gap-2 px-4 py-3 text-sm font-medium whitespace-nowrap transition-colors border-b-2 -mb-px',
            activeTab === tab.key
              ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
          ]"
        >
          <component :is="tab.icon" class="w-4 h-4" />
          {{ tab.label }}
          <span v-if="getTypeCount(tab.key) > 0" :class="[
            'inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 text-[10px] font-bold rounded-full',
            activeTab === tab.key
              ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300'
              : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
          ]">
            {{ getTypeCount(tab.key) }}
          </span>
        </Link>
      </div>

      <!-- Notifications List -->
      <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
        <div v-for="notif in notifications.data" :key="notif.id"
          :class="[
            'flex gap-4 px-5 py-4 transition-colors',
            notif.is_read
              ? 'opacity-70'
              : 'bg-indigo-50/40 dark:bg-indigo-900/10'
          ]">
          <!-- Icon -->
          <div class="flex-shrink-0 mt-0.5">
            <div :class="['h-10 w-10 rounded-full flex items-center justify-center', typeColor(notif.type).bg]">
              <component :is="typeIconComponent(notif.type)" :class="['w-5 h-5', typeColor(notif.type).text]" />
            </div>
          </div>
          <!-- Content -->
          <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="flex items-center gap-2">
                  <h4 :class="['text-sm', notif.is_read ? 'text-gray-600 dark:text-gray-400' : 'font-semibold text-gray-900 dark:text-white']">
                    {{ notif.title }}
                  </h4>
                  <span v-if="!notif.is_read" class="w-2 h-2 rounded-full bg-indigo-500 flex-shrink-0"></span>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ notif.body }}</p>
                <div class="flex items-center gap-3 mt-2">
                  <span class="text-xs text-gray-400 dark:text-gray-500">{{ formatDate(notif.created_at) }}</span>
                  <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium uppercase tracking-wide', typeBadge(notif.type)]">
                    {{ typeLabel(notif.type) }}
                  </span>
                </div>
              </div>
              <div class="flex items-center gap-2 flex-shrink-0">
                <Link v-if="!notif.is_read" :href="`/notifications/${notif.id}/read`" method="post" as="button"
                  class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 text-xs font-medium px-2 py-1 rounded hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors">
                  Mark Read
                </Link>
                <Link :href="`/notifications/${notif.id}`" method="delete" as="button"
                  class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-xs font-medium px-2 py-1 rounded hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                  Delete
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!notifications.data?.length" class="py-16 px-4">
        <div class="flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">
            {{ filters.type ? `No ${filters.type} notifications` : 'No notifications' }}
          </h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">You're all caught up! New notifications will appear here.</p>
        </div>
      </div>
    </div>

    <Pagination :links="notifications.links" class="mt-6" />
  </AppLayout>
</template>

<script setup>
import { computed, h as hFn } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatDate } from '@/utils/dateFormat';

const props = defineProps({
  notifications: Object,
  filters: Object,
  typeCounts: Object,
});

const activeTab = computed(() => props.filters?.type || 'all');

// Icon render functions
const BookingIcon = (iconProps) => hFn('svg', { ...iconProps, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2' }, [
  hFn('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' })
]);
const TicketIcon = (iconProps) => hFn('svg', { ...iconProps, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2' }, [
  hFn('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z' })
]);
const AlertIcon = (iconProps) => hFn('svg', { ...iconProps, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2' }, [
  hFn('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' })
]);
const BellIcon = (iconProps) => hFn('svg', { ...iconProps, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2' }, [
  hFn('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9' })
]);
const DistributionIcon = (iconProps) => hFn('svg', { ...iconProps, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2' }, [
  hFn('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4' })
]);

const tabs = [
  { key: 'all', label: 'All', icon: BellIcon },
  { key: 'booking_approved', label: 'Bookings', icon: BookingIcon },
  { key: 'ticket_status_updated', label: 'Tickets', icon: TicketIcon },
  { key: 'alert', label: 'Alerts', icon: AlertIcon },
  { key: 'distribution', label: 'Distributions', icon: DistributionIcon },
];

function getTypeCount(tabKey) {
  if (!props.typeCounts) return 0;
  if (tabKey === 'all') return Object.values(props.typeCounts).reduce((a, b) => a + b, 0);
  // Match partial type keys (e.g., tab "booking_approved" matches "booking_approved", "booking_rejected")
  return Object.entries(props.typeCounts)
    .filter(([k]) => tabKey === 'all' ? true : k.includes(tabKey.split('_')[0]))
    .reduce((sum, [, v]) => sum + v, 0);
}

function normalizeType(type) {
  if (!type) return 'general';
  if (type.includes('booking')) return 'booking';
  if (type.includes('ticket')) return 'ticket';
  if (type.includes('alert') || type.includes('geofence') || type.includes('speed') || type.includes('offline') || type.includes('idle')) return 'alert';
  if (type.includes('distribution')) return 'distribution';
  return 'general';
}

function typeColor(type) {
  const t = normalizeType(type);
  const colors = {
    booking: { bg: 'bg-blue-100 dark:bg-blue-900/30', text: 'text-blue-600 dark:text-blue-400' },
    ticket: { bg: 'bg-orange-100 dark:bg-orange-900/30', text: 'text-orange-600 dark:text-orange-400' },
    alert: { bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-600 dark:text-red-400' },
    distribution: { bg: 'bg-green-100 dark:bg-green-900/30', text: 'text-green-600 dark:text-green-400' },
    general: { bg: 'bg-gray-100 dark:bg-gray-700', text: 'text-gray-600 dark:text-gray-400' },
  };
  return colors[t] || colors.general;
}

function typeBadge(type) {
  const t = normalizeType(type);
  const badges = {
    booking: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    ticket: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    alert: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    distribution: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    general: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400',
  };
  return badges[t] || badges.general;
}

function typeLabel(type) {
  return normalizeType(type);
}

function typeIconComponent(type) {
  const t = normalizeType(type);
  const icons = { booking: BookingIcon, ticket: TicketIcon, alert: AlertIcon, distribution: DistributionIcon, general: BellIcon };
  return icons[t] || icons.general;
}
</script>
