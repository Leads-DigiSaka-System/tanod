<template>
  <AppLayout>
    <Head title="Live View" />

    <div class="flex h-[calc(100vh-4rem)] overflow-hidden -m-4 sm:-m-6 lg:-m-8">
      <!-- Left Panel -->
      <div class="w-80 flex-shrink-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col overflow-hidden">
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            Live View
          </h2>
        </div>

        <!-- Tabs: Objects / Tracks -->
        <div class="flex border-b border-gray-200 dark:border-gray-700">
          <button @click="activeTab = 'objects'"
            :class="['flex-1 py-2.5 text-sm font-medium text-center border-b-2 transition-colors',
              activeTab === 'objects' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300']">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            Objects
          </button>
          <button @click="activeTab = 'tracks'"
            :class="['flex-1 py-2.5 text-sm font-medium text-center border-b-2 transition-colors',
              activeTab === 'tracks' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300']">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            Tracks
          </button>
        </div>

        <!-- ═══════════════ OBJECTS TAB ═══════════════ -->
        <div v-show="activeTab === 'objects'" class="flex-1 flex flex-col overflow-hidden">
          <!-- Search -->
          <div class="px-3 py-2.5">
            <div class="relative">
              <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input v-model="deviceSearch" type="text" placeholder="Search IMEI, name, plate..."
                class="block w-full ps-10 p-2 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" />
            </div>
          </div>

          <!-- State Filter Pills -->
          <div class="flex px-3 gap-1.5 pb-2.5 flex-wrap">
            <button @click="activeState = 'all'"
              :class="['px-2.5 py-1 text-xs rounded-full font-medium transition-colors',
                activeState === 'all' ? 'bg-indigo-600 text-white dark:bg-indigo-500' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600']">
              All ({{ deviceList.length }})
            </button>
            <button @click="activeState = 'moving'"
              :class="['px-2.5 py-1 text-xs rounded-full font-medium transition-colors flex items-center gap-1',
                activeState === 'moving' ? 'bg-green-600 text-white dark:bg-green-500' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600']">
              <span class="w-1.5 h-1.5 rounded-full" :class="activeState === 'moving' ? 'bg-green-200' : 'bg-green-500'"></span>
              Online ({{ onlineCount }})
            </button>
            <button @click="activeState = 'idle'"
              :class="['px-2.5 py-1 text-xs rounded-full font-medium transition-colors flex items-center gap-1',
                activeState === 'idle' ? 'bg-yellow-500 text-white dark:bg-yellow-400 dark:text-gray-900' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600']">
              <span class="w-1.5 h-1.5 rounded-full" :class="activeState === 'idle' ? 'bg-yellow-200' : 'bg-yellow-500'"></span>
              Idle ({{ idleCount }})
            </button>
            <button @click="activeState = 'offline'"
              :class="['px-2.5 py-1 text-xs rounded-full font-medium transition-colors flex items-center gap-1',
                activeState === 'offline' ? 'bg-red-600 text-white dark:bg-red-500' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600']">
              <span class="w-1.5 h-1.5 rounded-full" :class="activeState === 'offline' ? 'bg-red-200' : 'bg-red-500'"></span>
              Offline ({{ offlineCount }})
            </button>
          </div>

          <!-- Group Filter & Device List -->
          <div class="flex-1 overflow-y-auto px-3 pb-3 space-y-1.5">
            <select v-model="selectedGroup"
              class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 p-2 mb-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
              <option value="">All Groups</option>
              <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
            </select>

            <div v-for="device in filteredDevices" :key="device.id"
              @click="selectDevice(device)"
              :class="['p-3 rounded-lg cursor-pointer border transition-all duration-200',
                selectedDevice?.id === device.id
                  ? 'border-indigo-500 bg-indigo-50 shadow-sm dark:bg-indigo-900/30 dark:border-indigo-400'
                  : 'border-gray-200 bg-white hover:border-gray-300 hover:shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:hover:border-gray-600']">
              <div class="flex items-center gap-2.5">
                <div :class="['w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0',
                  device.status === 'moving' ? 'bg-green-100 dark:bg-green-900/50' :
                  device.status === 'idle' ? 'bg-yellow-100 dark:bg-yellow-900/50' : 'bg-red-100 dark:bg-red-900/50']">
                  <svg class="w-4 h-4" :class="device.status === 'moving' ? 'text-green-600 dark:text-green-400' :
                    device.status === 'idle' ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400'" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                    <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-1h3.05a2.5 2.5 0 014.9 0H19a1 1 0 001-1v-5a1 1 0 00-.293-.707l-3-3A1 1 0 0016 4H3z" />
                  </svg>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900 dark:text-white truncate">
                      {{ device.tractor?.no_plate || device.device_name || device.imei }}
                    </span>
                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium',
                      device.status === 'moving' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' :
                      device.status === 'idle' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300']">
                      {{ device.status === 'moving' ? 'Moving' : device.status === 'idle' ? 'Idle' : 'Offline' }}
                    </span>
                  </div>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ device.imei }}</p>
                  <div v-if="device.speed > 0" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" /></svg>
                    {{ device.speed }} km/h
                  </div>
                </div>
              </div>
            </div>
            <div v-if="!filteredDevices.length" class="text-center py-10">
              <svg class="mx-auto w-10 h-10 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <p class="text-sm text-gray-500 dark:text-gray-400">No devices found.</p>
            </div>
          </div>
        </div>

        <!-- ═══════════════ TRACKS TAB ═══════════════ -->
        <div v-show="activeTab === 'tracks'" class="flex-1 flex flex-col overflow-hidden">
          <div class="p-3 space-y-3 border-b border-gray-200 dark:border-gray-700">
            <!-- Device select -->
            <div>
              <label class="block mb-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Device</label>
              <select v-model="trackDeviceId"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                <option value="" disabled>Select Device</option>
                <option v-for="d in deviceList" :key="d.id" :value="d.id">
                  {{ d.tractor?.no_plate || d.device_name || d.imei }}
                </option>
              </select>
            </div>

            <!-- Period select -->
            <div>
              <label class="block mb-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Period</label>
              <select v-model="trackPeriod"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
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
                <label class="block mb-1 text-xs text-gray-500 dark:text-gray-400">From</label>
                <input v-model="trackFrom" type="date"
                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
              </div>
              <div>
                <label class="block mb-1 text-xs text-gray-500 dark:text-gray-400">To</label>
                <input v-model="trackTo" type="date"
                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
              </div>
            </div>

            <!-- Search / Clear Buttons -->
            <div class="flex gap-2">
              <button @click="searchTracks" :disabled="!trackDeviceId || trackLoading || (trackPeriod === 'custom' && (!trackFrom || !trackTo))"
                class="flex-1 text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-4 py-2.5 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-indigo-500 dark:hover:bg-indigo-600 dark:focus:ring-indigo-800 flex items-center justify-center gap-2 transition-colors">
                <svg v-if="trackLoading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                {{ trackLoading ? 'Loading...' : 'Search' }}
              </button>
              <button v-if="trackData" @click="clearTracks"
                class="px-4 py-2.5 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 font-medium rounded-lg text-sm dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors">
                Clear
              </button>
            </div>
          </div>

          <!-- Track Info Panel -->
          <div v-if="trackData && trackData.totalPoints > 0" class="flex-1 overflow-y-auto">
            <!-- Summary Stats -->
            <div class="grid grid-cols-2 gap-px bg-gray-200 dark:bg-gray-700">
              <div class="bg-white dark:bg-gray-800 p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400">Distance</p>
                <p class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ trackData.distance }} <span class="text-xs font-normal text-gray-400">km</span></p>
              </div>
              <div class="bg-white dark:bg-gray-800 p-3 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400">Duration</p>
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
            </div>

            <!-- GPS Points count & time -->
            <div class="p-3 border-b border-gray-200 dark:border-gray-700 space-y-1.5">
              <div class="flex justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">GPS Points</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ trackData.totalPoints.toLocaleString() }}</span>
              </div>
              <div v-if="trackData.startTime" class="flex justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">Start</span>
                <span class="font-medium text-gray-900 dark:text-white text-xs">{{ formatDateTime(trackData.startTime) }}</span>
              </div>
              <div v-if="trackData.endTime" class="flex justify-between text-sm">
                <span class="text-gray-500 dark:text-gray-400">End</span>
                <span class="font-medium text-gray-900 dark:text-white text-xs">{{ formatDateTime(trackData.endTime) }}</span>
              </div>
            </div>

            <!-- Playback Controls -->
            <div class="p-3 space-y-3 border-b border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Playback</span>
                <div class="flex items-center gap-1">
                  <button v-for="s in [1, 2, 4, 8, 16]" :key="s" @click="playbackSpeed = s"
                    :class="['px-2 py-0.5 text-[10px] font-bold rounded transition-colors',
                      playbackSpeed === s ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300']">
                    {{ s }}x
                  </button>
                </div>
              </div>

              <div class="flex items-center gap-2">
                <button @click="togglePlayback"
                  class="w-10 h-10 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white flex items-center justify-center transition-colors shadow-sm flex-shrink-0">
                  <svg v-if="!isPlaying" class="w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z" />
                  </svg>
                  <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M5.75 3a.75.75 0 00-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 00.75-.75V3.75A.75.75 0 007.25 3h-1.5zM12.75 3a.75.75 0 00-.75.75v12.5c0 .414.336.75.75.75h1.5a.75.75 0 00.75-.75V3.75a.75.75 0 00-.75-.75h-1.5z" />
                  </svg>
                </button>

                <div class="flex-1">
                  <input type="range" v-model.number="playbackIndex" :min="0" :max="Math.max(0, (trackData?.points?.length || 1) - 1)"
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
              <p class="text-sm font-medium">No track data found</p>
              <p class="text-xs mt-1">Try a different period</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══════════════ MAP CONTAINER ═══════════════ -->
      <div class="flex-1 relative">
        <div ref="mapContainer" class="w-full h-full"></div>

        <!-- Map loading overlay -->
        <div v-if="!mapReady" class="absolute inset-0 bg-gray-50 dark:bg-gray-900 flex items-center justify-center">
          <div class="text-center">
            <svg class="mx-auto h-12 w-12 text-indigo-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            <p class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400">Loading Map...</p>
          </div>
        </div>

        <!-- Refresh countdown pill -->
        <div class="absolute bottom-4 left-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 flex items-center gap-2 z-10">
          <svg class="w-4 h-4 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <span>{{ refreshCountdown }}s</span>
        </div>

        <!-- Map type controls -->
        <div class="absolute top-4 right-4 inline-flex rounded-lg shadow-lg z-10" role="group">
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

        <!-- ═══════════════ DEVICE DETAIL SIDEBAR ═══════════════ -->
        <transition name="slide-right">
          <div v-if="showDetailSidebar && selectedDevice" class="absolute top-0 right-0 w-96 h-full bg-white dark:bg-gray-800 shadow-2xl z-20 overflow-y-auto border-l border-gray-200 dark:border-gray-700">
            <!-- Header -->
            <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3 flex items-center justify-between z-10">
              <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                  {{ selectedDevice.tractor?.no_plate || selectedDevice.device_name || 'Unknown' }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ selectedDevice.imei }}</p>
              </div>
              <button @click="showDetailSidebar = false" class="text-gray-400 hover:bg-gray-200 hover:text-gray-900 rounded-lg p-1.5 inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Status Bar -->
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
                  selectedDevice.status === 'moving' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' :
                  selectedDevice.status === 'idle' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300']">
                  <span :class="['w-2 h-2 rounded-full',
                    selectedDevice.status === 'moving' ? 'bg-green-500' :
                    selectedDevice.status === 'idle' ? 'bg-yellow-500' : 'bg-red-500']"></span>
                  {{ selectedDevice.status === 'moving' ? 'Moving' : selectedDevice.status === 'idle' ? 'Idling' : 'Offline' }}
                  (ACC: {{ selectedDevice.acc_status ? 'ON' : 'OFF' }})
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                  {{ selectedDevice.status === 'moving' ? (selectedDevice.speed + ' km/h') : formatTimeAgo(selectedDevice.minutes_ago) }}
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
                  <span class="text-gray-900 dark:text-white font-medium">{{ selectedDevice.tractor.id_no || '-' }}</span>
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
                <button @click="liveFollow" :class="['flex-1 font-medium rounded-lg text-sm px-3 py-2.5 flex items-center justify-center gap-1.5 transition-colors focus:ring-4',
                  isFollowing
                    ? 'text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-indigo-300 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800'
                    : 'text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-gray-200 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-700']">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
                  </svg>
                  {{ isFollowing ? 'Following...' : 'Live' }}
                </button>
                <Link :href="`/devices/${selectedDevice.id}`"
                  class="flex-1 text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-3 py-2.5 text-center dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-700 transition-colors">
                  Details
                </Link>
              </div>

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
                      class="flex-shrink-0 text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 p-1.5 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors">
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
const activeTab = ref('objects');
const activeState = ref('all');
const deviceSearch = ref('');
const selectedGroup = ref('');
const selectedDevice = ref(null);
const showDetailSidebar = ref(false);
const deviceAddress = ref('');
const isFollowing = ref(false);
const refreshCountdown = ref(20);
const currentMapType = ref('roadmap');

