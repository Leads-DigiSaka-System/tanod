<template>
  <AppLayout>
    <Head title="Dashboard" />

    <!-- Page header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Welcome back, {{ $page.props.auth?.user?.name }}. Here's your overview.</p>
    </div>

    <!-- ═══════════ ADMIN / SUB-ADMIN DASHBOARD ═══════════ -->
    <template v-if="charts">

      <!-- KPI Stat Cards – Row 1: Tractors -->
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Tractors</span>
            <div class="bg-indigo-500 rounded-lg p-2"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 6h3l4 4v6h-2"/></svg></div>
          </div>
          <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.totalTractors }}</h3>
          <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">All registered tractors</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Online</span>
            <div class="bg-green-500 rounded-lg p-2"><svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="10" r="6"/></svg></div>
          </div>
          <h3 class="text-3xl font-bold text-green-600 dark:text-green-400">{{ stats.onlineTractors }}</h3>
          <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Currently transmitting GPS</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Offline</span>
            <div class="bg-red-500 rounded-lg p-2"><svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"/></svg></div>
          </div>
          <h3 class="text-3xl font-bold text-red-600 dark:text-red-400">{{ stats.offlineTractors }}</h3>
          <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Device offline / no signal</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Inactive</span>
            <div class="bg-gray-400 rounded-lg p-2"><svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg></div>
          </div>
          <h3 class="text-3xl font-bold text-gray-500 dark:text-gray-400">{{ stats.inactiveTractors }}</h3>
          <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">No device / no location data</p>
        </div>
      </div>

      <!-- KPI Stat Cards – Row 2: Operations -->
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-7 mb-6">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
          <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Machine Hours</span>
          <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ Number(stats.totalMachineHours || 0).toLocaleString(undefined, { maximumFractionDigits: 1 }) }}</p>
          <p class="text-xs text-indigo-600 dark:text-indigo-400"></p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
          <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Devices</span>
          <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.totalDevices }}</p>
          <p class="text-xs text-green-600 dark:text-green-400">{{ stats.onlineDevices }} online</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
          <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Users</span>
          <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.totalUsers }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
          <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Pending Bookings</span>
          <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ stats.pendingBookings }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
          <span class="text-xs font-medium text-gray-500 dark:text-gray-400">PMS Due</span>
          <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">{{ stats.maintenanceDue }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
          <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Alerts</span>
          <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ stats.unacknowledgedAlerts }}</p>
          <p class="text-xs text-gray-400 dark:text-gray-500">of {{ stats.totalAlerts }} total</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
          <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Geo-Fences</span>
          <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.totalGeoFences }}</p>
          <p class="text-xs text-green-600 dark:text-green-400">{{ stats.activeGeoFences }} active</p>
        </div>
      </div>

      <!-- Charts Row 1: Tractor Status Donut + Alerts Trend Line -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
        <!-- Tractor Status Donut -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tractor Status</h3>
          <apexchart type="donut" height="280" :options="tractorDonutOptions" :series="tractorDonutSeries" />
        </div>

        <!-- Alerts Trend (7 days) -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Alerts &mdash; Last 7 Days</h3>
          <apexchart type="area" height="280" :options="alertsTrendOptions" :series="alertsTrendSeries" />
        </div>
      </div>

      <!-- Charts Row 2: Bookings Status Bar + Bookings Trend -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
        <!-- Bookings by Status -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Bookings by Status</h3>
          <apexchart type="bar" height="280" :options="bookingBarOptions" :series="bookingBarSeries" />
        </div>

        <!-- Bookings Trend (7 days) -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Bookings &mdash; Last 7 Days</h3>
          <apexchart type="line" height="280" :options="bookingsTrendOptions" :series="bookingsTrendSeries" />
        </div>
      </div>

      <!-- Charts Row 3: Maintenance Status + Alerts by Type + Tractors by Group -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 mb-6">
        <!-- PMS by Status -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">PMS by Status</h3>
          <apexchart v-if="maintenancePieSeries.length" type="pie" height="260" :options="maintenancePieOptions" :series="maintenancePieSeries" />
          <div v-else class="flex items-center justify-center h-48 text-sm text-gray-400 dark:text-gray-500">No maintenance data</div>
        </div>

        <!-- Alerts by Type -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Alerts by Type</h3>
          <apexchart v-if="alertTypePieSeries.length" type="pie" height="260" :options="alertTypePieOptions" :series="alertTypePieSeries" />
          <div v-else class="flex items-center justify-center h-48 text-sm text-gray-400 dark:text-gray-500">No alert data</div>
        </div>

        <!-- Tractors by Group -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tractors by Group</h3>
          <apexchart v-if="charts.tractorsByGroup?.length" type="bar" height="260" :options="groupBarOptions" :series="groupBarSeries" />
          <div v-else class="flex items-center justify-center h-48 text-sm text-gray-400 dark:text-gray-500">No group data</div>
        </div>
      </div>

      <!-- Data Tables Row -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Recent Alerts -->
        <div v-if="recentAlerts?.length" class="bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
              Unacknowledged Alerts
            </h3>
            <Link href="/alerts" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">View all</Link>
          </div>
          <div class="p-5 space-y-3">
            <div v-for="alert in recentAlerts" :key="alert.id" class="flex items-start gap-3 p-3 rounded-lg bg-red-50 dark:bg-red-900/20">
              <svg class="h-5 w-5 text-red-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-red-800 dark:text-red-400 truncate">{{ alert.title }}</p>
                <p class="text-xs text-red-600 dark:text-red-500">{{ alert.device?.device_name || alert.tractor?.no_plate }} &mdash; {{ timeAgo(alert.created_at) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Bookings -->
        <div v-if="recentBookings?.length" class="bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-indigo-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
              Recent Bookings
            </h3>
            <Link href="/bookings" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">View all</Link>
          </div>
          <div class="p-5 space-y-3">
            <div v-for="booking in recentBookings" :key="booking.id" class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700">
              <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ booking.tractor?.no_plate }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ booking.booked_by?.name }} &mdash; {{ booking.booking_date }}</p>
              </div>
              <StatusBadge :status="booking.status" />
            </div>
          </div>
        </div>

        <!-- Maintenance Due -->
        <div v-if="maintenanceDueList?.length" class="bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-orange-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
              PMS Due
            </h3>
            <Link href="/maintenance" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">View all</Link>
          </div>
          <div class="p-5 space-y-3">
            <div v-for="tractor in maintenanceDueList" :key="tractor.id" class="flex items-center justify-between p-3 rounded-lg bg-orange-50 dark:bg-orange-900/20">
              <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ tractor.no_plate }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ tractor.brand }} {{ tractor.model }} &mdash; {{ tractor.total_distance?.toLocaleString() }} km</p>
              </div>
              <span class="bg-orange-100 text-orange-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-orange-900 dark:text-orange-300">Due</span>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- ═══════════ NON-ADMIN FALLBACK (TPS / FCA / Farmer) ═══════════ -->
    <template v-else>
      <!-- Stats Grid -->
      <div v-if="stats" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
        <StatCard v-for="(value, key) in stats" :key="key" :title="formatTitle(key)" :value="value" :color="getStatColor(key)" />
      </div>

      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Pending Bookings (FCA) -->
        <div v-if="pendingBookings?.length" class="bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
              Pending Bookings
            </h3>
          </div>
          <div class="p-5 space-y-3">
            <div v-for="booking in pendingBookings" :key="booking.id" class="flex items-center justify-between p-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20">
              <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ booking.tractor?.no_plate }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ booking.booked_by?.name }} &mdash; {{ booking.booking_date }}</p>
              </div>
              <Link :href="`/bookings/${booking.id}`" class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">Review</Link>
            </div>
          </div>
        </div>

        <!-- My TPS Responsibilities -->
        <div v-if="myTractors?.length" class="bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
          <div class="p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">My TPS Responsibilities</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Your assigned tractors for coordination. The full fleet remains available in the Tractors module.</p>
          </div>
          <div class="p-5 space-y-3">
            <div v-for="tractor in myTractors" :key="tractor.id" class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700">
              <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ tractor.no_plate }} — {{ tractor.brand }} {{ tractor.model }}</p>
                <StatusBadge :status="tractor.device?.latest_location ? 'online' : 'offline'" :show-dot="true" />
              </div>
              <Link :href="`/tractors/${tractor.id}`" class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">View</Link>
            </div>
          </div>
        </div>

        <!-- My Bookings (Farmer) -->
        <div v-if="myBookings?.length" class="bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">My Bookings</h3>
          </div>
          <div class="p-5 space-y-3">
            <div v-for="booking in myBookings" :key="booking.id" class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700">
              <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ booking.tractor?.no_plate }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ booking.booking_date }} &mdash; {{ booking.purpose }}</p>
              </div>
              <StatusBadge :status="booking.status" />
            </div>
          </div>
        </div>

        <!-- Available Tractors (Farmer) -->
        <div v-if="availableTractors?.length" class="bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Available Tractors</h3>
          </div>
          <div class="p-5 space-y-3">
            <div v-for="tractor in availableTractors" :key="tractor.id" class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-700">
              <div>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ tractor.no_plate }} — {{ tractor.brand }} {{ tractor.model }}</p>
              </div>
              <Link :href="`/bookings/create?tractor=${tractor.id}`" class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">Book</Link>
            </div>
          </div>
        </div>
      </div>
    </template>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import VueApexCharts from 'vue3-apexcharts';
