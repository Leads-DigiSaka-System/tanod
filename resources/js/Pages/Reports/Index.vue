<template>
  <AppLayout>
    <Head title="Reports" />

    <!-- Page Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Reports</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Access detailed reports and analytics for your fleet</p>
    </div>

    <!-- Report Cards -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <Link href="/reports/tractor-usage" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition group">
        <div class="flex items-center gap-4">
          <div class="h-12 w-12 rounded-lg bg-blue-100 dark:bg-blue-900 flex items-center justify-center shrink-0">
            <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400">Tractor Usage</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Distance, hours, and utilization</p>
          </div>
        </div>
      </Link>

      <Link href="/reports/maintenance-summary" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition group">
        <div class="flex items-center gap-4">
          <div class="h-12 w-12 rounded-lg bg-amber-100 dark:bg-amber-900 flex items-center justify-center shrink-0">
            <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400">Maintenance Summary</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Costs, frequency, and overdue</p>
          </div>
        </div>
      </Link>

      <Link href="/reports/booking-summary" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition group">
        <div class="flex items-center gap-4">
          <div class="h-12 w-12 rounded-lg bg-green-100 dark:bg-green-900 flex items-center justify-center shrink-0">
            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400">Booking Summary</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Bookings by status and tractor</p>
          </div>
        </div>
      </Link>

      <Link href="/reports/device-status" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition group">
        <div class="flex items-center gap-4">
          <div class="h-12 w-12 rounded-lg bg-purple-100 dark:bg-purple-900 flex items-center justify-center shrink-0">
            <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400">Device Status</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Online/offline and sync stats</p>
          </div>
        </div>
      </Link>

      <Link href="/reports/alerts-history" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition group">
        <div class="flex items-center gap-4">
          <div class="h-12 w-12 rounded-lg bg-red-100 dark:bg-red-900 flex items-center justify-center shrink-0">
            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400">Alerts History</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Filter by type, status, and date</p>
          </div>
        </div>
      </Link>

      <Link href="/reports/ticket-summary" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700 hover:shadow-md transition group">
        <div class="flex items-center gap-4">
          <div class="h-12 w-12 rounded-lg bg-sky-100 dark:bg-sky-900 flex items-center justify-center shrink-0">
            <svg class="h-6 w-6 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400">Ticket Summary</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Resolution times and priorities</p>
          </div>
        </div>
      </Link>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
</script>