// Tracks
const trackDeviceId = ref('');
const trackPeriod = ref('today');
const trackFrom = ref('');
const trackTo = ref('');
const trackLoading = ref(false);
const trackData = ref(null);

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
let trackPolyline = null;
let trackProgressPolyline = null;
let trackMarkers = [];
let playbackMarker = null;
let playbackAnimationId = null;
let lastPlaybackTimestamp = null;
let previousPositions = {};  // imei -> { lat, lng } for bearing calc
let animationFrames = {};   // imei -> animation frame id

const mapTypes = [
  { id: 'roadmap', label: 'Map' },
  { id: 'satellite', label: 'Satellite' },
  { id: 'terrain', label: 'Terrain' },
];

// ═══════════════ COMPUTED ═══════════════
const onlineCount = computed(() => deviceList.value.filter(d => d.status === 'moving' || d.status === 'idle').length);
const idleCount = computed(() => deviceList.value.filter(d => d.status === 'idle').length);
const offlineCount = computed(() => deviceList.value.filter(d => d.status === 'offline').length);

const filteredDevices = computed(() => {
  let list = deviceList.value;
  if (activeState.value === 'moving') list = list.filter(d => d.status === 'moving' || d.status === 'idle');
  else if (activeState.value === 'idle') list = list.filter(d => d.status === 'idle');
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
  try {
    return new Date(p.gpsTime).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
  } catch { return '--:--:--'; }
});