const apexchart = VueApexCharts;

const props = defineProps({
  stats: Object,
  charts: Object,
  recentAlerts: Array,
  recentBookings: Array,
  maintenanceDueList: Array,
  pendingBookings: Array,
  myTractors: Array,
  pendingTasks: Array,
  myBookings: Array,
  availableTractors: Array,
  recentFeedback: Array,
});

// ── Helper ──
const timeAgo = (dateStr) => {
  if (!dateStr) return '';
  const seconds = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
  if (seconds < 60) return 'just now';
  if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
  if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
  return Math.floor(seconds / 86400) + 'd ago';
};

const formatTitle = (key) => key.replace(/([A-Z])/g, ' $1').replace(/^./, s => s.toUpperCase()).trim();
const getStatColor = (key) => {
  if (key.includes('pending') || key.includes('Pending')) return 'yellow';
  if (key.includes('active') || key.includes('Active') || key.includes('online') || key.includes('Online')) return 'green';
  if (key.includes('alert') || key.includes('Alert')) return 'red';
  return 'indigo';
};

// ── Shared chart theme ──
const chartForeColor = '#9ca3af';

// ── Tractor Status Donut ──
const tractorDonutSeries = computed(() => {
  const ts = props.charts?.tractorStatus || {};
  return [ts.online || 0, ts.offline || 0, ts.inactive || 0];
});
const tractorDonutOptions = computed(() => ({
  chart: { type: 'donut', foreColor: chartForeColor },
  labels: ['Online', 'Offline', 'Inactive'],
  colors: ['#10b981', '#ef4444', '#9ca3af'],
  plotOptions: { pie: { donut: { size: '60%', labels: { show: true, name: { show: true }, value: { show: true, fontSize: '22px', fontWeight: 700 }, total: { show: true, label: 'Total', fontSize: '14px' } } } } },
  legend: { position: 'bottom', labels: { colors: chartForeColor } },
  stroke: { width: 0 },
  dataLabels: { enabled: false },
}));

