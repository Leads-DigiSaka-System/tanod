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
        <button v-if="$page.props.auth.user.permissions.includes('tractors.edit') || $page.props.auth.user.permissions.includes('tractors.delete')" @click="openDuplicateModal()"
          class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2"
          style="background-color: #b45309;"
          @mouseenter="$event.target.style.backgroundColor='#92400e'"
          @mouseleave="$event.target.style.backgroundColor='#b45309'">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
          Duplicate IMEI
        </button>
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
        <button @click="switchTab('tsr')"
          class="whitespace-nowrap pb-3 px-1 border-b-2 text-sm font-medium transition-colors"
          :class="activeTab === 'tsr'
            ? 'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400'
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'">
          <span class="inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            TSR Responsibilities
          </span>
        </button>
        <button v-if="$page.props.auth.user.permissions.includes('tractors.view_deleted')" @click="switchTab('deleted')"
          class="whitespace-nowrap pb-3 px-1 border-b-2 text-sm font-medium transition-colors"
          :class="activeTab === 'deleted'
            ? 'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400'
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'">
          <span class="inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            Deleted
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
      <DataTable>
        <!-- Loading overlay -->
        <template #loading>
          <div v-if="pageLoading" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/70 dark:bg-gray-800/70 backdrop-blur-[1px]">
            <div class="flex flex-col items-center gap-3">
              <svg class="animate-spin h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400 animate-pulse">Loading...</p>
            </div>
          </div>
        </template>
        <template #head>
          <tr>
            <th v-if="canBulkAction" scope="col" class="px-4 py-3 w-10">
              <input type="checkbox" :checked="isAllSelected" @click="toggleSelectAll($event)"
                class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600" />
            </th>
            <SortableTh label="ID" field="id" :sort-field="sortField" :sort-direction="sortDirection" @sort="sortBy" />
            <SortableTh label="Name" field="name" :sort-field="sortField" :sort-direction="sortDirection" @sort="sortBy" />
            <SortableTh label="No. Plate" field="no_plate" :sort-field="sortField" :sort-direction="sortDirection" @sort="sortBy" />
            <SortableTh label="Total Distance (km)" field="total_distance" :sort-field="sortField" :sort-direction="sortDirection" @sort="sortBy" />
            <SortableTh label="Running Hours" field="running_hours" :sort-field="sortField" :sort-direction="sortDirection" @sort="sortBy" />
            <th scope="col" class="px-6 py-3 text-right whitespace-nowrap">Actions</th>
          </tr>
        </template>
        <template #body>
          <tr v-for="tractor in tractors?.data" :key="tractor.id"
            class="bg-white border-b hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-600"
            :class="{ 'bg-emerald-50/50 dark:bg-emerald-900/10': selectedIsChecked(tractor.id) }">
            <td v-if="canBulkAction" class="px-4 py-4">
              <input type="checkbox" :checked="selectedIsChecked(tractor.id)" @click="toggleSelect(tractor.id, $event)"
                class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600" />
            </td>
            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ tractor.id }}</td>
            <td class="px-6 py-4 whitespace-nowrap">{{ tractor.name || '—' }}</td>
            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ tractor.no_plate }}</td>
            <td class="px-6 py-4 whitespace-nowrap">{{ tractor.total_distance ?? '—' }}</td>
            <td class="px-6 py-4 whitespace-nowrap">{{ tractor.running_hours ?? '—' }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center justify-end gap-1">
                <Link :href="`/tractors/${tractor.id}`" class="p-1.5 rounded-lg text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:text-gray-400 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20 transition-colors" title="View">
                  <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                </Link>
                <Link v-if="$page.props.auth.user.permissions.includes('tractors.edit')" :href="`/tractors/${tractor.id}/edit`" class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:text-gray-400 dark:hover:text-blue-400 dark:hover:bg-blue-900/20 transition-colors" title="Edit">
                  <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                </Link>
                <button v-if="$page.props.auth.user.permissions.includes('tractors.delete')" @click="openDeleteDrawer(tractor)" class="p-1.5 rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50 dark:text-gray-400 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors" title="Delete">
                  <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
              </div>
            </td>
          </tr>

          <!-- Empty state -->
          <tr v-if="!tractors?.data?.length">
            <td :colspan="canBulkAction ? 7 : 6" class="px-6 py-12 text-center">
              <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
              </svg>
              <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No tractors found</h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your search or filter criteria.</p>
            </td>
          </tr>
        </template>
      </DataTable>
      <div class="flex items-center justify-between mt-6">
        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
          <span>Show</span>
          <select v-model="perPage" @change="applyAllFilter"
            class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 px-2 py-1.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option :value="15">15</option>
            <option :value="50">50</option>
            <option :value="100">100</option>
            <option :value="200">200</option>
            <option :value="500">500</option>
            <option :value="1000">1000</option>
          </select>
          <span>entries</span>
        </div>
        <Pagination v-if="tractors?.links" :links="tractors.links" />
      </div>

      <!-- Floating multi-select bar -->
      <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="translate-y-4 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-4 opacity-0">
        <div v-if="canBulkAction && selectedCount > 0" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-30 flex items-center gap-4 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 px-5 py-3">
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
            <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ selectedCount }}</span> tractor{{ selectedCount !== 1 ? 's' : '' }} selected
          </span>
          <div class="w-px h-6 bg-gray-200 dark:bg-gray-700"></div>
          <button @click="clearSelection" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
            Clear
          </button>
          <button v-if="canBulkEdit" @click="openGeneralEditDrawer"
            class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors bg-blue-600 hover:bg-blue-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
            General Edit
          </button>
          <button v-if="canBulkDelete" @click="openBatchDeleteDrawer"
            class="inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors"
            style="background-color: #dc2626;"
            @mouseenter="$event.target.style.backgroundColor='#b91c1c'"
            @mouseleave="$event.target.style.backgroundColor='#dc2626'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            Delete Selected
          </button>
        </div>
      </Transition>
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
      <!-- Flat FCA Distributions Table -->
      <DataTable>
        <!-- Loading overlay -->
        <template #loading>
          <div v-if="pageLoading" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/70 dark:bg-gray-800/70 backdrop-blur-[1px]">
            <div class="flex flex-col items-center gap-3">
              <svg class="animate-spin h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400 animate-pulse">Loading...</p>
            </div>
          </div>
        </template>
        <template #head>
          <tr>
            <th scope="col" class="px-6 py-3 whitespace-nowrap">ID</th>
            <th scope="col" class="px-6 py-3 whitespace-nowrap">FCA Name</th>
            <th scope="col" class="px-6 py-3 whitespace-nowrap">Coop</th>
            <th scope="col" class="px-6 py-3 whitespace-nowrap">Tractor No. Plate</th>
            <th scope="col" class="px-6 py-3 whitespace-nowrap">Address</th>
            <th scope="col" class="px-6 py-3 whitespace-nowrap">Total Distance (km)</th>
            <th scope="col" class="px-6 py-3 whitespace-nowrap">Running Hours</th>
            <th scope="col" class="px-6 py-3 text-right whitespace-nowrap">Status</th>
          </tr>
        </template>
        <template #body>
          <template v-for="fca in fcaDistributions" :key="fca.id">
            <tr v-for="dist in fca.distributions" :key="dist.id"
              class="bg-white border-b hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-600">
              <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ dist.id }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ fca.name }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ fca.organization_name || '—' }}</td>
              <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ dist.tractor?.no_plate || '—' }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ [fca.barangay, fca.city, fca.province].filter(Boolean).join(', ') || '—' }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ dist.tractor?.total_distance ?? '—' }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ dist.tractor?.running_hours ?? '—' }}</td>
              <td class="px-6 py-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1">
                  <StatusBadge :status="dist.status" />
                  <Link v-if="dist.status === 'distributed'" :href="`/tractors/distribution/${dist.id}/return`" class="p-1.5 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:text-amber-400 dark:hover:bg-amber-900/20 transition-colors" title="Return Tractor">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                  </Link>
                </div>
              </td>
            </tr>
          </template>
          <tr v-if="!fcaDistributions?.length">
            <td colspan="8" class="px-6 py-12 text-center">
              <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No distributions found</h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Distribute a tractor to an FCA to get started.</p>
            </td>
          </tr>
        </template>
      </DataTable>
    </template>

    <!-- ==================== TSR RESPONSIBILITIES TAB ==================== -->
    <template v-if="activeTab === 'tsr'">
      <!-- Filters -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 mb-6 dark:bg-gray-800 dark:border-gray-700">
        <div class="space-y-3">
          <input v-model="tpsSearch" type="text" placeholder="Search TSR name, plate, brand..." @input="debouncedTpsFilter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full sm:w-80 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" />
          <p class="text-sm text-gray-500 dark:text-gray-400">
            These assignments define TSR coordination responsibility. TSR users marked with full fleet access are managed from Users and do not appear in this responsibility view.
          </p>
        </div>
      </div>
      <!-- Flat TSR Responsibilities Table -->
      <DataTable>
        <!-- Loading overlay -->
        <template #loading>
          <div v-if="pageLoading" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/70 dark:bg-gray-800/70 backdrop-blur-[1px]">
            <div class="flex flex-col items-center gap-3">
              <svg class="animate-spin h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400 animate-pulse">Loading...</p>
            </div>
          </div>
        </template>
        <template #head>
          <tr>
            <th scope="col" class="px-6 py-3 whitespace-nowrap">No</th>
            <th scope="col" class="px-6 py-3 whitespace-nowrap">TPS Name</th>
            <th scope="col" class="px-6 py-3 whitespace-nowrap">Tractor Name</th>
            <th scope="col" class="px-6 py-3 whitespace-nowrap">Date Distributed</th>
            <th scope="col" class="px-6 py-3 text-right whitespace-nowrap">Actions</th>
          </tr>
        </template>
        <template #body>
          <template v-for="tsr in tsrAssignments" :key="tsr.id">
            <tr v-for="(tractor, tIndex) in tsr.tractors" :key="tractor.id"
              class="bg-white border-b hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-600">
              <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ tIndex + 1 }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ tsr.name }}</td>
              <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ tractor.no_plate }}</td>
              <td class="px-6 py-4 whitespace-nowrap">{{ tractor.pivot?.created_at ? formatDate(tractor.pivot.created_at) : '—' }}</td>
              <td class="px-6 py-4 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1">
                  <Link :href="`/tractors/${tractor.id}`" class="p-1.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20 transition-colors" title="View">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                  </Link>
                </div>
              </td>
            </tr>
          </template>
          <tr v-if="!tsrAssignments?.length">
            <td colspan="5" class="px-6 py-12 text-center">
              <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No TSR responsibilities yet</h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Assign TSR users to groups to define which tractors they coordinate here. Full fleet TSR access is configured from Users.</p>
            </td>
          </tr>
        </template>
      </DataTable>
    </template>

    <!-- ==================== DELETED TRACTORS TAB ==================== -->
    <template v-if="activeTab === 'deleted' && $page.props.auth.user.permissions.includes('tractors.view_deleted')">
      <!-- Filters -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 mb-6 dark:bg-gray-800 dark:border-gray-700">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <input v-model="deletedSearch" type="text" placeholder="Search plate, IMEI, brand, name..." @input="debouncedDeletedFilter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" />
        </div>
      </div>
      <!-- Table -->
      <DataTable>
        <template #loading>
          <div v-if="pageLoading" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-white/70 dark:bg-gray-800/70 backdrop-blur-[1px]">
            <div class="flex flex-col items-center gap-3">
              <svg class="animate-spin h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400 animate-pulse">Loading...</p>
            </div>
          </div>
        </template>
        <template #head>
          <tr>
            <th scope="col" class="px-6 py-3 whitespace-nowrap">ID</th>
            <th scope="col" class="px-6 py-3 whitespace-nowrap">Name</th>
            <th scope="col" class="px-6 py-3 whitespace-nowrap">No. Plate</th>
            <th scope="col" class="px-6 py-3 whitespace-nowrap">Brand / Model</th>
            <th scope="col" class="px-6 py-3 whitespace-nowrap">IMEI</th>
            <th scope="col" class="px-6 py-3 whitespace-nowrap">Deleted At</th>
            <th scope="col" class="px-6 py-3 text-right whitespace-nowrap">Actions</th>
          </tr>
        </template>
        <template #body>
          <tr v-for="tractor in deletedTractors?.data" :key="tractor.id"
            class="bg-white border-b hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-600">
            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ tractor.id }}</td>
            <td class="px-6 py-4 whitespace-nowrap">{{ tractor.name || '—' }}</td>
            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ tractor.no_plate }}</td>
            <td class="px-6 py-4 whitespace-nowrap">{{ [tractor.brand, tractor.model].filter(Boolean).join(' ') || '—' }}</td>
            <td class="px-6 py-4 whitespace-nowrap">{{ tractor.imei || '—' }}</td>
            <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(tractor.deleted_at) }}</td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="flex items-center justify-end gap-1">
                <button v-if="$page.props.auth.user.permissions.includes('tractors.delete')" @click="restoreTractor(tractor.id)" class="p-1.5 rounded-lg text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:text-gray-400 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20 transition-colors" title="Restore">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                </button>
                <button v-if="$page.props.auth.user.permissions.includes('tractors.delete')" @click="openForceDeleteModal(tractor)" class="p-1.5 rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50 dark:text-gray-400 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors" title="Permanently Delete">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!deletedTractors?.data?.length">
            <td colspan="7" class="px-6 py-12 text-center">
              <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No deleted tractors</h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Deleted tractors will appear here.</p>
            </td>
          </tr>
        </template>
      </DataTable>
      <div class="flex items-center justify-end mt-6">
        <Pagination v-if="deletedTractors?.links" :links="deletedTractors.links" />
      </div>
    </template>

    <!-- ==================== DELETE CONFIRMATION DRAWER ==================== -->
    <SlideOver :show="deleteDrawerOpen" max-width="2xl" title="Delete Tractor" subtitle="Review impact before confirming deletion" @close="closeDeleteDrawer">
      <div class="flex-1 overflow-y-auto">
        <div class="p-6 space-y-5">
          <!-- Tractor Info -->
          <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 p-4">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Tractor Details</h4>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-sm">
              <span class="text-gray-500 dark:text-gray-400">Plate No:</span>
              <span class="font-medium text-gray-900 dark:text-white">{{ deleteTractor?.no_plate }}</span>
              <span class="text-gray-500 dark:text-gray-400">Name:</span>
              <span class="font-medium text-gray-900 dark:text-white">{{ deleteTractor?.name || '—' }}</span>
              <span class="text-gray-500 dark:text-gray-400">Brand / Model:</span>
              <span class="font-medium text-gray-900 dark:text-white">{{ deleteTractor?.brand }} {{ deleteTractor?.model }}</span>
              <span class="text-gray-500 dark:text-gray-400">IMEI:</span>
              <span class="font-medium text-gray-900 dark:text-white">{{ deleteTractor?.imei || '—' }}</span>
            </div>
          </div>

          <!-- Loading indicator -->
          <div v-if="deleteCheckLoading" class="flex flex-col items-center justify-center py-8 gap-3">
            <svg class="animate-spin h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Checking related records...</p>
          </div>

          <!-- Impact Analysis -->
          <div v-if="!deleteCheckLoading && deleteImpact" class="space-y-3">
            <div class="flex items-center justify-between">
              <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Impact Analysis</h4>
              <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="deleteImpact.total_affected > 0
                  ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                  : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'">
                <svg v-if="deleteImpact.total_affected > 0" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                {{ deleteImpact.total_affected }} record{{ deleteImpact.total_affected !== 1 ? 's' : '' }} affected
              </span>
            </div>

            <!-- Affected records list -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 divide-y divide-gray-100 dark:divide-gray-700">
              <ImpactRow label="Distributions (FCA)" :count="deleteImpact.distributions_count" icon="distribution" />
              <ImpactRow label="Tickets" :count="deleteImpact.tickets_count" icon="ticket" />
              <ImpactRow label="Bookings" :count="deleteImpact.bookings_count" icon="booking" />
              <ImpactRow label="Maintenance Records" :count="deleteImpact.maintenances_count" icon="maintenance" />
              <ImpactRow label="Alerts" :count="deleteImpact.alerts_count" icon="alert" />
              <ImpactRow label="Farm Assets" :count="deleteImpact.farm_assets_count" icon="farm" />
              <ImpactRow label="Groups" :count="deleteImpact.groups_count" icon="group" />
              <ImpactRow label="Farmer Feedbacks" :count="deleteImpact.farmer_feedbacks_count" icon="feedback" />
              <ImpactRow label="FCA Tractor Details" :count="deleteImpact.fca_tractor_details_count" icon="detail" />
              <ImpactRow label="Tractor Recipients" :count="deleteImpact.tractor_recipients_count" icon="recipient" />
              <ImpactRow label="Images" :count="deleteImpact.images_count" icon="image" />
            </div>

            <!-- Warning message -->
            <div v-if="deleteImpact.total_affected > 0" class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3">
              <div class="flex gap-2">
                <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <p class="text-sm text-amber-700 dark:text-amber-400">
                  This tractor has related records in other parts of the system. Deleting it may affect data integrity. Consider reassigning or archiving related records first.
                </p>
              </div>
            </div>
          </div>

          <!-- Error state -->
          <div v-if="deleteCheckError" class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
            <div class="flex gap-2">
              <svg class="w-5 h-5 shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              <p class="text-sm text-red-700 dark:text-red-400">{{ deleteCheckError }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="sticky bottom-0 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <input id="confirmDeleteCheckbox" type="checkbox" v-model="confirmDelete"
            class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500 dark:bg-gray-700 dark:border-gray-600" />
          <label for="confirmDeleteCheckbox" class="text-sm text-gray-600 dark:text-gray-400">
            I understand the impact, delete this tractor
          </label>
        </div>
        <div class="flex gap-3">
          <button type="button" @click="closeDeleteDrawer"
            class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">Cancel</button>
          <button type="button" @click="confirmDeleteTractor" :disabled="!confirmDelete || deleteForm.processing"
            class="inline-flex items-center gap-1.5 rounded-lg px-5 py-2.5 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
            style="background-color: #dc2626;"
            @mouseenter="!deleteForm.processing && confirmDelete && ($event.target.style.backgroundColor='#b91c1c')"
            @mouseleave="!deleteForm.processing && ($event.target.style.backgroundColor='#dc2626')">
            <svg v-if="deleteForm.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            {{ deleteForm.processing ? 'Deleting...' : 'Delete Tractor' }}
          </button>
        </div>
      </div>
    </SlideOver>

    <!-- ==================== DISTRIBUTE TO FCA DRAWER ==================== -->
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
          <!-- FCA User -->
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

    <!-- ==================== BULK DELETE MODAL ==================== -->
    <Modal :show="bulkDeleteModalOpen" max-width="4xl" @close="closeBulkDeleteModal">
      <template #header>
        <div class="flex items-center gap-2">
          <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
          <span>Delete {{ selectedCount }} Tractor{{ selectedCount !== 1 ? 's' : '' }}</span>
        </div>
      </template>

      <!-- Loading -->
      <div v-if="bulkCheckLoading" class="flex flex-col items-center justify-center py-12 gap-3">
        <svg class="animate-spin h-10 w-10 text-emerald-600" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Checking related records for all selected tractors...</p>
      </div>

      <!-- Impact list -->
      <div v-if="!bulkCheckLoading && bulkCheckResults.length" class="space-y-4 max-h-[60vh] overflow-y-auto pr-1">
        <div v-for="item in bulkCheckResults" :key="item.id"
          class="rounded-lg border border-gray-200 dark:border-gray-600 overflow-hidden">
          <!-- Tractor Header -->
          <div class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
            <div class="flex items-center gap-3 min-w-0">
              <span class="flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold text-white shrink-0"
                :class="item.total_affected > 0 ? 'bg-red-500' : 'bg-green-500'">
                {{ item.total_affected }}
              </span>
              <div class="min-w-0">
                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ item.no_plate }}</span>
                <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">{{ item.brand }} {{ item.model }}</span>
              </div>
            </div>
            <button @click="removeFromBulkSelection(item.id)" type="button"
              class="shrink-0 p-1 rounded-lg text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:text-amber-400 dark:hover:bg-amber-900/20 transition-colors"
              title="Remove from selection">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>
          <!-- Impact rows -->
          <div class="divide-y divide-gray-100 dark:divide-gray-700">
            <div v-for="row in impactRows(item)" :key="row.key"
              class="flex items-center justify-between px-4 py-2 text-sm">
              <span class="text-gray-600 dark:text-gray-400">{{ row.label }}</span>
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="row.count > 0
                  ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                  : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'">
                {{ row.count }}
              </span>
            </div>
          </div>
        </div>

        <!-- Summary -->
        <div class="sticky bottom-0 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 p-4">
          <div class="flex items-center justify-between text-sm">
            <span class="font-medium text-gray-700 dark:text-gray-300">Total tractors to delete:</span>
            <span class="font-bold text-gray-900 dark:text-white">{{ bulkCheckResults.length }}</span>
          </div>
          <div class="flex items-center justify-between text-sm mt-1">
            <span class="font-medium text-gray-700 dark:text-gray-300">Total records affected:</span>
            <span class="font-bold text-red-600 dark:text-red-400">{{ totalBulkAffected }}</span>
          </div>
        </div>
      </div>

      <!-- Error -->
      <div v-if="bulkCheckError" class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
        <p class="text-sm text-red-700 dark:text-red-400">{{ bulkCheckError }}</p>
      </div>

      <template #footer>
        <button type="button" @click="closeBulkDeleteModal"
          class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-600 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-500">
          Cancel
        </button>
        <div class="flex items-center gap-2 ml-auto">
          <input id="bulkConfirmDeleteCheckbox" type="checkbox" v-model="bulkConfirmDelete"
            class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500 dark:bg-gray-700 dark:border-gray-600" />
          <label for="bulkConfirmDeleteCheckbox" class="text-sm text-gray-600 dark:text-gray-400 select-none cursor-pointer">
            I understand the impact, delete all {{ bulkCheckResults.length }} tractor{{ bulkCheckResults.length !== 1 ? 's' : '' }}
          </label>
        </div>
        <button type="button" @click="confirmBulkDelete" :disabled="!bulkConfirmDelete || bulkDeleteProcessing"
          class="inline-flex items-center gap-1.5 rounded-lg px-5 py-2.5 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
          style="background-color: #dc2626;"
          @mouseenter="!bulkDeleteProcessing && bulkConfirmDelete && ($event.target.style.backgroundColor='#b91c1c')"
          @mouseleave="!bulkDeleteProcessing && ($event.target.style.backgroundColor='#dc2626')">
          <svg v-if="bulkDeleteProcessing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
          {{ bulkDeleteProcessing ? 'Deleting...' : 'Delete All' }}
        </button>
      </template>
    </Modal>
    <!-- ==================== GENERAL EDIT DRAWER ==================== -->
    <SlideOver :show="generalEditOpen" max-width="2xl" title="General Edit" :subtitle="`Update common fields for ${selectedCount} tractor${selectedCount !== 1 ? 's' : ''}`" @close="closeGeneralEditDrawer">
      <form @submit.prevent="submitGeneralEdit" class="flex-1 overflow-y-auto">
        <div class="p-6 space-y-5">
          <p class="text-sm text-gray-500 dark:text-gray-400">
            Only fields you fill in will be updated. Leave a field blank to keep its current value.
          </p>

          <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3">
            <div class="flex items-start gap-2">
              <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              <p class="text-sm text-amber-700 dark:text-amber-400">
                Use <strong>Sync No. Plate</strong> to set each selected tractor's plate number to <code class="font-mono">VL103M-(last 5 digits of IMEI)</code>.
              </p>
            </div>
          </div>

          <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Brand</label>
            <input v-model="generalEditForm.brand" type="text" placeholder="Leave blank to keep current"
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
          </div>

          <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Model</label>
            <input v-model="generalEditForm.model" type="text" placeholder="Leave blank to keep current"
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
          </div>

          <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Fuel Consumption (L/hr)</label>
            <input v-model="generalEditForm.fuel_consumption" type="number" step="0.01" min="0" placeholder="Leave blank to keep current"
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
          </div>

          <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Maintenance KM Threshold</label>
            <input v-model="generalEditForm.maintenance_km" type="number" step="0.01" min="0" placeholder="Leave blank to keep current"
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
          </div>

          <div>
            <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Maintenance Hours Threshold</label>
            <input v-model="generalEditForm.maintenance_hours" type="number" step="0.01" min="0" placeholder="Leave blank to keep current"
              class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
          </div>
        </div>

        <div class="sticky bottom-0 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 px-6 py-4 flex justify-end gap-3">
          <button type="button" @click="syncNoPlates" :disabled="syncPlatesProcessing || !generalEditForm.tractor_ids.length"
            class="inline-flex items-center gap-1.5 rounded-lg px-5 py-2.5 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
            style="background-color: #b45309;"
            @mouseenter="!syncPlatesProcessing && ($event.target.style.backgroundColor='#92400e')"
            @mouseleave="!syncPlatesProcessing && ($event.target.style.backgroundColor='#b45309')">
            <svg v-if="syncPlatesProcessing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
            {{ syncPlatesProcessing ? 'Syncing...' : 'Sync No. Plate' }}
          </button>
          <button type="button" @click="closeGeneralEditDrawer"
            class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">Cancel</button>
          <button type="submit" :disabled="generalEditForm.processing"
            class="inline-flex items-center gap-1.5 rounded-lg px-5 py-2.5 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed bg-blue-600 hover:bg-blue-700">
            <svg v-if="generalEditForm.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
            {{ generalEditForm.processing ? 'Updating...' : `Update ${selectedCount} Tractor${selectedCount !== 1 ? 's' : ''}` }}
          </button>
        </div>
      </form>
    </SlideOver>

    <!-- ==================== FORCE DELETE MODAL ==================== -->
    <Modal :show="forceDeleteModalOpen" max-width="md" @close="closeForceDeleteModal">
      <template #header>
        <div class="flex items-center gap-2">
          <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
          <span>Permanently Delete Tractor</span>
        </div>
      </template>

      <div class="p-6 space-y-4">
        <p class="text-sm text-gray-600 dark:text-gray-300">
          You are about to <strong class="text-red-600 dark:text-red-400">permanently delete</strong> tractor
          <span class="font-semibold text-gray-900 dark:text-white">{{ forceDeleteTractor?.no_plate }}</span>
          <template v-if="forceDeleteTractor?.name">({{ forceDeleteTractor.name }})</template>.
          This action cannot be undone and will also remove its images, distributions, bookings, and maintenance records.
        </p>
        <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3">
          <p class="text-sm text-amber-700 dark:text-amber-400">
            Consider using <strong>Restore</strong> instead if you only need to bring this tractor back.
          </p>
        </div>
      </div>

      <template #footer>
        <button type="button" @click="closeForceDeleteModal"
          class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">Cancel</button>
        <button type="button" @click="confirmForceDelete" :disabled="forceDeleteProcessing"
          class="inline-flex items-center gap-1.5 rounded-lg px-5 py-2.5 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
          style="background-color: #dc2626;"
          @mouseenter="!forceDeleteProcessing && ($event.target.style.backgroundColor='#b91c1c')"
          @mouseleave="!forceDeleteProcessing && ($event.target.style.backgroundColor='#dc2626')">
          <svg v-if="forceDeleteProcessing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
          <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
          {{ forceDeleteProcessing ? 'Deleting...' : 'Delete Permanently' }}
        </button>
      </template>
    </Modal>

    <!-- ==================== DUPLICATE IMEI MODAL ==================== -->
    <Modal :show="duplicateModalOpen" max-width="4xl" @close="closeDuplicateModal">
      <template #header>
        <div class="flex items-center gap-2">
          <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
          <span>Duplicate IMEI Detection</span>
        </div>
      </template>

      <div class="max-h-[65vh] overflow-y-auto p-6 space-y-4">
        <!-- Loading -->
        <div v-if="duplicateLoading" class="flex flex-col items-center justify-center py-12 gap-3">
          <svg class="animate-spin h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <p class="text-sm font-medium text-gray-500 dark:text-gray-400 animate-pulse">Scanning tractors for duplicate IMEI...</p>
        </div>

        <!-- Error -->
        <div v-else-if="duplicateError" class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
          <p class="text-sm text-red-700 dark:text-red-400">{{ duplicateError }}</p>
        </div>

        <!-- Empty -->
        <div v-else-if="!duplicateGroups.length" class="py-12 text-center">
          <svg class="mx-auto h-12 w-12 text-emerald-500 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="mt-4 text-sm font-medium text-gray-900 dark:text-white">No duplicate IMEIs found</p>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">All tractors have unique IMEI values.</p>
        </div>

        <!-- Groups -->
        <template v-else>
          <div v-for="group in duplicateGroups" :key="group.imei"
            class="rounded-lg border border-gray-200 dark:border-gray-600 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 bg-amber-50 dark:bg-amber-900/20 border-b border-amber-200 dark:border-amber-800">
              <div>
                <p class="text-sm font-semibold text-gray-900 dark:text-white">IMEI: {{ group.imei || '—' }}</p>
                <p class="text-xs text-amber-700 dark:text-amber-400">{{ group.count }} tractor{{ group.count !== 1 ? 's' : '' }} share this IMEI</p>
              </div>
              <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold text-white bg-amber-500">{{ group.count }}</span>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
              <div v-for="tractor in group.tractors" :key="tractor.id"
                class="flex flex-col sm:flex-row sm:items-center gap-3 px-4 py-3">
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{ tractor.no_plate }}</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ tractor.name || '—' }} · {{ [tractor.brand, tractor.model].filter(Boolean).join(' ') || '—' }}
                  </p>
                </div>
                <div class="flex items-center gap-2">
                  <input v-model="imeiInputs[tractor.id]" type="text" placeholder="IMEI"
                    class="w-48 rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
                  <button type="button" @click="saveImei(tractor)" :disabled="imeiSaving[tractor.id]"
                    class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg v-if="imeiSaving[tractor.id]" class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                    {{ imeiSaving[tractor.id] ? 'Saving...' : 'Save' }}
                  </button>
                  <button type="button" @click="deleteDuplicate(tractor)" :disabled="imeiDeleting[tractor.id]"
                    class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-xs font-medium text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg v-if="imeiDeleting[tractor.id]" class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                    {{ imeiDeleting[tractor.id] ? 'Deleting...' : 'Delete' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </template>
      </div>

      <template #footer>
        <button type="button" @click="closeDuplicateModal"
          class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">Close</button>
        <span v-if="duplicateGroups.length" class="ml-auto text-sm text-gray-500 dark:text-gray-400">
          {{ duplicateGroups.length }} IMEI group{{ duplicateGroups.length !== 1 ? 's' : '' }} with duplicates
        </span>
      </template>
    </Modal>

  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, h } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import SlideOver from '@/Components/SlideOver.vue';
import Modal from '@/Components/Modal.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import DataTable from '@/Components/DataTable.vue';
import { formatDate } from '@/utils/dateFormat';
import axios from 'axios';

// ── Sortable Table Header Component ──
const SortableTh = {
  props: {
    label: String,
    field: String,
    sortField: String,
    sortDirection: String,
  },
  emits: ['sort'],
  setup(props, { emit }) {
    const isActive = computed(() => props.sortField === props.field);
    const icon = computed(() => {
      if (!isActive.value) return null;
      return props.sortDirection === 'asc' ? '▲' : '▼';
    });

    return () => h('th', {
      scope: 'col',
      class: 'px-6 py-3 whitespace-nowrap cursor-pointer select-none hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors group',
      onClick: () => emit('sort', props.field),
    }, [
      h('span', { class: 'inline-flex items-center gap-1.5' }, [
        h('span', { class: 'text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-300 transition-colors' }, props.label),
        h('span', { class: 'flex flex-col leading-none text-gray-400 dark:text-gray-500' }, [
          h('span', {
            class: isActive.value && props.sortDirection === 'asc'
              ? 'text-emerald-600 dark:text-emerald-400 font-bold'
              : 'group-hover:text-gray-600 dark:group-hover:text-gray-300',
          }, '▲'),
          h('span', {
            class: isActive.value && props.sortDirection === 'desc'
              ? 'text-emerald-600 dark:text-emerald-400 font-bold'
              : 'group-hover:text-gray-600 dark:group-hover:text-gray-300',
          }, '▼'),
        ]),
      ]),
    ]);
  },
};

// ── Impact Row Component ──
const ImpactRow = {
  props: { label: String, count: Number, icon: String },
  setup(props) {
    const iconPaths = {
      distribution: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
      ticket: 'M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z',
      booking: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
      maintenance: 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4',
      alert: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
      farm: 'M3 21h18M3 10h18M3 7l9-4 9 4v14H3V7z',
      group: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
      feedback: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
      detail: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
      recipient: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z',
      image: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
    };
    return () => h('div', { class: 'flex items-center justify-between px-4 py-2.5 text-sm' }, [
      h('div', { class: 'flex items-center gap-2' }, [
        h('svg', { class: 'w-4 h-4 text-gray-400', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
          h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: iconPaths[props.icon] || iconPaths.detail }),
        ]),
        h('span', { class: 'text-gray-700 dark:text-gray-300' }, props.label),
      ]),
      h('span', {
        class: props.count > 0
          ? 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
          : 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
      }, String(props.count)),
    ]);
  },
};

