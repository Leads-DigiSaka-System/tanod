<template>
  <GuestLayout>
    <Head title="Terms & Conditions — TanodTractor" />

    <div class="w-full min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center px-4 py-12">
      <div class="max-w-2xl w-full bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/60 dark:border-gray-700/40 shadow-sm overflow-hidden">
        <div class="px-6 sm:px-10 py-8 border-b border-gray-100 dark:border-gray-700/40" style="background: linear-gradient(135deg, #007f3d 0%, #0d4f0d 65%, #1a6e1a 100%);">
          <h1 class="text-xl sm:text-2xl font-bold text-white">Terms &amp; Conditions</h1>
          <p class="text-green-200 text-xs sm:text-sm mt-0.5">TanodTractor &middot; Tractor Monitoring &amp; Management System</p>
        </div>

        <div class="px-6 sm:px-10 py-10 text-center">
          <div class="flex justify-center mb-4">
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center bg-green-50 dark:bg-green-900/30">
              <svg class="w-7 h-7 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
          </div>
          <h2 class="text-lg font-bold text-gray-900 dark:text-white">Content coming soon</h2>
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
            Our Terms &amp; Conditions are currently being prepared. Please check back soon, or contact us if you have any
            questions about the use of the TanodTractor platform.
          </p>

          <div class="mt-8">
            <Link href="/login" class="inline-flex items-center gap-2 text-sm font-semibold text-green-700 dark:text-green-400 hover:underline">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
              Back to Sign In
            </Link>
          </div>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
</script>