// ── Alerts Trend (Area) ──
const alertsTrendSeries = computed(() => [{
  name: 'Alerts',
  data: (props.charts?.alertsTrend || []).map(d => d.count),
}]);
const alertsTrendOptions = computed(() => ({
  chart: { type: 'area', foreColor: chartForeColor, toolbar: { show: false }, sparkline: { enabled: false } },
  xaxis: { categories: (props.charts?.alertsTrend || []).map(d => d.date) },
  yaxis: { min: 0, forceNiceScale: true },
  colors: ['#ef4444'],
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 100] } },
  stroke: { curve: 'smooth', width: 2 },
  dataLabels: { enabled: false },
  grid: { borderColor: '#374151', strokeDashArray: 4 },
  tooltip: { theme: 'dark' },
}));

// ── Bookings by Status (Bar) ──
const bookingBarSeries = computed(() => {
  const bs = props.charts?.bookingsByStatus || {};
  return [{ name: 'Bookings', data: Object.values(bs) }];
});
const bookingStatusColors = { pending: '#f59e0b', approved: '#10b981', rejected: '#ef4444', cancelled: '#6b7280', completed: '#6366f1', in_use: '#8b5cf6' };
const bookingBarOptions = computed(() => {
  const bs = props.charts?.bookingsByStatus || {};
  const labels = Object.keys(bs).map(k => k.replace(/_/g, ' '));
  const colors = Object.keys(bs).map(k => bookingStatusColors[k] || '#6366f1');
  return {
    chart: { type: 'bar', foreColor: chartForeColor, toolbar: { show: false } },
    plotOptions: { bar: { borderRadius: 4, horizontal: false, distributed: true, columnWidth: '55%' } },
    xaxis: { categories: labels },
    yaxis: { min: 0, forceNiceScale: true },
    colors: colors,
    legend: { show: false },
    dataLabels: { enabled: true, style: { fontSize: '12px', fontWeight: 700 } },
    grid: { borderColor: '#374151', strokeDashArray: 4 },
    tooltip: { theme: 'dark' },
  };
});

