<template>
  <AppLayout>
    <Head title="Actual Maintenance" />

    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Actual Maintenance</h1>
      <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
        {{ maintenances.total || 0 }} records &middot; {{ recipientsData.length }} tractor recipients
      </p>
    </div>

    <!-- ═══ FILTERS ═══ -->
    <div class="bg-white rounded-xl border border-gray-200/60 shadow-sm p-5 mb-6 dark:bg-gray-800/60 dark:border-gray-700/50">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
          <label class="block mb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Search</label>
          <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input v-model="search" type="text" placeholder="Search tractor, description..." @input="debouncedFilter"
              class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 block w-full pl-10 pr-3 py-2.5 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500/20 dark:focus:border-indigo-500 transition-shadow" />
          </div>
        </div>
        <div>
          <label class="block mb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</label>
          <select v-model="statusFilter" @change="applyFilter"
            class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500/20 dark:focus:border-indigo-500 transition-shadow">
            <option value="">All Status</option>
            <option value="documentation">For Checking</option>
            <option value="scheduled">Scheduled</option>
            <option value="in_progress">Ongoing</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div>
          <label class="block mb-1.5 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tractor</label>
          <select v-model="tractorFilter" @change="applyFilter"
            class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-lg focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-indigo-500/20 dark:focus:border-indigo-500 transition-shadow">
            <option value="">All Tractors</option>
            <option v-for="t in tractors" :key="t.id" :value="t.id">{{ t.brand }} {{ t.model }} — {{ t.no_plate }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- ═══ MAINTENANCE TABLE ═══ -->
    <div class="mb-6">
      <DataTable>
        <template #head>
          <tr class="border-b border-gray-100 dark:border-gray-700/50">
            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 whitespace-nowrap">Tractor</th>
            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 whitespace-nowrap">Issue Type</th>
            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 whitespace-nowrap">Description</th>
            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 whitespace-nowrap">Status</th>
            <th scope="col" class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 whitespace-nowrap">Date</th>
            <th scope="col" class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 whitespace-nowrap">Actions</th>
          </tr>
        </template>
        <template #body>
          <tr v-for="m in maintenances.data" :key="m.id" class="group hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors border-b border-gray-100 dark:border-gray-700/50">
            <td class="px-5 py-3.5 max-w-[220px] whitespace-nowrap">
              <div class="flex items-center gap-3 min-w-0">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden group-hover:ring-2 group-hover:ring-indigo-300 dark:group-hover:ring-indigo-700 transition-all">
                  <img src="/images/tym-1.png" alt="Tractor" class="h-7 w-7 object-contain" />
                </div>
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-gray-900 dark:text-white truncate" :title="m.tractor?.brand">{{ m.tractor?.brand || '—' }}</p>
                  <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ m.tractor?.model }}{{ m.tractor?.model && m.tractor?.no_plate ? ' · ' : '' }}{{ m.tractor?.no_plate || '' }}</p>
                </div>
              </div>
            </td>
            <td class="px-5 py-3.5 whitespace-nowrap">
              <span :class="[
                m.is_damage
                  ? 'bg-red-50 text-red-700 ring-1 ring-red-200/50 dark:bg-red-900/20 dark:text-red-400 dark:ring-red-800/30'
                  : m.is_recipient
                    ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/50 dark:bg-amber-900/20 dark:text-amber-400 dark:ring-amber-800/30'
                    : 'bg-gray-50 text-gray-600 ring-1 ring-gray-200/50 dark:bg-gray-700/50 dark:text-gray-300 dark:ring-gray-600/30'
              ]" class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium">
                {{ m.issue_type?.name || '—' }}
              </span>
            </td>
            <td class="px-5 py-3.5 max-w-[220px] whitespace-nowrap">
              <p class="text-sm text-gray-700 dark:text-gray-300 truncate" :title="m.description">{{ m.description || '—' }}</p>
            </td>
            <td class="px-5 py-3.5 whitespace-nowrap">
              <span :class="statusBadgeClass(m.status)" class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold">
                <span :class="statusDotClass(m.status)" class="w-1.5 h-1.5 rounded-full"></span>
                {{ statusLabel(m.status) }}
              </span>
            </td>
            <td class="px-5 py-3.5 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">{{ formatDate(m.maintenance_date || m.created_at) }}</td>
            <td class="px-5 py-3.5 whitespace-nowrap">
              <div class="flex items-center justify-end gap-0.5">
                <button @click="openDetailModal(m)" class="p-2 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-indigo-900/20 transition-colors" title="View details">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
                <Link v-if="!m.is_recipient" :href="`/maintenance/${m.id}/edit`" class="p-2 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:text-blue-400 dark:hover:bg-blue-900/20 transition-colors" title="Edit">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </Link>
              </div>
            </td>
          </tr>

          <!-- Empty state -->
          <tr v-if="!maintenances.data?.length">
            <td colspan="6" class="px-5 py-16 text-center">
              <div class="flex flex-col items-center gap-3">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800">
                  <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No records found</p>
                <p class="text-xs text-gray-400 dark:text-gray-500">Maintenance records and tractor recipients will appear here.</p>
              </div>
            </td>
          </tr>
        </template>
      </DataTable>
    </div>

    <!-- Pagination -->
    <Pagination v-if="maintenances.links" :links="maintenances.links" class="mb-6" />

    <!-- ═══ CHARTS ROW: Geotagged Photos + Visited Tractors by Region ═══ -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

      <!-- Geotagged Maintenance Photos -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-4">Geotagged Maintenance Photos</h3>
        <div v-if="geotaggedPhotos.length" class="grid grid-cols-2 sm:grid-cols-3 gap-3 max-h-[420px] overflow-y-auto">
          <div
            v-for="(photo, idx) in geotaggedPhotos.slice(0, 12)"
            :key="idx"
            @click="openPhotoViewer(photo.recipient)"
            class="group relative rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 cursor-pointer hover:ring-2 hover:ring-indigo-400 transition-all">
            <img
              :src="photo.url"
              :alt="'Photo ' + (idx + 1)"
              class="w-full h-28 object-cover transition-transform group-hover:scale-105"
              @error="onPhotoError($event)"
              loading="lazy" />
            <!-- Fallback when image fails to load -->
            <div class="absolute inset-0 hidden flex items-center justify-center bg-gray-200 dark:bg-gray-800 photo-fallback">
              <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"/><circle cx="8.5" cy="8.5" r="1.5" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15l-5-5L5 21"/></svg>
            </div>
            <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/70 to-transparent p-2">
              <p class="text-[10px] text-white font-semibold truncate">{{ photo.recipient?.tractor_meta_name || 'Tractor' }}</p>
              <p class="text-[9px] text-gray-300 truncate">{{ photo.recipient?.barangay_name || '' }}, {{ photo.recipient?.city_name || '' }}</p>
            </div>
          </div>
        </div>
        <div v-else class="flex items-center justify-center h-48 text-sm text-gray-400">
          <div class="text-center">
            <svg class="w-10 h-10 mx-auto mb-2 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"/><circle cx="8.5" cy="8.5" r="1.5" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15l-5-5L5 21"/></svg>
            <p>No geotagged photos available.</p>
          </div>
        </div>
      </div>

      <!-- Visited Tractors by Region -->
      <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-4">Visited Tractors by Region</h3>
        <div v-if="regionChartSeries.length" class="h-[380px]">
          <apexchart type="bar" height="100%" :options="regionChartOptions" :series="regionChartSeries" />
        </div>
        <div v-else class="flex items-center justify-center h-48 text-sm text-gray-400">
          <p>Loading region data...</p>
        </div>
      </div>
    </div>

    <!-- ═══ MAIN ISSUES CHART (Donut + Horizontal Bar) ═══ -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 dark:bg-gray-800 dark:border-gray-700">
      <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-4">Main Issues</h3>
      <div v-if="issueSummary.length" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Donut Chart -->
        <div class="flex items-center justify-center">
          <div class="relative w-full max-w-[280px]">
            <apexchart type="donut" height="280" :options="issueDonutOptions" :series="issueDonutSeries" />
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
              <div class="text-center">
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ issueSummaryTotal }}</p>
                <p class="text-xs text-gray-400 dark:text-gray-500">Total</p>
              </div>
            </div>
          </div>
        </div>
        <!-- Legend + Horizontal Bar -->
        <div class="space-y-3">
          <div v-for="(item, idx) in issueSummary.slice(0, 8)" :key="item.name" class="flex items-center gap-3">
            <span class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: issueColors[idx % issueColors.length] }"></span>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between mb-0.5">
                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate">{{ item.name }}</span>
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 ml-2">{{ item.count }} {{ ((item.count / issueSummaryTotal) * 100).toFixed(1) }}%</span>
              </div>
              <div class="w-full h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all" :style="{ width: ((item.count / issueSummaryTotal) * 100) + '%', backgroundColor: issueColors[idx % issueColors.length] }"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="flex items-center justify-center h-32 text-sm text-gray-400">
        <p>No issue data available.</p>
      </div>
    </div>

    <!-- ═══ FULL-WIDTH PHOTO GALLERY MODAL ═══ -->
    <Teleport to="body">
      <div
        v-if="gallery.visible"
        class="fixed inset-0 z-50 flex flex-col bg-black/95"
        @keydown.left="galleryPrev"
        @keydown.right="galleryNext"
        @keydown.escape="closeGallery"
        tabindex="0"
        ref="galleryRef">
        <!-- Top bar -->
        <div class="flex items-center justify-between px-6 py-3 bg-black/60">
          <div class="min-w-0">
            <p class="text-sm font-semibold text-white truncate">{{ gallery.currentPhoto?.recipient?.tractor_meta_name || 'Tractor' }}</p>
            <p class="text-xs text-gray-400 truncate">
              {{ gallery.currentPhoto?.recipient?.barangay_name || '' }}{{ gallery.currentPhoto?.recipient?.city_name ? ', ' + gallery.currentPhoto.recipient.city_name : '' }}
              <span v-if="gallery.currentPhoto?.recipient?.park_latitude && gallery.currentPhoto.recipient.park_latitude !== '0'" class="ml-2">📍 {{ gallery.currentPhoto.recipient.park_latitude }}, {{ gallery.currentPhoto.recipient.park_longitude }}</span>
            </p>
          </div>
          <div class="flex items-center gap-4">
            <span class="text-sm text-gray-400 font-mono">{{ gallery.currentIndex + 1 }} / {{ gallery.photos.length }}</span>
            <button @click="closeGallery" class="p-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/10 transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
        </div>

        <!-- Main image area -->
        <div class="flex-1 flex items-center justify-center relative px-4">
          <!-- Prev button -->
          <button
            v-if="gallery.photos.length > 1"
            @click="galleryPrev"
            class="absolute left-4 z-10 p-3 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors backdrop-blur-sm">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
          </button>

          <!-- Image -->
          <div class="max-w-full max-h-full flex items-center justify-center">
            <img
              v-if="gallery.currentPhoto"
              :src="gallery.currentPhoto.url"
              :alt="'Photo ' + (gallery.currentIndex + 1)"
              class="max-w-full max-h-[75vh] object-contain rounded-lg shadow-2xl"
              @error="onGalleryPhotoError($event)"
            />
            <div v-if="gallery.currentPhoto?.error" class="text-center text-gray-400">
              <svg class="w-20 h-20 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" stroke-width="1.5"/><circle cx="8.5" cy="8.5" r="1.5" stroke-width="1.5"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 15l-5-5L5 21"/></svg>
              <p class="text-sm">Image unavailable</p>
            </div>
          </div>

          <!-- Next button -->
          <button
            v-if="gallery.photos.length > 1"
            @click="galleryNext"
            class="absolute right-4 z-10 p-3 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors backdrop-blur-sm">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </button>
        </div>

        <!-- Bottom thumbnail strip -->
        <div v-if="gallery.photos.length > 1" class="flex items-center gap-1.5 px-4 py-3 bg-black/60 overflow-x-auto">
          <div
            v-for="(photo, idx) in gallery.photos"
            :key="idx"
            @click="gallery.currentIndex = idx"
            :class="[
              'shrink-0 w-16 h-12 rounded-lg overflow-hidden border-2 cursor-pointer transition-all',
              idx === gallery.currentIndex ? 'border-indigo-400 opacity-100' : 'border-transparent opacity-50 hover:opacity-80'
            ]">
            <img :src="photo.url" class="w-full h-full object-cover" @error="e => e.target.style.display = 'none'" loading="lazy" />
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ═══ DETAIL SLIDE MODAL ═══ -->
    <Teleport to="body">
      <div v-if="detailModal.visible" class="fixed inset-0 z-50 flex">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeDetailModal"></div>
        <!-- Slide panel -->
        <div class="relative ml-auto w-full max-w-lg bg-white dark:bg-gray-800 shadow-2xl h-full overflow-y-auto animate-slide-in">
          <!-- Header with gradient -->
          <div class="sticky top-0 z-10 px-6 pt-6 pb-4 bg-gradient-to-b from-white via-white to-white/95 dark:from-gray-800 dark:via-gray-800 dark:to-gray-800/95">
            <div class="flex items-start justify-between gap-4">
              <div class="flex items-center gap-3 min-w-0">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10"/></svg>
                </div>
                <div class="min-w-0">
                  <h3 class="text-base font-bold text-gray-900 dark:text-white truncate">{{ detailModal.data?.tractor?.brand || 'Details' }}</h3>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ detailModal.data?.tractor?.model }}{{ detailModal.data?.tractor?.model && detailModal.data?.tractor?.no_plate ? ' · ' : '' }}{{ detailModal.data?.tractor?.no_plate }}</p>
                </div>
              </div>
              <button @click="closeDetailModal" class="shrink-0 p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
            <!-- Status badges -->
            <div class="flex items-center gap-2 mt-4" v-if="detailModal.data">
              <span :class="statusBadgeClass(detailModal.data.status)" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold">
                <span :class="statusDotClass(detailModal.data.status)" class="w-1.5 h-1.5 rounded-full"></span>
                {{ statusLabel(detailModal.data.status) }}
              </span>
              <span :class="[
                detailModal.data.is_damage ? 'bg-red-50 text-red-700 ring-1 ring-red-200/50 dark:bg-red-900/20 dark:text-red-400 dark:ring-red-800/30' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200/50 dark:bg-amber-900/20 dark:text-amber-400 dark:ring-amber-800/30'
              ]" class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium">
                {{ detailModal.data.issue_type?.name || '—' }}
              </span>
            </div>
          </div>

          <!-- Content -->
          <div class="px-6 pb-8 space-y-5" v-if="detailModal.data">
            <!-- Overview card -->
            <div class="rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700/50 p-4 space-y-3">
              <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Overview
              </div>
              <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ detailModal.data.description || '—' }}</p>
              <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400 pt-1">
                <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg> {{ formatDate(detailModal.data.maintenance_date || detailModal.data.created_at) }}</span>
                <span v-if="detailModal.data.performedBy?.name" class="flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg> {{ detailModal.data.performedBy.name }}</span>
              </div>
            </div>

            <!-- Full recipient data -->
            <template v-if="detailModal.recipient">
              <!-- Recipient card -->
              <div class="rounded-xl bg-white dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700/50 p-4 space-y-3">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  Recipient
                </div>
                <div class="flex items-center gap-3">
                  <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 text-sm font-bold">
                    {{ (detailModal.recipient.first_name || '?')[0] }}{{ (detailModal.recipient.last_name || '?')[0] }}
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ detailModal.recipient.first_name }} {{ detailModal.recipient.last_name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ detailModal.recipient.fca || '—' }}</p>
                  </div>
                </div>
                <div class="grid grid-cols-2 gap-3 text-xs">
                  <div><span class="text-gray-400 dark:text-gray-500">Contact</span><p class="text-gray-700 dark:text-gray-300 font-medium mt-0.5">{{ detailModal.recipient.mobile_number || detailModal.recipient.email || '—' }}</p></div>
                  <div><span class="text-gray-400 dark:text-gray-500">Received</span><p class="text-gray-700 dark:text-gray-300 font-medium mt-0.5">{{ detailModal.recipient.date_received || '—' }}</p></div>
                </div>
              </div>

              <!-- Location card -->
              <div class="rounded-xl bg-white dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700/50 p-4 space-y-3">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  Location
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300">{{ detailModal.recipient.park_address || '—' }}</p>
                <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                  <span>{{ detailModal.recipient.province_description || '—' }}</span>
                  <span class="text-gray-300 dark:text-gray-600">·</span>
                  <span>{{ detailModal.recipient.city_name || '—' }}</span>
                  <span class="text-gray-300 dark:text-gray-600">·</span>
                  <span>{{ detailModal.recipient.barangay_name || '—' }}</span>
                </div>
                <!-- Map -->
                <div v-if="detailModal.recipient.park_latitude && detailModal.recipient.park_latitude !== '0'" class="rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
                  <iframe
                    :src="`https://maps.google.com/maps?q=${Number(detailModal.recipient.park_latitude)},${Number(detailModal.recipient.park_longitude)}&z=16&output=embed`"
                    width="100%"
                    height="200"
                    class="border-0"
                    loading="lazy"
                    title="Tractor location">
                  </iframe>
                  <div class="flex items-center justify-between px-3 py-2 bg-gray-50 dark:bg-gray-900 text-xs">
                    <span class="text-gray-500 dark:text-gray-400">📍 {{ Number(detailModal.recipient.park_latitude).toFixed(6) }}, {{ Number(detailModal.recipient.park_longitude).toFixed(6) }}</span>
                    <a :href="`https://www.google.com/maps?q=${detailModal.recipient.park_latitude},${detailModal.recipient.park_longitude}`" target="_blank" class="text-indigo-600 hover:text-indigo-500 font-medium">Google Maps &rarr;</a>
                  </div>
                </div>
              </div>

              <!-- Machine details card -->
              <div class="rounded-xl bg-white dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700/50 p-4 space-y-3">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  Machine Details
                </div>
                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                  <div v-for="item in [
                    { label: 'S/N', value: detailModal.recipient.serial_number },
                    { label: 'Engine', value: detailModal.recipient.engine_number },
                    { label: 'Front Loader', value: detailModal.recipient.front_loader_serial_number },
                    { label: 'Rotavator', value: detailModal.recipient.rotavator_serial_number },
                    { label: 'Disk Plow', value: detailModal.recipient.disk_serial_number },
                    { label: 'DR No', value: detailModal.recipient.dr_no },
                  ]" :key="item.label" class="flex justify-between py-1.5 border-b border-gray-50 dark:border-gray-700/30 last:border-0">
                    <span class="text-gray-400 dark:text-gray-500">{{ item.label }}</span>
                    <span class="text-gray-700 dark:text-gray-300 font-medium text-right">{{ item.value || '—' }}</span>
                  </div>
                </div>
              </div>

              <!-- GPS card -->
              <div v-if="detailModal.recipient.gps_imei || detailModal.recipient.gps_sim_no" class="rounded-xl bg-white dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700/50 p-4 space-y-3">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                  GPS
                </div>
                <div class="grid grid-cols-3 gap-3 text-xs">
                  <div><span class="text-gray-400 dark:text-gray-500">IMEI</span><p class="text-gray-700 dark:text-gray-300 font-medium mt-0.5 font-mono">{{ detailModal.recipient.gps_imei || '—' }}</p></div>
                  <div><span class="text-gray-400 dark:text-gray-500">SIM</span><p class="text-gray-700 dark:text-gray-300 font-medium mt-0.5 font-mono">{{ detailModal.recipient.gps_sim_no || '—' }}</p></div>
                  <div><span class="text-gray-400 dark:text-gray-500">Mobile</span><p class="text-gray-700 dark:text-gray-300 font-medium mt-0.5">{{ detailModal.recipient.gps_mobile_no || '—' }}</p></div>
                </div>
              </div>

              <!-- TSR card -->
              <div class="rounded-xl bg-white dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700/50 p-4 space-y-3">
                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                  TSR Personnel
                </div>
                <div class="flex items-center gap-3">
                  <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 text-xs font-bold">
                    {{ (detailModal.recipient.tps_full_name || '?')[0] }}
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ detailModal.recipient.tps_full_name || '—' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ detailModal.recipient.tps_mobile }}{{ detailModal.recipient.tps_email ? ' · ' + detailModal.recipient.tps_email : '' }}</p>
                  </div>
                </div>
              </div>
            </template>
          </div>

          <!-- Empty state -->
          <div v-else class="px-6 py-12 text-center text-gray-400">
            <p>Loading details...</p>
          </div>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { ref, computed, reactive, nextTick, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import DataTable from '@/Components/DataTable.vue';
import { formatDate } from '@/utils/dateFormat';

const apexchart = VueApexCharts;

const props = defineProps({
  maintenances: Object,
  filters: Object,
  tractors: Array,
  tractorRecipients: Object,
});

// ── Maintenance Filters ──
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const tractorFilter = ref(props.filters?.tractor_id || '');

let timer;
const debouncedFilter = () => { clearTimeout(timer); timer = setTimeout(applyFilter, 300); };
const applyFilter = () => {
  router.get('/maintenance', {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
    tractor_id: tractorFilter.value || undefined,
  }, { preserveState: true, replace: true });
};

// ── Status helpers ──
function statusLabel(status) {
  const map = { documentation: 'For Checking', scheduled: 'Scheduled', in_progress: 'Ongoing', completed: 'Completed', cancelled: 'Cancelled' };
  return map[status] || status || '—';
}
function statusBadgeClass(status) {
  const map = {
    documentation: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    scheduled: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    in_progress: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
    completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    cancelled: 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
  };
  return map[status] || 'bg-gray-100 text-gray-500';
}
function statusDotClass(status) {
  const map = {
    documentation: 'bg-amber-500',
    scheduled: 'bg-blue-500',
    in_progress: 'bg-indigo-500',
    completed: 'bg-emerald-500',
    cancelled: 'bg-gray-400',
  };
  return map[status] || 'bg-gray-400';
}

// ── Tractor Recipients (local DB, synced via `php artisan app:fetch-tractor-recipients`) ──
const recipientsData = ref([]);

const DIGISAKA_BASE = 'http://digisaka.app';

function extractRecipients(source) {
  if (!source) return [];
  // Laravel paginator: { data: [...], current_page, ... }
  if (source.data && Array.isArray(source.data)) return source.data;
  // Plain collection/array
  if (Array.isArray(source)) return source;
  // Laravel Collection (has toArray or is iterable)
  if (typeof source === 'object' && source.length !== undefined) return Array.from(source);
  return [];
}

recipientsData.value = extractRecipients(props.tractorRecipients);

// ── Geotagged Photos ──
function getPhotos(recipient) {
  if (!recipient?.photos || recipient.photos === '') return [];
  return String(recipient.photos).trim().split(/\s+/).filter(Boolean).map(fn => ({
    filename: fn,
    url: `${DIGISAKA_BASE}/forms_images/${fn}`,
  }));
}

const geotaggedPhotos = computed(() => {
  const results = [];
  for (const r of recipientsData.value) {
    const photos = getPhotos(r);
    for (const p of photos) {
      results.push({ ...p, recipient: r });
    }
  }
  return results;
});

// ── Visited Tractors by Region Chart ──
const regionSummary = computed(() => {
  const map = {};
  for (const r of recipientsData.value) {
    const province = r.province_description || 'Unknown';
    map[province] = (map[province] || 0) + 1;
  }
  return Object.entries(map)
    .map(([name, count]) => ({ name, count }))
    .sort((a, b) => b.count - a.count);
});

const regionChartSeries = computed(() => [{
  name: 'Tractors',
  data: regionSummary.value.map(r => r.count),
}]);

const regionChartOptions = computed(() => ({
  chart: {
    type: 'bar',
    toolbar: { show: false },
    fontFamily: 'inherit',
    background: 'transparent',
  },
  plotOptions: {
    bar: {
      horizontal: true,
      barHeight: '60%',
      borderRadius: 6,
      distributed: true,
    },
  },
  colors: ['#6366f1', '#8b5cf6', '#a855f7', '#c084fc', '#e879f9', '#f472b6', '#fb923c', '#fbbf24'],
  dataLabels: { enabled: true, formatter: (val) => val, style: { fontSize: '12px', fontWeight: 'bold' } },
  xaxis: {
    categories: regionSummary.value.map(r => r.name),
    labels: { style: { fontSize: '11px' } },
  },
  yaxis: { labels: { style: { fontSize: '11px' } } },
  grid: { borderColor: 'rgba(156,163,175,0.15)' },
  legend: { show: false },
  tooltip: { y: { formatter: (val) => `${val} tractors` } },
  theme: { mode: 'light' },
}));

// ── Main Issues (Donut + Legend bars) — computed from ALL recipients, not just paginated page ──
const issueColors = ['#f59e0b', '#ef4444', '#3b82f6', '#10b981', '#8b5cf6', '#ec4899', '#6366f1', '#14b8a6', '#f97316', '#84cc16'];

const issueSummary = computed(() => {
  const map = {};
  // Aggregate damage_records from all tractor recipients
  for (const r of recipientsData.value) {
    const damages = r.damage_records || [];
    for (const d of damages) {
      const name = d.nature_of_problem || 'Unreported Issue';
      map[name] = (map[name] || 0) + 1;
    }
  }
  // Also include local maintenance issue types
  for (const m of (props.maintenances?.data || [])) {
    if (m.is_recipient) continue; // already covered above
    const name = m.issue_type?.name;
    if (!name || name === 'Uncategorized') continue;
    map[name] = (map[name] || 0) + 1;
  }
  return Object.entries(map)
    .map(([name, count]) => ({ name, count }))
    .sort((a, b) => b.count - a.count);
});

const issueSummaryTotal = computed(() => issueSummary.value.reduce((s, i) => s + i.count, 0));

const issueDonutSeries = computed(() => issueSummary.value.map(i => i.count));

const issueDonutOptions = computed(() => ({
  chart: {
    type: 'donut',
    fontFamily: 'inherit',
    background: 'transparent',
  },
  labels: issueSummary.value.map(i => i.name),
  colors: issueColors,
  plotOptions: {
    pie: {
      donut: {
        size: '70%',
        labels: {
          show: false,
        },
      },
    },
  },
  dataLabels: { enabled: false },
  legend: { show: false },
  stroke: { width: 2, colors: ['#fff'] },
  tooltip: {
    y: {
      formatter: (val) => {
        const total = issueSummaryTotal.value;
        const pct = total ? Math.round((val / total) * 100) : 0;
        return `${val} (${pct}%)`;
      },
    },
  },
  theme: { mode: 'light' },
}));

// ── Photo Gallery (full-width lightbox with prev/next) ──
const galleryRef = ref(null);
const gallery = reactive({
  visible: false,
  photos: [],
  currentIndex: 0,
  currentPhoto: computed(() => gallery.photos[gallery.currentIndex] || null),
});

function openPhotoViewer(recipient) {
  // Build the full list of all geotagged photos
  const allPhotos = geotaggedPhotos.value;
  // Find the index of the first photo belonging to this recipient
  const startIdx = allPhotos.findIndex(p => p.recipient?.source_id === recipient?.source_id);
  gallery.photos = allPhotos;
  gallery.currentIndex = startIdx >= 0 ? startIdx : 0;
  gallery.visible = true;
  nextTick(() => galleryRef.value?.focus());
}

function closeGallery() {
  gallery.visible = false;
  gallery.photos = [];
  gallery.currentIndex = 0;
}

function galleryPrev() {
  if (gallery.photos.length > 0) {
    gallery.currentIndex = (gallery.currentIndex - 1 + gallery.photos.length) % gallery.photos.length;
  }
}

function galleryNext() {
  if (gallery.photos.length > 0) {
    gallery.currentIndex = (gallery.currentIndex + 1) % gallery.photos.length;
  }
}

function onGalleryPhotoError(e) {
  if (gallery.currentPhoto) {
    gallery.currentPhoto.error = true;
  }
}

// Keyboard navigation
function onKeydown(e) {
  if (!gallery.visible) return;
  if (e.key === 'ArrowLeft') galleryPrev();
  else if (e.key === 'ArrowRight') galleryNext();
  else if (e.key === 'Escape') closeGallery();
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));

// ── Detail Slide Modal ──
const detailModal = reactive({
  visible: false,
  data: null,
  recipient: null,
});

function openDetailModal(row) {
  detailModal.data = row;
  // Look up full recipient data if this is a recipient row
  if (row.recipient_source_id) {
    detailModal.recipient = recipientsData.value.find(r => r.source_id === row.recipient_source_id) || null;
  } else {
    detailModal.recipient = null;
  }
  detailModal.visible = true;
}

function closeDetailModal() {
  detailModal.visible = false;
  detailModal.data = null;
  detailModal.recipient = null;
}

function onPhotoError(e) {
  e.target.style.display = 'none';
  const fallback = e.target.nextElementSibling;
  if (fallback && fallback.classList.contains('photo-fallback')) {
    fallback.classList.remove('hidden');
    fallback.classList.add('flex');
  }
}
</script>

<style scoped>
.animate-slide-in {
  animation: slideIn 0.25s ease-out;
}
@keyframes slideIn {
  from { transform: translateX(100%); }
  to { transform: translateX(0); }
}
</style>
