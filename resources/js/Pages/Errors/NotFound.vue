<template>
  <GuestLayout>
    <Head title="404 — Page Not Found" />

    <div class="w-full min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 px-4 py-12">
      <div class="max-w-lg w-full text-center">
        <!-- Magnifying Glass SVG Illustration -->
        <div class="mb-8 flex justify-center">
          <svg class="w-64 h-64 md:w-80 md:h-80" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Document outline -->
            <rect x="60" y="40" width="220" height="280" rx="12" fill="#f0fdf4" stroke="#007f3d" stroke-width="3" class="dark:fill-gray-800 dark:stroke-green-500" />
            <rect x="60" y="40" width="220" height="280" rx="12" fill="url(#docGrad)" />
            <defs>
              <linearGradient id="docGrad" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0%" stop-color="#dcfce7" stop-opacity="0.6" />
                <stop offset="100%" stop-color="#f0fdf4" stop-opacity="0" />
              </linearGradient>
            </defs>
            <!-- Document lines -->
            <rect x="90" y="80" width="140" height="10" rx="5" fill="#bbf7d0" class="dark:fill-green-900" />
            <rect x="90" y="105" width="110" height="10" rx="5" fill="#bbf7d0" class="dark:fill-green-900" />
            <rect x="90" y="130" width="160" height="10" rx="5" fill="#bbf7d0" class="dark:fill-green-900" />
            <rect x="90" y="155" width="90" height="10" rx="5" fill="#bbf7d0" class="dark:fill-green-900" />
            <rect x="90" y="185" width="140" height="10" rx="5" fill="#d1fae5" class="dark:fill-green-800" />
            <rect x="90" y="210" width="120" height="10" rx="5" fill="#d1fae5" class="dark:fill-green-800" />
            <!-- Magnifying glass -->
            <circle cx="290" cy="200" r="75" fill="none" stroke="#007f3d" stroke-width="8" class="dark:stroke-green-500" />
            <circle cx="290" cy="200" r="55" fill="#dcfce7" opacity="0.7" class="dark:fill-green-900/50" />
            <!-- Handle -->
            <line x1="340" y1="255" x2="370" y2="310" stroke="#007f3d" stroke-width="10" stroke-linecap="round" class="dark:stroke-green-500" />
            <!-- Question mark in glass -->
            <text x="290" y="218" text-anchor="middle" font-size="64" font-weight="800" fill="#007f3d" class="dark:fill-green-500">?</text>
          </svg>
        </div>

        <!-- Error Code -->
        <div class="text-8xl font-extrabold text-[#007f3d] dark:text-green-500 mb-4">404</div>

        <!-- Title -->
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-3">Page Not Found</h1>

        <!-- Description -->
        <p class="text-gray-600 dark:text-gray-400 max-w-md mx-auto mb-8 leading-relaxed">
          The page you're looking for doesn't exist or has been moved. Please check the URL or navigate back home.
        </p>

        <!-- Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
          <Link href="/" class="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-[#007f3d] hover:bg-green-800 rounded-lg transition-colors focus:ring-4 focus:ring-green-300">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Go to Dashboard
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
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const goBack = () => {
  window.history.back();
};
</script>