const currentPlaybackSpeed = computed(() => {
  if (!trackData.value?.points?.length) return '0';
  const p = trackData.value.points[playbackIndex.value];
  return p ? p.speed?.toFixed(1) : '0';
});

// ═══════════════ GOOGLE MAPS ═══════════════
function loadGoogleMaps() {
  return new Promise((resolve, reject) => {
    if (window.google && window.google.maps) { resolve(); return; }
    const key = props.googleMapKey;
    if (!key) { reject(new Error('Google Maps API key not provided')); return; }
    window.initGoogleMap = () => { resolve(); delete window.initGoogleMap; };
    const script = document.createElement('script');
    script.src = `https://maps.googleapis.com/maps/api/js?key=${key}&libraries=geometry&callback=initGoogleMap`;
    script.async = true; script.defer = true; script.onerror = reject;
    document.head.appendChild(script);
  });
}

async function initMap() {
  try {
    await loadGoogleMaps();
    map = new google.maps.Map(mapContainer.value, {
      center: { lat: 14.17092, lng: 121.291831 },
      zoom: 6,
      mapTypeId: 'roadmap',
      mapTypeControl: false,
      streetViewControl: true,
      fullscreenControl: false,
    });
    mapReady.value = true;
    createMarkers();
  } catch (e) {
    console.error('Failed to load Google Maps:', e);
  }
}

