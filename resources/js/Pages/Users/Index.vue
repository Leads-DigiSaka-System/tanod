<template>
  <AppLayout>
    <Head title="Users" />

    <!-- Page header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Users</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage all registered users in the system.</p>
      </div>
      <button @click="openDrawer"
        class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 mt-4 sm:mt-0"
        style="background-color: #007f3d;"
        @mouseenter="$event.target.style.backgroundColor='#006631'"
        @mouseleave="$event.target.style.backgroundColor='#007f3d'">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        {{ activeTab === 'regular' ? 'Add User' : (activeTab === 'fca' ? 'Add FCA' : 'Add Farmer') }}
      </button>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
      <nav class="-mb-px flex gap-6">
        <button @click="switchTab('regular')"
          class="whitespace-nowrap pb-3 px-1 border-b-2 text-sm font-medium transition-colors"
          :class="activeTab === 'regular'
            ? 'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400'
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'">
          <span class="inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            Regular Users
          </span>
        </button>
        <button @click="switchTab('fca')"
          class="whitespace-nowrap pb-3 px-1 border-b-2 text-sm font-medium transition-colors"
          :class="activeTab === 'fca'
            ? 'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400'
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'">
          <span class="inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            FCA &amp; Farmers
          </span>
        </button>
      </nav>
    </div>

    <!-- ==================== REGULAR USERS TAB ==================== -->
    <template v-if="activeTab === 'regular'">
      <!-- Filters -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 mb-6 dark:bg-gray-800 dark:border-gray-700">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
          <input v-model="search" type="text" placeholder="Search name, email, phone..." @input="debouncedFilter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" />
          <select v-model="selectedRole" @change="applyFilter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">All Roles</option>
            <option v-for="r in regularRoles" :key="r.id" :value="r.name">{{ r.name }}</option>
          </select>
          <select v-model="activeFilter" @change="applyFilter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">All Status</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
          <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
              <th scope="col" class="px-6 py-3">Name</th>
              <th scope="col" class="px-6 py-3">Email</th>
              <th scope="col" class="px-6 py-3">Phone</th>
              <th scope="col" class="px-6 py-3">Role</th>
              <th scope="col" class="px-6 py-3">Status</th>
              <th scope="col" class="px-6 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in regularUsers.data" :key="user.id" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
              <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">{{ user.name }}</td>
              <td class="px-6 py-4">{{ user.email }}</td>
              <td class="px-6 py-4">{{ user.phone || '—' }}</td>
              <td class="px-6 py-4">
                <div class="flex flex-col gap-1">
                  <span class="capitalize">{{ user.roles?.[0]?.name || '—' }}</span>
                  <span v-if="user.roles?.[0]?.name === 'tps'"
                    class="inline-flex w-fit items-center rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="user.tps_assign_all_tractors
                      ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                      : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'">
                    {{ user.tps_assign_all_tractors ? 'All tractors' : 'Group scoped' }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4"><StatusBadge :status="user.is_active ? 'online' : 'offline'" :label="user.is_active ? 'Active' : 'Inactive'" /></td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-1">
                  <Link :href="`/users/${user.id}`" class="p-1.5 rounded-lg text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:text-gray-400 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20 transition-colors" title="View">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                  </Link>
                  <Link :href="`/users/${user.id}/edit`" class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:text-gray-400 dark:hover:text-blue-400 dark:hover:bg-blue-900/20 transition-colors" title="Edit">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                  </Link>
                  <Link :href="`/users/${user.id}/toggle-active`" method="post" as="button"
                    class="p-1.5 rounded-lg transition-colors"
                    :class="user.is_active ? 'text-gray-500 hover:text-red-600 hover:bg-red-50 dark:text-gray-400 dark:hover:text-red-400 dark:hover:bg-red-900/20' : 'text-gray-500 hover:text-green-600 hover:bg-green-50 dark:text-gray-400 dark:hover:text-green-400 dark:hover:bg-green-900/20'"
                    :title="user.is_active ? 'Deactivate' : 'Activate'">
                    <svg v-if="user.is_active" class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                    <svg v-else class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                  </Link>
                </div>
              </td>
            </tr>
            <tr v-if="!regularUsers.data?.length">
              <td colspan="6" class="px-6 py-16 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
                <p class="mt-4 text-sm font-medium text-gray-900 dark:text-white">No users found</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your search or filter criteria.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <Pagination :links="regularUsers.links" class="mt-6" />
    </template>

    <!-- ==================== FCA & FARMERS TAB ==================== -->
    <template v-if="activeTab === 'fca'">
      <!-- Filters -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 mb-6 dark:bg-gray-800 dark:border-gray-700">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <input v-model="fcaSearch" type="text" placeholder="Search FCA name, email, phone..." @input="debouncedFcaFilter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white" />
          <select v-model="fcaActiveFilter" @change="applyFcaFilter"
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option value="">All Status</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
          </select>
        </div>
      </div>

      <!-- FCA Accordion Cards -->
      <div class="space-y-4">
        <div v-for="fca in fcaUsers.data" :key="fca.id"
          class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
          <!-- FCA Header (clickable) -->
          <div class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
            @click="toggleFca(fca.id)">
            <div class="flex items-center gap-4">
              <div class="flex items-center justify-center w-10 h-10 rounded-full text-sm font-bold text-white" style="background-color: #007f3d;">
                {{ fca.name.charAt(0).toUpperCase() }}
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ fca.name }}</h3>
                  <StatusBadge :status="fca.is_active ? 'online' : 'offline'" :label="fca.is_active ? 'Active' : 'Inactive'" />
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ fca.email }} &middot; {{ fca.phone || 'No phone' }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                {{ fca.farmers?.length || 0 }} farmer{{ (fca.farmers?.length || 0) !== 1 ? 's' : '' }}
              </span>
              <div class="flex items-center gap-1">
                <Link :href="`/users/${fca.id}`" class="p-1.5 rounded-lg text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:text-gray-400 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20 transition-colors" title="View" @click.stop>
                  <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                </Link>
                <Link :href="`/users/${fca.id}/edit`" class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:text-gray-400 dark:hover:text-blue-400 dark:hover:bg-blue-900/20 transition-colors" title="Edit" @click.stop>
                  <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                </Link>
                <button @click.stop="openDrawerForFarmer(fca.id)" class="p-1.5 rounded-lg text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:text-gray-400 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20 transition-colors" title="Add Farmer">
                  <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" /></svg>
                </button>
              </div>
              <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': expandedFcas.includes(fca.id) }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </div>
          </div>

          <!-- Farmer Members (expandable) -->
          <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-[1000px] opacity-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="max-h-[1000px] opacity-100"
            leave-to-class="max-h-0 opacity-0">
            <div v-if="expandedFcas.includes(fca.id)" class="overflow-hidden">
              <div class="border-t border-gray-100 dark:border-gray-700">
                <table v-if="fca.farmers?.length" class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                  <thead class="text-xs text-gray-500 uppercase bg-gray-50/50 dark:bg-gray-700/50 dark:text-gray-400">
                    <tr>
                      <th scope="col" class="px-6 py-2.5 pl-16">Farmer Name</th>
                      <th scope="col" class="px-6 py-2.5">Email</th>
                      <th scope="col" class="px-6 py-2.5">Phone</th>
                      <th scope="col" class="px-6 py-2.5">Status</th>
                      <th scope="col" class="px-6 py-2.5 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="farmer in fca.farmers" :key="farmer.id" class="border-b border-gray-50 dark:border-gray-700/50 hover:bg-gray-50/50 dark:hover:bg-gray-700/30">
                      <td class="px-6 py-3 pl-16 font-medium text-gray-900 dark:text-white">{{ farmer.name }}</td>
                      <td class="px-6 py-3">{{ farmer.email }}</td>
                      <td class="px-6 py-3">{{ farmer.phone || '—' }}</td>
                      <td class="px-6 py-3"><StatusBadge :status="farmer.is_active ? 'online' : 'offline'" :label="farmer.is_active ? 'Active' : 'Inactive'" /></td>
                      <td class="px-6 py-3">
                        <div class="flex items-center justify-end gap-1">
                          <Link :href="`/users/${farmer.id}`" class="p-1.5 rounded-lg text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:text-gray-400 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20 transition-colors" title="View">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                          </Link>
                          <Link :href="`/users/${farmer.id}/edit`" class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:text-gray-400 dark:hover:text-blue-400 dark:hover:bg-blue-900/20 transition-colors" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                          </Link>
                          <Link :href="`/users/${farmer.id}/toggle-active`" method="post" as="button"
                            class="p-1.5 rounded-lg transition-colors"
                            :class="farmer.is_active ? 'text-gray-500 hover:text-red-600 hover:bg-red-50 dark:text-gray-400 dark:hover:text-red-400 dark:hover:bg-red-900/20' : 'text-gray-500 hover:text-green-600 hover:bg-green-50 dark:text-gray-400 dark:hover:text-green-400 dark:hover:bg-green-900/20'"
                            :title="farmer.is_active ? 'Deactivate' : 'Activate'">
                            <svg v-if="farmer.is_active" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                          </Link>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
                <div v-else class="px-6 py-8 text-center">
                  <svg class="mx-auto h-8 w-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                  </svg>
                  <p class="mt-2 text-sm text-gray-400 dark:text-gray-500">No farmers assigned yet.</p>
                  <button @click="openDrawerForFarmer(fca.id)" class="mt-2 text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300">
                    + Add a farmer member
                  </button>
                </div>
              </div>
            </div>
          </Transition>
        </div>

        <!-- Empty FCA state -->
        <div v-if="!fcaUsers.data?.length" class="bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 px-6 py-16 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <p class="mt-4 text-sm font-medium text-gray-900 dark:text-white">No FCAs found</p>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your search or create a new FCA.</p>
        </div>
      </div>
      <Pagination :links="fcaUsers.links" class="mt-6" />
    </template>

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

          <!-- Drawer body (scrollable) -->
          <form @submit.prevent="submitForm" class="flex-1 overflow-y-auto">
            <div class="p-6 space-y-5">
              <!-- Full Name -->
              <div>
                <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Full Name <span class="text-red-500">*</span></label>
                <input v-model="form.name" type="text" placeholder="John Doe"
                  class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
              </div>

              <!-- Email + Phone row -->
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Email <span class="text-red-500">*</span></label>
                  <input v-model="form.email" type="email" placeholder="john@example.com"
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
                  <p v-if="form.errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.email }}</p>
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Phone <span class="text-red-500">*</span></label>
                  <input v-model="form.phone" type="text" placeholder="09xxxxxxxxx"
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
                  <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.phone }}</p>
                </div>
              </div>

              <!-- Password row -->
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Password <span class="text-red-500">*</span></label>
                  <input v-model="form.password" type="password" placeholder="Min 8 characters"
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
                  <p v-if="form.errors.password" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.password }}</p>
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password <span class="text-red-500">*</span></label>
                  <input v-model="form.password_confirmation" type="password" placeholder="Re-enter password"
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
                </div>
              </div>

              <!-- Role + Gender row (regular tab) / FCA + Gender row (farmer mode) -->
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div v-if="drawerMode === 'farmer'">
                  <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">FCA</label>
                  <select v-model="form.fca_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Select FCA</option>
                    <option v-for="f in fcaList" :key="f.id" :value="f.id">{{ f.name }}</option>
                  </select>
                  <p v-if="form.errors.fca_id" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.fca_id }}</p>
                </div>
                <div v-else>
                  <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Role <span class="text-red-500">*</span></label>
                  <select v-model="form.role"
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Select Role</option>
                    <option v-for="r in drawerRoles" :key="r.id" :value="r.name">{{ r.name }}</option>
                  </select>
                  <p v-if="form.errors.role" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.role }}</p>
                </div>
                <div>
                  <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Gender</label>
                  <select v-model="form.gender"
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">Select</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                  </select>
                  <p v-if="form.errors.gender" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.gender }}</p>
                </div>
              </div>

              <!-- TPS Access Mode -->
              <div v-if="drawerMode === 'regular' && form.role === 'tps'" class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
                <label class="flex items-start gap-3">
                  <input v-model="form.tps_assign_all_tractors" type="checkbox"
                    class="mt-1 h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700" />
                  <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Assign this TPS to all tractors</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">When enabled, this TPS user can see the full tractor fleet in the mobile app. Leave it off to manage tractor visibility through Group responsibilities.</p>
                  </div>
                </label>
                <p v-if="form.errors.tps_assign_all_tractors" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.tps_assign_all_tractors }}</p>
              </div>

              <!-- Profile Photo -->
              <div>
                <label class="block mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-300">Profile Photo</label>
                <div class="flex items-center gap-4">
                  <div class="shrink-0 flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                    <img v-if="photoPreview" :src="photoPreview" class="w-12 h-12 object-cover rounded-full" />
                    <svg v-else class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                  </div>
                  <label class="cursor-pointer rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Choose photo
                    <input type="file" accept="image/*" class="sr-only" @change="handlePhoto" />
                  </label>
                </div>
                <p v-if="form.errors.profile_photo" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.profile_photo }}</p>
              </div>
            </div>

            <!-- Drawer footer (sticky) -->
            <div class="sticky bottom-0 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 px-6 py-4 flex justify-end gap-3">
              <button type="button" @click="closeDrawer"
                class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">Cancel</button>
              <button type="submit" :disabled="form.processing"
                class="inline-flex items-center gap-1.5 rounded-lg px-5 py-2.5 text-sm font-medium text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                style="background-color: #007f3d;"
                @mouseenter="!form.processing && ($event.target.style.backgroundColor='#006631')"
                @mouseleave="!form.processing && ($event.target.style.backgroundColor='#007f3d')">
                <svg v-if="form.processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                {{ form.processing ? 'Creating...' : drawerSubmitLabel }}
              </button>
            </div>
          </form>
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

