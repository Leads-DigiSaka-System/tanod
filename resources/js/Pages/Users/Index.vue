<template>
  <AppLayout>
    <Head title="Users" />

    <!-- Page header -->
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Users</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage all registered users in the system.</p>
      </div>
      <button v-if="activeTab !== 'permissions'" @click="openDrawer"
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
      <nav class="-mb-px flex gap-6 overflow-x-auto">
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
        <button @click="switchTab('permissions')"
          class="whitespace-nowrap pb-3 px-1 border-b-2 text-sm font-medium transition-colors"
          :class="activeTab === 'permissions'
            ? 'border-emerald-600 text-emerald-600 dark:border-emerald-400 dark:text-emerald-400'
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'">
          <span class="inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 1.105-.895 2-2 2s-2-.895-2-2 .895-2 2-2 2 .895 2 2zm0 0h8m-4-4v8M5 4h6a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" /></svg>
            Roles and Permissions
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
            <option v-for="r in regularRoles" :key="r.id" :value="r.name">{{ formatRoleName(r) }}</option>
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
                  <span>{{ formatRoleName(user.roles?.[0]) || '—' }}</span>
                  <span v-if="user.roles?.[0]?.name === 'tsr'"
                    class="inline-flex w-fit items-center rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="user.tsr_assign_all_tractors
                      ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                      : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'">
                    {{ user.tsr_assign_all_tractors ? 'All tractors' : 'Group scoped' }}
                  </span>
                </div>
              </td>
              <td class="px-6 py-4"><StatusBadge :status="user.is_active ? 'online' : 'offline'" :label="user.is_active ? 'Active' : 'Inactive'" /></td>
              <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-1">
                  <button type="button" class="p-1.5 rounded-lg text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:text-gray-400 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20 transition-colors" title="View" @click="openViewDrawer(user)">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                  </button>
                  <button type="button" class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:text-gray-400 dark:hover:text-blue-400 dark:hover:bg-blue-900/20 transition-colors" title="Edit" @click="openEditDrawer(user)">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                  </button>
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
                <button type="button" class="p-1.5 rounded-lg text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:text-gray-400 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20 transition-colors" title="View" @click.stop="openViewDrawer(fca)">
                  <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                </button>
                <button type="button" class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:text-gray-400 dark:hover:text-blue-400 dark:hover:bg-blue-900/20 transition-colors" title="Edit" @click.stop="openEditDrawer(fca)">
                  <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                </button>
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
                          <button type="button" class="p-1.5 rounded-lg text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:text-gray-400 dark:hover:text-emerald-400 dark:hover:bg-emerald-900/20 transition-colors" title="View" @click="openViewDrawer(farmer)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                          </button>
                          <button type="button" class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:text-gray-400 dark:hover:text-blue-400 dark:hover:bg-blue-900/20 transition-colors" title="Edit" @click="openEditDrawer(farmer)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                          </button>
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

    <!-- ==================== ROLES & PERMISSIONS TAB ==================== -->
    <template v-if="activeTab === 'permissions'">
      <div class="mb-6 overflow-hidden rounded-2xl bg-linear-to-r from-emerald-700 to-emerald-500 p-6 text-white shadow-lg">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-100">Access control</p>
            <h2 class="mt-2 text-xl font-bold">Role access across every admin menu</h2>
            <p class="mt-1 max-w-2xl text-sm text-emerald-50">Choose a role, then grant menu access and the actions users in that role can perform.</p>
          </div>
          <div class="flex gap-3">
            <div class="rounded-xl bg-white/15 px-4 py-3 backdrop-blur-sm">
              <p class="text-2xl font-bold">{{ rolePermissions.length }}</p>
              <p class="text-xs text-emerald-50">Roles</p>
            </div>
            <div class="rounded-xl bg-white/15 px-4 py-3 backdrop-blur-sm">
              <p class="text-2xl font-bold">{{ permissionGroups.length }}</p>
              <p class="text-xs text-emerald-50">Access areas</p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
        <aside class="h-fit rounded-2xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800 xl:sticky xl:top-20">
          <div class="px-3 pb-3 pt-2">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Select role</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Permissions apply to every user assigned to the role.</p>
          </div>
          <div class="space-y-1.5">
            <button v-for="role in rolePermissions" :key="role.id" type="button" @click="selectPermissionRole(role)"
              class="w-full rounded-xl border px-3 py-3 text-left transition-all"
              :class="selectedPermissionRole?.id === role.id
                ? 'border-emerald-300 bg-emerald-50 shadow-sm dark:border-emerald-700 dark:bg-emerald-900/25'
                : 'border-transparent hover:border-gray-200 hover:bg-gray-50 dark:hover:border-gray-700 dark:hover:bg-gray-700/50'">
              <div class="flex items-center justify-between gap-2">
                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatRoleName(role) }}</span>
                <svg v-if="role.is_protected" class="h-4 w-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
              </div>
              <div class="mt-1 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>{{ role.users_count }} user{{ role.users_count === 1 ? '' : 's' }}</span>
                <span>{{ role.permissions.length }} permissions</span>
              </div>
            </button>
          </div>
        </aside>

        <section v-if="selectedPermissionRole" class="min-w-0">
          <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ formatRoleName(selectedPermissionRole) }}</h3>
                <span v-if="selectedPermissionRole.is_protected" class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">Protected</span>
              </div>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ grantedPermissionCount }} of {{ totalPermissionCount }} permissions granted</p>
            </div>
            <button v-if="canManageRolePermissions && !selectedPermissionRole.is_protected" type="button" @click="saveRolePermissions"
              :disabled="permissionForm.processing || !permissionsChanged"
              class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
              <svg v-if="permissionForm.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
              {{ permissionForm.processing ? 'Saving...' : (permissionsChanged ? 'Save changes' : 'Saved') }}
            </button>
          </div>

          <div v-if="selectedPermissionRole.is_protected" class="mb-4 flex gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.667 1.73-3L13.73 4c-.77-1.333-2.69-1.333-3.46 0L3.34 16c-.77 1.333.19 3 1.73 3z" /></svg>
            Super Admin always has full access and cannot be modified, preventing administrators from being locked out.
          </div>
          <div v-else-if="!canManageRolePermissions" class="mb-4 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-200">
            You have read-only access. Only a Super Admin can change role permissions.
          </div>

          <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
            <article v-for="group in permissionGroups" :key="group.key"
              class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition dark:border-gray-700 dark:bg-gray-800"
              :class="groupHasAccess(group) ? 'ring-1 ring-emerald-200 dark:ring-emerald-800' : ''">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <h4 class="font-semibold text-gray-900 dark:text-white">{{ group.label }}</h4>
                  <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">{{ group.description }}</p>
                </div>
                <button type="button" role="switch" :aria-checked="groupHasAccess(group)" @click="togglePermissionGroup(group)"
                  :disabled="!canEditSelectedRole"
                  class="relative mt-0.5 h-6 w-11 shrink-0 rounded-full transition-colors disabled:cursor-not-allowed disabled:opacity-60"
                  :class="groupHasAccess(group) ? 'bg-emerald-600' : 'bg-gray-200 dark:bg-gray-600'">
                  <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform duration-200"
                    :class="groupHasAccess(group) ? 'translate-x-5' : 'translate-x-0'"></span>
                </button>
              </div>

              <div class="mt-4 flex flex-wrap gap-2">
                <label v-for="permission in group.permissions" :key="permission.name"
                  class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition"
                  :class="hasPermission(permission.name)
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/25 dark:text-emerald-300'
                    : 'border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-700/50 dark:text-gray-400'">
                  <input type="checkbox" class="h-3.5 w-3.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                    :checked="hasPermission(permission.name)" :disabled="!canEditSelectedRole"
                    @change="togglePermission(group, permission.name)" />
                  {{ permission.label }}
                </label>
              </div>
            </article>
          </div>
        </section>
      </div>
    </template>

    <UserSlideOver
      :show="drawerOpen"
      :action="drawerAction"
      :create-mode="drawerMode"
      :user="selectedUser"
      :roles="roles"
      :regular-roles="regularRoles"
      :fca-list="fcaList"
      :default-fca-id="defaultFcaId"
      @close="closeDrawer"
      @edit="openEditDrawer" />
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import UserSlideOver from '@/Pages/Users/Partials/UserSlideOver.vue';
import { formatRoleName } from '@/utils/roleFormat';

