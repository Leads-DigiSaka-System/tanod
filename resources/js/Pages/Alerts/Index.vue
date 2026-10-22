<template>
  <AppLayout>
    <Head title="Alerts" />

    <!-- Page Header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Alerts</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Monitor and manage device alerts and notifications.</p>
      </div>
      <div class="flex items-center gap-3 mt-3 sm:mt-0">
        <Link v-if="hasActiveFilters" href="/alerts"
          class="text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-4 py-2.5 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
          Clear Filters
        </Link>
        <Link href="/alerts/acknowledge-all" method="post" as="button"
          class="inline-flex items-center gap-2 text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          Acknowledge All
        </Link>
        <button @click="purgeOldAlerts" :disabled="purgeProcessing"
          class="inline-flex items-center gap-2 text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800 disabled:opacity-50 disabled:cursor-not-allowed">
          <svg v-if="purgeProcessing" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
          {{ purgeProcessing ? 'Cleaning...' : 'Clean Old Alerts' }}
        </button>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-xl border border-gray-200 p-4 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCount(totalAlerts) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Total Alerts</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ formatCount(totalUnacknowledged) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Unacknowledged</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatCount(totalAlerts - totalUnacknowledged) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Acknowledged</p>
          </div>
        </div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-4 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
          </div>
          <div>
            <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ formatCount(typeCount) }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">Alert Types</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Card -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
      <!-- Type Tabs -->
      <div class="flex border-b border-gray-200 dark:border-gray-700 px-2 overflow-x-auto scrollbar-hide">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="setTypeFilter(tab.key)"
          :class="[
            'relative flex items-center gap-2 px-4 py-3 text-sm font-medium whitespace-nowrap transition-colors border-b-2 -mb-px',
            activeTab === tab.key
              ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
          ]"
        >
          <div :class="['w-6 h-6 rounded flex items-center justify-center', tab.iconBg]">
            <component :is="tab.icon" :class="['w-3.5 h-3.5', tab.iconColor]" />
          </div>
          {{ tab.label }}
          <span v-if="getTabCount(tab.key) > 0" :class="[
            'inline-flex items-center justify-center min-w-5 h-5 px-1.5 text-[10px] font-bold rounded-full',
            activeTab === tab.key
              ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300'
              : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'
          ]">
            {{ formatCount(getTabCount(tab.key)) }}
          </span>
          <span v-if="getTabUnack(tab.key) > 0" class="w-2 h-2 rounded-full bg-red-500"></span>
        </button>
      </div>

      <!-- Search & Acknowledge Filter -->
      <div class="flex items-center gap-3 px-5 py-3 border-b border-gray-100 dark:border-gray-700/50">
        <div class="relative flex-1 max-w-sm">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
          <input v-model="search" type="text" placeholder="Search alerts..." @input="debouncedFilter"
            class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
        </div>
        <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-0.5">
          <button @click="setAckFilter('')" :class="['px-3 py-1.5 text-xs font-medium rounded-md transition-colors', ackFilter === '' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700']">All</button>
          <button @click="setAckFilter('0')" :class="['px-3 py-1.5 text-xs font-medium rounded-md transition-colors', ackFilter === '0' ? 'bg-white dark:bg-gray-600 text-red-600 dark:text-red-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700']">New</button>
          <button @click="setAckFilter('1')" :class="['px-3 py-1.5 text-xs font-medium rounded-md transition-colors', ackFilter === '1' ? 'bg-white dark:bg-gray-600 text-green-600 dark:text-green-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700']">Acknowledged</button>
        </div>
      </div>

      <!-- Alert List -->
      <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
        <div v-for="alert in alerts.data" :key="alert.id"
          :class="['flex gap-4 px-5 py-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/30', !alert.is_acknowledged ? 'bg-red-50/30 dark:bg-red-900/5' : '']">
          <!-- Type Icon -->
          <div class="shrink-0 mt-0.5">
            <div :class="['w-10 h-10 rounded-full flex items-center justify-center', alertTypeStyle(alert.type).bg]">
              <component :is="alertTypeStyle(alert.type).icon" :class="['w-5 h-5', alertTypeStyle(alert.type).text]" />
            </div>
          </div>

          <!-- Content -->
          <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <h4 :class="['text-sm', alert.is_acknowledged ? 'text-gray-600 dark:text-gray-400' : 'font-semibold text-gray-900 dark:text-white']">
                    {{ alert.title || formatType(alert.type) }}
                  </h4>
                  <span :class="[
                    'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide',
                    alert.is_acknowledged
                      ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                      : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                  ]">
                    <span :class="['w-1.5 h-1.5 rounded-full', alert.is_acknowledged ? 'bg-green-500' : 'bg-red-500']"></span>
                    {{ alert.is_acknowledged ? 'Acknowledged' : 'New' }}
                  </span>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">{{ alert.message }}</p>
                <div class="flex items-center flex-wrap gap-x-4 gap-y-1 mt-2">
                  <span v-if="alert.device" class="inline-flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" /></svg>
                    {{ alert.device.device_name || alert.device.imei }}
                  </span>
                  <span v-if="alert.tractor" class="inline-flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    {{ alert.tractor.no_plate || `${alert.tractor.brand} ${alert.tractor.model}` }}
                  </span>
                  <span v-if="alert.geo_fence" class="inline-flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    {{ alert.geo_fence.name }}
                  </span>
                  <span class="inline-flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ formatDate(alert.created_at) }}
                  </span>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex items-center gap-1 shrink-0">
                <Link v-if="!alert.is_acknowledged" :href="`/alerts/${alert.id}/acknowledge`" method="post" as="button"
                  class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-green-700 bg-green-50 hover:bg-green-100 rounded-lg dark:text-green-400 dark:bg-green-900/20 dark:hover:bg-green-900/40 transition-colors" title="Acknowledge">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                  Ack
                </Link>
                <Link :href="`/alerts/${alert.id}`" method="delete" as="button"
                  class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors" title="Delete">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!alerts.data?.length" class="py-16 px-4">
        <div class="flex flex-col items-center justify-center text-center">
          <div class="w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
          </div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">
            {{ filters.type ? `No ${formatType(filters.type)} alerts` : 'No alerts found' }}
          </h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">Alerts will appear here when triggered by your devices.</p>
        </div>
      </div>
    </div>

    <Pagination :links="alerts.links" class="mt-6" />
  </AppLayout>
