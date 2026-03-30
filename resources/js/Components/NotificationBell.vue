<template>
  <div class="relative" ref="bellRef">
    <!-- Bell Button -->
    <button @click="toggle" type="button" class="relative p-2 text-gray-500 rounded-lg hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 transition-colors">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0018 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
      </svg>
      <span v-if="unreadCount > 0" class="absolute top-0.5 right-0.5 inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-red-500 rounded-full ring-2 ring-white dark:ring-gray-800">
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown Panel -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-1"
    >
      <div v-if="open" class="absolute right-0 mt-2 w-96 bg-white rounded-xl shadow-2xl border border-gray-200 dark:bg-gray-800 dark:border-gray-700 z-50 overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
          <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Notifications</h3>
          <div class="flex items-center gap-2">
            <span v-if="unreadCount > 0" class="text-xs font-medium text-indigo-600 dark:text-indigo-400">{{ unreadCount }} new</span>
            <button v-if="unreadCount > 0" @click="markAllRead" class="text-xs font-medium text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors">
              Mark all read
            </button>
          </div>
        </div>

        <!-- Type Tabs -->
        <div class="flex border-b border-gray-100 dark:border-gray-700 px-1 overflow-x-auto scrollbar-hide">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key"
            :class="[
              'relative px-3 py-2 text-xs font-medium whitespace-nowrap transition-colors',
              activeTab === tab.key
                ? 'text-indigo-600 dark:text-indigo-400'
                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
            ]"
          >
            {{ tab.label }}
            <span v-if="tabUnreadCount(tab.key) > 0" class="ml-1 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white bg-red-500 rounded-full">
              {{ tabUnreadCount(tab.key) }}
            </span>
            <div v-if="activeTab === tab.key" class="absolute bottom-0 left-0 right-0 h-0.5 bg-indigo-600 dark:bg-indigo-400 rounded-full"></div>
          </button>
        </div>

        <!-- Notification List -->
        <div class="max-h-80 overflow-y-auto divide-y divide-gray-50 dark:divide-gray-700/50">
          <template v-if="filteredNotifications.length > 0">
            <div
              v-for="notif in filteredNotifications"
              :key="notif.id"
              :class="[
                'flex gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer',
                !notif.is_read ? 'bg-indigo-50/50 dark:bg-indigo-900/10' : ''
              ]"
              @click="handleNotifClick(notif)"
            >
              <!-- Icon -->
              <div class="flex-shrink-0 mt-0.5">
                <div :class="['w-8 h-8 rounded-full flex items-center justify-center', typeColor(notif.type).bg]">
                  <component :is="typeIcon(notif.type)" :class="['w-4 h-4', typeColor(notif.type).text]" />
                </div>
              </div>
              <!-- Content -->
              <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-2">
                  <p :class="['text-sm leading-tight', notif.is_read ? 'text-gray-600 dark:text-gray-400' : 'font-semibold text-gray-900 dark:text-white']">
                    {{ notif.title }}
                  </p>
                  <span v-if="!notif.is_read" class="flex-shrink-0 w-2 h-2 mt-1.5 rounded-full bg-indigo-500"></span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ notif.body }}</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1">{{ timeAgo(notif.created_at) }}</p>
              </div>
            </div>
          </template>

          <!-- Empty State -->
          <div v-else class="flex flex-col items-center justify-center py-10 px-4">
            <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No notifications</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">You're all caught up!</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="border-t border-gray-100 dark:border-gray-700">
          <a :href="route('notifications.index')" class="flex items-center justify-center gap-1.5 py-3 text-sm font-medium text-indigo-600 hover:text-indigo-700 hover:bg-gray-50 dark:text-indigo-400 dark:hover:text-indigo-300 dark:hover:bg-gray-700/50 transition-colors">
            View all notifications
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </a>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, h as hFn } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const bellRef = ref(null);
const open = ref(false);
const activeTab = ref('all');
const notifications = ref([]);
const unreadCount = ref(0);
const loading = ref(false);

const route = window.route || ((name) => {
  const routes = {
    'notifications.index': '/notifications',
    'notifications.read': (id) => `/notifications/${id}/read`,
    'notifications.read-all': '/notifications/read-all',
  };
  const r = routes[name];
  return typeof r === 'function' ? r : r || '/';
});