const props = defineProps({
  tractors: Object,
  fcaDistributions: Array,
  tsrAssignments: Array,
  deletedTractors: Object,
  filters: Object,
  groups: Array,
  fcaUsers: Array,
  allTractors: Array,
});

// --- Loading state ---
const pageLoading = ref(false);
let startHandler, finishHandler;

onMounted(() => {
  startHandler = () => { pageLoading.value = true; };
  finishHandler = () => { pageLoading.value = false; };
  router.on('start', startHandler);
  router.on('finish', finishHandler);
});

onUnmounted(() => {
  if (startHandler) router.off('start', startHandler);
  if (finishHandler) router.off('finish', finishHandler);
});

// --- Tabs ---
const activeTab = ref(props.filters?.tab || 'all');
const switchTab = (tab) => {
  activeTab.value = tab;
  router.get('/tractors', { tab }, { preserveState: true, replace: true });
};

// --- All Tractors Filters & Sort ---
const search = ref(props.filters?.search || '');
const selectedGroup = ref(props.filters?.group_id || '');
const selectedStatus = ref(props.filters?.status || '');
const perPage = ref(props.filters?.per_page || 15);
const sortField = ref(props.filters?.sort || '');
const sortDirection = ref(props.filters?.direction || 'asc');

let timer;
const debouncedFilter = () => { clearTimeout(timer); timer = setTimeout(applyAllFilter, 300); };

