<template>
  <AppLayout>
    <Head title="Tractors" />

    <!-- Page Header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tractors</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage and monitor all registered tractors.</p>
      </div>
      <div class="flex items-center gap-3 mt-4 sm:mt-0">
        <button v-if="activeTab === 'fca'" @click="openDistributeDrawer()"
          class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2"
          style="background-color: #007f3d;"
          @mouseenter="$event.target.style.backgroundColor='#006631'"
          @mouseleave="$event.target.style.backgroundColor='#007f3d'">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
          Distribute to FCA
        </button>

        <Link v-else-if="$page.props.auth.user.permissions.includes('tractors.create')" href="/tractors/create"
          class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2"
          style="background-color: #007f3d;"
          @mouseenter="$event.target.style.backgroundColor='#006631'"
          @mouseleave="$event.target.style.backgroundColor='#007f3d'">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
          Add Tractor
        </Link>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
      <nav class="-mb-px flex gap-6">
        <button @click="switchTab('all')"
          class="whitespace-nowrap pb-3 px-1 border-b-2 text-sm font-medium transition-colors"
          :class="activeTab === 'all'
            ? 'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400'
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'">
          <span class="inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0H21M3.375 14.25h3.75L9 7.5h6l1.875 6.75h3.75" /></svg>
            All Tractors
          </span>
        </button>
        <button @click="switchTab('fca')"
          class="whitespace-nowrap pb-3 px-1 border-b-2 text-sm font-medium transition-colors"
          :class="activeTab === 'fca'
            ? 'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400'
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'">
          <span class="inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
            FCA Distributions
          </span>
        </button>
        <button @click="switchTab('tps')"
          class="whitespace-nowrap pb-3 px-1 border-b-2 text-sm font-medium transition-colors"
          :class="activeTab === 'tps'
            ? 'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400'
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'">
          <span class="inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            TPS Responsibilities
          </span>
        </button>
      </nav>
    </div>

    <!-- ==================== ALL TRACTORS TAB ==================== -->
    <template v-if="activeTab === 'all'">
      <!-- Filters -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 mb-6 dark:bg-gray-800 dark:border-gray-700">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <input v-model="search" type="text" placeholder="Search plate, IMEI, brand..." @input="debouncedFilter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" />
          <select v-model="selectedGroup" @change="applyAllFilter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">All Groups</option>
            <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
          </select>
          <select v-model="selectedStatus" @change="applyAllFilter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">All Status</option>
            <option value="online">Online</option>
            <option value="offline">Offline</option>
          </select>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
          <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
              <th scope="col" class="px-6 py-3">Plate</th>
              <th scope="col" class="px-6 py-3">Brand/Model</th>
              <th scope="col" class="px-6 py-3">IMEI</th>
              <th scope="col" class="px-6 py-3">Group</th>
              <th scope="col" class="px-6 py-3">Status</th>
              <th scope="col" class="px-6 py-3">Assignee</th>
              <th scope="col" class="px-6 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="tractor in tractors?.data" :key="tractor.id"
              class="bg-white border-b hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-600">
              <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ tractor.no_plate }}</td>
              <td class="px-6 py-4">{{ tractor.brand }} {{ tractor.model }}</td>
              <td class="px-6 py-4 font-mono text-xs">{{ tractor.imei }}</td>
              <td class="px-6 py-4">{{ tractor.groups?.map(g => g.name).join(', ') || '—' }}</td>
              <td class="px-6 py-4"><StatusBadge :status="getOnlineStatus(tractor)" /></td>
              <td class="px-6 py-4">{{ tractor.assignee?.name || '—' }}</td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-1">
                  <Link :href="`/tractors/${tractor.id}`" class="p-1.5 rounded-lg text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:text-gray-400 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20 transition-colors" title="View">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                  </Link>
                  <Link v-if="$page.props.auth.user.permissions.includes('tractors.edit')" :href="`/tractors/${tractor.id}/edit`" class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:text-gray-400 dark:hover:text-blue-400 dark:hover:bg-blue-900/20 transition-colors" title="Edit">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                  </Link>
                </div>
              </td>
            </tr>
            <tr v-if="!tractors?.data?.length">
              <td colspan="7" class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No tractors found</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your search or filter criteria.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <Pagination v-if="tractors?.links" :links="tractors.links" class="mt-6" />
    </template>

    <!-- ==================== FCA DISTRIBUTIONS TAB ==================== -->
    <template v-if="activeTab === 'fca'">
      <!-- Filters -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 mb-6 dark:bg-gray-800 dark:border-gray-700">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <input v-model="fcaSearch" type="text" placeholder="Search FCA name, plate, brand..." @input="debouncedFcaFilter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" />
          <select v-model="fcaStatusFilter" @change="applyFcaFilter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">All Status</option>
            <option value="distributed">Active</option>
            <option value="returned">Returned</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>

      <!-- Collapsible FCA sections -->
      <div class="space-y-4">
        <div v-for="fca in fcaDistributions" :key="fca.id"
          class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700 transition-shadow hover:shadow-md">
          <!-- FCA Header (clickable) -->
          <button @click="toggleFca(fca.id)"
            class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <div class="flex items-center gap-3">
              <div class="h-10 w-10 rounded-full flex items-center justify-center shrink-0 text-sm font-bold text-white shadow-sm" style="background-color: #007f3d;">
                {{ fca.name?.charAt(0)?.toUpperCase() }}
              </div>
              <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ fca.name }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ fca.email }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span v-if="fca.active_count > 0" class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">
                {{ fca.active_count }} active
              </span>
              <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                {{ fca.distribution_count }} total
              </span>
              <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': expandedFca.has(fca.id) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </button>

          <!-- Distributions table (collapsible) -->
          <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-[2000px] opacity-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="max-h-[2000px] opacity-100"
            leave-to-class="max-h-0 opacity-0">
            <div v-if="expandedFca.has(fca.id)" class="overflow-hidden">
              <div class="border-t border-gray-100 dark:border-gray-700">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                  <thead class="text-xs text-gray-500 uppercase bg-gray-50/50 dark:bg-gray-700/50 dark:text-gray-400">
                    <tr>
                      <th class="px-5 py-2.5">Tractor</th>
                      <th class="px-5 py-2.5">Distributed By</th>
                      <th class="px-5 py-2.5">Area</th>
                      <th class="px-5 py-2.5">Date</th>
                      <th class="px-5 py-2.5">Status</th>
                      <th class="px-5 py-2.5 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="dist in fca.distributions" :key="dist.id"
                      class="border-b border-gray-50 last:border-0 hover:bg-gray-50/50 dark:border-gray-700/50 dark:hover:bg-gray-700/30 transition-colors">
                      <td class="px-5 py-3">
                        <div class="font-medium text-gray-900 dark:text-white">{{ dist.tractor?.no_plate }}</div>
                        <div class="text-xs text-gray-400">{{ dist.tractor?.brand }} {{ dist.tractor?.model }}</div>
                      </td>
                      <td class="px-5 py-3">{{ dist.distributor?.name || '—' }}</td>
                      <td class="px-5 py-3">{{ dist.area || '—' }}</td>
                      <td class="px-5 py-3">{{ formatDate(dist.distribution_date) }}</td>
                      <td class="px-5 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                          :class="{
                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400': dist.status === 'distributed',
                            'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400': dist.status === 'returned',
                            'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400': dist.status === 'cancelled',
                          }">
                          {{ dist.status === 'distributed' ? 'Active' : dist.status.charAt(0).toUpperCase() + dist.status.slice(1) }}
                        </span>
                      </td>
                      <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-1">
                          <Link v-if="dist.status === 'distributed'" :href="`/tractor-distributions/${dist.id}/return`" method="post" as="button"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:text-amber-400 dark:hover:bg-amber-900/20 transition-colors" title="Mark Returned">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                          </Link>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </Transition>
        </div>

        <!-- Empty state -->
        <div v-if="!fcaDistributions?.length" class="bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 px-6 py-12 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No distributions found</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Distribute a tractor to an FCA to get started.</p>
        </div>
      </div>
    </template>

    <!-- ==================== TPS RESPONSIBILITIES TAB ==================== -->
    <template v-if="activeTab === 'tps'">
      <!-- Filters -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 mb-6 dark:bg-gray-800 dark:border-gray-700">
        <div class="space-y-3">
          <input v-model="tpsSearch" type="text" placeholder="Search TPS name, plate, brand..." @input="debouncedTpsFilter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full sm:w-80 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" />
          <p class="text-sm text-gray-500 dark:text-gray-400">
            These assignments define TPS coordination responsibility. TPS users marked with full fleet access are managed from Users and do not appear in this responsibility view.
          </p>
        </div>
      </div>

      <!-- Collapsible TPS sections -->
      <div class="space-y-4">
        <div v-for="tps in tpsAssignments" :key="tps.id"
          class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700 transition-shadow hover:shadow-md">
          <!-- TPS Header (clickable) -->
          <button @click="toggleTps(tps.id)"
            class="w-full flex items-center justify-between px-5 py-4 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
            <div class="flex items-center gap-3">
              <div class="h-10 w-10 rounded-full flex items-center justify-center shrink-0 text-sm font-bold text-white shadow-sm" style="background-color: #007f3d;">
                {{ tps.name?.charAt(0)?.toUpperCase() }}
              </div>
              <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ tps.name }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ tps.email }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400">
                {{ tps.tractor_count }} {{ tps.tractor_count === 1 ? 'tractor' : 'tractors' }}
              </span>
              <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': expandedTps.has(tps.id) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </button>

          <!-- Tractors table (collapsible) -->
          <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-[2000px] opacity-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="max-h-[2000px] opacity-100"
            leave-to-class="max-h-0 opacity-0">
            <div v-if="expandedTps.has(tps.id)" class="overflow-hidden">
              <div class="border-t border-gray-100 dark:border-gray-700">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                  <thead class="text-xs text-gray-500 uppercase bg-gray-50/50 dark:bg-gray-700/50 dark:text-gray-400">
                    <tr>
                      <th class="px-5 py-2.5">Plate</th>
                      <th class="px-5 py-2.5">Brand / Model</th>
                      <th class="px-5 py-2.5">IMEI</th>
                      <th class="px-5 py-2.5">Group</th>
                      <th class="px-5 py-2.5">Status</th>
                      <th class="px-5 py-2.5 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="tractor in tps.tractors" :key="tractor.id"
                      class="border-b border-gray-50 last:border-0 hover:bg-gray-50/50 dark:border-gray-700/50 dark:hover:bg-gray-700/30 transition-colors">
                      <td class="px-5 py-3 font-medium text-gray-900 dark:text-white whitespace-nowrap">{{ tractor.no_plate }}</td>
                      <td class="px-5 py-3">{{ tractor.brand }} {{ tractor.model }}</td>
                      <td class="px-5 py-3 font-mono text-xs">{{ tractor.imei }}</td>
                      <td class="px-5 py-3">
                        <div class="flex flex-wrap gap-1">
                          <span v-for="g in tractor.groups" :key="g.id"
                            class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            {{ g.name }}
                          </span>
                          <span v-if="!tractor.groups?.length" class="text-gray-400">—</span>
                        </div>
                      </td>
                      <td class="px-5 py-3"><StatusBadge :status="getOnlineStatus(tractor)" /></td>
                      <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-1">
                          <Link :href="`/tractors/${tractor.id}`" class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20 transition-colors" title="View">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                          </Link>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </Transition>
        </div>

        <!-- Empty state -->
        <div v-if="!tpsAssignments?.length" class="bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 px-6 py-12 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No TPS responsibilities yet</h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Assign TPS users to groups to define which tractors they coordinate here. Full fleet TPS access is configured from Users.</p>
        </div>
      </div>
    </template>

    <!-- ==================== SLIDE-OVER DRAWER ==================== -->
    <SlideOver :show="drawerOpen" max-width="2xl" title="Distribute to FCA" subtitle="Select tractors to distribute to an FCA user" @close="closeDrawer">
      <form @submit.prevent="submitDrawer" class="flex-1 overflow-y-auto">
        <div class="p-6 space-y-5">
          <!-- Tractor multi-select -->
          <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">
              Tractors <span class="text-red-500">*</span>
              <span v-if="drawerForm.tractor_ids.length" class="ml-1 text-emerald-600 dark:text-emerald-400 font-normal">({{ drawerForm.tractor_ids.length }} selected)</span>
            </label>
            <!-- Search within tractors -->
            <div class="relative mb-2">
              <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
              <input v-model="tractorSearchQuery" type="text" placeholder="Search tractors..."
                class="w-full pl-9 pr-3 py-2 rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
            </div>
            <!-- Select all / Clear -->
            <div class="flex items-center justify-between mb-2">
              <button type="button" @click="selectAllTractors" class="text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">Select all visible</button>
              <button type="button" @click="drawerForm.tractor_ids = []" class="text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400">Clear</button>
            </div>
            <!-- Checkbox list -->
            <div class="max-h-64 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-600 divide-y divide-gray-100 dark:divide-gray-700">
              <label v-for="t in filteredTractors" :key="t.id"
                class="flex items-center gap-3 px-3 py-2.5 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                :class="{ 'bg-emerald-50/50 dark:bg-emerald-900/10': drawerForm.tractor_ids.includes(t.id) }">
                <input type="checkbox" :value="t.id" v-model="drawerForm.tractor_ids"
                  class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600" />
                <span class="relative flex h-2.5 w-2.5 shrink-0">
                  <span v-if="getOnlineStatus(t) === 'online'" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex h-2.5 w-2.5 rounded-full" :class="getOnlineStatus(t) === 'online' ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600'"></span>
                </span>
                <div class="flex-1 min-w-0">
                  <span class="text-sm font-medium text-gray-900 dark:text-white">{{ t.no_plate }}</span>
                  <span class="ml-1.5 text-xs text-gray-500 dark:text-gray-400">{{ t.brand }} {{ t.model }}</span>
                </div>
              </label>
              <div v-if="!filteredTractors.length" class="px-3 py-4 text-center text-sm text-gray-400 dark:text-gray-500">
                No tractors match your search.
              </div>
            </div>
            <p v-if="drawerForm.errors.tractor_ids" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ drawerForm.errors.tractor_ids }}</p>
          </div>

          <!-- FCA distribute fields -->
          <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">FCA User <span class="text-red-500">*</span></label>
            <select v-model="drawerForm.distributed_to"
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
              <option value="">Select FCA</option>
              <option v-for="u in fcaUsers" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
            </select>
            <p v-if="drawerForm.errors.distributed_to" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ drawerForm.errors.distributed_to }}</p>
          </div>
          <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Distribution Date <span class="text-red-500">*</span></label>
            <input v-model="drawerForm.distribution_date" type="date"
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
            <p v-if="drawerForm.errors.distribution_date" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ drawerForm.errors.distribution_date }}</p>
          </div>
          <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Area</label>
            <input v-model="drawerForm.area" type="text" placeholder="e.g. Tarlac, Pampanga"
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
          </div>
          <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
            <textarea v-model="drawerForm.notes" rows="3" placeholder="Any additional notes..."
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400"></textarea>
          </div>
        </div>

        <!-- Footer -->
        <div class="sticky bottom-0 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 px-6 py-4 flex justify-end gap-3">
          <button type="button" @click="closeDrawer"
            class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">Cancel</button>
          <button type="submit" :disabled="drawerForm.processing"
            class="inline-flex items-center gap-1.5 rounded-lg px-5 py-2.5 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
            style="background-color: #007f3d;"
            @mouseenter="!drawerForm.processing && ($event.target.style.backgroundColor='#006631')"
            @mouseleave="!drawerForm.processing && ($event.target.style.backgroundColor='#007f3d')">
            <svg v-if="drawerForm.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
            {{ drawerForm.processing ? 'Saving...' : 'Distribute' }}
          </button>
        </div>
      </form>
    </SlideOver>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import SlideOver from '@/Components/SlideOver.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/utils/dateFormat';