const props = defineProps({
  regularUsers: Object,
  fcaUsers: Object,
  filters: Object,
  roles: Array,
  regularRoles: Array,
  fcaList: Array,
  rolePermissions: Array,
  permissionGroups: Array,
  canManageRolePermissions: Boolean,
});

// --- Tabs ---
const activeTab = ref(props.filters?.tab || 'regular');
const switchTab = (tab) => {
  activeTab.value = tab;
  router.get('/users', { tab }, { preserveState: true, replace: true });
};

// --- Roles and permissions ---
const selectedPermissionRole = ref(props.rolePermissions?.[0] || null);
const permissionForm = useForm({ permissions: selectedPermissionRole.value?.permissions || [] });
const savedPermissions = ref([...(selectedPermissionRole.value?.permissions || [])]);

const totalPermissionCount = computed(() => props.permissionGroups.reduce((total, group) => total + group.permissions.length, 0));
const grantedPermissionCount = computed(() => permissionForm.permissions.length);
const canEditSelectedRole = computed(() => props.canManageRolePermissions && !selectedPermissionRole.value?.is_protected);
const permissionsChanged = computed(() => {
  const current = [...permissionForm.permissions].sort();
  const saved = [...savedPermissions.value].sort();
  return current.length !== saved.length || current.some((permission, index) => permission !== saved[index]);
});