// ── Bookings Trend (Line) ──
const bookingsTrendSeries = computed(() => [{
  name: 'Bookings',
  data: (props.charts?.bookingsTrend || []).map(d => d.count),
}]);
const bookingsTrendOptions = computed(() => ({
  chart: { type: 'line', foreColor: chartForeColor, toolbar: { show: false } },
  xaxis: { categories: (props.charts?.bookingsTrend || []).map(d => d.date) },
  yaxis: { min: 0, forceNiceScale: true },
  colors: ['#6366f1'],
  stroke: { curve: 'smooth', width: 3 },
  markers: { size: 4, colors: ['#6366f1'], strokeColors: '#fff', strokeWidth: 2 },
  dataLabels: { enabled: false },
  grid: { borderColor: '#374151', strokeDashArray: 4 },
  tooltip: { theme: 'dark' },
}));

// ── PMS by Status (Pie) ──
const maintenancePieSeries = computed(() => Object.values(props.charts?.maintenanceByStatus || {}));
const maintenanceStatusColors = { documentation: '#f59e0b', scheduled: '#3b82f6', in_progress: '#8b5cf6', completed: '#10b981', cancelled: '#6b7280' };
const maintenancePieOptions = computed(() => {
  const ms = props.charts?.maintenanceByStatus || {};
  return {
    chart: { type: 'pie', foreColor: chartForeColor },
    labels: Object.keys(ms).map(k => k.replace(/_/g, ' ')),
    colors: Object.keys(ms).map(k => maintenanceStatusColors[k] || '#6366f1'),
    legend: { position: 'bottom', labels: { colors: chartForeColor } },
    stroke: { width: 0 },
    dataLabels: { enabled: true, formatter: (val) => val.toFixed(0) + '%' },
  };
});

// ── Alerts by Type (Pie) ──
const alertTypePieSeries = computed(() => Object.values(props.charts?.alertsByType || {}));
const alertTypePieOptions = computed(() => {
  const at = props.charts?.alertsByType || {};
  return {
    chart: { type: 'pie', foreColor: chartForeColor },
    labels: Object.keys(at).map(k => k.replace(/_/g, ' ')),
    colors: ['#ef4444', '#f59e0b', '#3b82f6', '#8b5cf6', '#10b981', '#ec4899'],
    legend: { position: 'bottom', labels: { colors: chartForeColor } },
    stroke: { width: 0 },
    dataLabels: { enabled: true, formatter: (val) => val.toFixed(0) + '%' },
  };
});

// ── Tractors by Group (Horizontal Bar) ──
const groupBarSeries = computed(() => [{
  name: 'Tractors',
  data: (props.charts?.tractorsByGroup || []).map(g => g.count),
}]);
const groupBarOptions = computed(() => ({
  chart: { type: 'bar', foreColor: chartForeColor, toolbar: { show: false } },
  plotOptions: { bar: { borderRadius: 4, horizontal: true } },
  xaxis: { categories: (props.charts?.tractorsByGroup || []).map(g => g.name.length > 18 ? g.name.substring(0, 18) + '...' : g.name) },
  yaxis: { labels: { style: { fontSize: '11px' } } },
  colors: ['#6366f1'],
  dataLabels: { enabled: true, style: { fontSize: '11px' } },
  grid: { borderColor: '#374151', strokeDashArray: 4 },
  tooltip: { theme: 'dark' },
}));
</script>