const sortBy = (field) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
  applyAllFilter();
};

const applyAllFilter = () => {
  router.get('/tractors', {
    tab: 'all',
    search: search.value || undefined,
    group_id: selectedGroup.value || undefined,
    status: selectedStatus.value || undefined,
    per_page: perPage.value,
    sort: sortField.value || undefined,
    direction: sortDirection.value || undefined,
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

// --- TSR Filters ---
const tpsSearch = ref(props.filters?.tsr_search || '');
let tpsTimer;
const debouncedTpsFilter = () => { clearTimeout(tpsTimer); tpsTimer = setTimeout(applyTpsFilter, 300); };
const applyTpsFilter = () => {
  router.get('/tractors', {
    tab: 'tsr',
    tsr_search: tpsSearch.value || undefined,
  }, { preserveState: true, replace: true });
};

// --- Deleted Tractors Filters ---
const deletedSearch = ref(props.filters?.deleted_search || '');
let deletedTimer;
const debouncedDeletedFilter = () => { clearTimeout(deletedTimer); deletedTimer = setTimeout(applyDeletedFilter, 300); };
const applyDeletedFilter = () => {
  router.get('/tractors', {
    tab: 'deleted',
    deleted_search: deletedSearch.value || undefined,
  }, { preserveState: true, replace: true });
};

// --- Online status (JIMI status flag: 1 = online, e.g. parked/moving) ---
const getOnlineStatus = (tractor) => {
  return Number(tractor.device?.latest_location?.status) === 1 ? 'online' : 'offline';
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

// ── General Edit (Bulk) ──
const generalEditOpen = ref(false);
const generalEditForm = useForm({
  tractor_ids: [],
  brand: '',
  model: '',
  fuel_consumption: '',
  maintenance_km: '',
  maintenance_hours: '',
});

const openGeneralEditDrawer = () => {
  generalEditForm.reset();
  generalEditForm.clearErrors();
  generalEditForm.tractor_ids = Object.keys(selectedIds.value).map(Number);
  generalEditOpen.value = true;
};

const closeGeneralEditDrawer = () => {
  generalEditOpen.value = false;
};

const submitGeneralEdit = () => {
  generalEditForm.post('/tractors/batch-update', {
    preserveScroll: true,
    onSuccess: () => {
      closeGeneralEditDrawer();
      clearSelection();
    },
  });
};

// ── Sync No. Plate (VL103M-{last 5 IMEI}) ──
const syncPlatesProcessing = ref(false);

const syncNoPlates = () => {
  const ids = generalEditForm.tractor_ids;
  if (!ids.length) return;

  if (!window.confirm(`Set no. plate of ${ids.length} selected tractor${ids.length !== 1 ? 's' : ''} to VL103M-(last 5 digits of IMEI)?`)) {
    return;
  }

  syncPlatesProcessing.value = true;
  router.post('/tractors/sync-no-plates', { tractor_ids: ids }, {
    preserveScroll: true,
    onSuccess: () => {
      syncPlatesProcessing.value = false;
      closeGeneralEditDrawer();
    },
    onError: () => {
      syncPlatesProcessing.value = false;
    },
  });
};

// ── Multi-Select (persisted in sessionStorage so it survives Inertia navigation) ──
const STORAGE_KEY = 'tractor_selected_ids';

const page = usePage();
const canBulkEdit = computed(() =>
  page.props.auth.user.permissions.includes('tractors.edit')
);
const canBulkDelete = computed(() =>
  page.props.auth.user.permissions.includes('tractors.delete')
);
const canBulkAction = computed(() => canBulkEdit.value || canBulkDelete.value);

const saved = sessionStorage.getItem(STORAGE_KEY);
const selectedIds = ref(saved ? JSON.parse(saved) : {});

const persistSelection = () => {
  sessionStorage.setItem(STORAGE_KEY, JSON.stringify(selectedIds.value));
};

const selectedCount = computed(() => Object.keys(selectedIds.value).length);

const selectedIsChecked = (id) => !!selectedIds.value[id];

const toggleSelect = (id, event) => {
  const next = { ...selectedIds.value };
  if (next[id]) {
    delete next[id];
  } else {
    next[id] = true;
  }
  selectedIds.value = next;
  persistSelection();
};

const isAllSelected = computed(() => {
  const data = props.tractors?.data;
  return data && data.length > 0 && data.every(t => selectedIds.value[t.id]);
});

const toggleSelectAll = (event) => {
  const data = props.tractors?.data;
  if (!data) return;
  const next = { ...selectedIds.value };
  if (isAllSelected.value) {
    data.forEach(t => delete next[t.id]);
  } else {
    data.forEach(t => next[t.id] = true);
  }
  selectedIds.value = next;
  persistSelection();
};

const clearSelection = () => {
  selectedIds.value = {};
  sessionStorage.removeItem(STORAGE_KEY);
};

// ── Bulk Delete ──
const bulkDeleteModalOpen = ref(false);
const bulkCheckLoading = ref(false);
const bulkCheckResults = ref([]);
const bulkCheckError = ref(null);
const bulkConfirmDelete = ref(false);
const bulkDeleteProcessing = ref(false);

const impactRows = (item) => [
  { key: 'distributions', label: 'Distributions (FCA)', count: item.distributions_count },
  { key: 'tickets', label: 'Tickets', count: item.tickets_count },
  { key: 'bookings', label: 'Bookings', count: item.bookings_count },
  { key: 'maintenances', label: 'Maintenance Records', count: item.maintenances_count },
  { key: 'alerts', label: 'Alerts', count: item.alerts_count },
  { key: 'farm_assets', label: 'Farm Assets', count: item.farm_assets_count },
  { key: 'groups', label: 'Groups', count: item.groups_count },
  { key: 'feedbacks', label: 'Farmer Feedbacks', count: item.farmer_feedbacks_count },
  { key: 'fca_details', label: 'FCA Tractor Details', count: item.fca_tractor_details_count },
  { key: 'recipients', label: 'Tractor Recipients', count: item.tractor_recipients_count },
  { key: 'images', label: 'Images', count: item.images_count },
];

const totalBulkAffected = computed(() =>
  bulkCheckResults.value.reduce((sum, item) => sum + item.total_affected, 0)
);

const openBatchDeleteDrawer = async () => {
  const ids = Object.keys(selectedIds.value).map(Number);
  if (ids.length === 0) return;
  bulkCheckResults.value = [];
  bulkCheckError.value = null;
  bulkCheckLoading.value = true;
  bulkConfirmDelete.value = false;
  bulkDeleteModalOpen.value = true;

  try {
    const { data } = await axios.post('/tractors/batch-delete-check', { tractor_ids: ids });
    bulkCheckResults.value = data.data;
  } catch (err) {
    bulkCheckError.value = err.response?.data?.message || 'Failed to check related records. Please try again.';
  } finally {
    bulkCheckLoading.value = false;
  }
};

const closeBulkDeleteModal = () => {
  bulkDeleteModalOpen.value = false;
  bulkCheckResults.value = [];
  bulkCheckError.value = null;
  bulkConfirmDelete.value = false;
};

const removeFromBulkSelection = (id) => {
  const next = { ...selectedIds.value };
  delete next[id];
  selectedIds.value = next;
  persistSelection();

  // Remove from results too
  bulkCheckResults.value = bulkCheckResults.value.filter(r => r.id !== id);

  // If nothing left, close modal
  if (Object.keys(selectedIds.value).length === 0) {
    closeBulkDeleteModal();
  }
};

const confirmBulkDelete = () => {
  const ids = Object.keys(selectedIds.value).map(Number);
  if (!bulkConfirmDelete || ids.length === 0) return;
  bulkDeleteProcessing.value = true;

  router.post('/tractors/batch-destroy', {
    tractor_ids: ids,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      bulkDeleteProcessing.value = false;
      closeBulkDeleteModal();
      clearSelection();
    },
    onError: () => {
      bulkDeleteProcessing.value = false;
      bulkCheckError.value = 'Failed to delete tractors. Please try again.';
    },
  });
};

// ── Single Delete Tractor ──
const deleteDrawerOpen = ref(false);
const deleteTractor = ref(null);
const deleteImpact = ref(null);
const deleteCheckLoading = ref(false);
const deleteCheckError = ref(null);
const confirmDelete = ref(false);

const deleteForm = useForm({});

const openDeleteDrawer = async (tractor) => {
  deleteTractor.value = tractor;
  deleteImpact.value = null;
  deleteCheckError.value = null;
  deleteCheckLoading.value = true;
  confirmDelete.value = false;
  deleteDrawerOpen.value = true;

  try {
    const { data } = await axios.get(`/tractors/${tractor.id}/delete-check`);
    deleteImpact.value = data.data;
  } catch (err) {
    deleteCheckError.value = err.response?.data?.message || 'Failed to check related records. Please try again.';
  } finally {
    deleteCheckLoading.value = false;
  }
};

const closeDeleteDrawer = () => {
  deleteDrawerOpen.value = false;
  deleteTractor.value = null;
  deleteImpact.value = null;
  deleteCheckError.value = null;
  confirmDelete.value = false;
};

const confirmDeleteTractor = () => {
  if (!confirmDelete.value || !deleteTractor.value) return;

  deleteForm.delete(`/tractors/${deleteTractor.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      closeDeleteDrawer();
    },
    onError: () => {
      deleteCheckError.value = 'Failed to delete tractor. Please try again.';
    },
  });
};

// ── Deleted Tractors: Restore & Force Delete ──
const restoreTractor = (id) => {
  router.post(`/tractors/${id}/restore`, {}, {
    preserveScroll: true,
  });
};

const forceDeleteModalOpen = ref(false);
const forceDeleteTractor = ref(null);
const forceDeleteProcessing = ref(false);

const openForceDeleteModal = (tractor) => {
  forceDeleteTractor.value = tractor;
  forceDeleteModalOpen.value = true;
};

const closeForceDeleteModal = () => {
  forceDeleteModalOpen.value = false;
  forceDeleteTractor.value = null;
};

const confirmForceDelete = () => {
  if (!forceDeleteTractor.value) return;
  forceDeleteProcessing.value = true;
  router.delete(`/tractors/${forceDeleteTractor.value.id}/force-delete`, {
    preserveScroll: true,
    onSuccess: () => {
      forceDeleteProcessing.value = false;
      closeForceDeleteModal();
    },
    onError: () => {
      forceDeleteProcessing.value = false;
    },
  });
};

// ── Duplicate IMEI Modal ──
const duplicateModalOpen = ref(false);
const duplicateLoading = ref(false);
const duplicateError = ref(null);
const duplicateGroups = ref([]);
const imeiInputs = ref({});
const imeiSaving = ref({});
const imeiDeleting = ref({});

const loadDuplicates = async () => {
  const { data } = await axios.get('/tractors/duplicates');
  duplicateGroups.value = data.data;
  imeiInputs.value = {};
  data.data.forEach((group) => group.tractors.forEach((t) => { imeiInputs.value[t.id] = t.imei; }));
};

const openDuplicateModal = async () => {
  duplicateModalOpen.value = true;
  duplicateLoading.value = true;
  duplicateError.value = null;
  duplicateGroups.value = [];
  imeiInputs.value = {};

  try {
    await loadDuplicates();
  } catch (err) {
    duplicateError.value = err.response?.data?.message || 'Failed to load duplicate IMEI data.';
  } finally {
    duplicateLoading.value = false;
  }
};

const closeDuplicateModal = () => {
  duplicateModalOpen.value = false;
  duplicateGroups.value = [];
  imeiInputs.value = {};
  duplicateError.value = null;
};

const saveImei = async (tractor) => {
  const newImei = (imeiInputs.value[tractor.id] || '').trim();
  if (!newImei) return;

  imeiSaving.value = { ...imeiSaving.value, [tractor.id]: true };
  try {
    await axios.post(`/tractors/${tractor.id}/update-imei`, { imei: newImei });
    await loadDuplicates();
  } catch (err) {
    window.alert(err.response?.data?.errors?.imei?.[0] || err.response?.data?.message || 'Failed to update IMEI.');
  } finally {
    imeiSaving.value = { ...imeiSaving.value, [tractor.id]: false };
  }
};

const deleteDuplicate = async (tractor) => {
  if (!window.confirm(`Delete tractor ${tractor.no_plate}? This will remove it from the system.`)) return;

  imeiDeleting.value = { ...imeiDeleting.value, [tractor.id]: true };
  try {
    await axios.delete(`/tractors/${tractor.id}/quick-delete`);
    await loadDuplicates();
  } catch (err) {
    window.alert(err.response?.data?.message || 'Failed to delete tractor.');
  } finally {
    imeiDeleting.value = { ...imeiDeleting.value, [tractor.id]: false };
  }
};
</script>