const props = defineProps({
  tractors: Object,
  fcaDistributions: Array,
  tpsAssignments: Array,
  filters: Object,
  groups: Array,
  fcaUsers: Array,
  allTractors: Array,
});

// --- Tabs ---
const activeTab = ref(props.filters?.tab || 'all');
const switchTab = (tab) => {
  activeTab.value = tab;
  router.get('/tractors', { tab }, { preserveState: true, replace: true });
};

// --- All Tractors Filters ---
const search = ref(props.filters?.search || '');
const selectedGroup = ref(props.filters?.group_id || '');
const selectedStatus = ref(props.filters?.status || '');

let timer;
const debouncedFilter = () => { clearTimeout(timer); timer = setTimeout(applyAllFilter, 300); };
const applyAllFilter = () => {
  router.get('/tractors', {
    tab: 'all',
    search: search.value || undefined,
    group_id: selectedGroup.value || undefined,
    status: selectedStatus.value || undefined,
  }, { preserveState: true, replace: true });
};

// --- FCA Filters ---
const fcaSearch = ref(props.filters?.fca_search || '');
const fcaStatusFilter = ref(props.filters?.fca_status || '');

let fcaTimer;
const debouncedFcaFilter = () => { clearTimeout(fcaTimer); fcaTimer = setTimeout(applyFcaFilter, 300); };
const applyFcaFilter = () => {
  router.get('/tractors', {
    tab: 'fca',
    fca_search: fcaSearch.value || undefined,
    fca_status: fcaStatusFilter.value || undefined,
  }, { preserveState: true, replace: true });
};

