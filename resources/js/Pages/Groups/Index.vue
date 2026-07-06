<template>
  <AppLayout>
    <Head title="Tractor Groups" />

    <!-- Page header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tractor Groups</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Organize tractors into groups and define TPS responsibility areas.</p>
      </div>
      <button @click="openCreateDrawer"
        class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 mt-4 sm:mt-0"
        style="background-color: #007f3d;"
        @mouseenter="$event.target.style.backgroundColor='#006631'"
        @mouseleave="$event.target.style.backgroundColor='#007f3d'">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        Add Group
      </button>
    </div>

    <!-- Search filter -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 mb-6 dark:bg-gray-800 dark:border-gray-700">
      <input v-model="search" type="text" placeholder="Search group name or area..." @input="debouncedFilter"
        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full sm:w-80 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-emerald-500 dark:focus:border-emerald-500" />
    </div>

    <!-- Group cards -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="group in groups.data" :key="group.id"
        class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 hover:shadow-md transition dark:bg-gray-800 dark:border-gray-700">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ group.name }}</h3>
          <StatusBadge :status="group.is_active ? 'online' : 'offline'" :label="group.is_active ? 'Active' : 'Inactive'" />
        </div>
        <p v-if="group.area" class="text-sm text-gray-500 dark:text-gray-400 mb-2">
          <span class="font-medium text-gray-700 dark:text-gray-300">Area:</span> {{ group.area }}
        </p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 line-clamp-2">{{ group.description || 'No description' }}</p>
        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-4">
          <span class="inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
            {{ group.tractors_count ?? 0 }} Tractors
          </span>
          <span class="inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            {{ group.tps_count ?? 0 }} TPS
          </span>
        </div>
        <div class="flex items-center justify-end gap-1 border-t border-gray-200 dark:border-gray-700 pt-4">
          <button @click="openShowDrawer(group)" class="p-1.5 rounded-lg text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:text-gray-400 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20 transition-colors" title="View">
            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
          </button>
          <button @click="openEditDrawer(group)" class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:text-gray-400 dark:hover:text-blue-400 dark:hover:bg-blue-900/20 transition-colors" title="Edit">
            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
          </button>
          <button @click="confirmDelete(group)" class="p-1.5 rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50 dark:text-gray-400 dark:hover:text-red-400 dark:hover:bg-red-900/20 transition-colors" title="Delete">
            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
          </button>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="!groups.data?.length" class="sm:col-span-2 lg:col-span-3 bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <div class="flex flex-col items-center justify-center py-16 px-6">
          <svg class="h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 01-1.125-1.125v-3.75zM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-8.25zM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-2.25z" />
          </svg>
          <p class="mt-4 text-sm font-medium text-gray-900 dark:text-white">No groups found</p>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new tractor group.</p>
        </div>
      </div>
    </div>

    <Pagination :links="groups.links" class="mt-6" />

    <!-- Delete confirmation modal -->
    <Modal :show="showDelete" @close="showDelete = false" maxWidth="sm">
      <div class="p-6 dark:bg-gray-800">
        <div class="flex items-center gap-3 mb-4">
          <div class="shrink-0 w-10 h-10 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Delete Group</h3>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">Are you sure you want to delete "<span class="font-medium text-gray-900 dark:text-white">{{ deleteTarget?.name }}</span>"? This action cannot be undone.</p>
        <div class="mt-6 flex justify-end space-x-3">
          <button @click="showDelete = false"
            class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700">Cancel</button>
          <button @click="performDelete"
            class="text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-red-600 dark:hover:bg-red-700 focus:outline-none dark:focus:ring-red-800">Delete</button>
        </div>
      </div>
    </Modal>

    <!-- Slide-over drawer backdrop -->
    <Transition
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0">
      <div v-if="drawerOpen" class="fixed inset-0 z-40 bg-gray-900/50" @click="closeDrawer"></div>
    </Transition>

    <!-- Slide-over drawer panel -->
    <Transition
      enter-active-class="transition-transform duration-300 ease-out"
      enter-from-class="translate-x-full"
      enter-to-class="translate-x-0"
      leave-active-class="transition-transform duration-200 ease-in"
      leave-from-class="translate-x-0"
      leave-to-class="translate-x-full">
      <div v-if="drawerOpen" class="fixed inset-y-0 right-0 z-50 w-full max-w-lg">
        <div class="flex h-full flex-col bg-white shadow-2xl dark:bg-gray-800">
          <!-- Drawer header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700" style="background-color: #007f3d;">
            <div>
              <h2 class="text-lg font-semibold text-white">{{ drawerTitle }}</h2>
              <p class="text-sm text-green-100">{{ drawerSubtitle }}</p>
            </div>
            <button @click="closeDrawer" class="rounded-lg p-1.5 text-green-100 hover:text-white hover:bg-white/20 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>

          <!-- ==================== SHOW MODE ==================== -->
          <template v-if="drawerMode === 'show'">
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
              <!-- Details -->
              <div>
                <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Details</h3>
                <dl class="space-y-3">
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Area</dt>
                    <dd class="mt-0.5 text-sm text-gray-900 dark:text-white">{{ selectedGroup?.area || '—' }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                    <dd class="mt-0.5"><StatusBadge :status="selectedGroup?.is_active ? 'online' : 'offline'" :label="selectedGroup?.is_active ? 'Active' : 'Inactive'" /></dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                    <dd class="mt-0.5 text-sm text-gray-900 dark:text-white">{{ selectedGroup?.description || '—' }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                    <dd class="mt-0.5 text-sm text-gray-900 dark:text-white">{{ formatDate(selectedGroup?.created_at) }}</dd>
                  </div>
                </dl>
              </div>

              <!-- Tractors -->
              <div>
                <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Tractors ({{ selectedGroup?.tractors?.length || 0 }})</h3>
                <div v-if="selectedGroup?.tractors?.length" class="space-y-2">
                  <div v-for="t in selectedGroup.tractors" :key="t.id"
                    class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                    <span class="relative shrink-0 flex items-center justify-center w-9 h-9 rounded-lg"
                      :class="t.is_online ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-gray-100 dark:bg-gray-600'">
                      <svg class="w-4.5 h-4.5" :class="t.is_online ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0H21M3.375 14.25h3.75L9 7.5h6l1.875 6.75h3.75" />
                      </svg>
                      <span class="absolute -top-0.5 -right-0.5 w-2 h-2 rounded-full border-2 border-white dark:border-gray-700"
                        :class="t.is_online ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-500'"></span>
                    </span>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ t.brand }} {{ t.model }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ t.no_plate }}</p>
                    </div>
                    <span class="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                      :class="t.is_online
                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400'
                        : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'">
                      {{ t.is_online ? 'Online' : 'Offline' }}
                    </span>
                  </div>
                </div>
                <p v-else class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">No tractors assigned.</p>
              </div>

              <!-- TPS Responsibility Users -->
              <div>
                <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Responsible TPS ({{ selectedGroup?.tps_users?.length || 0 }})</h3>
                <div v-if="selectedGroup?.tps_users?.length" class="space-y-2">
                  <div v-for="u in selectedGroup.tps_users" :key="u.id"
                    class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700">
                    <div class="h-9 w-9 rounded-full flex items-center justify-center shrink-0 text-sm font-semibold text-white" style="background-color: #007f3d;">
                      {{ u.name?.charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                      <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ u.name }}</p>
                      <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ u.email }}</p>
                    </div>
                  </div>
                </div>
                <p v-else class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">No TPS responsibility set.</p>
              </div>
            </div>

            <!-- Show drawer footer -->
            <div class="sticky bottom-0 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 px-6 py-4 flex justify-end gap-3">
              <button @click="closeDrawer"
                class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">Close</button>
              <button @click="openEditDrawer(selectedGroup)"
                class="inline-flex items-center gap-1.5 rounded-lg px-5 py-2.5 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2"
                style="background-color: #007f3d;"
                @mouseenter="$event.target.style.backgroundColor='#006631'"
                @mouseleave="$event.target.style.backgroundColor='#007f3d'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                Edit Group
              </button>
            </div>
          </template>

          <!-- ==================== CREATE / EDIT MODE ==================== -->
          <template v-else>
            <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto">
              <div class="p-6 space-y-6">
                <!-- Group Details -->
                <div>
                  <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Group Details</h3>
                  <div class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                      <div>
                        <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Group Name <span class="text-red-500">*</span></label>
                        <input v-model="form.name" type="text" placeholder="e.g. Northern Region Fleet"
                          class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
                      </div>
                      <div>
                        <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Area</label>
                        <input v-model="form.area" type="text" placeholder="e.g. Tarlac, Pampanga"
                          class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
                        <p v-if="form.errors.area" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.area }}</p>
                      </div>
                    </div>
                    <div>
                      <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                      <textarea v-model="form.description" rows="3" placeholder="Brief description of this group..."
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400"></textarea>
                    </div>
                    <div>
                      <label class="relative inline-flex items-center cursor-pointer">
                        <input v-model="form.is_active" type="checkbox" class="sr-only peer" />
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:inset-s-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:after:border-gray-500 peer-checked:bg-emerald-600"></div>
                        <span class="ms-3 text-sm font-medium text-gray-700 dark:text-gray-300">Active</span>
                      </label>
                    </div>
                  </div>
                </div>

                <!-- Assign Tractors -->
                <div>
                  <div class="flex items-center justify-between mb-3">
                    <div>
                      <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Assign Tractors</h3>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ form.tractor_ids.length }} selected</p>
                    </div>
                    <span class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                      <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span> Online
                      <span class="inline-block w-2 h-2 rounded-full bg-gray-300 dark:bg-gray-500 ml-2"></span> Offline
                    </span>
                  </div>
                  <input v-model="tractorSearch" type="text" placeholder="Search by plate, brand, model, or IMEI..."
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 mb-3" />
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                      {{ filteredTractors.length }} tractor{{ filteredTractors.length !== 1 ? 's' : '' }} shown
                    </span>
                    <button type="button" @click="toggleAllTractors"
                      class="text-xs font-medium text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300">
                      {{ allFilteredSelected ? 'Deselect All' : 'Select All' }}
                    </button>
                  </div>
                  <div class="max-h-56 overflow-y-auto space-y-2">
                    <label v-for="t in filteredTractors" :key="t.id"
                      class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all"
                      :class="form.tractor_ids.includes(t.id)
                        ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 dark:border-emerald-400'
                        : 'border-gray-200 hover:border-gray-300 dark:border-gray-600 dark:hover:border-gray-500'">
                      <input type="checkbox" :value="t.id" v-model="form.tractor_ids" class="sr-only" />
                      <span class="relative shrink-0 flex items-center justify-center w-10 h-10 rounded-lg text-lg"
                        :class="t.is_online ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-gray-100 dark:bg-gray-700'">
                        <svg class="w-5 h-5" :class="t.is_online ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0H21M3.375 14.25h3.75L9 7.5h6l1.875 6.75h3.75" />
                        </svg>
                        <span class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-white dark:border-gray-800"
                          :class="t.is_online ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-500'"></span>
                      </span>
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                          <span class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ t.brand }} {{ t.model }}</span>
                          <span class="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="t.is_online
                              ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400'
                              : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'">
                            {{ t.is_online ? 'Online' : 'Offline' }}
                          </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ t.no_plate }} &middot; {{ t.imei || 'No IMEI' }}</p>
                      </div>
                      <div class="shrink-0">
                        <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                          :class="form.tractor_ids.includes(t.id)
                            ? 'bg-emerald-600 border-emerald-600'
                            : 'border-gray-300 dark:border-gray-500'">
                          <svg v-if="form.tractor_ids.includes(t.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                      </div>
                    </label>
                    <p v-if="!filteredTractors.length" class="text-center py-6 text-sm text-gray-400 dark:text-gray-500">
                      {{ tractorSearch ? 'No tractors match your search.' : 'No tractors available.' }}
                    </p>
                  </div>
                </div>

                <!-- Set TPS Responsibilities -->
                <div>
                  <div class="mb-3">
                    <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Set TPS Responsibilities</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                      {{ form.assign_all_tps
                        ? `All ${allTpsCount} TPS users will be assigned to this group when you save.`
                        : `${form.tps_user_ids.length} selected. These assignments control responsibility, not overall fleet visibility. TPS users already set to all tractors are managed from Users and will not appear here.` }}
                    </p>
                  </div>
                  <div class="space-y-4">
                    <label class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50/70 p-3 dark:border-emerald-800 dark:bg-emerald-900/20">
                      <input v-model="form.assign_all_tps" type="checkbox" value="1" :disabled="!allTpsCount"
                        class="mt-1 h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-600 dark:bg-gray-700" />
                      <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Assign this group to all TPS</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Every current TPS user will be linked to the tractors in this group. Turn this off to choose specific TPS users instead.</p>
                        <p v-if="!allTpsCount" class="mt-1 text-xs text-amber-600 dark:text-amber-400">No TPS users are available yet.</p>
                      </div>
                    </label>

                    <div v-if="form.assign_all_tps" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300">
                      All {{ allTpsCount }} TPS users will be assigned automatically when this group is saved.
                    </div>

                    <template v-else>
                      <input v-model="tpsSearch" type="text" placeholder="Search by name or email..."
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
                      <div class="max-h-48 overflow-y-auto space-y-2">
                        <label v-for="u in filteredTpsUsers" :key="u.id"
                          class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all"
                          :class="form.tps_user_ids.includes(u.id)
                            ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 dark:border-emerald-400'
                            : 'border-gray-200 hover:border-gray-300 dark:border-gray-600 dark:hover:border-gray-500'">
                          <input type="checkbox" :value="u.id" v-model="form.tps_user_ids" class="sr-only" />
                          <span class="shrink-0 flex items-center justify-center w-10 h-10 rounded-full text-sm font-semibold text-white" style="background-color: #007f3d;">
                            {{ u.name.charAt(0).toUpperCase() }}
                          </span>
                          <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ u.name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ u.email }}</p>
                          </div>
                          <div class="shrink-0">
                            <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                              :class="form.tps_user_ids.includes(u.id)
                                ? 'bg-emerald-600 border-emerald-600'
                                : 'border-gray-300 dark:border-gray-500'">
                              <svg v-if="form.tps_user_ids.includes(u.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                              </svg>
                            </div>
                          </div>
                        </label>
                        <p v-if="!filteredTpsUsers.length" class="text-center py-6 text-sm text-gray-400 dark:text-gray-500">
                          {{ tpsSearch ? 'No TPS users match your search.' : 'No TPS users available.' }}
                        </p>
                      </div>
                    </template>
                  </div>
                </div>
              </div>

              <!-- Form drawer footer (sticky) -->
              <div class="sticky bottom-0 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 px-6 py-4 flex justify-end gap-3">
                <button type="button" @click="closeDrawer"
                  class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">Cancel</button>
                <button type="submit" :disabled="form.processing"
                  class="inline-flex items-center gap-1.5 rounded-lg px-5 py-2.5 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                  style="background-color: #007f3d;"
                  @mouseenter="!form.processing && ($event.target.style.backgroundColor='#006631')"
                  @mouseleave="!form.processing && ($event.target.style.backgroundColor='#007f3d')">
                  <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                  {{ form.processing ? (drawerMode === 'edit' ? 'Updating...' : 'Creating...') : (drawerMode === 'edit' ? 'Update Group' : 'Create Group') }}
                </button>
              </div>
            </form>
          </template>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Modal from '@/Components/Modal.vue';