const props = defineProps({
  regularUsers: Object,
  fcaUsers: Object,
  filters: Object,
  roles: Array,
  regularRoles: Array,
  fcaList: Array,
});

// --- Tabs ---
const activeTab = ref(props.filters?.tab || 'regular');
const switchTab = (tab) => {
  activeTab.value = tab;
  router.get('/users', { tab }, { preserveState: true, replace: true });
};

// --- Regular Users Filters ---
const search = ref(props.filters?.search || '');
const selectedRole = ref(props.filters?.role || '');
const activeFilter = ref(props.filters?.active ?? '');

let timer;
const debouncedFilter = () => { clearTimeout(timer); timer = setTimeout(applyFilter, 300); };
const applyFilter = () => {
  router.get('/users', {
    tab: 'regular',
    search: search.value || undefined,
    role: selectedRole.value || undefined,
    active: activeFilter.value !== '' ? activeFilter.value : undefined,
  }, { preserveState: true, replace: true });
};

// --- FCA Filters ---
const fcaSearch = ref(props.filters?.fca_search || '');
const fcaActiveFilter = ref(props.filters?.fca_active ?? '');

let fcaTimer;
const debouncedFcaFilter = () => { clearTimeout(fcaTimer); fcaTimer = setTimeout(applyFcaFilter, 300); };
const applyFcaFilter = () => {
  router.get('/users', {
    tab: 'fca',
    fca_search: fcaSearch.value || undefined,
    fca_active: fcaActiveFilter.value !== '' ? fcaActiveFilter.value : undefined,
  }, { preserveState: true, replace: true });
};