// --- TPS Filters ---
const tpsSearch = ref(props.filters?.tps_search || '');

let tpsTimer;
const debouncedTpsFilter = () => { clearTimeout(tpsTimer); tpsTimer = setTimeout(applyTpsFilter, 300); };
const applyTpsFilter = () => {
  router.get('/tractors', {
    tab: 'tps',
    tps_search: tpsSearch.value || undefined,
  }, { preserveState: true, replace: true });
};

// --- Online status ---
const getOnlineStatus = (tractor) => {
  if (!tractor.device?.latest_location?.heartbeat_at) return 'offline';
  const heartbeat = new Date(tractor.device.latest_location.heartbeat_at);
  return (Date.now() - heartbeat.getTime()) < 600000 ? 'online' : 'offline';
};

// --- FCA collapsible state ---
const expandedFca = ref(new Set());
const toggleFca = (id) => {
  const next = new Set(expandedFca.value);
  next.has(id) ? next.delete(id) : next.add(id);
  expandedFca.value = next;
};

// --- TPS collapsible state ---
const expandedTps = ref(new Set());
const toggleTps = (id) => {
  const next = new Set(expandedTps.value);
  next.has(id) ? next.delete(id) : next.add(id);
  expandedTps.value = next;
};

// --- Drawer (FCA distribute only) ---
const drawerOpen = ref(false);
const tractorSearchQuery = ref('');

const filteredTractors = computed(() => {
  const q = tractorSearchQuery.value.toLowerCase().trim();
  if (!q) return props.allTractors;
  return props.allTractors.filter(t =>
    `${t.no_plate} ${t.brand} ${t.model}`.toLowerCase().includes(q)
  );
});

const selectAllTractors = () => {
  const visibleIds = filteredTractors.value.map(t => t.id);
  const merged = new Set([...drawerForm.tractor_ids, ...visibleIds]);
  drawerForm.tractor_ids = [...merged];
};

const drawerForm = useForm({
  tractor_ids: [],
  distributed_to: '',
  distribution_date: new Date().toISOString().slice(0, 10),
  area: '',
  notes: '',
});

const openDistributeDrawer = () => {
  drawerForm.reset();
  drawerForm.clearErrors();
  drawerForm.tractor_ids = [];
  drawerForm.distribution_date = new Date().toISOString().slice(0, 10);
  tractorSearchQuery.value = '';
  drawerOpen.value = true;
};

const closeDrawer = () => {
  drawerOpen.value = false;
};

const submitDrawer = () => {
  drawerForm.post('/tractors/distribute', {
    onSuccess: () => closeDrawer(),
  });
};
</script>
