<template>
  <GuestLayout>
    <Head title="500 — Server Error" />

    <div class="w-full min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 px-4 py-12">
      <div class="max-w-lg w-full text-center">
        <!-- Gear Wrench SVG Illustration -->
        <div class="mb-8 flex justify-center">
          <svg class="w-64 h-64 md:w-80 md:h-80" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Gear body -->
            <circle cx="180" cy="200" r="60" fill="none" stroke="#f97316" stroke-width="6" class="dark:stroke-orange-500" />
            <circle cx="180" cy="200" r="40" fill="none" stroke="#f97316" stroke-width="3" class="dark:stroke-orange-500" />
            <circle cx="180" cy="200" r="18" fill="#fff7ed" stroke="#f97316" stroke-width="4" class="dark:fill-gray-800 dark:stroke-orange-500" />
            <!-- Gear teeth -->
            <g class="dark:stroke-orange-500">
              <rect x="170" y="130" width="20" height="20" rx="4" fill="#f97316" stroke="#ea580c" stroke-width="2" />
              <rect x="170" y="250" width="20" height="20" rx="4" fill="#f97316" stroke="#ea580c" stroke-width="2" />
              <rect x="110" y="190" width="20" height="20" rx="4" fill="#f97316" stroke="#ea580c" stroke-width="2" />
              <rect x="230" y="190" width="20" height="20" rx="4" fill="#f97316" stroke="#ea580c" stroke-width="2" />
              <rect x="137" y="144" width="18" height="18" rx="4" fill="#f97316" stroke="#ea580c" stroke-width="2" transform="rotate(45 146 153)" />
              <rect x="205" y="212" width="18" height="18" rx="4" fill="#f97316" stroke="#ea580c" stroke-width="2" transform="rotate(45 214 221)" />
              <rect x="205" y="144" width="18" height="18" rx="4" fill="#f97316" stroke="#ea580c" stroke-width="2" transform="rotate(-45 214 153)" />
              <rect x="137" y="212" width="18" height="18" rx="4" fill="#f97316" stroke="#ea580c" stroke-width="2" transform="rotate(-45 146 221)" />
            </g>
            <!-- Wrench -->
            <g transform="translate(260, 240) rotate(-45)">
              <rect x="-12" y="-4" width="80" height="8" rx="4" fill="#9ca3af" stroke="#6b7280" stroke-width="2" class="dark:fill-gray-500 dark:stroke-gray-400" />
              <circle cx="72" cy="-8" r="14" fill="none" stroke="#9ca3af" stroke-width="6" class="dark:stroke-gray-500" />
              <circle cx="72" cy="-8" r="8" fill="#fff" class="dark:fill-gray-800" />
            </g>
            <!-- Exclamation mark -->
            <circle cx="180" cy="200" r="32" fill="white" class="dark:fill-gray-800" />
            <text x="180" y="215" text-anchor="middle" font-size="44" font-weight="800" fill="#f97316" class="dark:fill-orange-500">!</text>
          </svg>
        </div>

        <!-- Error Code -->
        <div class="text-8xl font-extrabold text-orange-500 dark:text-orange-400 mb-4">500</div>

        <!-- Title -->
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-3">Server Error</h1>

        <!-- Description -->
        <p class="text-gray-600 dark:text-gray-400 max-w-md mx-auto mb-8 leading-relaxed">
          Something went wrong on our end. Our team has been notified. Please try again later.
        </p>

        <!-- Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
          <button @click="reloadPage" class="inline-flex items-center px-6 py-3 text-sm font-medium text-white bg-[#007f3d] hover:bg-green-800 rounded-lg transition-colors focus:ring-4 focus:ring-green-300">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Try Again
          </button>
          <Link href="/" class="inline-flex items-center px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg transition-colors dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            Go Home
          </Link>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const reloadPage = () => {
  window.location.reload();
};
</script>
