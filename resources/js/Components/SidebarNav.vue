<template>
  <nav class="flex-1 space-y-1">
    <ul class="space-y-1">
      <li v-for="item in navigation" :key="item.name">
        <Link
          :href="item.href"
          :class="[
            isActive(item.href)
              ? 'bg-white/20 text-white font-semibold' 
              : 'text-green-100 hover:bg-white/10 hover:text-white',
            'flex items-center p-2 rounded-lg group transition-colors duration-150'
          ]"
        >
          <!-- Dashboard -->
          <svg v-if="item.icon === 'dashboard'" class="w-5 h-5 transition duration-75" :class="isActive(item.href) ? 'text-yellow-400' : 'text-green-200 group-hover:text-white'" fill="currentColor" viewBox="0 0 22 21">
            <path d="M16.975 11H10V4.025a1 1 0 0 0-1.066-.998 8.5 8.5 0 1 0 9.039 9.039.999.999 0 0 0-1-1.066h.002ZM12.5 0c-.157 0-.311.01-.565.027A1 1 0 0 0 11 1.02V10h8.975a1 1 0 0 0 1-.935c.013-.188.028-.374.028-.565A8.51 8.51 0 0 0 12.5 0Z"/>
          </svg>
          <!-- Map / Live View -->
          <svg v-else-if="item.icon === 'map'" class="w-5 h-5 transition duration-75" :class="isActive(item.href) ? 'text-yellow-400' : 'text-green-200 group-hover:text-white'" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
          </svg>
          <!-- Tractor -->
          <svg v-else-if="item.icon === 'tractor'" class="w-5 h-5 transition duration-75" :class="isActive(item.href) ? 'text-yellow-400' : 'text-green-200 group-hover:text-white'" fill="currentColor" viewBox="0 0 20 20">
            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/>
            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-1h3.05a2.5 2.5 0 014.9 0H19a1 1 0 001-1v-5a1 1 0 00-.293-.707l-3-3A1 1 0 0016 4H3z"/>
          </svg>
          <!-- Device / Wifi -->
          <svg v-else-if="item.icon === 'device'" class="w-5 h-5 transition duration-75" :class="isActive(item.href) ? 'text-yellow-400' : 'text-green-200 group-hover:text-white'" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M9.706 8.646a.25.25 0 01.588 0l1.758 4.832a.25.25 0 01-.23.354H8.178a.25.25 0 01-.23-.354l1.758-4.832zM10 1.667c-2.667 0-5.111 1.015-6.946 2.685a.5.5 0 00.679.735A8.165 8.165 0 0110 3.167c2.346 0 4.488.783 6.267 1.92a.5.5 0 00.679-.735C15.11 2.682 12.667 1.667 10 1.667zM6.104 6.95a5.53 5.53 0 017.792 0 .5.5 0 00.708-.708 6.53 6.53 0 00-9.208 0 .5.5 0 00.708.708z" clip-rule="evenodd"/>
          </svg>
          <!-- Group / Folder -->
          <svg v-else-if="item.icon === 'group'" class="w-5 h-5 transition duration-75" :class="isActive(item.href) ? 'text-yellow-400' : 'text-green-200 group-hover:text-white'" fill="currentColor" viewBox="0 0 20 20">
            <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
          </svg>
          <!-- Calendar / Bookings -->
          <svg v-else-if="item.icon === 'calendar'" class="w-5 h-5 transition duration-75" :class="isActive(item.href) ? 'text-yellow-400' : 'text-green-200 group-hover:text-white'" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
          </svg>
          <!-- Wrench / Maintenance -->
          <svg v-else-if="item.icon === 'wrench'" class="w-5 h-5 transition duration-75" :class="isActive(item.href) ? 'text-yellow-400' : 'text-green-200 group-hover:text-white'" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
          </svg>
          <!-- Share / Distributions -->
          <svg v-else-if="item.icon === 'share'" class="w-5 h-5 transition duration-75" :class="isActive(item.href) ? 'text-yellow-400' : 'text-green-200 group-hover:text-white'" fill="currentColor" viewBox="0 0 20 20">
            <path d="M15 8a3 3 0 10-2.977-2.63l-4.94 2.47a3 3 0 100 4.319l4.94 2.47a3 3 0 10.895-1.789l-4.94-2.47a3.027 3.027 0 000-.74l4.94-2.47C13.456 7.68 14.19 8 15 8z"/>
          </svg>
          <!-- Fence / Geo-Fences -->
          <svg v-else-if="item.icon === 'fence'" class="w-5 h-5 transition duration-75" :class="isActive(item.href) ? 'text-yellow-400' : 'text-green-200 group-hover:text-white'" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
          </svg>
          <!-- Alert / Bell -->
          <svg v-else-if="item.icon === 'alert'" class="w-5 h-5 transition duration-75" :class="isActive(item.href) ? 'text-yellow-400' : 'text-green-200 group-hover:text-white'" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
          </svg>
          <!-- Ticket -->
          <svg v-else-if="item.icon === 'ticket'" class="w-5 h-5 transition duration-75" :class="isActive(item.href) ? 'text-yellow-400' : 'text-green-200 group-hover:text-white'" fill="currentColor" viewBox="0 0 20 20">
            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/>
            <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"/>
          </svg>
          <!-- Feedback -->
          <svg v-else-if="item.icon === 'feedback'" class="w-5 h-5 transition duration-75" :class="isActive(item.href) ? 'text-yellow-400' : 'text-green-200 group-hover:text-white'" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 13V5a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2h3l3 3 3-3h3a2 2 0 002-2zM5 7a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm1 3a1 1 0 100 2h3a1 1 0 100-2H6z" clip-rule="evenodd"/>
          </svg>
          <!-- Report / Chart -->
          <svg v-else-if="item.icon === 'report'" class="w-5 h-5 transition duration-75" :class="isActive(item.href) ? 'text-yellow-400' : 'text-green-200 group-hover:text-white'" fill="currentColor" viewBox="0 0 20 20">
            <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"/><path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"/>
          </svg>
          <!-- Support Contact -->
          <svg v-else-if="item.icon === 'support'" class="w-5 h-5 transition duration-75" :class="isActive(item.href) ? 'text-yellow-400' : 'text-green-200 group-hover:text-white'" fill="currentColor" viewBox="0 0 20 20">
            <path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-2 0c0 .993-.241 1.929-.668 2.754l1.524 1.524a9.05 9.05 0 00.923-4.278c0-1.555-.391-3.018-1.078-4.294l-1.522 1.522A5.973 5.973 0 0116 10zm-5.35-4.744a.75.75 0 00-1.3 0l-2.5 4.33a.75.75 0 00.65 1.124h5a.75.75 0 00.65-1.124l-2.5-4.33zM10 12.5a2 2 0 100 4 2 2 0 000-4z"/>
          </svg>
          <!-- Users -->
          <svg v-else-if="item.icon === 'users'" class="w-5 h-5 transition duration-75" :class="isActive(item.href) ? 'text-yellow-400' : 'text-green-200 group-hover:text-white'" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
          </svg>
          <!-- Fallback -->
          <svg v-else class="w-5 h-5 transition duration-75" :class="isActive(item.href) ? 'text-yellow-400' : 'text-green-200 group-hover:text-white'" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 2a8 8 0 100 16 8 8 0 000-16zm1 11H9v-2h2v2zm0-4H9V5h2v4z"/>
          </svg>
          <span class="ml-3 text-sm font-medium">{{ item.name }}</span>
        </Link>
      </li>
    </ul>
  </nav>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
  navigation: Array,
});

const page = usePage();

const isActive = (href) => {
  const raw = page.url;
  // Normalize to pathname only (handles both full URLs and relative paths)
  let path = raw;
  if (raw.startsWith('http://') || raw.startsWith('https://')) {
    path = new URL(raw).pathname;
  }
  // Remove trailing slash for consistent matching
  if (path !== '/') path = path.replace(/\/+$/, '');

  const target = href === '/' ? '/' : href.replace(/\/+$/, '');
  if (target === '/') return path === '/';
  return path === target || path.startsWith(target + '/');
};
</script>