const tabs = [
  { key: 'all', label: 'All' },
  { key: 'booking', label: 'Bookings' },
  { key: 'ticket', label: 'Tickets' },
  { key: 'alert', label: 'Alerts' },
  { key: 'general', label: 'General' },
];

const filteredNotifications = computed(() => {
  if (activeTab.value === 'all') return notifications.value;
  return notifications.value.filter(n => normalizeType(n.type) === activeTab.value);
});

function normalizeType(type) {
  if (!type) return 'general';
  if (type.includes('booking')) return 'booking';
  if (type.includes('ticket')) return 'ticket';
  if (type.includes('alert') || type.includes('geofence') || type.includes('speed') || type.includes('offline') || type.includes('idle')) return 'alert';
  if (type.includes('distribution')) return 'general';
  return 'general';
}

function tabUnreadCount(tabKey) {
  if (tabKey === 'all') return unreadCount.value;
  return notifications.value.filter(n => normalizeType(n.type) === tabKey && !n.is_read).length;
}

// Type-based styling
function typeColor(type) {
  const t = normalizeType(type);
  const colors = {
    booking: { bg: 'bg-blue-100 dark:bg-blue-900/30', text: 'text-blue-600 dark:text-blue-400' },
    ticket: { bg: 'bg-orange-100 dark:bg-orange-900/30', text: 'text-orange-600 dark:text-orange-400' },
    alert: { bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-600 dark:text-red-400' },
    general: { bg: 'bg-gray-100 dark:bg-gray-700', text: 'text-gray-600 dark:text-gray-400' },
  };
  return colors[t] || colors.general;
}

// SVG Icons as render functions
const BookingIcon = (props) => hFn('svg', { ...props, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2' }, [
  hFn('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' })
]);
const TicketIcon = (props) => hFn('svg', { ...props, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2' }, [
  hFn('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z' })
]);
const AlertIcon = (props) => hFn('svg', { ...props, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2' }, [
  hFn('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' })
]);
const GeneralIcon = (props) => hFn('svg', { ...props, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2' }, [
  hFn('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9' })
]);

function typeIcon(type) {
  const t = normalizeType(type);
  const icons = { booking: BookingIcon, ticket: TicketIcon, alert: AlertIcon, general: GeneralIcon };
  return icons[t] || icons.general;
}

function timeAgo(dateStr) {
  if (!dateStr) return '';
  const now = Date.now();
  const date = new Date(dateStr).getTime();
  const diff = Math.floor((now - date) / 1000);

  if (diff < 60) return 'Just now';
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
  if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
  return new Date(dateStr).toLocaleDateString();
}

async function fetchRecent() {
  loading.value = true;
  try {
    const { data } = await axios.get('/notifications/recent');
    notifications.value = data.notifications;
    unreadCount.value = data.unread_count;
  } catch {
    // Silently fail — badge still shows from shared props
  } finally {
    loading.value = false;
  }
}

function toggle() {
  open.value = !open.value;
  if (open.value) fetchRecent();
}

function handleNotifClick(notif) {
  if (!notif.is_read) {
    axios.post(`/notifications/${notif.id}/read`).then(() => {
      notif.is_read = true;
      unreadCount.value = Math.max(0, unreadCount.value - 1);
    });
  }
}

async function markAllRead() {
  try {
    await axios.post('/notifications/read-all');
    notifications.value.forEach(n => n.is_read = true);
    unreadCount.value = 0;
  } catch {
    // silent
  }
}

function handleClickOutside(e) {
  if (bellRef.value && !bellRef.value.contains(e.target)) {
    open.value = false;
  }
}

// Listen for WebSocket-triggered refreshes
function handleWsRefresh() {
  if (open.value) fetchRecent();
  else unreadCount.value++;
}

// Initialize unread count from shared props
onMounted(() => {
  unreadCount.value = page.props.unreadNotifications || 0;
  document.addEventListener('click', handleClickOutside);
  window.addEventListener('tanod:notification', handleWsRefresh);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  window.removeEventListener('tanod:notification', handleWsRefresh);
});
</script>