// Tractor SVG path — a recognizable tractor silhouette (facing up/north at rotation 0)
const TRACTOR_PATH = 'M12 2C11.2 2 10.5 2.5 10.2 3.2L9.5 5H7C5.9 5 5 5.9 5 7V9.5C3.6 9.5 2.5 10.6 2.5 12C2.5 13.4 3.6 14.5 5 14.5V16C5 17.1 5.9 18 7 18H7.2C7.6 19.2 8.7 20 10 20C11.3 20 12.4 19.2 12.8 18H15.2C15.6 19.2 16.7 20 18 20C19.3 20 20.4 19.2 20.8 18H21C22.1 18 23 17.1 23 16V12C23 10.9 22.1 10 21 10H19L17.4 6.2C17 5.5 16.3 5 15.5 5H14.5L13.8 3.2C13.5 2.5 12.8 2 12 2ZM10 16C9.2 16 8.5 16.7 8.5 17.5C8.5 18.3 9.2 19 10 19C10.8 19 11.5 18.3 11.5 17.5C11.5 16.7 10.8 16 10 16ZM18 16C17.2 16 16.5 16.7 16.5 17.5C16.5 18.3 17.2 19 18 19C18.8 19 19.5 18.3 19.5 17.5C19.5 16.7 18.8 16 18 16ZM7 7H15.5L17 10H7V7ZM7 12H21V16H20.8C20.4 14.8 19.3 14 18 14C16.7 14 15.6 14.8 15.2 16H12.8C12.4 14.8 11.3 14 10 14C8.7 14 7.6 14.8 7.2 16H7V12Z';

