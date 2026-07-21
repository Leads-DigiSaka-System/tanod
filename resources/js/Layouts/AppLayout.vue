<template>
  <div class="antialiased bg-gray-50 dark:bg-gray-900">
    <!-- Sidebar backdrop (mobile) -->
    <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden" @click="sidebarOpen = false"></div>

    <!-- Sidebar -->
    <aside :class="[
      'fixed top-0 left-0 z-40 w-64 h-screen transition-transform',
      sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
    ]" aria-label="Sidebar">
      <div class="flex flex-col h-full px-3 py-4 overflow-y-auto" style="background-color: #007f3d;">
        <!-- Logo -->
        <Link href="/" class="flex items-center mb-6 px-2">
          <img src="/images/logo.png" alt="TanodTractor" class="h-10 w-auto" />
        </Link>

        <!-- Navigation -->
        <SidebarNav :navigation="navigation" />

        <!-- Bottom user info -->
        <div class="mt-auto pt-4 border-t border-white/20">
          <div class="flex items-center gap-3 px-2 py-2 text-green-100">
            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-yellow-500 flex items-center justify-center text-green-900 text-sm font-bold">
              {{ $page.props.auth?.user?.name?.charAt(0)?.toUpperCase() || '?' }}
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-white truncate">{{ $page.props.auth?.user?.name }}</p>
              <p class="text-xs text-green-200 truncate">{{ $page.props.auth?.user?.roles?.[0] || 'User' }}</p>
            </div>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main content wrapper -->
    <div class="lg:ml-64">
      <!-- Top navbar -->
      <nav class="fixed top-0 z-30 w-full lg:w-[calc(100%-16rem)] bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-3 py-3 lg:px-5 lg:pl-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <button @click="sidebarOpen = !sidebarOpen" type="button" class="inline-flex items-center p-2 text-sm text-gray-500 rounded-lg lg:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path clip-rule="evenodd" fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z"/></svg>
              </button>
              <div class="ml-2 lg:ml-0">
                <slot name="breadcrumb" />
              </div>
            </div>

            <div class="flex items-center gap-2">
              <!-- Dark mode toggle -->
              <button @click="toggleDarkMode" type="button" class="p-2 text-gray-500 rounded-lg hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 transition-colors" title="Toggle dark mode">
                <!-- Sun icon (shown in dark mode) -->
                <svg v-if="isDark" class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd" />
                </svg>
                <!-- Moon icon (shown in light mode) -->
                <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
                </svg>
              </button>

              <!-- Notifications -->
              <NotificationBell />

              <!-- User menu -->
              <div class="relative" ref="userMenuRef">
                <button @click="userMenuOpen = !userMenuOpen" type="button" class="flex items-center gap-2 text-sm rounded-full focus:ring-4 focus:ring-green-300 dark:focus:ring-green-700">
                  <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold" style="background-color: #007f3d;">
                    {{ $page.props.auth?.user?.name?.charAt(0)?.toUpperCase() || '?' }}
                  </div>
                  <span class="hidden md:inline-block text-gray-900 font-medium text-sm">{{ $page.props.auth?.user?.name }}</span>
                  <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div v-if="userMenuOpen" class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-100 divide-y divide-gray-100 z-50">
                  <div class="px-4 py-3">
                    <p class="text-sm font-medium text-gray-900">{{ $page.props.auth?.user?.name }}</p>
                    <p class="text-sm text-gray-500 truncate">{{ $page.props.auth?.user?.email }}</p>
                  </div>
                  <ul class="py-1">
                    <li>
                      <Link :href="route('profile')" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        My Profile
                      </Link>
                    </li>
                  </ul>
                  <div class="py-1">
                    <Link :href="route('logout')" method="post" as="button" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                      Sign out
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </nav>

      <!-- Toast notifications -->
      <Toast />

      <!-- Page content -->
      <main class="pt-16 min-h-screen">
        <div class="p-4 lg:p-6">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import SidebarNav from '@/Components/SidebarNav.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import Toast from '@/Components/Toast.vue';

const page = usePage();
const sidebarOpen = ref(false);
const userMenuOpen = ref(false);
const userMenuRef = ref(null);

// Dark mode
const isDark = ref(false);

