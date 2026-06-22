<template>
  <GuestLayout>
    <Head title="419 — Session Expired" />

    <div class="w-full min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 px-4 py-12">
      <div class="max-w-lg w-full text-center">
        <!-- Hourglass SVG Illustration -->
        <div class="mb-8 flex justify-center">
          <svg class="w-64 h-64 md:w-80 md:h-80" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Hourglass outer frame -->
            <rect x="130" y="50" width="140" height="300" rx="16" fill="none" stroke="#f59e0b" stroke-width="5" class="dark:stroke-amber-500" />
            <rect x="140" y="60" width="120" height="130" rx="8" fill="#fef3c7" class="dark:fill-gray-800" />
            <rect x="140" y="210" width="120" height="130" rx="8" fill="#fef3c7" class="dark:fill-gray-800" />
            <!-- Sand in top chamber (draining) -->
            <path d="M140 140 L160 190 L240 190 L260 140 Z" fill="#f59e0b" class="dark:fill-amber-500" />
            <rect x="145" y="110" width="110" height="30" rx="6" fill="#d97706" class="dark:fill-amber-600" />
            <!-- Sand stream -->
            <line x1="200" y1="190" x2="200" y2="215" stroke="#f59e0b" stroke-width="4" class="dark:stroke-amber-500" />
            <!-- Sand in bottom chamber (accumulated) -->
            <path d="M140 260 L160 210 L240 210 L260 260 Z" fill="#f59e0b" class="dark:fill-amber-500" />
            <rect x="145" y="260" width="110" height="120" rx="6" fill="#d97706" class="dark:fill-amber-600" />
            <!-- Sand pile at bottom -->
            <path d="M150 340 L200 260 L250 340 Z" fill="#f59e0b" class="dark:fill-amber-500" />
            <!-- Crossbars -->
            <rect x="100" y="90" width="200" height="10" rx="5" fill="#f59e0b" class="dark:fill-amber-500" />
            <rect x="100" y="300" width="200" height="10" rx="5" fill="#f59e0b" class="dark:fill-amber-500" />
            <!-- Spilling sand particles -->
            <circle cx="190" cy="230" r="4" fill="#f59e0b" opacity="0.6" class="dark:fill-amber-500" />
            <circle cx="210" cy="235" r="3" fill="#d97706" opacity="0.5" class="dark:fill-amber-600" />
            <circle cx="205" cy="245" r="5" fill="#f59e0b" opacity="0.4" class="dark:fill-amber-500" />
          </svg>
        </div>

        <!-- Error Code -->
        <div class="text-8xl font-extrabold text-amber-500 dark:text-amber-400 mb-4">419</div>

        <!-- Title -->
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-3">Session Expired</h1>

        <!-- Description -->
        <p class="text-gray-600 dark:text-gray-400 max-w-md mx-auto mb-8 leading-relaxed">
          Your session has expired due to inactivity. Please refresh the page and log in again to continue.
        </p>

        <!-- Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
          <button @click="refreshPage" class="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-[#007f3d] hover:bg-green-800 rounded-lg transition-colors focus:ring-4 focus:ring-green-300">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Refresh Page
          </button>
          <Link href="/login" class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg transition-colors dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
            </svg>
            Go to Login
          </Link>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const refreshPage = () => {
  window.location.reload();
};
</script>