</template>

<script setup>
import { ref, computed, h as hFn } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { formatDate } from '@/utils/dateFormat';
import { formatCount } from '@/utils/numberFormat';

const props = defineProps({ alerts: Object, filters: Object, typeCounts: Object });

const search = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || '');
const ackFilter = ref(props.filters?.acknowledged ?? '');

const activeTab = computed(() => typeFilter.value || 'all');

// ─── Summary stats ───────────────────────────────
const totalAlerts = computed(() => {
  if (!props.typeCounts) return 0;
  return Object.values(props.typeCounts).reduce((a, t) => a + (t.total || 0), 0);
});
const totalUnacknowledged = computed(() => {
  if (!props.typeCounts) return 0;
  return Object.values(props.typeCounts).reduce((a, t) => a + (t.unacknowledged || 0), 0);
});
const typeCount = computed(() => props.typeCounts ? Object.keys(props.typeCounts).length : 0);

const hasActiveFilters = computed(() => props.filters?.type || props.filters?.acknowledged || props.filters?.search);

// ─── Icon render functions ───────────────────────
const GeofenceIcon = (p) => hFn('svg', { ...p, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2' }, [
  hFn('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z' }),
  hFn('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15 11a3 3 0 11-6 0 3 3 0 016 0z' }),
]);
const SpeedIcon = (p) => hFn('svg', { ...p, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2' }, [
  hFn('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M13 10V3L4 14h7v7l9-11h-7z' }),
]);
const OfflineIcon = (p) => hFn('svg', { ...p, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2' }, [
  hFn('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a5 5 0 01-.354-7.065' }),
]);
const IdleIcon = (p) => hFn('svg', { ...p, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2' }, [
  hFn('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' }),
]);
const WrenchIcon = (p) => hFn('svg', { ...p, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2' }, [
  hFn('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' }),
  hFn('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z' }),
]);
const AlertTriangle = (p) => hFn('svg', { ...p, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2' }, [
  hFn('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' }),
]);
const BellIcon = (p) => hFn('svg', { ...p, fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', 'stroke-width': '2' }, [
  hFn('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9' }),
]);