const initDarkMode = () => {
  const stored = localStorage.getItem('tanod-dark-mode');
  if (stored === 'true' || (!stored && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    isDark.value = true;
    document.documentElement.classList.add('dark');
  } else {
    isDark.value = false;
    document.documentElement.classList.remove('dark');
  }
};

const toggleDarkMode = () => {
  isDark.value = !isDark.value;
  if (isDark.value) {
    document.documentElement.classList.add('dark');
    localStorage.setItem('tanod-dark-mode', 'true');
  } else {
    document.documentElement.classList.remove('dark');
    localStorage.setItem('tanod-dark-mode', 'false');
  }
};

const user = computed(() => page.props.auth?.user);
const roles = computed(() => user.value?.roles || []);

const hasRole = (...roleNames) => roleNames.some(r => roles.value.includes(r));

const route = window.route || ((name) => {
  const routes = {
    'dashboard': '/',
    'tractors.index': '/tractors',
    'devices.index': '/devices',
    'groups.index': '/groups',
    'bookings.index': '/bookings',
    'maintenance.index': '/maintenance',
    'distributions.index': '/distributions',
    'live-view.index': '/live-view',
    'alerts.index': '/alerts',
    'notifications.index': '/notifications',
    'geofences.index': '/geofences',
    'tickets.index': '/tickets',
    'feedback.index': '/feedback',
    'reports.index': '/reports',
    'support-contact.index': '/support-contact',
    'collectibles.index': '/collectibles',
    'miscellaneous.index': '/miscellaneous',
    'api-integration.index': '/api-integration',
    'users.index': '/users',
    'profile': '/profile',
    'logout': '/logout',
  };
  return routes[name] || '/';
});

const navigation = computed(() => {
  const items = [
    { name: 'Dashboard', href: route('dashboard'), icon: 'dashboard', show: true },
    { name: 'Live View', href: route('live-view.index'), icon: 'map', show: hasRole('super-admin', 'sub-admin', 'tps') },
    { name: 'Tractors', href: route('tractors.index'), icon: 'tractor', show: true },
    { name: 'Devices', href: route('devices.index'), icon: 'device', show: hasRole('super-admin', 'sub-admin', 'tps') },
    { name: 'Groups', href: route('groups.index'), icon: 'group', show: hasRole('super-admin', 'sub-admin') },
    { name: 'Bookings', href: route('bookings.index'), icon: 'calendar', show: true },
    { name: 'Maintenance', href: route('maintenance.index'), icon: 'wrench', show: hasRole('super-admin', 'sub-admin', 'tps') },
    { name: 'Distributions', href: route('distributions.index'), icon: 'share', show: hasRole('super-admin', 'sub-admin', 'tps') },
    { name: 'Geo-Fences', href: route('geofences.index'), icon: 'fence', show: hasRole('super-admin', 'sub-admin', 'tps') },
    { name: 'Alerts', href: route('alerts.index'), icon: 'alert', show: hasRole('super-admin', 'sub-admin', 'tps') },
    { name: 'Tickets', href: route('tickets.index'), icon: 'ticket', show: true },
    { name: 'Feedback', href: route('feedback.index'), icon: 'feedback', show: hasRole('super-admin', 'sub-admin', 'fca', 'farmer') },
    { name: 'Reports', href: route('reports.index'), icon: 'report', show: hasRole('super-admin', 'sub-admin', 'tps') },
    { name: 'Support Contact', href: route('support-contact.index'), icon: 'support', show: hasRole('super-admin', 'sub-admin') },
    { name: 'Collectibles', href: route('collectibles.index'), icon: 'collectible', show: hasRole('super-admin', 'sub-admin') },
    { name: 'Miscellaneous', href: route('miscellaneous.index'), icon: 'misc', show: hasRole('super-admin', 'sub-admin') },
    { name: 'API Integration', href: route('api-integration.index'), icon: 'api', show: hasRole('super-admin', 'sub-admin') },
    { name: 'Users', href: route('users.index'), icon: 'users', show: hasRole('super-admin', 'sub-admin') },
  ];
  return items.filter(i => i.show);
});

const handleClickOutside = (e) => {
  if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
    userMenuOpen.value = false;
  }
};
onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  initDarkMode();
});
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>