function getMarkerIcon(status, rotation = 0) {
  const colors = { moving: '#16a34a', idle: '#ca8a04', offline: '#dc2626' };
  const color = colors[status] || colors.offline;
  return {
    path: TRACTOR_PATH,
    anchor: new google.maps.Point(12, 12),
    fillColor: color,
    fillOpacity: 1,
    strokeColor: '#fff',
    strokeWeight: 1.5,
    scale: 1.4,
    rotation: rotation,
  };
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

// Smooth-animate a marker from current position to target position over duration ms
function animateMarkerTo(marker, targetLat, targetLng, imei, duration = 1500) {
  if (animationFrames[imei]) cancelAnimationFrame(animationFrames[imei]);
  const startPos = marker.getPosition();
  if (!startPos) { marker.setPosition({ lat: targetLat, lng: targetLng }); return; }
  const startLat = startPos.lat();
  const startLng = startPos.lng();
  if (Math.abs(startLat - targetLat) < 0.000001 && Math.abs(startLng - targetLng) < 0.000001) return;
  const startTime = performance.now();
  function step(now) {
    const elapsed = now - startTime;
    const t = Math.min(elapsed / duration, 1);
    // ease-out cubic for smooth deceleration
    const ease = 1 - Math.pow(1 - t, 3);
    const lat = startLat + (targetLat - startLat) * ease;
    const lng = startLng + (targetLng - startLng) * ease;
    marker.setPosition({ lat, lng });
    if (t < 1) animationFrames[imei] = requestAnimationFrame(step);
    else delete animationFrames[imei];
  }
  animationFrames[imei] = requestAnimationFrame(step);
}

function createMarkers() {
  if (!map) return;
  Object.values(markers).forEach(m => m.setMap(null));
  markers = {};
  infoWindows = {};
  previousPositions = {};
  const clusterMarkersList = [];

  deviceList.value.forEach(device => {
    if (!device.lat || !device.lng) return;
    const lat = parseFloat(device.lat);
    const lng = parseFloat(device.lng);
    const heading = parseFloat(device.direction) || 0;
    previousPositions[device.imei] = { lat, lng };
    const marker = new google.maps.Marker({
      position: { lat, lng },
      map: map,
      icon: getMarkerIcon(device.status, heading),
      title: device.tractor?.no_plate || device.device_name || device.imei,
    });
    marker.addListener('click', () => {
      Object.values(infoWindows).forEach(iw => iw.close());
      map.setZoom(16);
      map.panTo(marker.getPosition());
      const found = deviceList.value.find(d => d.imei === device.imei);
      if (found) {
        selectedDevice.value = found;
        showDetailSidebar.value = true;
        reverseGeocode(found.lat, found.lng);
      }
    });
    markers['marker_' + device.imei] = marker;
    clusterMarkersList.push(marker);
  });

  if (clusterMarkersList.length > 0) {
    const bounds = new google.maps.LatLngBounds();
    clusterMarkersList.forEach(m => bounds.extend(m.getPosition()));
    map.fitBounds(bounds);
  }
}

function updateMarkers() {
  deviceList.value.forEach(device => {
    if (!device.lat || !device.lng) return;
    const key = 'marker_' + device.imei;
    const newLat = parseFloat(device.lat);
    const newLng = parseFloat(device.lng);

    // Calculate bearing from previous position for rotation
    let heading = parseFloat(device.direction) || 0;
    const prev = previousPositions[device.imei];
    if (prev && (Math.abs(prev.lat - newLat) > 0.00001 || Math.abs(prev.lng - newLng) > 0.00001)) {
      heading = calcBearing(prev.lat, prev.lng, newLat, newLng);
    }
    previousPositions[device.imei] = { lat: newLat, lng: newLng };

    if (markers[key]) {
      // Smooth animate to new position
      animateMarkerTo(markers[key], newLat, newLng, device.imei);
      markers[key].setIcon(getMarkerIcon(device.status, heading));
    } else {
      const marker = new google.maps.Marker({
        position: { lat: newLat, lng: newLng },
        map: map,
        icon: getMarkerIcon(device.status, heading),
        title: device.tractor?.no_plate || device.device_name || device.imei,
      });
      marker.addListener('click', () => {
        map.setZoom(16);
        map.panTo(marker.getPosition());
        const found = deviceList.value.find(d => d.imei === device.imei);
        if (found) {
          selectedDevice.value = found;
          showDetailSidebar.value = true;
          reverseGeocode(found.lat, found.lng);
        }
      });
      markers[key] = marker;
    }
  });
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
    reverseGeocode(device.lat, device.lng);
    if (map) { map.setZoom(16); map.panTo(new google.maps.LatLng(parseFloat(device.lat), parseFloat(device.lng))); }
  }
  stopFollow();
}