import { formatDate } from '@/utils/dateFormat';

const props = defineProps({
  groups: Object,
  tractors: Array,
  tpsUsers: Array,
  filters: Object,
});

// --- Search ---
const search = ref(props.filters?.search || '');
let timer;
const debouncedFilter = () => { clearTimeout(timer); timer = setTimeout(() => {
  router.get('/groups', { search: search.value || undefined }, { preserveState: true, replace: true });
}, 300); };

// --- Delete ---
const showDelete = ref(false);
const deleteTarget = ref(null);
const confirmDelete = (group) => { deleteTarget.value = group; showDelete.value = true; };
const performDelete = () => {
  router.delete(`/groups/${deleteTarget.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showDelete.value = false;
      deleteTarget.value = null;
    },
  });
};

// --- Drawer ---
const drawerOpen = ref(false);
const drawerMode = ref('create'); // 'create' | 'edit' | 'show'
const selectedGroup = ref(null);
const tractorSearch = ref('');
const tpsSearch = ref('');
const allTpsCount = computed(() => props.tpsUsers?.length || 0);
const hasAllTpsAssigned = (assignedUsers = []) => allTpsCount.value > 0 && assignedUsers.length === allTpsCount.value;

const form = useForm({
  name: '', area: '', description: '', is_active: true,
  tractor_ids: [], tps_user_ids: [], assign_all_tps: false,
});

const drawerTitle = computed(() => {
  if (drawerMode.value === 'show') return selectedGroup.value?.name || 'Group Details';
  if (drawerMode.value === 'edit') return 'Edit Group';
  return 'Create Group';
});

const drawerSubtitle = computed(() => {
  if (drawerMode.value === 'show') return 'View group details and assignments';
  if (drawerMode.value === 'edit') return 'Update group details and assignments';
  return 'Set up a new tractor group';
});

const filteredTractors = computed(() => {
  let list = props.tractors || [];
  if (tractorSearch.value) {
    const q = tractorSearch.value.toLowerCase();
    list = list.filter(t =>
      t.no_plate?.toLowerCase().includes(q) ||
      t.brand?.toLowerCase().includes(q) ||
      t.model?.toLowerCase().includes(q) ||
      t.imei?.toLowerCase().includes(q)
    );
  }
  return [...list].sort((a, b) => {
    const aSelected = form.tractor_ids.includes(a.id) ? 0 : 1;
    const bSelected = form.tractor_ids.includes(b.id) ? 0 : 1;
    return aSelected - bSelected;
  });
});

const filteredTpsUsers = computed(() => {
  if (!tpsSearch.value) return props.tpsUsers || [];
  const q = tpsSearch.value.toLowerCase();
  return (props.tpsUsers || []).filter(u =>
    u.name?.toLowerCase().includes(q) ||
    u.email?.toLowerCase().includes(q)
  );
});

const allFilteredSelected = computed(() => {
  if (!filteredTractors.value.length) return false;
  return filteredTractors.value.every(t => form.tractor_ids.includes(t.id));
});

const toggleAllTractors = () => {
  if (allFilteredSelected.value) {
    const filteredIds = new Set(filteredTractors.value.map(t => t.id));
    form.tractor_ids = form.tractor_ids.filter(id => !filteredIds.has(id));
  } else {
    const current = new Set(form.tractor_ids);
    for (const t of filteredTractors.value) {
      if (!current.has(t.id)) {
        form.tractor_ids.push(t.id);
      }
    }
  }
};

const resetForm = () => {
  form.reset();
  form.clearErrors();
  tractorSearch.value = '';
  tpsSearch.value = '';
};

const openCreateDrawer = () => {
  resetForm();
  selectedGroup.value = null;
  drawerMode.value = 'create';
  drawerOpen.value = true;
};

const openEditDrawer = (group) => {
  resetForm();
  selectedGroup.value = group;
  drawerMode.value = 'edit';
  form.name = group.name;
  form.area = group.area || '';
  form.description = group.description || '';
  form.is_active = group.is_active;
  form.tractor_ids = group.tractors?.map(t => t.id) || [];
  form.tps_user_ids = group.tps_users?.map(u => u.id) || [];
  form.assign_all_tps = hasAllTpsAssigned(group.tps_users || []);
  drawerOpen.value = true;
};

const openShowDrawer = (group) => {
  selectedGroup.value = group;
  drawerMode.value = 'show';
  drawerOpen.value = true;
};

const closeDrawer = () => {
  drawerOpen.value = false;
};

const submitForm = () => {
  if (drawerMode.value === 'edit' && selectedGroup.value) {
    form.put(`/groups/${selectedGroup.value.id}`, {
      onSuccess: () => closeDrawer(),
    });
  } else {
    form.post('/groups', {
      onSuccess: () => closeDrawer(),
    });
  }
};
</script>