// --- FCA Accordion ---
const expandedFcas = ref([]);
const toggleFca = (id) => {
  const idx = expandedFcas.value.indexOf(id);
  if (idx === -1) {
    expandedFcas.value.push(id);
  } else {
    expandedFcas.value.splice(idx, 1);
  }
};

// --- Drawer ---
const drawerOpen = ref(false);
const drawerMode = ref('regular'); // 'regular' | 'fca' | 'farmer'
const photoPreview = ref(null);

const form = useForm({
  name: '', email: '', password: '', password_confirmation: '',
  phone: '', gender: '', role: '', tps_assign_all_tractors: false, profile_photo: null, fca_id: '',
});

const drawerTitle = computed(() => {
  if (drawerMode.value === 'farmer') return 'Add Farmer Member';
  if (drawerMode.value === 'fca') return 'Add New FCA';
  return 'Add New User';
});

const drawerSubtitle = computed(() => {
  if (drawerMode.value === 'farmer') return 'Create a farmer account under an FCA.';
  if (drawerMode.value === 'fca') return 'Create a new FCA account.';
  return 'Fill in the details to create a user account.';
});

const drawerSubmitLabel = computed(() => {
  if (drawerMode.value === 'farmer') return 'Create Farmer';
  if (drawerMode.value === 'fca') return 'Create FCA';
  return 'Create User';
});

const drawerRoles = computed(() => {
  if (activeTab.value === 'regular') return props.regularRoles;
  return props.roles.filter(r => r.name === 'fca');
});

const openDrawer = () => {
  if (activeTab.value === 'fca') {
    drawerMode.value = 'fca';
    form.role = 'fca';
  } else {
    drawerMode.value = 'regular';
    form.role = '';
  }
  form.fca_id = '';
  drawerOpen.value = true;
};

const openDrawerForFarmer = (fcaId) => {
  drawerMode.value = 'farmer';
  form.role = 'farmer';
  form.fca_id = fcaId;
  drawerOpen.value = true;
};

const closeDrawer = () => {
  drawerOpen.value = false;
  form.reset();
  form.clearErrors();
  photoPreview.value = null;
};

const handlePhoto = (e) => {
  const file = e.target.files[0];
  if (file) {
    form.profile_photo = file;
    photoPreview.value = URL.createObjectURL(file);
  }
};

const submitForm = () => {
  form.post('/users', {
    forceFormData: true,
    onSuccess: () => closeDrawer(),
  });
};
</script>
