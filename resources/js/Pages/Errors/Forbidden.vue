<template>
  <GuestLayout>
    <Head title="403 — Access Denied" />

    <div class="w-full min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 px-4 py-12">
      <div class="max-w-lg w-full text-center">
        <!-- Shield Lock SVG Illustration -->
        <div class="mb-8 flex justify-center">
          <svg class="w-64 h-64 md:w-80 md:h-80" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Shield body -->
            <path d="M200 40 L330 100 L330 220 C330 300 270 360 200 380 C130 360 70 300 70 220 L70 100 Z"
              fill="#eef2ff" stroke="#4f46e5" stroke-width="4" class="dark:fill-gray-800 dark:stroke-indigo-500" />
            <path d="M200 40 L330 100 L330 220 C330 300 270 360 200 380 C130 360 70 300 70 220 L70 100 Z"
              fill="url(#shieldGrad)" />
            <defs>
              <linearGradient id="shieldGrad" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#e0e7ff" stop-opacity="0.6" />
                <stop offset="100%" stop-color="#eef2ff" stop-opacity="0" />
              </linearGradient>
            </defs>
            <!-- Lock body -->
            <rect x="160" y="170" width="80" height="70" rx="10" fill="#4f46e5" class="dark:fill-indigo-500" />
            <!-- Lock shackle -->
            <path d="M175 170 L175 140 C175 115 225 115 225 140 L225 170"
              fill="none" stroke="#4f46e5" stroke-width="10" stroke-linecap="round" class="dark:stroke-indigo-500" />
            <!-- Keyhole -->
            <circle cx="200" cy="200" r="10" fill="white" />
            <rect x="196" y="200" width="8" height="16" rx="2" fill="white" />
            <!-- X mark overlay -->
            <line x1="130" y1="300" x2="270" y2="140" stroke="#ef4444" stroke-width="8" stroke-linecap="round" opacity="0.8" class="dark:stroke-red-400" />
            <line x1="270" y1="300" x2="130" y2="140" stroke="#ef4444" stroke-width="8" stroke-linecap="round" opacity="0.8" class="dark:stroke-red-400" />
          </svg>
        </div>

        <!-- Error Code -->
        <div class="text-8xl font-extrabold text-indigo-600 dark:text-indigo-400 mb-4">403</div>

        <!-- Title -->
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-3">Access Denied</h1>

        <!-- Description -->
        <p class="text-gray-600 dark:text-gray-400 max-w-md mx-auto mb-8 leading-relaxed">
          You don't have permission to access this page. If you believe this is a mistake, please contact your administrator.
        </p>

        <!-- Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
          <Link v-if="isAuthenticated" href="/" class="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-[#007f3d] hover:bg-green-800 rounded-lg transition-colors focus:ring-4 focus:ring-green-300">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Go to Dashboard
          </Link>
          <Link v-else href="/login" class="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-[#007f3d] hover:bg-green-800 rounded-lg transition-colors focus:ring-4 focus:ring-green-300">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
            </svg>
            Go to Login
          </Link>
          <button @click="goBack" class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg transition-colors dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Go Back
          </button>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const page = usePage();

const isAuthenticated = computed(() => {
  return !!(page.props?.auth?.user);
});

const goBack = () => {
  window.history.back();
};
</script>