// ─── Type config ─────────────────────────────────
const typeConfig = {
  geofence_breach: { label: 'Geofence', icon: GeofenceIcon, bg: 'bg-yellow-100 dark:bg-yellow-900/30', text: 'text-yellow-600 dark:text-yellow-400', iconBg: 'bg-yellow-100 dark:bg-yellow-900/30', iconColor: 'text-yellow-600 dark:text-yellow-400' },
  speed:           { label: 'Overspeed', icon: SpeedIcon, bg: 'bg-orange-100 dark:bg-orange-900/30', text: 'text-orange-600 dark:text-orange-400', iconBg: 'bg-orange-100 dark:bg-orange-900/30', iconColor: 'text-orange-600 dark:text-orange-400' },
  overspeed:       { label: 'Overspeed', icon: SpeedIcon, bg: 'bg-orange-100 dark:bg-orange-900/30', text: 'text-orange-600 dark:text-orange-400', iconBg: 'bg-orange-100 dark:bg-orange-900/30', iconColor: 'text-orange-600 dark:text-orange-400' },
  offline:         { label: 'Offline', icon: OfflineIcon, bg: 'bg-gray-100 dark:bg-gray-700', text: 'text-gray-600 dark:text-gray-400', iconBg: 'bg-gray-100 dark:bg-gray-700', iconColor: 'text-gray-600 dark:text-gray-400' },
  idle:            { label: 'Idle', icon: IdleIcon, bg: 'bg-amber-100 dark:bg-amber-900/30', text: 'text-amber-600 dark:text-amber-400', iconBg: 'bg-amber-100 dark:bg-amber-900/30', iconColor: 'text-amber-600 dark:text-amber-400' },
  maintenance_due: { label: 'Maintenance', icon: WrenchIcon, bg: 'bg-blue-100 dark:bg-blue-900/30', text: 'text-blue-600 dark:text-blue-400', iconBg: 'bg-blue-100 dark:bg-blue-900/30', iconColor: 'text-blue-600 dark:text-blue-400' },
  sos:             { label: 'SOS', icon: AlertTriangle, bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-600 dark:text-red-400', iconBg: 'bg-red-100 dark:bg-red-900/30', iconColor: 'text-red-600 dark:text-red-400' },
  low_battery:     { label: 'Low Battery', icon: AlertTriangle, bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-600 dark:text-red-400', iconBg: 'bg-red-100 dark:bg-red-900/30', iconColor: 'text-red-600 dark:text-red-400' },
  power_cut:       { label: 'Power Cut', icon: OfflineIcon, bg: 'bg-red-100 dark:bg-red-900/30', text: 'text-red-600 dark:text-red-400', iconBg: 'bg-red-100 dark:bg-red-900/30', iconColor: 'text-red-600 dark:text-red-400' },
};
const defaultConfig = { label: 'Alert', icon: BellIcon, bg: 'bg-gray-100 dark:bg-gray-700', text: 'text-gray-600 dark:text-gray-400', iconBg: 'bg-gray-100 dark:bg-gray-700', iconColor: 'text-gray-600 dark:text-gray-400' };

function alertTypeStyle(type) { return typeConfig[type] || defaultConfig; }
function formatType(type) { return (typeConfig[type]?.label || type?.replace(/_/g, ' ') || 'Alert'); }

// ─── Dynamic tabs from actual data ───────────────
const tabs = computed(() => {
  const items = [{ key: 'all', label: 'All Alerts', icon: BellIcon, iconBg: 'bg-gray-100 dark:bg-gray-700', iconColor: 'text-gray-600 dark:text-gray-400' }];
  if (props.typeCounts) {
    for (const type of Object.keys(props.typeCounts)) {
      const cfg = typeConfig[type] || defaultConfig;
      items.push({ key: type, label: cfg.label, icon: cfg.icon, iconBg: cfg.iconBg, iconColor: cfg.iconColor });
    }
  }
  return items;
});

function getTabCount(key) {
  if (!props.typeCounts) return 0;
  if (key === 'all') return totalAlerts.value;
  return props.typeCounts[key]?.total || 0;
}
function getTabUnack(key) {
  if (!props.typeCounts) return 0;
  if (key === 'all') return totalUnacknowledged.value;
  return props.typeCounts[key]?.unacknowledged || 0;
}

// ─── Filter logic ────────────────────────────────
let timer;
const debouncedFilter = () => { clearTimeout(timer); timer = setTimeout(applyFilter, 300); };

function setTypeFilter(key) {
  typeFilter.value = key === 'all' ? '' : key;
  applyFilter();
}
function setAckFilter(val) {
  ackFilter.value = val;
  applyFilter();
}
function applyFilter() {
  router.get('/alerts', {
    search: search.value || undefined,
    type: typeFilter.value || undefined,
    acknowledged: ackFilter.value !== '' ? ackFilter.value : undefined,
  }, { preserveState: true, replace: true });
}

const purgeProcessing = ref(false);

const purgeOldAlerts = () => {
  if (!window.confirm('Delete all alerts older than 30 days? This cannot be undone.')) {
    return;
  }

  purgeProcessing.value = true;
  router.post('/alerts/purge', {}, {
    preserveScroll: true,
    onSuccess: () => { purgeProcessing.value = false; },
    onError: () => { purgeProcessing.value = false; },
  });
};
</script>