function liveFollow() {
  if (isFollowing.value) { stopFollow(); return; }
  if (!selectedDevice.value) return;
  isFollowing.value = true;
  const deviceId = selectedDevice.value.id;
  const imei = selectedDevice.value.imei;
  const m = markers['marker_' + imei];
  if (m && map) { map.setZoom(16); map.panTo(m.getPosition()); }

  // Stop the 20s all-device polling while following a single device
  if (refreshInterval) { clearInterval(refreshInterval); refreshInterval = null; }

  // Fetch fresh location immediately, then every 10 seconds
  fetchFollowedDevice(deviceId, imei);
  followInterval = setInterval(() => {
    fetchFollowedDevice(deviceId, imei);
  }, 10000);
}

async function fetchFollowedDevice(deviceId, imei) {
  try {
    const { data } = await axios.get(`/live-view/follow/${deviceId}`);
    if (data.device) {
      // Update the device in the list so markers refresh
      const idx = deviceList.value.findIndex(d => d.id === deviceId);
      if (idx !== -1) deviceList.value[idx] = data.device;
      selectedDevice.value = data.device;

      // Animate marker to new position
      const key = 'marker_' + imei;
      const newLat = parseFloat(data.device.lat);
      const newLng = parseFloat(data.device.lng);
      if (markers[key] && newLat && newLng) {
        let heading = parseFloat(data.device.direction) || 0;
        const prev = previousPositions[imei];
        if (prev && (Math.abs(prev.lat - newLat) > 0.00001 || Math.abs(prev.lng - newLng) > 0.00001)) {
          heading = calcBearing(prev.lat, prev.lng, newLat, newLng);
        }
        previousPositions[imei] = { lat: newLat, lng: newLng };
        animateMarkerTo(markers[key], newLat, newLng, imei);
        markers[key].setIcon(getMarkerIcon(data.device.status, heading));
        if (map) map.panTo({ lat: newLat, lng: newLng });
      }
    }
  } catch (e) { console.error('Follow refresh failed:', e); }
}

function stopFollow() {
  isFollowing.value = false;
  if (followInterval) { clearInterval(followInterval); followInterval = null; }

  // Resume the 20s all-device polling
  refreshData();
  refreshInterval = setInterval(refreshData, 20000);
}

// ═══════════════ DATA REFRESH ═══════════════
async function refreshData() {
  try {
    const { data } = await axios.get('/live-view/locations');
    if (data.devices) {
      deviceList.value = data.devices;
      updateMarkers();
      if (selectedDevice.value) {
        const updated = data.devices.find(d => d.id === selectedDevice.value.id);
        if (updated) selectedDevice.value = updated;
      }
    }
  } catch (e) { console.error('Refresh failed:', e); }
  refreshCountdown.value = 20;
}

// ═══════════════ TRACKS ═══════════════
async function searchTracks() {
  if (!trackDeviceId.value) return;
  trackLoading.value = true;
  clearTracks();

  try {
    const params = { device_id: trackDeviceId.value, period: trackPeriod.value };
    if (trackPeriod.value === 'custom') {
      params.from = trackFrom.value;
      params.to = trackTo.value;
    }

    const { data } = await axios.get('/live-view/track-data', { params });

    if (data.success && data.track) {
      trackData.value = data.track;
      playbackIndex.value = 0;
      if (data.track.points?.length > 0) drawTrack(data.track.points);
    }
  } catch (e) {
    console.error('Failed to load tracks:', e);
    trackData.value = { points: [], totalPoints: 0, distance: 0, maxSpeed: 0, avgSpeed: 0, duration: 0, startTime: null, endTime: null };
  }
  trackLoading.value = false;
}

