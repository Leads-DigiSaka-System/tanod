<template>
  <AppLayout>
    <Head title="Tractor Details" />

    <!-- Page Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <Link href="/tractors" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
          <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
          Back to Tractors
        </Link>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ tractor.no_plate }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tractor details and associated information</p>
      </div>
      <div class="flex gap-3">
        <Link v-if="$page.props.auth.user.permissions.includes('tractors.edit')" :href="`/tractors/${tractor.id}/edit`"
          class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800 inline-flex items-center">
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          Edit Tractor
        </Link>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- Main Info -->
      <div class="lg:col-span-2 space-y-6">

        <!-- Tractor Information Card -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center mb-5">
            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900 mr-3">
              <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Tractor Information</h2>
          </div>
          <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Plate Number</dt>
              <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ tractor.no_plate }}</dd>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">IMEI</dt>
              <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ tractor.imei }}</dd>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Brand / Model</dt>
              <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ tractor.brand }} {{ tractor.model }}</dd>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Engine No</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ tractor.engine_no }}</dd>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Chassis No</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ tractor.chassis_no || '—' }}</dd>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">ID No</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ tractor.id_no }}</dd>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Fuel Consumption</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ tractor.fuel_consumption }} L/hr</dd>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Total Distance</dt>
              <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ (tractor.total_distance || 0).toLocaleString() }} km</dd>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Running Hours</dt>
              <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ (tractor.total_running_hours || 0).toLocaleString() }} hrs</dd>
            </div>
          </dl>
        </div>

        <!-- Implements Card -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center mb-5">
            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-orange-100 dark:bg-orange-900 mr-3">
              <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Implements</h2>
          </div>
          <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Front Loader SN</dt>
              <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ tractor.front_loader_sn || '—' }}</dd>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Rotavator SN</dt>
              <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ tractor.rotary_tiller_sn || '—' }}</dd>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Disc Plow SN</dt>
              <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ tractor.disc_plow_sn || '—' }}</dd>
            </div>
          </dl>
        </div>

        <!-- Additional Info -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center mb-5">
            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 mr-3">
              <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Assignment & Installation</h2>
          </div>
          <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Groups</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ tractor.groups?.map(g => g.name).join(', ') || '—' }}</dd>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Assigned To</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ tractor.assignee?.name || '—' }}</dd>
            </div>
            <div class="p-3 bg-gray-50 rounded-lg sm:col-span-2 dark:bg-gray-700">
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Installation</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ tractor.installation_time }} — {{ tractor.installation_address }}</dd>
            </div>
          </dl>
        </div>

        <!-- Images Card -->
        <div v-if="tractor.images?.length" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center mb-5">
            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900 mr-3">
              <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Images</h2>
          </div>
          <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
            <img v-for="img in tractor.images" :key="img.id" :src="`/storage/${img.path}`" class="rounded-lg object-cover h-48 w-full border border-gray-200 dark:border-gray-600" />
          </div>
        </div>

        <!-- Recent Maintenance Card -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center p-6 pb-4">
            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-yellow-100 dark:bg-yellow-900 mr-3">
              <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Maintenance</h2>
          </div>
          <div v-if="tractor.maintenances?.length" class="px-6 pb-6">
            <div class="space-y-3">
              <div v-for="m in tractor.maintenances" :key="m.id" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-100 dark:bg-gray-700 dark:border-gray-600">
                <div>
                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{ m.title }}</p>
                  <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ m.created_at }}</p>
                </div>
                <StatusBadge :status="m.status" />
              </div>
            </div>
          </div>
          <div v-else class="px-6 pb-6">
            <div class="flex flex-col items-center justify-center py-8">
              <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
              <p class="text-sm text-gray-500 dark:text-gray-400">No maintenance records yet</p>
            </div>
          </div>
        </div>

        <!-- Ticket / Repair History Card -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center p-6 pb-4">
            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900 mr-3">
              <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div class="flex-1">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Ticket / Repair History</h2>
              <p class="text-xs text-gray-500 dark:text-gray-400">Service requests and repairs logged for this tractor</p>
            </div>
            <span v-if="tractor.tickets?.length" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300">
              {{ tractor.tickets.length }} tickets
            </span>
          </div>
          <div v-if="tractor.tickets?.length" class="px-6 pb-6">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
              <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                  <tr>
                    <th scope="col" class="px-4 py-3">Date</th>
                    <th scope="col" class="px-4 py-3">Subject</th>
                    <th scope="col" class="px-4 py-3">Category</th>
                    <th scope="col" class="px-4 py-3">Priority</th>
                    <th scope="col" class="px-4 py-3">Assigned To</th>
                    <th scope="col" class="px-4 py-3">Charge</th>
                    <th scope="col" class="px-4 py-3 text-right">Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="t in tractor.tickets" :key="t.id"
                    class="bg-white border-b hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-600">
                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600 dark:text-gray-400">
                      {{ t.created_at ? formatDate(t.created_at) : '—' }}
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white max-w-[200px] truncate" :title="t.subject">
                      {{ t.subject }}
                    </td>
                    <td class="px-4 py-3">
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                        :class="categoryClass(t.category)">
                        {{ t.category || '—' }}
                      </span>
                    </td>
                    <td class="px-4 py-3">
                      <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                        :class="priorityClass(t.priority)">
                        {{ t.priority || '—' }}
                      </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ t.assignee?.name || '—' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap font-mono text-xs">{{ t.service_charge ? '₱' + Number(t.service_charge).toLocaleString() : '—' }}</td>
                    <td class="px-4 py-3 text-right"><StatusBadge :status="t.status" /></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div v-else class="px-6 pb-6">
            <div class="flex flex-col items-center justify-center py-8">
              <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
              <p class="text-sm text-gray-500 dark:text-gray-400">No ticket or repair history yet</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">

        <!-- Device Status Card -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center mb-5">
            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900 mr-3">
              <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Device Status</h2>
          </div>
          <div v-if="tractor.device">
            <div class="mb-4">
              <StatusBadge :status="isOnline ? 'online' : 'offline'" />
            </div>
            <dl class="space-y-3">
              <div class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">IMEI</dt>
                <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ tractor.device.imei }}</dd>
              </div>
              <div v-if="tractor.device.latest_location" class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Coordinates</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ tractor.device.latest_location.lat }}, {{ tractor.device.latest_location.lng }}</dd>
              </div>
              <div v-if="tractor.device.latest_location" class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Speed</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ tractor.device.latest_location.speed }} km/h</dd>
              </div>
              <div v-if="tractor.device.latest_location" class="p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-400">Last Heartbeat</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ tractor.device.latest_location.heartbeat_at }}</dd>
              </div>
            </dl>
          </div>
          <div v-else class="flex flex-col items-center justify-center py-6">
            <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
            <p class="text-sm text-gray-500 dark:text-gray-400">No device assigned</p>
          </div>
        </div>

        <!-- Recent Bookings Card -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
          <div class="flex items-center mb-5">
            <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900 mr-3">
              <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Bookings</h2>
          </div>
          <div v-if="tractor.bookings?.length" class="space-y-3">
            <div v-for="b in tractor.bookings" :key="b.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg dark:bg-gray-700">
              <span class="text-sm text-gray-600 dark:text-gray-300">{{ b.booking_date }}</span>
              <StatusBadge :status="b.status" />
            </div>
          </div>
          <div v-else class="flex flex-col items-center justify-center py-6">
            <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <p class="text-sm text-gray-500 dark:text-gray-400">No bookings yet</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/utils/dateFormat';

const props = defineProps({ tractor: Object });

const isOnline = computed(() => {
  if (!props.tractor.device?.latest_location?.heartbeat_at) return false;
  return (Date.now() - new Date(props.tractor.device.latest_location.heartbeat_at).getTime()) < 600000;
});

const categoryClass = (cat) => {
  const map = {
    'repair': 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    'maintenance': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
    'warranty': 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    'installation': 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    'others': 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
  };
  return map[cat?.toLowerCase()] || map['others'];
};

const priorityClass = (pri) => {
  const map = {
    'critical': 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    'high': 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
    'medium': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
    'low': 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
  };
  return map[pri?.toLowerCase()] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
};
</script>
