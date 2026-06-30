<template>
  <AppLayout>
    <Head title="Collectibles" />

    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Collectibles</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage collectible items and records.</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200/60 shadow-sm p-12 dark:bg-gray-800/60 dark:border-gray-700/50">
      <div class="flex flex-col items-center justify-center text-center">
        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 mb-4">
          <svg class="w-8 h-8 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
          </svg>
        </div>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Collectibles</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md">This page is under construction. Check back soon!</p>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
</script>