function drawTrack(points) {
  if (!map || !points.length) return;
  const path = points.map(p => new google.maps.LatLng(p.lat, p.lng));

  // Full track polyline (faded)
  trackPolyline = new google.maps.Polyline({
    path, geodesic: true, strokeColor: '#6366f1', strokeOpacity: 0.25, strokeWeight: 4, map,
  });

  // Progress polyline (solid)
  trackProgressPolyline = new google.maps.Polyline({
    path, geodesic: true, strokeColor: '#4f46e5', strokeOpacity: 0.9, strokeWeight: 4, map,
  });

  // Start marker
  trackMarkers.push(new google.maps.Marker({
    position: path[0], map,
    label: { text: 'S', color: '#fff', fontWeight: 'bold', fontSize: '11px' },
    icon: { path: google.maps.SymbolPath.CIRCLE, scale: 14, fillColor: '#22c55e', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 2 },
    zIndex: 100,
  }));

  // End marker
  if (path.length > 1) {
    trackMarkers.push(new google.maps.Marker({
      position: path[path.length - 1], map,
      label: { text: 'E', color: '#fff', fontWeight: 'bold', fontSize: '11px' },
      icon: { path: google.maps.SymbolPath.CIRCLE, scale: 14, fillColor: '#ef4444', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 2 },
      zIndex: 100,
    }));
  }

  // Playback marker — same tractor icon in indigo
  playbackMarker = new google.maps.Marker({
    position: path[0], map,
    icon: {
      path: TRACTOR_PATH,
      anchor: new google.maps.Point(12, 12),
      fillColor: '#4f46e5', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 1.5, scale: 1.8,
      rotation: 0,
    },
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
  if (trackPolyline) { trackPolyline.setMap(null); trackPolyline = null; }
  if (trackProgressPolyline) { trackProgressPolyline.setMap(null); trackProgressPolyline = null; }
  if (playbackMarker) { playbackMarker.setMap(null); playbackMarker = null; }
  trackMarkers.forEach(m => m.setMap(null));
  trackMarkers = [];
  trackData.value = null;
  playbackIndex.value = 0;
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

  // Calculate rotation based on bearing to next or from previous point
  if (playbackMarker) {
    let bearing = 0;
    const prevPoint = trackData.value.points[Math.max(0, idx - 1)];
    if (prevPoint && (prevPoint.lat !== point.lat || prevPoint.lng !== point.lng)) {
      bearing = calcBearing(prevPoint.lat, prevPoint.lng, point.lat, point.lng);
    }
    const icon = playbackMarker.getIcon();
    playbackMarker.setIcon({ ...icon, rotation: bearing });
    playbackMarker.setPosition(pos);
  }

  if (trackProgressPolyline) {
    const progressPath = trackData.value.points.slice(0, idx + 1).map(p => new google.maps.LatLng(p.lat, p.lng));
    trackProgressPolyline.setPath(progressPath);
  }
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
function formatTimeAgo(minutes) {
  if (!minutes || minutes >= 999) return 'N/A';
  if (minutes < 60) return `${minutes}m ago`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours}h ago`;
  return `${Math.floor(hours / 24)}d ago`;
}

function formatDateTime(dt) {
  if (!dt) return 'N/A';
  try {
    const d = new Date(dt);
    if (isNaN(d.getTime())) return String(dt);
    const pad = (n) => String(n).padStart(2, '0');
    const year = d.getFullYear();
    const month = pad(d.getMonth() + 1);
    const day = pad(d.getDate());
    let hours = d.getHours();
    const mins = pad(d.getMinutes());
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;
    return `${year}-${month}-${day} ${pad(hours)}:${mins} ${ampm}`;
  } catch { return dt; }
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
  refreshInterval = setInterval(refreshData, 20000);
  countdownInterval = setInterval(() => {
    refreshCountdown.value = Math.max(0, refreshCountdown.value - 1);
  }, 1000);
});

onUnmounted(() => {
  if (refreshInterval) clearInterval(refreshInterval);
  if (countdownInterval) clearInterval(countdownInterval);
  stopFollow();
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
