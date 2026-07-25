<template>
  <AppLayout>
    <Head title="Live View" />

    <div class="flex h-[calc(100vh-4rem)] overflow-hidden -m-4 sm:-m-6 lg:-m-8">
      <!-- Left Panel -->
      <div class="w-80 shrink-0 bg-gray-50/80 dark:bg-gray-900/80 backdrop-blur-sm border-r border-gray-200/70 dark:border-gray-700/50 flex flex-col overflow-hidden">
        <!-- Header -->
        <div class="px-4 py-3.5 border-b border-gray-200/70 dark:border-gray-700/50 bg-white/60 dark:bg-gray-800/60">
          <div class="flex items-center gap-2.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-linear-to-br from-indigo-500 to-indigo-600 shadow-md shadow-indigo-500/20">
              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
              </svg>
            </div>
            <div>
              <h2 class="text-base font-bold text-gray-900 dark:text-white">Live View</h2>
              <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ deviceList.length }} devices tracked</p>
            </div>
          </div>
        </div>

        <!-- Tabs: Objects / Tracks -->
        <div class="flex px-3 pt-3 pb-0 gap-1.5">
          <button @click="activeTab = 'objects'"
            :class="['flex-1 py-2 text-xs font-semibold rounded-lg transition-all duration-200 flex items-center justify-center gap-1.5',
              activeTab === 'objects'
                ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm ring-1 ring-gray-200/70 dark:ring-gray-700/50'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-white/50 dark:hover:bg-gray-800/50']">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            Objects
          </button>
          <button @click="activeTab = 'tracks'"
            :class="['flex-1 py-2 text-xs font-semibold rounded-lg transition-all duration-200 flex items-center justify-center gap-1.5',
              activeTab === 'tracks'
                ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm ring-1 ring-gray-200/70 dark:ring-gray-700/50'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-white/50 dark:hover:bg-gray-800/50']">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            Tracks
          </button>
        </div>

        <!-- ═══════════════ OBJECTS TAB ═══════════════ -->
        <div v-show="activeTab === 'objects'" class="flex-1 flex flex-col overflow-hidden">
          <!-- Search -->
          <div class="px-3 pt-3 pb-2">
            <div class="relative">
              <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <svg class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input v-model="deviceSearch" type="text" placeholder="Search IMEI, name, plate..."
                class="block w-full pl-9 pr-3 py-2 bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/50 text-gray-900 text-xs rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 dark:placeholder-gray-500 dark:text-white shadow-sm transition-all" />
            </div>
          </div>

          <!-- State Filter Pills -->
          <div class="flex px-3 gap-1.5 pb-2.5 flex-wrap">
            <button @click="activeState = 'all'"
              :class="['px-2.5 py-1 text-[11px] rounded-full font-semibold transition-all duration-200',
                activeState === 'all'
                  ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-500/25'
                  : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 ring-1 ring-gray-200/70 dark:ring-gray-700/50']">
              All {{ deviceList.length }}
            </button>
            <button @click="activeState = 'moving'"
              :class="['px-2.5 py-1 text-[11px] rounded-full font-semibold transition-all duration-200 flex items-center gap-1',
                activeState === 'moving'
                  ? 'bg-green-600 text-white shadow-sm shadow-green-500/25'
                  : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-green-50 dark:hover:bg-green-950/30 ring-1 ring-gray-200/70 dark:ring-gray-700/50']">
              <span class="w-1.5 h-1.5 rounded-full" :class="activeState === 'moving' ? 'bg-green-200' : 'bg-green-500'"></span>
              {{ movingCount }} moving
            </button>
            <button @click="activeState = 'idling'"
              :class="['px-2.5 py-1 text-[11px] rounded-full font-semibold transition-all duration-200 flex items-center gap-1',
                activeState === 'idling'
                  ? 'bg-amber-500 text-white shadow-sm shadow-amber-500/25'
                  : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-amber-50 dark:hover:bg-amber-950/30 ring-1 ring-gray-200/70 dark:ring-gray-700/50']">
              <span class="w-1.5 h-1.5 rounded-full" :class="activeState === 'idling' ? 'bg-amber-200' : 'bg-amber-500'"></span>
              {{ idlingCount }} idling
            </button>
            <button @click="activeState = 'parked'"
              :class="['px-2.5 py-1 text-[11px] rounded-full font-semibold transition-all duration-200 flex items-center gap-1',
                activeState === 'parked'
                  ? 'bg-sky-600 text-white shadow-sm shadow-sky-500/25'
                  : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-sky-50 dark:hover:bg-sky-950/30 ring-1 ring-gray-200/70 dark:ring-gray-700/50']">
              <span class="w-1.5 h-1.5 rounded-full" :class="activeState === 'parked' ? 'bg-sky-200' : 'bg-sky-500'"></span>
              {{ parkedCount }} parked
            </button>
            <button @click="activeState = 'offline'"
              :class="['px-2.5 py-1 text-[11px] rounded-full font-semibold transition-all duration-200 flex items-center gap-1',
                activeState === 'offline'
                  ? 'bg-red-600 text-white shadow-sm shadow-red-500/25'
                  : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-red-50 dark:hover:bg-red-950/30 ring-1 ring-gray-200/70 dark:ring-gray-700/50']">
              <span class="w-1.5 h-1.5 rounded-full" :class="activeState === 'offline' ? 'bg-red-200' : 'bg-red-500'"></span>
              {{ offlineCount }} offline
            </button>
          </div>

          <!-- Group Filter & Device List -->
          <div class="flex-1 overflow-y-auto px-3 pb-3 space-y-1.5">
            <select v-model="selectedGroup"
              class="w-full bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/50 text-gray-700 dark:text-gray-300 text-xs rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 p-2 shadow-sm transition-all">
              <option value="">All Groups</option>
              <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
            </select>

            <div v-if="devicesLoading && !deviceList.length" class="rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 bg-white/50 dark:bg-gray-800/50 p-6 text-center">
              <div class="flex h-12 w-12 mx-auto items-center justify-center rounded-2xl bg-indigo-50 dark:bg-indigo-950/30 mb-3">
                <svg class="h-6 w-6 animate-spin text-indigo-500" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                </svg>
              </div>
              <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Loading tractors...</p>
              <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Fetching live locations and preparing the map.</p>
            </div>

            <template v-else>
              <div v-for="device in filteredDevices" :key="device.id"
                @click="selectDevice(device)"
                :class="['p-3 rounded-xl cursor-pointer transition-all duration-200 border',
                  selectedDevice?.id === device.id
                    ? 'border-indigo-300 dark:border-indigo-600 bg-indigo-50/80 dark:bg-indigo-950/30 shadow-sm'
                    : 'border-gray-100 dark:border-gray-700/50 bg-white dark:bg-gray-800 hover:border-gray-200 dark:hover:border-gray-600 hover:shadow-sm']">
                <div class="flex items-center gap-2.5">
                  <div :class="['w-9 h-9 rounded-xl flex items-center justify-center shrink-0', statusIconBackgroundClass(device.status)]">
                    <svg class="w-4 h-4" :class="statusIconTextClass(device.status)" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                      <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-1h3.05a2.5 2.5 0 014.9 0H19a1 1 0 001-1v-5a1 1 0 00-.293-.707l-3-3A1 1 0 0016 4H3z" />
                    </svg>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-1.5">
                      <span class="text-[13px] font-semibold text-gray-900 dark:text-white truncate">
                        {{ device.tractor?.no_plate || device.device_name || device.imei }}
                      </span>
                      <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold shrink-0', statusBadgeClass(device.status)]">
                        {{ statusLabel(device.status) }}
                      </span>
                    </div>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5 font-mono tracking-tight">{{ device.imei }}</p>
                    <div v-if="device.status === 'moving'" class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1 font-medium">
                      <svg class="w-3 h-3 text-indigo-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" /></svg>
                      {{ device.speed }} km/h
                    </div>
                    <div v-else-if="device.status === 'idling'" class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1 font-medium">
                      <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                      Last fix {{ formatTimeAgo(device.gps_minutes_ago) }}
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="!filteredDevices.length" class="text-center py-12">
                <div class="flex h-12 w-12 mx-auto items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800 mb-3">
                  <svg class="w-6 h-6 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <p class="text-sm font-medium text-gray-400 dark:text-gray-500">No devices found.</p>
              </div>
            </template>
          </div>
        </div>

        <!-- ═══════════════ TRACKS TAB ═══════════════ -->
        <div v-show="activeTab === 'tracks'" class="flex-1 flex flex-col overflow-hidden">
          <div class="p-3 space-y-3">
            <!-- Device select -->
            <div>
              <label class="block mb-1.5 text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Device</label>
              <select v-model="trackDeviceId"
                class="bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/50 text-gray-700 dark:text-gray-300 text-xs rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 block w-full p-2.5 shadow-sm transition-all">
                <option value="" disabled>Select Device</option>
                <option v-for="d in deviceList" :key="d.id" :value="d.id">
                  {{ d.tractor?.no_plate || d.device_name || d.imei }}
                </option>
              </select>
            </div>

            <!-- Period select -->
            <div>
              <label class="block mb-1.5 text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Period</label>
              <select v-model="trackPeriod"
                class="bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/50 text-gray-700 dark:text-gray-300 text-xs rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 block w-full p-2.5 shadow-sm transition-all">
                <option value="today">Today</option>
                <option value="yesterday">Yesterday</option>
                <option value="3days">Last 3 Days</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="custom">Custom Range</option>
              </select>
            </div>

            <!-- Custom date range -->
            <div v-if="trackPeriod === 'custom'" class="grid grid-cols-2 gap-2">
              <div>
                <label class="block mb-1 text-[11px] font-medium text-gray-500 dark:text-gray-400">From</label>
                <input v-model="trackFrom" type="date"
                  class="bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/50 text-gray-700 dark:text-gray-300 text-xs rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 block w-full p-2.5 shadow-sm transition-all" />
              </div>
              <div>
                <label class="block mb-1 text-[11px] font-medium text-gray-500 dark:text-gray-400">To</label>
                <input v-model="trackTo" type="date"
                  class="bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/50 text-gray-700 dark:text-gray-300 text-xs rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 block w-full p-2.5 shadow-sm transition-all" />
              </div>
            </div>

            <!-- Search / Clear Buttons -->
            <div class="flex gap-2 pt-1">
              <button @click="searchTracks" :disabled="!trackDeviceId || trackLoading || (trackPeriod === 'custom' && (!trackFrom || !trackTo))"
                class="flex-1 text-white bg-indigo-600 hover:bg-indigo-500 focus:ring-2 focus:ring-indigo-500/20 font-semibold rounded-xl text-xs px-4 py-2.5 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-1.5 transition-all shadow-sm shadow-indigo-500/20">
                <svg v-if="trackLoading" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                {{ trackLoading ? 'Loading...' : 'Search' }}
              </button>
              <button v-if="trackData" @click="clearTracks"
                class="px-4 py-2.5 text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-800 border border-gray-200/80 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700 font-semibold rounded-xl text-xs transition-all shadow-sm">
                Clear
              </button>
            </div>
          </div>

          <!-- Track Info Panel -->
          <div v-if="trackData && trackData.totalPoints > 0" class="flex-1 overflow-y-auto">
            <div v-if="trackWarnings.length" class="border-b border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-800 dark:border-amber-800/50 dark:bg-amber-950/30 dark:text-amber-300" role="status">
              <p class="font-semibold">Partial track data</p>
              <p class="mt-0.5">{{ trackWarnings.length }} JIMI time range{{ trackWarnings.length === 1 ? '' : 's' }} could not be loaded.</p>
              <p class="mt-1 text-[11px] opacity-80">The available route is shown, but distance and activity totals may be incomplete.</p>
            </div>

            <div class="flex flex-wrap gap-x-3 gap-y-1 border-b border-gray-200 bg-white px-3 py-2 text-[11px] text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400" aria-label="Track marker legend">
              <span class="inline-flex items-center gap-1"><span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-green-500 text-[9px] font-bold text-white">S</span> Start</span>
              <span class="inline-flex items-center gap-1"><span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white">E</span> End</span>
              <span class="inline-flex items-center gap-1" title="Hover, focus, or tap an orange marker on the map for details."><span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-amber-500 text-[9px] font-bold text-white">!</span> GPS data warning</span>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-2 gap-px bg-gray-200 dark:bg-gray-700">
              <div class="bg-white dark:bg-gray-800 p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400">Distance</p>
                <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ trackData.distance }} <span class="text-xs font-normal text-gray-400">km</span></p>
              </div>
              <div class="bg-white dark:bg-gray-800 p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400">Elapsed Window</p>
                <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ formatDuration(trackData.duration) }}</p>
              </div>
              <div class="bg-white dark:bg-gray-800 p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400">Max Speed</p>
                <p class="text-lg font-bold text-red-500">{{ trackData.maxSpeed }} <span class="text-xs font-normal text-gray-400">km/h</span></p>
              </div>
              <div class="bg-white dark:bg-gray-800 p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400">Avg Speed</p>
                <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ trackData.avgSpeed }} <span class="text-xs font-normal text-gray-400">km/h</span></p>
              </div>
              <div class="bg-white dark:bg-gray-800 p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400">Moving</p>
                <p class="text-base font-bold text-green-600 dark:text-green-400">{{ formatDuration(trackData.movingDuration) }}</p>
              </div>
              <div class="bg-white dark:bg-gray-800 p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400">Idle</p>
                <p class="text-base font-bold text-amber-600 dark:text-amber-400">{{ formatDuration(trackData.idleDuration) }}</p>
              </div>
              <div class="bg-white dark:bg-gray-800 p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400">Stops</p>
                <p class="text-base font-bold text-gray-800 dark:text-gray-200">{{ trackData.stopCount }}</p>
              </div>
              <div class="bg-white dark:bg-gray-800 p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400" title="Reporting gaps are periods with no GPS points. Spikes are impossible jumps removed from the route.">Gaps / Spikes</p>
                <p class="text-base font-bold" :class="trackData.gapCount ? 'text-amber-600 dark:text-amber-400' : 'text-gray-800 dark:text-gray-200'">{{ trackData.gapCount }}</p>
              </div>
            </div>

            <!-- GPS Points count & time -->
            <div class="p-3 border-b border-gray-200 dark:border-gray-700 space-y-1.5">
              <div class="flex justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">GPS Points</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ trackData.totalPoints.toLocaleString() }}</span>
              </div>
              <div v-if="trackData.startTime" class="flex justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Start (PH)</span>
                <span class="font-medium text-gray-900 dark:text-white text-xs">{{ formatDateTime(trackData.startTime) }}</span>
              </div>
              <div v-if="trackData.endTime" class="flex justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">End (PH)</span>
                <span class="font-medium text-gray-900 dark:text-white text-xs">{{ formatDateTime(trackData.endTime) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400" title="A new segment starts after a reporting gap so the map does not draw a false straight line.">Segments</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ trackData.segmentCount }}</span>
              </div>
              <div v-if="trackData.invalidPointCount || trackData.duplicatePointCount || trackData.outlierPointCount" class="flex justify-between gap-3 text-sm">
                <span class="text-gray-500 dark:text-gray-400" title="Invalid coordinates, exact duplicates, and impossible GPS jumps are excluded from route totals.">Cleaned points</span>
                <span class="text-right text-xs font-medium text-gray-900 dark:text-white">{{ trackData.invalidPointCount }} invalid, {{ trackData.duplicatePointCount }} duplicate, {{ trackData.outlierPointCount }} spike</span>
              </div>
            </div>

            <!-- Playback Controls -->
            <div class="p-3 space-y-3 border-b border-gray-200 dark:border-gray-700" tabindex="0" @keydown="handlePlaybackKeydown">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Playback</span>
                <div class="flex items-center gap-1">
                  <button v-for="s in [1, 2, 4, 8, 16]" :key="s" @click="playbackSpeed = s"
                    :aria-pressed="playbackSpeed === s" :aria-label="`Playback speed ${s}x`"
                    :class="['px-2 py-0.5 text-[10px] font-bold rounded transition-colors',
                      playbackSpeed === s ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300']">
                    {{ s }}x
                  </button>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <button @click="togglePlayback"
                  :aria-label="isPlaying ? 'Pause track playback' : 'Play track history'"
                  class="w-10 h-10 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white flex items-center justify-center transition-colors shadow-sm shrink-0">
                  <svg v-if="!isPlaying" class="w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
                  </svg>
                  <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M5.75 3a.75.75 0 00-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 00.75-.75V3.75A.75.75 0 007.25 3h-1.5zM12.75 3a.75.75 0 00-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 00.75-.75V3.75a.75.75 0 00-.75-.75h-1.5z" />
                  </svg>
                </button>

                <div class="flex-1">
                  <input type="range" v-model.number="playbackIndex" :min="0" :max="Math.max(0, (trackData?.points?.length || 1) - 1)"
                    aria-label="Track playback position" :aria-valuetext="currentPlaybackTime"
                    class="w-full h-1.5 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700 accent-indigo-600" />
                  <div class="flex justify-between mt-1">
                    <span class="text-[10px] text-gray-500 dark:text-gray-400 font-mono">{{ currentPlaybackTime }}</span>
                    <span class="text-[10px] text-gray-500 dark:text-gray-400">
                      <span class="font-bold text-indigo-600">{{ currentPlaybackSpeed }}</span> km/h
                    </span>
                  </div>
                </div>
              </div>

              <button @click="stopPlayback(); fitTrackBounds()"
                class="w-full text-xs text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 py-1 flex items-center justify-center gap-1 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                </svg>
                Fit to Track
              </button>
            </div>
          </div>

          <!-- No track data loaded yet -->
          <div v-else-if="!trackData" class="flex-1 flex items-center justify-center p-6">
            <div class="text-center text-gray-400 dark:text-gray-500">
              <svg class="mx-auto w-16 h-16 mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
              </svg>
              <p class="text-sm font-medium">Select a device and period</p>
              <p class="text-xs mt-1">to view GPS track history</p>
            </div>
          </div>

          <!-- Track data but 0 points -->
          <div v-else-if="trackData && trackData.totalPoints === 0" class="flex-1 flex items-center justify-center p-6">
            <div class="text-center text-gray-400 dark:text-gray-500">
              <svg class="mx-auto w-12 h-12 mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.834-2.694-.834-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
              <p class="text-sm font-medium">{{ trackError || 'No track data found' }}</p>
              <p class="text-xs mt-1">{{ trackError ? 'Try again or choose a shorter period.' : 'Try a different period.' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════ MAP CONTAINER ═══════════════ -->
      <div class="flex-1 relative">
        <div ref="mapContainer" class="w-full h-full"></div>

        <!-- Map loading overlay -->
        <div v-if="!mapReady" class="absolute inset-0 bg-gray-50 dark:bg-gray-900 flex items-center justify-center">
          <div class="max-w-md px-6 text-center">
            <svg class="mx-auto h-12 w-12" :class="mapError ? 'text-red-400' : 'text-indigo-400 animate-pulse'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            <p class="mt-3 text-sm font-medium" :class="mapError ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'">
              {{ mapError || 'Loading Map...' }}
            </p>
            <p v-if="mapError" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
              Update the Google Maps key restrictions to allow this host, or replace the configured key with one that permits
              <span class="font-medium text-gray-700 dark:text-gray-200">{{ currentHost }}</span>.
            </p>
          </div>
        </div>

        <!-- Refresh interval control -->
        <div class="absolute bottom-4 left-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-10 flex items-center">
          <button @click="manualRefresh"
            :disabled="devicesLoading || isFollowing"
            class="px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 flex items-center gap-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-l-lg transition-colors disabled:opacity-50"
            title="Refresh now">
            <svg class="w-4 h-4 text-indigo-500 dark:text-indigo-400" :class="{ 'animate-spin': devicesLoading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span class="tabular-nums w-5 text-right">{{ refreshCountdown }}</span><span class="text-xs text-gray-400 ml-0.5">s</span>
          </button>
          <div class="w-px h-5 bg-gray-200 dark:bg-gray-600"></div>
          <select v-model.number="refreshIntervalSec" @change="changeRefreshInterval"
            class="appearance-none bg-transparent text-xs font-medium text-gray-500 dark:text-gray-400 py-2 pl-2.5 pr-7 rounded-r-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <option :value="5">5s</option>
            <option :value="10">10s</option>
            <option :value="20">20s</option>
            <option :value="30">30s</option>
            <option :value="60">60s</option>
          </select>
        </div>

        <!-- Map type controls -->
        <div class="absolute top-4 right-4 flex flex-col gap-2 z-10">
          <div class="inline-flex rounded-lg shadow-lg" role="group">
            <button v-for="(mt, idx) in mapTypes" :key="mt.id" @click="setMapType(mt.id)"
              :class="['px-4 py-2 text-xs font-medium border transition-colors',
                idx === 0 ? 'rounded-s-lg' : '',
                idx === mapTypes.length - 1 ? 'rounded-e-lg' : '',
                currentMapType === mt.id
                  ? 'bg-indigo-700 text-white border-indigo-700 dark:bg-indigo-600 dark:border-indigo-600'
                  : 'bg-white text-gray-900 border-gray-200 hover:bg-gray-100 hover:text-indigo-700 dark:bg-gray-800 dark:text-white dark:border-gray-700 dark:hover:bg-gray-700']">
              {{ mt.label }}
            </button>
          </div>
          <!-- GPS Correction Toggle -->
          <button @click="gpsCorrection = !gpsCorrection; updateMarkers()"
            :class="['self-end rounded-lg shadow-lg px-3 py-1.5 text-[11px] font-semibold border transition-colors flex items-center gap-1.5',
              gpsCorrection
                ? 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900/60 dark:text-amber-300 dark:border-amber-700'
                : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700 dark:hover:bg-gray-700']"
            title="Corrects ~300m GCJ-02 offset if device coordinates appear shifted from roads">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            {{ gpsCorrection ? 'GPS Corrected' : 'GPS Correction' }}
          </button>
        </div>

        <!-- ═══════════════ DEVICE DETAIL SIDEBAR ═══════════════ -->
        <transition name="slide-right">
          <div v-if="showDetailSidebar && selectedDevice" class="absolute top-0 right-0 w-96 h-full bg-white dark:bg-gray-800 shadow-2xl z-20 overflow-y-auto border-l border-gray-200 dark:border-gray-700">
            <!-- Header -->
            <div class="sticky top-0 bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm border-b border-gray-200/70 dark:border-gray-700/50 px-4 py-3 flex items-center justify-between z-10">
              <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                  {{ selectedDevice.tractor?.no_plate || selectedDevice.device_name || 'Unknown' }}
                </h3>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 font-mono tracking-tight">{{ selectedDevice.imei }}</p>
              </div>
              <button @click="closeDeviceDetails" class="text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300 rounded-xl p-1.5 inline-flex items-center transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Status Bar -->
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium', statusBadgeClass(selectedDevice.status)]">
                  <span :class="['w-2 h-2 rounded-full', statusDotClass(selectedDevice.status)]"></span>
                  {{ statusLabel(selectedDevice.status) }}
                  (ACC: {{ selectedDevice.acc_status ? 'ON' : 'OFF' }})
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                  {{ selectedDevice.status === 'moving'
                    ? (selectedDevice.speed + ' km/h')
                    : selectedDevice.status === 'idling'
                      ? formatTimeAgo(selectedDevice.gps_minutes_ago)
                      : formatTimeAgo(selectedDevice.minutes_ago) }}
                </span>
              </div>
            </div>

            <!-- Address -->
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
              <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                Address
              </h4>
              <p class="text-sm text-gray-700 dark:text-gray-300">{{ deviceAddress || 'Loading address...' }}</p>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 font-mono">
                {{ selectedDevice.lat?.toFixed(6) }}, {{ selectedDevice.lng?.toFixed(6) }}
              </p>
            </div>

            <!-- Device Info -->
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
              <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" /></svg>
                Device
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">GNSS</span>
                  <span class="text-gray-900 dark:text-white font-medium">{{ selectedDevice.pos_type || 'N/A' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Satellites</span>
                  <span class="text-gray-900 dark:text-white font-medium">{{ selectedDevice.gps_num ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Last Online</span>
                  <span class="text-gray-900 dark:text-white font-medium">{{ formatDateTime(selectedDevice.heartbeat_at) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Last Fix</span>
                  <span class="text-gray-900 dark:text-white font-medium">{{ formatDateTime(selectedDevice.gps_time) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Mileage</span>
                  <span class="text-gray-900 dark:text-white font-medium">{{ selectedDevice.mileage ? (Number(selectedDevice.mileage).toFixed(2) + ' km') : 'N/A' }}</span>
                </div>
              </div>
            </div>

            <!-- Vehicle Info -->
            <div v-if="selectedDevice.tractor" class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
              <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-1h3.05a2.5 2.5 0 014.9 0H19a1 1 0 001-1v-5a1 1 0 00-.293-.707l-3-3A1 1 0 0016 4H3z" /></svg>
                Vehicle
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Name</span>
                  <span class="text-gray-900 dark:text-white font-medium">{{ selectedDevice.tractor.name || '-' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Model</span>
                  <span class="text-gray-900 dark:text-white font-medium">{{ selectedDevice.tractor.brand }} {{ selectedDevice.tractor.model }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">License Plate</span>
                  <span class="text-gray-900 dark:text-white font-medium">{{ selectedDevice.tractor.no_plate || '-' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Group</span>
                  <span class="text-gray-900 dark:text-white font-medium">{{ selectedDevice.tractor.group || '-' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500 dark:text-gray-400">Assigned To</span>
                  <span class="text-gray-900 dark:text-white font-medium">{{ selectedDevice.tractor.assignee || '-' }}</span>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="px-4 py-3 space-y-2">
              <div class="flex gap-2">
                <button @click="liveFollow" :disabled="followRequestPending && !isFollowing" :class="['flex-1 font-medium rounded-lg text-sm px-3 py-2.5 flex items-center justify-center gap-1.5 transition-colors focus:ring-4 disabled:opacity-60 disabled:cursor-wait',
                  isFollowing
                    ? 'text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-indigo-300 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800'
                    : 'text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-gray-200 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-700']">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
                  </svg>
                  {{ isFollowing ? (followRequestPending ? 'Updating...' : 'Following') : (followRequestPending ? 'Connecting...' : 'Follow Live') }}
                </button>
                <Link :href="`/devices/${selectedDevice.id}`"
                  class="flex-1 text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-3 py-2.5 text-center dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-700 transition-colors">
                  Details
                </Link>
              </div>

              <p v-if="followError" class="text-xs text-red-600 dark:text-red-400" role="status">{{ followError }}</p>

              <!-- Share Button -->
              <button @click="openShareModal"
                class="w-full flex items-center justify-center gap-2 text-sm font-medium px-3 py-2.5 rounded-lg text-white bg-emerald-600 hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-300 dark:bg-emerald-500 dark:hover:bg-emerald-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                </svg>
                Share Location
              </button>

              <!-- Quick Track -->
              <button @click="quickTrack"
                class="w-full flex items-center justify-center gap-2 text-sm font-medium px-3 py-2.5 rounded-lg text-gray-700 bg-gray-100 hover:bg-gray-200 dark:text-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
                View Track History
              </button>
            </div>
          </div>
        </transition>

        <!-- ═══════════════ SHARE MODAL ═══════════════ -->
        <transition name="fade">
          <div v-if="showShareModal" class="absolute inset-0 bg-black/40 z-30 flex items-center justify-center" @click.self="showShareModal = false">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
              <!-- Modal Header -->
              <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/50 rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                    </svg>
                  </div>
                  <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Share Live Location</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedDevice?.tractor?.no_plate || selectedDevice?.device_name }}</p>
                  </div>
                </div>
                <button @click="showShareModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
              </div>

              <!-- Modal Body -->
              <div class="px-6 py-5">
                <!-- Duration selector -->
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Link Duration</label>
                <div class="grid grid-cols-4 gap-2 mb-5">
                  <button v-for="h in [1, 4, 12, 24]" :key="h" @click="shareDuration = h"
                    :class="['py-2 px-3 text-sm font-medium rounded-lg border transition-colors',
                      shareDuration === h
                        ? 'bg-emerald-600 text-white border-emerald-600'
                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600']">
                    {{ h }}h
                  </button>
                </div>

                <!-- Generate or show link -->
                <div v-if="!shareUrl">
                  <button @click="generateShareLink" :disabled="shareLoading"
                    class="w-full text-white bg-emerald-600 hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-300 font-medium rounded-lg text-sm px-5 py-3 disabled:opacity-50 flex items-center justify-center gap-2 transition-colors">
                    <svg v-if="shareLoading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                    {{ shareLoading ? 'Generating...' : 'Generate Share Link' }}
                  </button>
                </div>
                <div v-else class="space-y-3">
                  <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3 flex items-center gap-2">
                    <input :value="shareUrl" readonly
                      class="flex-1 bg-transparent text-sm text-gray-800 dark:text-gray-200 font-mono truncate border-none focus:ring-0 p-0" />
                    <button @click="copyShareUrl"
                      class="shrink-0 text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 p-1.5 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors">
                      <svg v-if="!shareCopied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" /></svg>
                      <svg v-else class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </button>
                  </div>
                  <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    This link is valid for <strong class="text-gray-700 dark:text-gray-300">{{ shareDuration }} hour{{ shareDuration > 1 ? 's' : '' }}</strong>. Anyone with the link can view the live location.
                  </div>
                  <button @click="shareUrl = ''; shareExpires = ''"
                    class="w-full text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 py-2 transition-colors">
                    Generate New Link
                  </button>
                </div>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import axios from 'axios';

const props = defineProps({
  devices: Array,
  groups: Array,
  googleMapKey: String,
});

// ═══════════════ STATE ═══════════════
const mapContainer = ref(null);
const mapReady = ref(false);
const mapError = ref('');
const devicesLoading = ref((props.devices?.length ?? 0) === 0);
const activeTab = ref('objects');
const activeState = ref('all');
const deviceSearch = ref('');
const selectedGroup = ref('');
const selectedDevice = ref(null);
const showDetailSidebar = ref(false);
const deviceAddress = ref('');
const isFollowing = ref(false);
const followRequestPending = ref(false);
const followError = ref('');
const refreshCountdown = ref(10);
const refreshIntervalSec = ref(10);
const gpsCorrection = ref(false);
const currentMapType = ref('satellite');

// Tracks
const trackDeviceId = ref('');
const trackPeriod = ref('today');
const trackFrom = ref('');
const trackTo = ref('');
const trackLoading = ref(false);
const trackData = ref(null);
const trackError = ref('');
const trackWarnings = ref([]);

// Playback
const isPlaying = ref(false);
const playbackIndex = ref(0);
const playbackSpeed = ref(1);

// Share
const showShareModal = ref(false);
const shareDuration = ref(1);
const shareLoading = ref(false);
const shareUrl = ref('');
const shareExpires = ref('');
const shareCopied = ref(false);

const deviceList = ref(props.devices || []);

// Map objects
let map = null;
let markers = {};
let infoWindows = {};
let followInterval = null;
let refreshInterval = null;
let countdownInterval = null;
let trackPolylines = [];
let trackProgressPolylines = [];
let trackSegmentPaths = [];
let trackSegmentRanges = [];
let trackProgressLengths = [];
let trackMarkers = [];
let playbackMarker = null;
let playbackMarkerImage = null;
let playbackAnimationId = null;
let lastPlaybackTimestamp = null;
let previousPositions = {};  // imei -> { lat, lng } for bearing calc
let animationFrames = {};   // imei -> animation frame id
let mapErrorPoll = null;
let clusterMarkers = [];
let mapIdleListener = null;
let AdvancedMarkerElementClass = null;
let latestRefreshRequestId = 0;
let latestFollowRequestId = 0;
let followSessionId = 0;

const PHILIPPINES_BOUNDS = {
  north: 21.5,
  south: 4.5,
  west: 116.7,
  east: 126.8,
};

const mapTypes = [
  { id: 'roadmap', label: 'Map' },
  { id: 'satellite', label: 'Satellite' },
  { id: 'terrain', label: 'Terrain' },
];

const GOOGLE_MAP_DEMO_ID = 'DEMO_MAP_ID';
const DISPLAY_TIME_ZONE = 'Asia/Manila';
const currentHost = computed(() => window.location.origin);

// ═══════════════ GCJ-02 → WGS-84 CORRECTION ═══════════════
// Many GPS trackers (especially Chinese-made) use GCJ-02 coordinates
// which are offset ~100-700m from WGS-84. Google Maps uses WGS-84.
// This converts GCJ-02 back to WGS-84 so markers appear on the road.
const PI = Math.PI;
const X_PI = (PI * 3000.0) / 180.0;
const A = 6378245.0; // semi-major axis
const EE = 0.00669342162296594323; // eccentricity squared

function isOutOfChina(lat, lng) {
  return lng < 72.004 || lng > 137.8347 || lat < 0.8293 || lat > 55.8271;
}

function transformLat(x, y) {
  let ret = -100.0 + 2.0 * x + 3.0 * y + 0.2 * y * y + 0.1 * x * y + 0.2 * Math.sqrt(Math.abs(x));
  ret += ((20.0 * Math.sin(6.0 * x * PI) + 20.0 * Math.sin(2.0 * x * PI)) * 2.0) / 3.0;
  ret += ((20.0 * Math.sin(y * PI) + 40.0 * Math.sin((y / 3.0) * PI)) * 2.0) / 3.0;
  ret += ((160.0 * Math.sin((y / 12.0) * PI) + 320.0 * Math.sin((y * PI) / 30.0)) * 2.0) / 3.0;
  return ret;
}

function transformLng(x, y) {
  let ret = 300.0 + x + 2.0 * y + 0.1 * x * x + 0.1 * x * y + 0.1 * Math.sqrt(Math.abs(x));
  ret += ((20.0 * Math.sin(6.0 * x * PI) + 20.0 * Math.sin(2.0 * x * PI)) * 2.0) / 3.0;
  ret += ((20.0 * Math.sin(x * PI) + 40.0 * Math.sin((x / 3.0) * PI)) * 2.0) / 3.0;
  ret += ((150.0 * Math.sin((x / 12.0) * PI) + 300.0 * Math.sin((x / 30.0) * PI)) * 2.0) / 3.0;
  return ret;
}

/**
 * Convert GCJ-02 (Mars) coordinates to WGS-84.
 * Returns { lat, lng } — pass-through if already outside China.
 */
function gcj02ToWgs84(lat, lng) {
  if (isOutOfChina(lat, lng)) {
    return { lat, lng };
  }

  let dLat = transformLat(lng - 105.0, lat - 35.0);
  let dLng = transformLng(lng - 105.0, lat - 35.0);
  const radLat = (lat / 180.0) * PI;
  let magic = Math.sin(radLat);
  magic = 1 - EE * magic * magic;
  const sqrtMagic = Math.sqrt(magic);
  dLat = (dLat * 180.0) / (((A * (1 - EE)) / (magic * sqrtMagic)) * PI);
  dLng = (dLng * 180.0) / ((A / sqrtMagic) * Math.cos(radLat) * PI);

  return {
    lat: lat - dLat,
    lng: lng - dLng,
  };
}

/**
 * Apply GPS correction to coordinates if enabled.
 */
function correctCoords(lat, lng) {
  if (!gpsCorrection.value) return { lat, lng };
  return gcj02ToWgs84(lat, lng);
}

// ═══════════════ COMPUTED ═══════════════
const movingCount = computed(() => deviceList.value.filter(d => d.status === 'moving').length);
const idlingCount = computed(() => deviceList.value.filter(d => d.status === 'idling').length);
const parkedCount = computed(() => deviceList.value.filter(d => d.status === 'parked').length);
const offlineCount = computed(() => deviceList.value.filter(d => d.status === 'offline').length);

const filteredDevices = computed(() => {
  let list = deviceList.value;
  if (activeState.value === 'moving') list = list.filter(d => d.status === 'moving');
  else if (activeState.value === 'idling') list = list.filter(d => d.status === 'idling');
  else if (activeState.value === 'parked') list = list.filter(d => d.status === 'parked');
  else if (activeState.value === 'offline') list = list.filter(d => d.status === 'offline');
  if (selectedGroup.value) list = list.filter(d => d.tractor?.group_id == selectedGroup.value);
  if (deviceSearch.value) {
    const q = deviceSearch.value.toLowerCase();
    list = list.filter(d =>
      d.imei?.toLowerCase().includes(q) ||
      d.device_name?.toLowerCase().includes(q) ||
      d.tractor?.no_plate?.toLowerCase().includes(q)
    );
  }
  return list;
});

const currentPlaybackTime = computed(() => {
  if (!trackData.value?.points?.length) return '--:--:--';
  const p = trackData.value.points[playbackIndex.value];
  if (!p?.gpsTime) return '--:--:--';
  return formatClockTime(p.gpsTime);
});

const currentPlaybackSpeed = computed(() => {
  if (!trackData.value?.points?.length) return '0';
  const p = trackData.value.points[playbackIndex.value];
  return p ? p.speed?.toFixed(1) : '0';
});

// ═══════════════ GOOGLE MAPS ═══════════════
function syncMapAuthorizationError() {
  const pageText = document.body?.innerText?.trim() ?? '';

  if (!/didn't load google maps correctly|for development purposes only|referernotallowedmaperror/i.test(pageText)) {
    return false;
  }

  mapReady.value = false;
  mapError.value = `Google Maps rejected this site for ${window.location.origin}.`;

  return true;
}

function watchForMapAuthorizationError() {
  if (!mapContainer.value) {
    return;
  }

  if (mapErrorPoll) {
    window.clearInterval(mapErrorPoll);
  }

  let attempts = 0;
  mapErrorPoll = window.setInterval(() => {
    attempts += 1;

    if (syncMapAuthorizationError() || attempts >= 20) {
      window.clearInterval(mapErrorPoll);
      mapErrorPoll = null;
    }
  }, 500);
}

function loadGoogleMaps() {
  if (window.google?.maps) {
    return Promise.resolve();
  }

  const key = props.googleMapKey;
  if (!key) {
    watchForMapAuthorizationError();
    return Promise.reject(new Error('Google Maps API key not provided.'));
  }

  if (window.__tanodGoogleMapsPromise) {
    return window.__tanodGoogleMapsPromise;
  }

  window.__tanodGoogleMapsPromise = new Promise((resolve, reject) => {
    let timeoutId = null;

    const cleanup = () => {
      if (timeoutId) {
        window.clearTimeout(timeoutId);
      }
      delete window.initGoogleMap;
      delete window.gm_authFailure;
    };

    const fail = (message) => {
      cleanup();
      window.__tanodGoogleMapsPromise = null;
      reject(new Error(message));
    };

    const complete = () => {
      if (!window.google?.maps) {
        return;
      }

      cleanup();
      resolve();
    };

    window.initGoogleMap = complete;
    window.gm_authFailure = () => {
      fail(`Google Maps rejected this site for ${window.location.origin}.`);
    };

    timeoutId = window.setTimeout(() => {
      if (window.google?.maps) {
        complete();
        return;
      }

      fail('Google Maps did not finish loading.');
    }, 10000);

    const existing = document.querySelector('script[src*="maps.googleapis.com/maps/api/js"]');
    if (existing) {
      existing.addEventListener('load', complete, { once: true });
      existing.addEventListener('error', () => fail('Failed to load Google Maps.'), { once: true });
      return;
    }

    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${key}&libraries=geometry,marker&loading=async&v=weekly&callback=initGoogleMap`;
    script.async = true;
    script.defer = true;
    script.addEventListener('error', () => fail('Failed to load Google Maps.'), { once: true });
    document.head.appendChild(script);
  });

  return window.__tanodGoogleMapsPromise;
}

async function ensureAdvancedMarkerLibrary() {
  if (AdvancedMarkerElementClass) {
    return;
  }

  const { AdvancedMarkerElement } = await google.maps.importLibrary('marker');
  AdvancedMarkerElementClass = AdvancedMarkerElement;
}

function focusMapOnPhilippines() {
  if (!map || !window.google?.maps) {
    return;
  }

  const bounds = new google.maps.LatLngBounds(
    new google.maps.LatLng(PHILIPPINES_BOUNDS.south, PHILIPPINES_BOUNDS.west),
    new google.maps.LatLng(PHILIPPINES_BOUNDS.north, PHILIPPINES_BOUNDS.east),
  );

  map.fitBounds(bounds, 24);

  google.maps.event.addListenerOnce(map, 'idle', () => {
    if (map && map.getZoom() > 6) {
      map.setZoom(6);
    }
  });
}

async function initMap() {
  try {
    mapError.value = '';
    await loadGoogleMaps();
    await ensureAdvancedMarkerLibrary();
    map = new google.maps.Map(mapContainer.value, {
      center: { lat: 14.17092, lng: 121.291831 },
      zoom: 6,
      mapTypeId: currentMapType.value,
      mapTypeControl: false,
      streetViewControl: true,
      fullscreenControl: false,
      mapId: GOOGLE_MAP_DEMO_ID,
    });
    mapReady.value = true;
    focusMapOnPhilippines();

    if (!mapIdleListener) {
      mapIdleListener = map.addListener('idle', () => {
        createMarkers();
      });
    }

    createMarkers();
  } catch (e) {
    mapError.value = e instanceof Error ? e.message : 'Failed to load Google Maps.';
    console.error('Failed to load Google Maps:', e);
  }
}

// Vehicle SVG path — directional silhouette facing up/north at rotation 0.
const TRACTOR_PATH = 'M12 2C11.2 2 10.5 2.5 10.2 3.2L9.5 5H7C5.9 5 5 5.9 5 7V9.5C3.6 9.5 2.5 10.6 2.5 12C2.5 13.4 3.6 14.5 5 14.5V16C5 17.1 5.9 18 7 18H7.2C7.6 19.2 8.7 20 10 20C11.3 20 12.4 19.2 12.8 18H15.2C15.6 19.2 16.7 20 18 20C19.3 20 20.4 19.2 20.8 18H21C22.1 18 23 17.1 23 16V12C23 10.9 22.1 10 21 10H19L17.4 6.2C17 5.5 16.3 5 15.5 5H14.5L13.8 3.2C13.5 2.5 12.8 2 12 2ZM10 16C9.2 16 8.5 16.7 8.5 17.5C8.5 18.3 9.2 19 10 19C10.8 19 11.5 18.3 11.5 17.5C11.5 16.7 10.8 16 10 16ZM18 16C17.2 16 16.5 16.7 16.5 17.5C16.5 18.3 17.2 19 18 19C18.8 19 19.5 18.3 19.5 17.5C19.5 16.7 18.8 16 18 16ZM7 7H15.5L17 10H7V7ZM7 12H21V16H20.8C20.4 14.8 19.3 14 18 14C16.7 14 15.6 14.8 15.2 16H12.8C12.4 14.8 11.3 14 10 14C8.7 14 7.6 14.8 7.2 16H7V12Z';
const TRACTOR_MARKER_IMAGES = {
  moving: '/images/green_tractor.png',
  idling: '/images/yellow_tractor.png',
  parked: '/images/yellow_tractor.png',
  offline: '/images/red_tractor.png',
};

function createVehicleMarkerContainer(size = 32) {
  const container = document.createElement('div');
  container.style.width = `${size}px`;
  container.style.height = `${size}px`;
  container.style.display = 'flex';
  container.style.alignItems = 'center';
  container.style.justifyContent = 'center';
  container.style.userSelect = 'none';

  return container;
}

function createStatusTractorMarkerContent(status, { iconSize = 38, rotation = 0 } = {}) {
  const container = createVehicleMarkerContainer(iconSize);
  const image = document.createElement('img');
  image.src = TRACTOR_MARKER_IMAGES[status] || TRACTOR_MARKER_IMAGES.offline;
  image.alt = `${status || 'offline'} tractor`;
  image.width = iconSize;
  image.height = iconSize;
  image.draggable = false;
  image.style.display = 'block';
  image.style.width = `${iconSize}px`;
  image.style.height = `${iconSize}px`;
  image.style.objectFit = 'contain';
  image.style.transform = `rotate(${rotation}deg)`;
  image.style.transformOrigin = 'center';
  const statusFilter = status === 'parked' ? 'hue-rotate(155deg) saturate(0.9)' : '';
  image.style.filter = `${statusFilter} drop-shadow(0 8px 14px rgba(15, 23, 42, 0.32))`.trim();
  container.appendChild(image);

  return { element: container, image };
}

function createMarkerShell({
  size,
  background = 'rgba(255, 255, 255, 0.95)',
  border = '2px solid #ffffff',
  borderRadius = '9999px',
  shadow = '0 10px 22px rgba(15, 23, 42, 0.28)',
} = {}) {
  const shell = document.createElement('div');
  shell.style.width = `${size}px`;
  shell.style.height = `${size}px`;
  shell.style.display = 'flex';
  shell.style.alignItems = 'center';
  shell.style.justifyContent = 'center';
  shell.style.borderRadius = borderRadius;
  shell.style.background = background;
  shell.style.border = border;
  shell.style.boxShadow = shadow;
  shell.style.userSelect = 'none';

  return shell;
}

function createSvgPath(pathData, fill, rotation = 0, { size = 24, stroke = '#ffffff', strokeWidth = '1.5' } = {}) {
  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  svg.setAttribute('viewBox', '0 0 24 24');
  svg.setAttribute('width', String(size));
  svg.setAttribute('height', String(size));
  svg.style.transform = `rotate(${rotation}deg)`;
  svg.style.transformOrigin = 'center';

  const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
  path.setAttribute('d', pathData);
  path.setAttribute('fill', fill);
  path.setAttribute('stroke', stroke);
  path.setAttribute('stroke-width', strokeWidth);
  path.setAttribute('stroke-linejoin', 'round');

  svg.appendChild(path);

  return { svg, path };
}

function createTractorMarkerContent(status, rotation = 0, { fillColor, iconSize = 32 } = {}) {
  const colors = { moving: '#16a34a', idling: '#ca8a04', parked: '#0284c7', offline: '#dc2626' };
  const color = fillColor || colors[status] || colors.offline;
  const container = createVehicleMarkerContainer(iconSize);
  const { svg } = createSvgPath(TRACTOR_PATH, color, rotation, {
    size: iconSize,
    stroke: 'none',
    strokeWidth: '0',
  });
  svg.style.display = 'block';
  svg.style.filter = 'drop-shadow(0 8px 14px rgba(15, 23, 42, 0.32))';
  container.appendChild(svg);

  return { element: container, svg };
}

function createClusterMarkerContent(count) {
  const size = count >= 500 ? 60 : count >= 100 ? 52 : count >= 25 ? 44 : 36;
  const shell = createMarkerShell({
    size,
    background: '#6d6af8',
    border: '2px solid #ffffff',
    shadow: '0 12px 24px rgba(79, 70, 229, 0.35)',
  });
  shell.style.color = '#ffffff';
  shell.style.fontWeight = '700';
  shell.style.fontSize = count >= 100 ? '13px' : '12px';
  shell.textContent = getClusterLabel(count);

  return shell;
}

function getClusterLabel(count) {
  return count > 999 ? '999+' : String(count);
}

function createTrackBadgeMarkerContent(label, fillColor, tooltip = '') {
  const shell = createMarkerShell({
    size: 30,
    background: fillColor,
    border: '2px solid #ffffff',
    shadow: '0 8px 18px rgba(15, 23, 42, 0.24)',
  });
  shell.style.color = '#ffffff';
  shell.style.fontWeight = '700';
  shell.style.fontSize = '11px';
  shell.textContent = label;

  if (tooltip) {
    shell.style.position = 'relative';
    shell.style.cursor = 'help';
    shell.tabIndex = 0;
    shell.setAttribute('role', 'button');
    shell.setAttribute('aria-label', tooltip);
    shell.title = tooltip;

    const tooltipBubble = document.createElement('div');
    tooltipBubble.textContent = tooltip;
    tooltipBubble.setAttribute('role', 'tooltip');
    tooltipBubble.style.position = 'absolute';
    tooltipBubble.style.left = '50%';
    tooltipBubble.style.bottom = 'calc(100% + 10px)';
    tooltipBubble.style.transform = 'translateX(-50%)';
    tooltipBubble.style.width = 'min(260px, 70vw)';
    tooltipBubble.style.padding = '8px 10px';
    tooltipBubble.style.borderRadius = '8px';
    tooltipBubble.style.background = 'rgba(17, 24, 39, 0.96)';
    tooltipBubble.style.color = '#ffffff';
    tooltipBubble.style.fontSize = '11px';
    tooltipBubble.style.fontWeight = '500';
    tooltipBubble.style.lineHeight = '1.35';
    tooltipBubble.style.textAlign = 'left';
    tooltipBubble.style.whiteSpace = 'normal';
    tooltipBubble.style.pointerEvents = 'none';
    tooltipBubble.style.opacity = '0';
    tooltipBubble.style.visibility = 'hidden';
    tooltipBubble.style.transition = 'opacity 120ms ease';
    tooltipBubble.style.zIndex = '1000';
    shell.appendChild(tooltipBubble);

    const showTooltip = () => {
      tooltipBubble.style.opacity = '1';
      tooltipBubble.style.visibility = 'visible';
    };
    const hideTooltip = () => {
      tooltipBubble.style.opacity = '0';
      tooltipBubble.style.visibility = 'hidden';
    };
    shell.addEventListener('mouseenter', showTooltip);
    shell.addEventListener('mouseleave', hideTooltip);
    shell.addEventListener('focus', showTooltip);
    shell.addEventListener('blur', hideTooltip);
    shell.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') hideTooltip();
    });
    shell.addEventListener('click', (event) => {
      event.stopPropagation();
      const isVisible = tooltipBubble.style.visibility === 'visible';
      if (isVisible) hideTooltip();
      else showTooltip();
    });
  }

  return shell;
}

function createAdvancedMarker({ position, title, content, zIndex = 0, clickable = false }) {
  return new AdvancedMarkerElementClass({
    map,
    position,
    title,
    content,
    zIndex,
    ...(clickable ? { gmpClickable: true } : {}),
  });
}

function addAdvancedMarkerClickListener(marker, handler) {
  marker.addEventListener('gmp-click', handler);
}

function removeAdvancedMarker(marker) {
  if (marker) {
    marker.map = null;
  }
}

function getClusteredDevices() {
  const zoom = map?.getZoom() ?? 6;
  const bounds = map?.getBounds();
  const devices = deviceList.value.filter((device) => device.lat && device.lng);

  const visibleDevices = bounds
    ? devices.filter((device) => bounds.contains(new google.maps.LatLng(parseFloat(device.lat), parseFloat(device.lng))))
    : devices;

  if (!visibleDevices.length) {
    return [];
  }

  const gridSize = 90;
  const scale = 256 * Math.pow(2, zoom);
  const clusterMap = new Map();

  visibleDevices.forEach((device) => {
    const lat = parseFloat(device.lat);
    const lng = parseFloat(device.lng);
    const sinLat = Math.sin((lat * Math.PI) / 180);
    const x = ((lng + 180) / 360) * scale;
    const y = (0.5 - Math.log((1 + sinLat) / (1 - sinLat)) / (4 * Math.PI)) * scale;
    const key = `${Math.floor(x / gridSize)}:${Math.floor(y / gridSize)}`;

    if (!clusterMap.has(key)) {
      clusterMap.set(key, []);
    }

    clusterMap.get(key).push(device);
  });

  return Array.from(clusterMap.values()).map((clusterDevices) => {
    const center = clusterDevices.reduce((carry, device) => {
      carry.lat += parseFloat(device.lat);
      carry.lng += parseFloat(device.lng);
      return carry;
    }, { lat: 0, lng: 0 });

    const clusterBounds = new google.maps.LatLngBounds();
    clusterDevices.forEach((device) => {
      clusterBounds.extend(new google.maps.LatLng(parseFloat(device.lat), parseFloat(device.lng)));
    });

    return {
      count: clusterDevices.length,
      devices: clusterDevices,
      center: {
        lat: center.lat / clusterDevices.length,
        lng: center.lng / clusterDevices.length,
      },
      bounds: clusterBounds,
    };
  });
}

// Calculate bearing between two lat/lng points (returns degrees 0-360, 0=north)
function calcBearing(lat1, lng1, lat2, lng2) {
  const toRad = (d) => d * Math.PI / 180;
  const toDeg = (r) => r * 180 / Math.PI;
  const dLng = toRad(lng2 - lng1);
  const y = Math.sin(dLng) * Math.cos(toRad(lat2));
  const x = Math.cos(toRad(lat1)) * Math.sin(toRad(lat2)) - Math.sin(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.cos(dLng);
  return (toDeg(Math.atan2(y, x)) + 360) % 360;
}

function clearRenderedMarkers() {
  Object.values(markers).forEach((marker) => removeAdvancedMarker(marker));
  clusterMarkers.forEach((marker) => removeAdvancedMarker(marker));

  markers = {};
  clusterMarkers = [];
  infoWindows = {};
  previousPositions = {};
}

function createMarkers() {
  if (!map || !window.google?.maps || !AdvancedMarkerElementClass) return;
  clearRenderedMarkers();

  getClusteredDevices().forEach((cluster) => {
    if (cluster.count === 1) {
      const device = cluster.devices[0];
      const lat = parseFloat(device.lat);
      const lng = parseFloat(device.lng);
      const corrected = correctCoords(lat, lng);

      previousPositions[device.imei] = { lat: corrected.lat, lng: corrected.lng };

      const { element } = createStatusTractorMarkerContent(device.status, { rotation: 0 });
      const marker = createAdvancedMarker({
        position: corrected,
        title: device.tractor?.no_plate || device.device_name || device.imei,
        content: element,
        clickable: true,
      });

      addAdvancedMarkerClickListener(marker, () => {
        map.setZoom(16);
        map.panTo(marker.position);
        const found = deviceList.value.find((item) => item.imei === device.imei);
        if (found) {
          selectedDevice.value = found;
          showDetailSidebar.value = true;
          reverseGeocode(corrected.lat, corrected.lng);
        }
      });

      markers[`marker_${device.imei}`] = marker;
      return;
    }

    const clusterMarker = createAdvancedMarker({
      position: cluster.center,
      content: createClusterMarkerContent(cluster.count),
      zIndex: 10,
      clickable: true,
    });

    addAdvancedMarkerClickListener(clusterMarker, () => {
      map.fitBounds(cluster.bounds, 80);
    });

    clusterMarkers.push(clusterMarker);
  });
}

function updateMarkers() {
  if (!map || !window.google?.maps) return;
  createMarkers();
}

function mergeDevicePayload(currentDevice, nextDevice) {
  if (!currentDevice) {
    return nextDevice;
  }

  return {
    ...currentDevice,
    ...nextDevice,
    tractor: currentDevice.tractor || nextDevice.tractor
      ? {
          ...(currentDevice.tractor ?? {}),
          ...(nextDevice.tractor ?? {}),
        }
      : null,
  };
}

function upsertDevice(device) {
  const index = deviceList.value.findIndex((item) => item.id === device.id);

  if (index === -1) {
    deviceList.value.push(device);
    return device;
  }

  const mergedDevice = mergeDevicePayload(deviceList.value[index], device);
  deviceList.value[index] = mergedDevice;

  return mergedDevice;
}

async function loadSelectedDevice(deviceId) {
  try {
    const { data } = await axios.get(`/live-view/follow/${deviceId}`);

    if (!data.device) {
      return;
    }

    const mergedDevice = upsertDevice(data.device);

    if (selectedDevice.value?.id === deviceId) {
      selectedDevice.value = mergeDevicePayload(selectedDevice.value, mergedDevice);
    }
  } catch (error) {
    console.error('Failed to load selected device details:', error);
  }
}

// ═══════════════ DEVICE SELECTION ═══════════════
function reverseGeocode(lat, lng) {
  deviceAddress.value = 'Loading address...';
  if (!window.google || !google.maps.Geocoder) { deviceAddress.value = `${lat}, ${lng}`; return; }
  const geocoder = new google.maps.Geocoder();
  geocoder.geocode({ location: { lat: parseFloat(lat), lng: parseFloat(lng) } }, (results, status) => {
    if (status === 'OK' && results?.[0]) deviceAddress.value = results[0].formatted_address;
    else deviceAddress.value = `${parseFloat(lat).toFixed(6)}, ${parseFloat(lng).toFixed(6)}`;
  });
}

function selectDevice(device) {
  selectedDevice.value = device;
  showDetailSidebar.value = true;
  if (device.lat && device.lng) {
    const corrected = correctCoords(parseFloat(device.lat), parseFloat(device.lng));
    reverseGeocode(corrected.lat, corrected.lng);
    if (map) { map.setZoom(16); map.panTo(corrected); }
  }
  stopFollow({ resumePolling: isFollowing.value });
  void loadSelectedDevice(device.id);
}

function closeDeviceDetails() {
  stopFollow({ resumePolling: isFollowing.value });
  showDetailSidebar.value = false;
}

async function liveFollow() {
  if (isFollowing.value) {
    stopFollow();
    followError.value = '';
    return;
  }

  stopFollow({ resumePolling: false });
  if (!selectedDevice.value) return;
  followError.value = '';
  isFollowing.value = true;
  followSessionId += 1;
  const currentFollowSessionId = followSessionId;
  const deviceId = selectedDevice.value.id;
  const imei = selectedDevice.value.imei;
  const m = markers['marker_' + imei];
  if (m && map) {
    map.setZoom(16);
    map.panTo(m.position);
  } else if (map && selectedDevice.value.lat && selectedDevice.value.lng) {
    const corrected = correctCoords(parseFloat(selectedDevice.value.lat), parseFloat(selectedDevice.value.lng));
    map.setZoom(16);
    map.panTo(corrected);
  }

  // Stop all-device polling while following a single device.
  stopRefreshLoop();

  const started = await fetchFollowedDevice(deviceId, currentFollowSessionId);

  if (!started) {
    if (currentFollowSessionId === followSessionId && isFollowing.value) {
      stopFollow();
      followError.value = 'Unable to start live follow. Please try again.';
    }
    return;
  }

  followInterval = setInterval(() => {
    if (!followRequestPending.value) {
      void fetchFollowedDevice(deviceId, currentFollowSessionId);
    }
  }, 10000);
}

async function fetchFollowedDevice(deviceId, currentFollowSessionId) {
  const requestId = ++latestFollowRequestId;
  followRequestPending.value = true;

  try {
    const { data } = await axios.get(`/live-view/follow/${deviceId}`);
    if (
      requestId !== latestFollowRequestId ||
      currentFollowSessionId !== followSessionId ||
      !isFollowing.value ||
      selectedDevice.value?.id !== deviceId
    ) {
      return false;
    }

    if (data.device) {
      followError.value = '';
      const mergedDevice = upsertDevice(data.device);
      selectedDevice.value = mergeDevicePayload(selectedDevice.value, mergedDevice);

      if (data.device.lat && data.device.lng) {
        if (map) {
          const corrected = correctCoords(parseFloat(data.device.lat), parseFloat(data.device.lng));
          map.panTo(corrected);
        }

        createMarkers();
      }

      return true;
    }
  } catch (e) {
    if (currentFollowSessionId === followSessionId && isFollowing.value) {
      followError.value = 'Live update delayed. Retrying...';
    }
    console.error('Follow refresh failed:', e);
  } finally {
    if (requestId === latestFollowRequestId) {
      followRequestPending.value = false;
    }
  }

  return false;
}

function stopRefreshLoop() {
  if (refreshInterval) {
    clearInterval(refreshInterval);
    refreshInterval = null;
  }
}

function startRefreshLoop({ immediate = false } = {}) {
  stopRefreshLoop();
  refreshCountdown.value = refreshIntervalSec.value;

  if (immediate) {
    void refreshData();
  }

  refreshInterval = setInterval(refreshData, refreshIntervalSec.value * 1000);
}

function changeRefreshInterval() {
  refreshCountdown.value = refreshIntervalSec.value;

  if (isFollowing.value) {
    return;
  }

  startRefreshLoop({ immediate: true });
}

function manualRefresh() {
  if (isFollowing.value) {
    return;
  }

  refreshCountdown.value = refreshIntervalSec.value;
  void refreshData();
  // Reset the refresh timer so the next auto-refresh is a full interval away
  startRefreshLoop();
}

function stopFollow({ resumePolling = true } = {}) {
  isFollowing.value = false;
  followSessionId += 1;
  if (followInterval) { clearInterval(followInterval); followInterval = null; }

  if (resumePolling) {
    startRefreshLoop({ immediate: true });
  }
}

// ═══════════════ DATA REFRESH ═══════════════
async function refreshData() {
  const requestId = ++latestRefreshRequestId;
  devicesLoading.value = true;

  try {
    const { data } = await axios.get('/live-view/locations');
    if (requestId !== latestRefreshRequestId || isFollowing.value) {
      return;
    }

    if (data.devices) {
      deviceList.value = data.devices;

      if (map && Object.keys(markers).length === 0) {
        createMarkers();
      } else {
        updateMarkers();
      }

      if (selectedDevice.value) {
        const updated = data.devices.find(d => d.id === selectedDevice.value.id);
        if (updated) {
          selectedDevice.value = mergeDevicePayload(selectedDevice.value, updated);
        }
      }
    }
  } catch (e) { console.error('Refresh failed:', e); }
  finally {
    if (requestId === latestRefreshRequestId) {
      devicesLoading.value = false;
    }
  }

  if (requestId === latestRefreshRequestId && !isFollowing.value) {
    refreshCountdown.value = refreshIntervalSec.value;
  }
}

// ═══════════════ TRACKS ═══════════════
async function searchTracks() {
  if (!trackDeviceId.value) return;
  trackLoading.value = true;
  clearTracks();
  trackError.value = '';
  trackWarnings.value = [];

  try {
    const params = { device_id: trackDeviceId.value, period: trackPeriod.value };
    if (trackPeriod.value === 'custom') {
      params.from = trackFrom.value;
      params.to = trackTo.value;
    }

    const { data } = await axios.get('/live-view/track-data', { params });

    trackWarnings.value = data.warnings || [];

    if (data.track) {
      trackData.value = data.track;
      playbackIndex.value = 0;
      if (data.track.points?.length > 0) drawTrack(data.track.points);
    }

    if (!data.success) {
      trackError.value = data.warnings?.[0]?.message || 'Track data is temporarily unavailable.';
    }
  } catch (e) {
    console.error('Failed to load tracks:', e);
    trackError.value = e.response?.data?.message || 'Unable to load track history.';
    trackData.value = { points: [], totalPoints: 0, distance: 0, maxSpeed: 0, avgSpeed: 0, duration: 0, startTime: null, endTime: null };
  }
  trackLoading.value = false;
}

function drawTrack(points) {
  if (!map || !points.length || !AdvancedMarkerElementClass) return;
  const segments = groupTrackPointsBySegment(points);
  const path = points.map(p => new google.maps.LatLng(p.lat, p.lng));
  let pointOffset = 0;
  trackSegmentPaths = segments.map(segmentPoints => segmentPoints.map(p => new google.maps.LatLng(p.lat, p.lng)));
  trackSegmentRanges = segments.map((segmentPoints) => {
    const range = { start: pointOffset, end: pointOffset + segmentPoints.length - 1 };
    pointOffset += segmentPoints.length;
    return range;
  });
  trackProgressLengths = segments.map(() => -1);

  trackPolylines = trackSegmentPaths.map(segmentPath => new google.maps.Polyline({
    path: segmentPath,
    geodesic: true, strokeColor: '#6366f1', strokeOpacity: 0.45, strokeWeight: 4, map,
  }));
  trackProgressPolylines = segments.map(() => new google.maps.Polyline({
    path: [], geodesic: true, strokeColor: '#4f46e5', strokeOpacity: 0.95, strokeWeight: 4, map,
  }));
  updateTrackProgress();

  // Start marker
  trackMarkers.push(createAdvancedMarker({
    position: path[0],
    content: createTrackBadgeMarkerContent('S', '#22c55e', 'Track start'),
    zIndex: 100,
  }));

  // End marker
  if (path.length > 1) {
    trackMarkers.push(createAdvancedMarker({
      position: path[path.length - 1],
      content: createTrackBadgeMarkerContent('E', '#ef4444', 'Track end'),
      zIndex: 100,
    }));
  }

  (trackData.value?.gaps || []).forEach((gap) => {
    if (!Number.isFinite(Number(gap.markerLat)) || !Number.isFinite(Number(gap.markerLng))) return;
    const tooltip = trackGapTooltip(gap);
    trackMarkers.push(createAdvancedMarker({
      position: { lat: Number(gap.markerLat), lng: Number(gap.markerLng) },
      content: createTrackBadgeMarkerContent('!', '#f59e0b', tooltip),
      title: tooltip,
      zIndex: 110,
    }));
  });

  // Playback marker
  const playbackContent = createStatusTractorMarkerContent('moving', {
    iconSize: 40,
    rotation: 0,
  });
  playbackMarkerImage = playbackContent.image;
  playbackMarker = createAdvancedMarker({
    position: path[0],
    content: playbackContent.element,
    zIndex: 200,
  });

  fitTrackBounds();
}

function fitTrackBounds() {
  if (!trackData.value?.points?.length || !map) return;
  const bounds = new google.maps.LatLngBounds();
  trackData.value.points.forEach(p => bounds.extend(new google.maps.LatLng(p.lat, p.lng)));
  map.fitBounds(bounds, 60);
}

function clearTracks() {
  stopPlayback();
  trackPolylines.forEach(polyline => polyline.setMap(null));
  trackProgressPolylines.forEach(polyline => polyline.setMap(null));
  trackPolylines = [];
  trackProgressPolylines = [];
  trackSegmentPaths = [];
  trackSegmentRanges = [];
  trackProgressLengths = [];
  if (playbackMarker) { playbackMarker.map = null; playbackMarker = null; }
  playbackMarkerImage = null;
  trackMarkers.forEach(m => { m.map = null; });
  trackMarkers = [];
  trackData.value = null;
  playbackIndex.value = 0;
}

function groupTrackPointsBySegment(points) {
  const groups = new Map();
  points.forEach((point) => {
    const segment = point.segment ?? 0;
    if (!groups.has(segment)) groups.set(segment, []);
    groups.get(segment).push(point);
  });
  return Array.from(groups.values());
}

function trackGapTooltip(gap) {
  const duration = formatDuration(Number(gap.duration) || 0);
  const resumedAt = formatDateTime(gap.toTime);
  if (gap.reason === 'time_gap') {
    return `Reporting gap: no GPS points were received for ${duration}. The route resumes at ${resumedAt} and is shown as a new segment.`;
  }

  const distance = Number(gap.distance) || 0;
  return `GPS spike removed: this point implied an impossible ${distance.toFixed(1)} km jump in ${duration}, so it was excluded from the route and totals.`;
}

function updateTrackProgress() {
  if (!map || !trackData.value?.points?.length) return;

  trackProgressPolylines.forEach((polyline, index) => {
    const range = trackSegmentRanges[index];
    if (!range) return;
    const desiredLength = playbackIndex.value < range.start
      ? 0
      : Math.min(playbackIndex.value - range.start + 1, trackSegmentPaths[index].length);
    if (trackProgressLengths[index] === desiredLength) return;
    trackProgressLengths[index] = desiredLength;
    polyline.setPath(trackSegmentPaths[index].slice(0, desiredLength));
  });
}

function quickTrack() {
  if (!selectedDevice.value) return;
  trackDeviceId.value = selectedDevice.value.id;
  trackPeriod.value = 'today';
  activeTab.value = 'tracks';
  showDetailSidebar.value = false;
  nextTick(() => searchTracks());
}

// ═══════════════ PLAYBACK ═══════════════
function togglePlayback() {
  if (isPlaying.value) stopPlayback();
  else startPlayback();
}

function handlePlaybackKeydown(event) {
  if (!trackData.value?.points?.length) return;
  if (event.key === ' ') {
    event.preventDefault();
    togglePlayback();
  } else if (event.key === 'ArrowRight') {
    event.preventDefault();
    playbackIndex.value = Math.min(playbackIndex.value + 1, trackData.value.points.length - 1);
  } else if (event.key === 'ArrowLeft') {
    event.preventDefault();
    playbackIndex.value = Math.max(playbackIndex.value - 1, 0);
  }
}

function startPlayback() {
  if (!trackData.value?.points?.length) return;
  if (playbackIndex.value >= trackData.value.points.length - 1) playbackIndex.value = 0;
  isPlaying.value = true;
  lastPlaybackTimestamp = null;
  playbackAnimationId = requestAnimationFrame(playbackTick);
}

function stopPlayback() {
  isPlaying.value = false;
  if (playbackAnimationId) { cancelAnimationFrame(playbackAnimationId); playbackAnimationId = null; }
  lastPlaybackTimestamp = null;
}

function playbackTick(timestamp) {
  if (!isPlaying.value) return;
  if (lastPlaybackTimestamp === null) {
    lastPlaybackTimestamp = timestamp;
    playbackAnimationId = requestAnimationFrame(playbackTick);
    return;
  }
  const elapsed = timestamp - lastPlaybackTimestamp;
  const pointsPerMs = playbackSpeed.value / 100;
  const pointsToAdvance = elapsed * pointsPerMs;
  if (pointsToAdvance >= 1) {
    const newIndex = Math.min(playbackIndex.value + Math.floor(pointsToAdvance), trackData.value.points.length - 1);
    playbackIndex.value = newIndex;
    lastPlaybackTimestamp = timestamp;
    if (newIndex >= trackData.value.points.length - 1) { stopPlayback(); return; }
  }
  playbackAnimationId = requestAnimationFrame(playbackTick);
}

watch(playbackIndex, (idx, oldIdx) => {
  if (!trackData.value?.points?.length || !map) return;
  const point = trackData.value.points[idx];
  if (!point) return;
  const pos = new google.maps.LatLng(point.lat, point.lng);

  if (playbackMarker) {
    playbackMarker.position = pos;
  }

  updateTrackProgress();
  if (isPlaying.value && map) {
    const bounds = map.getBounds();
    if (bounds && !bounds.contains(pos)) map.panTo(pos);
  }
});

// ═══════════════ SHARE ═══════════════
function openShareModal() {
  shareUrl.value = '';
  shareExpires.value = '';
  shareCopied.value = false;
  shareDuration.value = 1;
  showShareModal.value = true;
}

async function generateShareLink() {
  if (!selectedDevice.value) return;
  shareLoading.value = true;
  try {
    const { data } = await axios.post('/live-view/share', {
      device_id: selectedDevice.value.id,
      duration: shareDuration.value,
    });
    if (data.success) {
      shareUrl.value = data.url;
      shareExpires.value = data.expires;
    }
  } catch (e) { console.error('Failed to generate share link:', e); }
  shareLoading.value = false;
}

function copyShareUrl() {
  if (!shareUrl.value) return;
  navigator.clipboard.writeText(shareUrl.value).then(() => {
    shareCopied.value = true;
    setTimeout(() => { shareCopied.value = false; }, 2000);
  });
}

// ═══════════════ MAP TYPE ═══════════════
function setMapType(typeId) {
  currentMapType.value = typeId;
  if (map) map.setMapTypeId(typeId);
}

// ═══════════════ HELPERS ═══════════════
function statusLabel(status) {
  return { moving: 'Moving', idling: 'Idling', parked: 'Parked', offline: 'Offline' }[status] || 'Offline';
}

function statusIconBackgroundClass(status) {
  return {
    moving: 'bg-green-100 dark:bg-green-900/40',
    idling: 'bg-amber-100 dark:bg-amber-900/40',
    parked: 'bg-sky-100 dark:bg-sky-900/40',
    offline: 'bg-red-100 dark:bg-red-900/40',
  }[status] || 'bg-red-100 dark:bg-red-900/40';
}

function statusIconTextClass(status) {
  return {
    moving: 'text-green-600 dark:text-green-400',
    idling: 'text-amber-600 dark:text-amber-400',
    parked: 'text-sky-600 dark:text-sky-400',
    offline: 'text-red-600 dark:text-red-400',
  }[status] || 'text-red-600 dark:text-red-400';
}

function statusBadgeClass(status) {
  return {
    moving: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    idling: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    parked: 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
    offline: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
  }[status] || 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300';
}

function statusDotClass(status) {
  return {
    moving: 'bg-green-500',
    idling: 'bg-amber-500',
    parked: 'bg-sky-500',
    offline: 'bg-red-500',
  }[status] || 'bg-red-500';
}

function formatTimeAgo(minutes) {
  if (minutes == null || minutes >= 999) return 'N/A';
  if (minutes < 1) return 'Just now';
  if (minutes < 60) return `${minutes}m ago`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours}h ago`;
  return `${Math.floor(hours / 24)}d ago`;
}

function parseDateTime(dt) {
  if (!dt) return null;

  const value = new Date(dt);

  return Number.isNaN(value.getTime()) ? null : value;
}

function formatClockTime(dt) {
  const value = parseDateTime(dt);

  if (!value) return '--:--:--';

  return new Intl.DateTimeFormat('en-PH', {
    timeZone: DISPLAY_TIME_ZONE,
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  }).format(value);
}

function formatDateTime(dt) {
  const value = parseDateTime(dt);

  if (!value) return dt ? String(dt) : 'N/A';

  const parts = new Intl.DateTimeFormat('en-PH', {
    timeZone: DISPLAY_TIME_ZONE,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
  }).formatToParts(value).reduce((carry, part) => {
    if (part.type !== 'literal') {
      carry[part.type] = part.value;
    }

    return carry;
  }, {});

  return `${parts.year}-${parts.month}-${parts.day} ${parts.hour}:${parts.minute} ${parts.dayPeriod}`;
}

function formatDuration(seconds) {
  if (!seconds) return '0m';
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  if (h > 0) return `${h}h ${m}m`;
  return `${m}m`;
}

// ═══════════════ LIFECYCLE ═══════════════
onMounted(async () => {
  await nextTick();
  await initMap();
  await refreshData();
  startRefreshLoop();
  countdownInterval = setInterval(() => {
    if (refreshCountdown.value <= 0) {
      refreshCountdown.value = refreshIntervalSec.value;
    } else {
      refreshCountdown.value -= 1;
    }
  }, 1000);
});

onUnmounted(() => {
  stopRefreshLoop();
  if (countdownInterval) clearInterval(countdownInterval);
  if (mapErrorPoll) window.clearInterval(mapErrorPoll);
  if (mapIdleListener) google.maps.event.removeListener(mapIdleListener);
  stopFollow({ resumePolling: false });
  stopPlayback();
});
</script>

<style scoped>
.slide-right-enter-active,
.slide-right-leave-active {
  transition: transform 0.3s ease;
}
.slide-right-enter-from,
.slide-right-leave-to {
  transform: translateX(100%);
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
input[type=range]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: #4f46e5;
  cursor: pointer;
  border: 2px solid white;
  box-shadow: 0 1px 3px rgba(0,0,0,0.3);
}
input[type=range]::-moz-range-thumb {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: #4f46e5;
  cursor: pointer;
  border: 2px solid white;
  box-shadow: 0 1px 3px rgba(0,0,0,0.3);
}
</style>