const selectPermissionRole = (role) => {
  selectedPermissionRole.value = role;
  permissionForm.permissions = [...role.permissions];
  permissionForm.clearErrors();
  savedPermissions.value = [...role.permissions];
};

const hasPermission = (permission) => permissionForm.permissions.includes(permission);
const groupHasAccess = (group) => hasPermission(group.permissions[0].name);

const togglePermissionGroup = (group) => {
  if (!canEditSelectedRole.value) return;
  const groupPermissions = group.permissions.map(permission => permission.name);
  if (groupHasAccess(group)) {
    permissionForm.permissions = permissionForm.permissions.filter(permission => !groupPermissions.includes(permission));
  } else {
    permissionForm.permissions = [...new Set([...permissionForm.permissions, ...groupPermissions])];
  }
};

const togglePermission = (group, permission) => {
  if (!canEditSelectedRole.value) return;
  const accessPermission = group.permissions[0].name;
  const groupPermissions = group.permissions.map(item => item.name);

  if (hasPermission(permission)) {
    permissionForm.permissions = permission === accessPermission
      ? permissionForm.permissions.filter(item => !groupPermissions.includes(item))
      : permissionForm.permissions.filter(item => item !== permission);
    return;
  }

  permissionForm.permissions = [...new Set([...permissionForm.permissions, accessPermission, permission])];
};

const saveRolePermissions = () => {
  if (!canEditSelectedRole.value || !permissionsChanged.value) return;
  permissionForm.put(`/users/roles/${selectedPermissionRole.value.id}/permissions`, {
    preserveScroll: true,
    onSuccess: () => {
      savedPermissions.value = [...permissionForm.permissions];
      selectedPermissionRole.value.permissions = [...permissionForm.permissions];
    },
  });
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
  const index = expandedFcas.value.indexOf(id);
  if (index === -1) {
    expandedFcas.value.push(id);
  } else {
    expandedFcas.value.splice(index, 1);
  }
};

// --- Drawer ---
const drawerOpen = ref(false);
const drawerAction = ref('create');
const drawerMode = ref('regular');
const selectedUser = ref(null);
const defaultFcaId = ref('');

const openDrawer = () => {
  drawerAction.value = 'create';
  selectedUser.value = null;
  defaultFcaId.value = '';
  if (activeTab.value === 'fca') {
    drawerMode.value = 'fca';
  } else {
    drawerMode.value = 'regular';
  }
  drawerOpen.value = true;
};

const openDrawerForFarmer = (fcaId) => {
  drawerAction.value = 'create';
  drawerMode.value = 'farmer';
  selectedUser.value = null;
  defaultFcaId.value = fcaId;
  drawerOpen.value = true;
};

const openViewDrawer = (user) => {
  drawerAction.value = 'view';
  selectedUser.value = user;
  drawerOpen.value = true;
};

const openEditDrawer = (user) => {
  drawerAction.value = 'edit';
  selectedUser.value = user;
  drawerOpen.value = true;
};

const closeDrawer = () => {
  drawerOpen.value = false;
};
</script>
