<template>
  <AppLayout>
    <Head :title="`Ticket #${ticket.id}`" />

    <!-- Breadcrumb -->
    <Link href="/tickets" class="inline-flex items-center gap-1 text-xs font-medium text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors mb-5">
      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m0 0l7 7m-7-7l7-7"/></svg>
      Tickets
    </Link>

    <!-- 2-Column Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
      <!-- LEFT COLUMN (2/3) -->
      <div class="xl:col-span-2 space-y-5">
        <!-- Ticket Details -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/40 dark:border-gray-700/30 shadow-sm overflow-hidden">
        <!-- Header Row -->
        <div class="px-6 py-4 border-b border-gray-50 dark:border-gray-700/30">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2 mb-1">
                <span class="font-mono text-[11px] text-gray-400 tabular-nums">TAN-{{ String(ticket.id).padStart(4, '0') }}</span>
                <span class="text-gray-300 dark:text-gray-600">·</span>
                <span class="text-[11px] text-gray-400">{{ formatDate(ticket.created_at) }}</span>
              </div>
              <h1 class="text-lg font-bold text-gray-900 dark:text-white leading-tight">{{ ticket.subject }}</h1>
              <div class="flex items-center gap-2 mt-2 flex-wrap">
                <StatusBadge :status="ticket.status" />
                <span v-if="ticket.category" class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ ticket.category }}</span>
                <span v-if="ticket.fca_name" class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ ticket.fca_name }}</span>
                <span v-if="ticket.tractor" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                  {{ ticket.tractor.no_plate }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Description -->
        <div class="px-6 py-4 border-b border-gray-50 dark:border-gray-700/20">
          <p class="text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap leading-relaxed">{{ ticket.description || 'No description provided.' }}</p>
        </div>

        <!-- Evidence Filmstrip -->
        <div v-if="ticket.nameplate_photo_url || ticket.dashboard_photo_url || ticket.photo_url || (ticket.damage_photos && ticket.damage_photos.length)" class="px-6 py-4">
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
              <div class="flex h-5 w-5 items-center justify-center rounded-md bg-gray-100 dark:bg-gray-700">
                <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              </div>
              <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Evidence</span>
            </div>
            <span class="text-[10px] text-gray-400 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded-full tabular-nums">{{ (ticket.nameplate_photo_url ? 1 : 0) + (ticket.dashboard_photo_url ? 1 : 0) + (ticket.photo_url ? 1 : 0) + (ticket.damage_photos?.length || 0) }}</span>
          </div>
          <div class="flex gap-2.5 overflow-x-auto pb-1">
            <div v-if="ticket.nameplate_photo_url" @click="previewImage = { url: ticket.nameplate_photo_url, label: 'Nameplate' }" class="group shrink-0 relative w-40 h-56 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-900 border border-gray-200/50 dark:border-gray-700/50 hover:shadow-md transition-shadow cursor-zoom-in">
              <img :src="ticket.nameplate_photo_url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
              <div class="absolute inset-x-0 bottom-0 bg-linear-to-t from-black/60 to-transparent pt-6 pb-1.5 px-2"><span class="text-[10px] text-white font-medium">Nameplate</span></div>
            </div>
            <div v-if="ticket.dashboard_photo_url" @click="previewImage = { url: ticket.dashboard_photo_url, label: 'Dashboard' }" class="group shrink-0 relative w-40 h-56 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-900 border border-gray-200/50 dark:border-gray-700/50 hover:shadow-md transition-shadow cursor-zoom-in">
              <img :src="ticket.dashboard_photo_url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
              <div class="absolute inset-x-0 bottom-0 bg-linear-to-t from-black/60 to-transparent pt-6 pb-1.5 px-2"><span class="text-[10px] text-white font-medium">Dashboard</span></div>
            </div>
            <div v-if="ticket.photo_url" @click="previewImage = { url: ticket.photo_url, label: 'Issue' }" class="group shrink-0 relative w-40 h-56 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-900 border border-gray-200/50 dark:border-gray-700/50 hover:shadow-md transition-shadow cursor-zoom-in">
              <img :src="ticket.photo_url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
              <div class="absolute inset-x-0 bottom-0 bg-linear-to-t from-black/60 to-transparent pt-6 pb-1.5 px-2"><span class="text-[10px] text-white font-medium">Issue</span></div>
            </div>
            <div v-for="dp in ticket.damage_photos" :key="dp.id" @click="previewImage = { url: dp.photo_url, label: 'Damage #' + (dp.sort_order + 1) }" class="group shrink-0 relative w-40 h-56 rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-900 border border-gray-200/50 dark:border-gray-700/50 hover:shadow-md transition-shadow cursor-zoom-in">
              <img :src="dp.photo_url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
              <div class="absolute inset-x-0 bottom-0 bg-linear-to-t from-black/60 to-transparent pt-6 pb-1.5 px-2"><span class="text-[10px] text-white font-medium">Damage #{{ dp.sort_order + 1 }}</span></div>
            </div>
          </div>
        </div>
      </div>

        <!-- Resolution Card -->
        <div v-if="ticket.status === 'resolved' || ticket.status === 'closed'" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/40 dark:border-gray-700/30 shadow-sm overflow-hidden">
      <!-- Resolution Header -->
      <div class="px-6 py-4 border-b border-gray-50 dark:border-gray-700/30 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-900/30">
            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Resolution</h2>
        </div>
        <div class="flex items-center gap-2 text-xs text-gray-400">
          <span v-if="ticket.resolver">by {{ ticket.resolver.name }}</span>
          <span v-if="ticket.resolver && ticket.resolved_at" class="text-gray-300">·</span>
          <span v-if="ticket.resolved_at">{{ formatDate(ticket.resolved_at) }}</span>
        </div>
      </div>

      <!-- Resolution Details Content -->
      <div class="px-6 py-5">
        <!-- Parsed Notes Grid -->
        <div v-if="parsedNotes" class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-5">
          <!-- Findings -->
          <div v-if="parsedNotes.Findings" class="rounded-xl bg-amber-50/50 dark:bg-amber-900/10 p-3 border border-amber-100 dark:border-amber-800/20 hover:shadow-sm transition-shadow duration-200">
            <p class="text-[10px] font-semibold text-amber-600 uppercase tracking-wider mb-1">🔍 Findings</p>
            <p class="text-sm text-gray-700 dark:text-gray-200 leading-relaxed">{{ parsedNotes.Findings }}</p>
          </div>
          <!-- Job Done -->
          <div v-if="parsedNotes['Job Done']" class="rounded-xl bg-blue-50/50 dark:bg-blue-900/10 p-3 border border-blue-100 dark:border-blue-800/20 hover:shadow-sm transition-shadow duration-200">
            <p class="text-[10px] font-semibold text-blue-600 uppercase tracking-wider mb-1">🔧 Job Done</p>
            <p class="text-sm text-gray-700 dark:text-gray-200 leading-relaxed">{{ parsedNotes['Job Done'] }}</p>
          </div>
          <!-- Recommendation -->
          <div v-if="parsedNotes.Recommendation" class="rounded-xl bg-purple-50/50 dark:bg-purple-900/10 p-3 border border-purple-100 dark:border-purple-800/20 hover:shadow-sm transition-shadow duration-200">
            <p class="text-[10px] font-semibold text-purple-600 uppercase tracking-wider mb-1">💡 Recommendation</p>
            <p class="text-sm text-gray-700 dark:text-gray-200 leading-relaxed">{{ parsedNotes.Recommendation }}</p>
          </div>
          <!-- Remarks -->
          <div v-if="parsedNotes.Remarks" class="rounded-xl bg-gray-50 dark:bg-gray-700/30 p-3 border border-gray-100 dark:border-gray-700/20 hover:shadow-sm transition-shadow duration-200">
            <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1">📝 Remarks</p>
            <p class="text-sm text-gray-700 dark:text-gray-200 leading-relaxed">{{ parsedNotes.Remarks }}</p>
          </div>

          <!-- Fallback: raw text if nothing parsed -->
          <div v-if="!parsedNotes.Findings && !parsedNotes['Job Done'] && !parsedNotes.Recommendation && !parsedNotes.Remarks && ticket.resolution_notes" class="col-span-2 rounded-xl bg-gray-50 dark:bg-gray-700/30 p-3 border border-gray-100 dark:border-gray-700/20">
            <p class="text-sm text-gray-700 dark:text-gray-200 leading-relaxed whitespace-pre-wrap">{{ ticket.resolution_notes }}</p>
          </div>
        </div>

        <!-- Billing Statement -->
        <div v-if="ticket.service_charge || ticket.down_payment || ticket.installments || (ticket.tractor_parts && ticket.tractor_parts.length)" class="mt-6 bg-white dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
          <!-- Billing Header -->
          <div class="px-4 py-3 bg-gray-50/80 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700/30">
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
              <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Billing Statement</p>
            </div>
          </div>

          <div class="p-4 space-y-3">
            <!-- Parts line items -->
            <template v-if="ticket.tractor_parts && ticket.tractor_parts.length">
              <div class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Parts &amp; Materials</div>
              <div v-for="p in ticket.tractor_parts" :key="p.id" class="flex justify-between items-center text-sm pl-2">
                <span class="text-gray-600 dark:text-gray-300">
                  {{ p.name }}
                  <span class="text-gray-400 text-xs ml-1">×{{ p.quantity }}</span>
                </span>
                <span class="font-medium text-gray-800 dark:text-gray-100 tabular-nums">₱{{ Number(p.amount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</span>
              </div>
              <div class="flex justify-between items-center text-sm pl-2 border-t border-dashed border-gray-200 dark:border-gray-600 pt-2">
                <span class="text-gray-500">Parts Subtotal</span>
                <span class="font-semibold text-gray-800 dark:text-gray-100 tabular-nums">₱{{ Number(ticket.tractor_parts.reduce((sum, p) => sum + parseFloat(p.amount), 0)).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</span>
              </div>
              <div class="border-t border-gray-100 dark:border-gray-700/30"></div>
            </template>

            <!-- Service Charge -->
            <div v-if="ticket.service_charge" class="flex justify-between items-center text-sm">
              <span class="text-gray-500">Service / Labor Charge</span>
              <span class="font-medium text-gray-800 dark:text-gray-100 tabular-nums">₱{{ Number(ticket.service_charge).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</span>
            </div>

            <!-- TOTAL -->
            <div class="flex justify-between items-center text-sm pt-2 border-t-2 border-gray-200 dark:border-gray-600">
              <span class="font-semibold text-gray-900 dark:text-white">TOTAL AMOUNT</span>
              <span class="font-bold text-base text-gray-900 dark:text-white tabular-nums">
                ₱{{ Number(totalAmount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
              </span>
            </div>

            <!-- Down Payment -->
            <div v-if="ticket.down_payment" class="flex justify-between items-center text-sm pl-2">
              <span class="text-gray-500">Less: Down Payment</span>
              <span class="font-medium text-red-600 dark:text-red-400 tabular-nums">− ₱{{ Number(ticket.down_payment).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}</span>
            </div>

            <!-- BALANCE -->
            <div v-if="ticket.down_payment" class="flex justify-between items-center text-sm pt-2 border-t border-dashed border-gray-200 dark:border-gray-600">
              <span class="font-semibold text-gray-900 dark:text-white">REMAINING BALANCE</span>
              <span class="font-bold text-gray-900 dark:text-white tabular-nums">
                ₱{{ Number(balance).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
              </span>
            </div>

            <!-- Installments -->
            <div v-if="ticket.installments && ticket.down_payment && balance > 0" class="mt-3 p-3 bg-amber-50/50 dark:bg-amber-900/10 rounded-lg border border-amber-100 dark:border-amber-800/20">
              <div class="flex items-center gap-2 mb-2">
                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span class="text-[11px] font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Payment Plan</span>
              </div>
              <div class="flex justify-between text-xs text-amber-700 dark:text-amber-300 mb-1">
                <span>{{ ticket.installments }} monthly installment{{ ticket.installments > 1 ? 's' : '' }}</span>
                <span>of</span>
              </div>
              <div class="text-center">
                <span class="text-lg font-bold text-amber-700 dark:text-amber-300 tabular-nums">
                  ₱{{ Number(balance / ticket.installments).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) }}
                </span>
                <span class="text-xs text-amber-600 dark:text-amber-400 ml-1">/ month</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Photo / Document Grid -->
        <div v-if="ticket.resolution_photo_url || (ticket.dr_photo_urls && ticket.dr_photo_urls.length)" class="mt-6">
          <!-- 2-Column Layout: Service Report (1/3) + DR/SI/CR (2/3) -->
          <div v-if="ticket.resolution_photo_url && ticket.dr_photo_urls && ticket.dr_photo_urls.length" class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <!-- Service Report Photo (1/3) -->
            <div>
              <div class="flex items-center gap-2 mb-3">
                <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-900/30">
                  <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Service Report</p>
              </div>
              <div @click="previewImage = { url: ticket.resolution_photo_url, label: 'Service Report' }"
                class="group relative rounded-2xl overflow-hidden bg-gray-50 dark:bg-gray-900 border-2 border-gray-100 dark:border-gray-700 hover:border-emerald-300 dark:hover:border-emerald-700 hover:shadow-lg transition-all duration-200 cursor-zoom-in aspect-[4/3]">
                <img :src="ticket.resolution_photo_url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors flex items-center justify-center">
                  <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-2 px-4 py-2 rounded-full bg-black/50 text-white text-xs font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                    Click to enlarge
                  </div>
                </div>
              </div>
            </div>

            <!-- DR/SI/CR Documents (2/3) -->
            <div class="md:col-span-2">
              <div class="flex items-center gap-2 mb-3">
                <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/30">
                  <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">DR / SI / CR Documents</p>
                <span class="text-[10px] text-gray-400 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded-full">{{ ticket.dr_photo_urls.length }}</span>
              </div>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <div v-for="(url, i) in ticket.dr_photo_urls" :key="i"
                  @click="previewImage = { url: url, label: 'DR/SI/CR #' + (i + 1) }"
                  class="group relative aspect-[3/4] rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-900 border-2 border-gray-100 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-700 hover:shadow-md transition-all duration-200 cursor-zoom-in">
                  <img :src="url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                  <div class="absolute inset-x-0 bottom-0 bg-linear-to-t from-black/70 via-black/30 to-transparent pt-8 pb-2 px-2.5">
                    <span class="text-[11px] text-white font-semibold">Doc #{{ i + 1 }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Fallback: Only Service Report (full width) -->
          <div v-else-if="ticket.resolution_photo_url && !(ticket.dr_photo_urls && ticket.dr_photo_urls.length)">
            <div class="flex items-center gap-2 mb-3">
              <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-900/30">
                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              </div>
              <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Service Report</p>
            </div>
            <div @click="previewImage = { url: ticket.resolution_photo_url, label: 'Service Report' }"
              class="group relative rounded-2xl overflow-hidden bg-gray-50 dark:bg-gray-900 border-2 border-gray-100 dark:border-gray-700 hover:border-emerald-300 dark:hover:border-emerald-700 hover:shadow-lg transition-all duration-200 cursor-zoom-in aspect-[16/9]">
              <img :src="ticket.resolution_photo_url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
              <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors flex items-center justify-center">
                <div class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-2 px-4 py-2 rounded-full bg-black/50 text-white text-xs font-medium">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                  Click to enlarge
                </div>
              </div>
            </div>
          </div>

          <!-- Fallback: Only DR/SI/CR (full width) -->
          <div v-else-if="ticket.dr_photo_urls && ticket.dr_photo_urls.length && !ticket.resolution_photo_url">
            <div class="flex items-center gap-2 mb-3">
              <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/30">
                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              </div>
              <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">DR / SI / CR Documents</p>
              <span class="text-[10px] text-gray-400 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded-full">{{ ticket.dr_photo_urls.length }}</span>
            </div>
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
              <div v-for="(url, i) in ticket.dr_photo_urls" :key="i"
                @click="previewImage = { url: url, label: 'DR/SI/CR #' + (i + 1) }"
                class="group relative aspect-[3/4] rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-900 border-2 border-gray-100 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-700 hover:shadow-md transition-all duration-200 cursor-zoom-in">
                <img :src="url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                <div class="absolute inset-x-0 bottom-0 bg-linear-to-t from-black/70 via-black/30 to-transparent pt-8 pb-2 px-2.5">
                  <span class="text-[11px] text-white font-semibold">Doc #{{ i + 1 }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Raw notes fallback -->
        <div v-if="!parsedNotes && ticket.resolution_notes" class="mt-4 text-sm text-gray-700 dark:text-gray-200 whitespace-pre-wrap leading-relaxed">
          {{ ticket.resolution_notes }}
        </div>
      </div>
    </div>

        <!-- Discussion Card -->
        <div v-if="ticket.status !== 'resolved' && ticket.status !== 'closed'" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/40 dark:border-gray-700/30 shadow-sm overflow-hidden">
          <div class="px-5 py-3 flex items-center gap-2 border-b border-gray-100 dark:border-gray-700/40">
            <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-sky-50 dark:bg-sky-900/30">
              <svg class="w-3.5 h-3.5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
            </div>
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Discussion</h3>
            <span class="text-[10px] font-medium text-gray-400 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded-full">{{ localComments.length }}</span>
            <span v-if="isListeningRealtime" class="ml-auto flex items-center gap-1 text-[10px] font-medium text-emerald-600 dark:text-emerald-400">
              <span class="relative flex h-1.5 w-1.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span></span>Live
            </span>
          </div>
          <div ref="commentsContainer" class="max-h-80 px-4 py-3 overflow-y-auto space-y-3 bg-gray-50/30 dark:bg-gray-900/10">
            <div v-if="!localComments.length" class="text-center py-10">
              <p class="text-xs text-gray-400">No messages yet</p>
            </div>
            <template v-for="(comment, index) in localComments" :key="comment.id">
              <div v-if="index === 0 || !isSameDay(comment.created_at, localComments[index - 1].created_at)" class="flex items-center gap-2 py-1.5">
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                <span class="text-[10px] text-gray-400 font-medium uppercase">{{ formatDayLabel(comment.created_at) }}</span>
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
              </div>
              <div class="flex" :class="comment.user?.id === currentUserId ? 'justify-end' : 'justify-start'">
                <div v-if="comment.user?.id !== currentUserId" class="h-6 w-6 rounded-full flex items-center justify-center shrink-0 mr-1.5 mt-auto bg-indigo-100 dark:bg-indigo-900/50">
                  <span class="text-[9px] font-bold text-indigo-600 dark:text-indigo-300">{{ comment.user?.name?.charAt(0) || '?' }}</span>
                </div>
                <div class="max-w-[80%]">
                  <p v-if="comment.user?.id !== currentUserId" class="text-[10px] font-semibold text-gray-500 dark:text-gray-400 mb-0.5 ml-1">{{ comment.user?.name || 'Unknown' }}</p>
                  <div class="px-3 py-2 rounded-2xl shadow-sm text-sm leading-relaxed"
                    :class="comment.user?.id === currentUserId ? 'bg-emerald-600 text-white rounded-br-md' : 'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-600 rounded-bl-md'">
                    <div v-if="comment.attachment_url && isImage(comment.attachment_url)" @click="previewImage = { url: comment.attachment_url, label: 'Attachment' }" class="block mb-1.5 -mx-1 -mt-1 cursor-zoom-in">
                      <img :src="comment.attachment_url" class="max-w-full max-h-48 rounded-xl object-cover hover:opacity-90" />
                    </div>
                    <a v-else-if="comment.attachment_url" :href="comment.attachment_url" target="_blank" class="flex items-center gap-1.5 mb-1.5 px-2 py-1.5 rounded-lg text-xs" :class="comment.user?.id === currentUserId ? 'bg-emerald-700/50' : 'bg-gray-100 dark:bg-gray-600'">
                      <svg class="w-4 h-4 shrink-0" :class="comment.user?.id === currentUserId ? 'text-emerald-200' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                      Attachment
                    </a>
                    <p v-if="comment.body" class="whitespace-pre-wrap wrap-break-word">{{ comment.body }}</p>
                    <span class="block text-[9px] mt-1 text-right" :class="comment.user?.id === currentUserId ? 'text-emerald-200' : 'text-gray-400'">{{ formatTime(comment.created_at) }}</span>
                  </div>
                </div>
              </div>
            </template>
          </div>
          <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-700/40">
            <div v-if="typingUser" class="text-[10px] text-gray-400 mb-1.5 italic">{{ typingUser.role }} {{ typingUser.name }} is typing...</div>
            <div v-if="attachmentPreview" class="mb-1.5 relative inline-block">
              <img v-if="attachmentPreview.isImage" :src="attachmentPreview.url" class="h-16 rounded-lg border border-gray-200 dark:border-gray-600 object-cover" />
              <button @click="removeAttachment" class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full bg-red-500 text-white flex items-center justify-center text-[10px] hover:bg-red-600 shadow">&times;</button>
            </div>
            <form @submit.prevent="addComment" class="flex items-end gap-1.5">
              <input ref="fileInput" type="file" accept="image/*,.pdf" class="hidden" @change="onFileSelected" />
              <button type="button" @click="$refs.fileInput.click()" class="p-2 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-gray-100 dark:hover:bg-gray-700" title="Attach">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
              </button>
              <input :value="commentForm.body" @input="onCommentInput" type="text" placeholder="Write a message..."
                class="flex-1 bg-gray-50 border border-gray-200 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 px-3 py-2 dark:bg-gray-900 dark:border-gray-600 dark:text-white" @keydown.enter.exact.prevent="addComment" />
              <button type="submit" :disabled="commentForm.processing || (!commentForm.body.trim() && !selectedFile)"
                class="p-2 text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-40 transition-colors shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
              </button>
            </form>
          </div>
        </div>

      </div> <!-- close left col -->

      <!-- RIGHT COLUMN (1/3) -->
      <div class="space-y-5 xl:sticky xl:top-4 xl:self-start">
        <!-- Details Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/40 dark:border-gray-700/30 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-700/30">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Details</h3>
          </div>
          <div class="px-5 py-4 space-y-3">
            <div class="flex items-center gap-3">
              <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/30 shrink-0">
                <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
              </div>
              <div class="min-w-0">
                <p class="text-[10px] text-gray-400 uppercase tracking-wider">Reporter</p>
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ ticket.submitter?.name || '—' }}</p>
              </div>
            </div>
            <div v-if="ticket.tractor" class="flex items-center gap-3">
              <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-900/30 shrink-0">
                <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" /></svg>
              </div>
              <div class="min-w-0">
                <p class="text-[10px] text-gray-400 uppercase tracking-wider">Tractor</p>
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ ticket.tractor.no_plate }} · {{ ticket.tractor.brand }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-50 dark:bg-purple-900/30 shrink-0">
                <svg class="w-3.5 h-3.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2" /></svg>
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-[10px] text-gray-400 uppercase tracking-wider">Assigned TPS</p>
                <template v-if="ticket.status === 'resolved' || ticket.status === 'closed'">
                  <span class="text-xs text-gray-400 italic">Locked</span>
                </template>
                <template v-else-if="ticket.assignees?.length">
                  <div class="flex flex-wrap gap-1 mt-0.5">
                    <span v-for="a in ticket.assignees" :key="a.id" class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-medium bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-300">{{ a.name }}</span>
                  </div>
                </template>
                <span v-else class="text-xs text-gray-400">Unassigned</span>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-gray-50 dark:bg-gray-700 shrink-0">
                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              </div>
              <div>
                <p class="text-[10px] text-gray-400 uppercase tracking-wider">Created</p>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDate(ticket.created_at) }}</p>
              </div>
            </div>
            <div v-if="(ticket.status === 'resolved' || ticket.status === 'closed') && ticket.resolver" class="flex items-center gap-3">
              <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-900/30 shrink-0">
                <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              </div>
              <div class="min-w-0">
                <p class="text-[10px] text-gray-400 uppercase tracking-wider">Resolved By</p>
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ ticket.resolver.name }}</p>
              </div>
            </div>
            <div v-if="ticket.resolved_at" class="flex items-center gap-3">
              <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-900/30 shrink-0">
                <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
              </div>
              <div>
                <p class="text-[10px] text-gray-400 uppercase tracking-wider">Resolved At</p>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDate(ticket.resolved_at) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- PMS Checklist Card -->
        <div v-if="ticket.pms_checklist && ticket.pms_checklist.length" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/40 dark:border-gray-700/30 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-700/30">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">PMS Checklist</h3>
          </div>
          <div class="px-5 py-4">
          <div class="flex items-center justify-between mb-2">
            <span class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">Progress</span>
            <span class="text-[11px] font-bold" :class="donePmsCount === ticket.pms_checklist.length ? 'text-emerald-600' : 'text-amber-600'">{{ donePmsCount }}/{{ ticket.pms_checklist.length }}</span>
          </div>
          <div class="mb-2 h-1 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
            <div class="h-full rounded-full transition-all" :class="donePmsCount === ticket.pms_checklist.length ? 'bg-emerald-500' : 'bg-amber-500'" :style="{ width: (donePmsCount / ticket.pms_checklist.length * 100) + '%' }"></div>
          </div>
          <div class="space-y-1">
            <div v-for="(item, i) in ticket.pms_checklist" :key="i" class="flex items-center gap-2">
              <div class="shrink-0 flex h-3.5 w-3.5 items-center justify-center rounded-full border" :class="(item.done === true || item.done === '1' || item.done === 1) ? 'bg-emerald-500 border-emerald-500' : 'border-gray-300 dark:border-gray-600'">
                <svg v-if="item.done === true || item.done === '1' || item.done === 1" class="w-2 h-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
              </div>
              <span class="text-xs flex-1 truncate" :class="(item.done === true || item.done === '1' || item.done === 1) ? 'text-gray-700 dark:text-gray-200 font-medium' : 'text-gray-400'">{{ item.name }}</span>
            </div>
          </div>
        </div>
        </div>

        <!-- Actions Card -->
        <div v-if="canManage && ticket.status !== 'resolved' && ticket.status !== 'closed'" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/40 dark:border-gray-700/30 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-700/30">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Actions</h3>
          </div>
          <div class="px-5 py-4">
          <div class="space-y-3">
            <div>
              <label class="block text-[10px] font-semibold text-gray-500 uppercase mb-1.5">Status</label>
              <div class="flex gap-2">
                <select v-model="statusForm.status" class="flex-1 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-sm rounded-lg px-2.5 py-2 dark:text-white">
                  <option value="open">Open</option><option value="in_progress">In Progress</option><option value="resolved">Resolved</option><option value="closed">Closed</option>
                </select>
                <button @click="updateStatus" :disabled="statusForm.processing" class="px-3 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">Save</button>
              </div>
            </div>
            <div>
              <label class="block text-[10px] font-semibold text-gray-500 uppercase mb-1.5">Assign TPS</label>
              <div class="max-h-36 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
                <label v-for="tps in tpsUsers" :key="tps.id" class="flex items-center gap-2 px-2.5 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer text-xs">
                  <input type="checkbox" :value="tps.id" v-model="selectedAssignees" class="w-3.5 h-3.5 text-indigo-600 rounded" />
                  {{ tps.name }}
                </label>
              </div>
              <button @click="assignTicket" :disabled="assignForm.processing || !selectedAssignees.length" class="mt-2 w-full py-2 text-xs font-semibold text-white bg-linear-to-r from-purple-600 to-indigo-600 rounded-lg hover:from-purple-700 hover:to-indigo-700 disabled:opacity-50">Update ({{ selectedAssignees.length }})</button>
            </div>
          </div>
        </div>
        </div>

        <!-- Assistance Card -->
        <div v-if="assistanceRequests?.length" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/40 dark:border-gray-700/30 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-700/30">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Assistance ({{ assistanceRequests.length }})</h3>
          </div>
          <div class="divide-y divide-gray-50 dark:divide-gray-700/50 max-h-48 overflow-y-auto">
            <div v-for="req in assistanceRequests" :key="req.id" class="px-5 py-3">
              <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">{{ req.body }}</p>
              <p class="text-[10px] text-gray-400 mt-1">{{ formatDate(req.created_at) }}</p>
            </div>
          </div>
        </div>

        <!-- Activity Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200/40 dark:border-gray-700/30 shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-50 dark:border-gray-700/30">
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Activity</h3>
          </div>
          <div class="px-5 py-4">
          <div class="space-y-3">
            <!-- Creation -->
            <div class="flex gap-3">
              <div class="relative flex flex-col items-center">
                <div class="w-5 h-5 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center shrink-0">
                  <svg class="w-3 h-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2m6-6a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="w-px h-full bg-gray-200 dark:bg-gray-600 mt-1"></div>
              </div>
              <div class="pb-3">
                <p class="text-xs font-medium text-gray-900 dark:text-white">Ticket Created</p>
                <p class="text-[11px] text-gray-500">{{ formatDate(ticket.created_at) }}</p>
                <p v-if="ticket.submitter" class="text-[11px] text-gray-400">by {{ ticket.submitter.name }}</p>
              </div>
            </div>

            <!-- Comments -->
            <template v-for="comment in ticket.comments" :key="comment.id">
              <div class="flex gap-3">
                <div class="relative flex flex-col items-center">
                  <div class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center shrink-0">
                    <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                  </div>
                  <div class="w-px h-full bg-gray-200 dark:bg-gray-600 mt-1"></div>
                </div>
                <div class="pb-3 min-w-0">
                  <p class="text-xs font-medium text-gray-900 dark:text-white truncate">{{ comment.user?.name || 'Unknown' }}</p>
                  <p v-if="comment.body" class="text-[11px] text-gray-500 line-clamp-2">{{ comment.body }}</p>
                  <p class="text-[10px] text-gray-400">{{ formatDate(comment.created_at) }}</p>
                </div>
              </div>
            </template>

            <!-- Resolution -->
            <div v-if="ticket.status === 'resolved' || ticket.status === 'closed'" class="flex gap-3">
              <div class="flex flex-col items-center">
                <div class="w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0">
                  <svg class="w-3 h-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
              </div>
              <div class="pb-3">
                <p class="text-xs font-medium text-emerald-700 dark:text-emerald-300">Resolved</p>
                <p v-if="ticket.resolved_at" class="text-[11px] text-gray-500">{{ formatDate(ticket.resolved_at) }}</p>
                <p v-if="ticket.resolver" class="text-[11px] text-gray-400">by {{ ticket.resolver.name }}</p>
              </div>
            </div>
          </div>
        </div>
        </div>

      </div> <!-- close right col -->
    </div> <!-- close grid -->

    <!-- Image Preview Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="previewImage" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" @click="previewImage = null">
          <div class="relative max-w-5xl max-h-[90vh] w-full" @click.stop>
            <button @click="previewImage = null" class="absolute -top-10 right-0 text-white/80 hover:text-white text-sm font-medium flex items-center gap-1 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              Close
            </button>
            <img :src="previewImage.url" :alt="previewImage.label" class="w-full max-h-[85vh] object-contain rounded-2xl shadow-2xl" />
            <p class="text-center text-white/60 text-xs mt-3">{{ previewImage.label }}</p>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { formatDate } from '@/utils/dateFormat';

const props = defineProps({ ticket: Object, tpsUsers: Array, assistanceRequests: Array });

const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id);
const canManage = computed(() => {
  const perms = page.props.auth?.user?.permissions || [];
  return perms.includes('tickets.manage') || perms.includes('tickets.assign');
});

const donePmsCount = computed(() => {
  const items = props.ticket.pms_checklist || [];
  return items.filter(c => c.done === true || c.done === '1' || c.done === 1).length;
});

/**
 * Parse resolution_notes raw text into labeled fields.
 * Splits on known labels: Findings, Job Done, Recommendation, Remarks.
 */
const parsedNotes = computed(() => {
  if (!props.ticket.resolution_notes) return null;
  const text = props.ticket.resolution_notes;
  const fields = {};

  const labels = ['Findings:', 'Job Done:', 'Recommendation:', 'Remarks:'];
  let remaining = text;

  for (let i = 0; i < labels.length; i++) {
    const label = labels[i];
    const nextLabel = labels[i + 1];
    const idx = remaining.indexOf(label);

    if (idx === -1) continue;

    const valueStart = idx + label.length;
    let value;

    if (nextLabel) {
      const nextIdx = remaining.indexOf(nextLabel, valueStart);
      value = nextIdx === -1
        ? remaining.substring(valueStart).trim()
        : remaining.substring(valueStart, nextIdx).trim();
    } else {
      value = remaining.substring(valueStart).trim();
    }

    const key = label.replace(':', '');
    fields[key] = value || null;
  }

  // If nothing parsed, fall back to raw text as Findings
  if (Object.keys(fields).length === 0 && text.trim()) {
    fields['Findings'] = text.trim();
  }

  return fields;
});

const totalAmount = computed(() => {
  let total = 0;
  // Sum parts
  if (props.ticket.tractor_parts) {
    total += props.ticket.tractor_parts.reduce((sum, p) => sum + parseFloat(p.amount || 0), 0);
  }
  // Add service charge
  if (props.ticket.service_charge) {
    total += parseFloat(props.ticket.service_charge);
  }
  return total;
});

const balance = computed(() => {
  const down = props.ticket.down_payment ? parseFloat(props.ticket.down_payment) : 0;
  return Math.max(0, totalAmount.value - down);
});

// Local comments for real-time updates
const localComments = ref([...(props.ticket.comments || [])]);
const commentsContainer = ref(null);
const isListeningRealtime = ref(false);
const fileInput = ref(null);
const selectedFile = ref(null);
const attachmentPreview = ref(null);
const previewImage = ref(null); // { url, label }

const scrollToBottom = () => {
  nextTick(() => {
    if (commentsContainer.value) {
      commentsContainer.value.scrollTop = commentsContainer.value.scrollHeight;
    }
  });
};

// Watch for prop changes (Inertia page visits)
watch(() => props.ticket.comments, (newComments) => {
  localComments.value = [...(newComments || [])];
  scrollToBottom();
});

// Helpers
const isImage = (url) => /\.(jpg|jpeg|png|gif|webp)$/i.test(url);

const formatTime = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const formatDayLabel = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  const today = new Date();
  const yesterday = new Date(today);
  yesterday.setDate(yesterday.getDate() - 1);
  if (d.toDateString() === today.toDateString()) return 'Today';
  if (d.toDateString() === yesterday.toDateString()) return 'Yesterday';
  return d.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
};

const isSameDay = (a, b) => {
  if (!a || !b) return false;
  const da = new Date(a);
  const db = new Date(b);
  return da.toDateString() === db.toDateString();
};

// File attachment
const onFileSelected = (e) => {
  const file = e.target.files?.[0];
  if (!file) return;
  selectedFile.value = file;
  const fileIsImage = file.type.startsWith('image/');
  attachmentPreview.value = {
    name: file.name,
    isImage: fileIsImage,
    url: fileIsImage ? URL.createObjectURL(file) : null,
  };
};

const removeAttachment = () => {
  if (attachmentPreview.value?.url) URL.revokeObjectURL(attachmentPreview.value.url);
  selectedFile.value = null;
  attachmentPreview.value = null;
  if (fileInput.value) fileInput.value.value = '';
};

// Comment form
const commentForm = useForm({ body: '' });
const addComment = () => {
  if (!commentForm.body.trim() && !selectedFile.value) return;

  const formData = new FormData();
  const socketId = window.Echo?.socketId?.();
  logTicketChat('posting comment', {
    socketId,
    hasBody: Boolean(commentForm.body.trim()),
    hasAttachment: Boolean(selectedFile.value),
  });
  formData.append('body', commentForm.body || '');
  if (socketId) formData.append('socket_id', socketId);
  if (selectedFile.value) formData.append('attachment', selectedFile.value);

  router.post(`/tickets/${props.ticket.id}/comment`, formData, {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      commentForm.reset();
      removeAttachment();
      scrollToBottom();
    },
  });
};

// ── Typing indicator ─────────────────────────────────
const typingUser = ref(null);       // { name, role }
let typingTimeout = null;
let typingThrottle = null;

const displayRole = (roles) => {
  if (!roles?.length) return '';
  const r = roles[0];
  if (r === 'super-admin' || r === 'sub-admin') return 'Admin';
  if (r === 'tsr') return 'tsr';
  if (r === 'fca') return 'FCA';
  if (r === 'farmer') return 'Farmer';
  return '';
};

const sendTyping = () => {
  if (typingThrottle) return;
  if (!echoChannel) return;
  const user = page.props.auth?.user;
  logTicketChat('sending typing whisper', {
    userId: user?.id,
    role: displayRole(user?.roles),
  });
  echoChannel.whisper('typing', {
    name: user?.name || 'Someone',
    role: displayRole(user?.roles),
    user_id: user?.id,
  });
  typingThrottle = setTimeout(() => { typingThrottle = null; }, 2000);
};

const onCommentInput = (e) => {
  commentForm.body = e.target.value;
  sendTyping();
};

// Status form
const statusForm = useForm({ status: props.ticket.status });
const updateStatus = () => {
  statusForm.put(`/tickets/${props.ticket.id}/status`, { preserveScroll: true });
};

// Assign form (multi-TPS)
const selectedAssignees = ref((props.ticket.assignees || []).map(a => a.id));
const assignForm = useForm({});
const assignTicket = () => {
  assignForm.transform(() => ({
    assignee_ids: selectedAssignees.value,
  })).put(`/tickets/${props.ticket.id}/assign`, { preserveScroll: true });
};

// Real-time comments via Echo
let echoChannel = null;
const logTicketChat = (message, context = {}) => {
  console.debug(`[ticket-chat:${props.ticket.id}] ${message}`, context);
};

onMounted(() => {
  scrollToBottom();

  if (window.Echo) {
    logTicketChat('subscribing to realtime room');
    echoChannel = window.Echo.private(`ticket.${props.ticket.id}`);
    echoChannel.listen('TicketCommentAdded', (e) => {
      logTicketChat('received comment event', {
        commentId: e.comment?.id,
        userId: e.comment?.user?.id,
      });
      // Avoid duplicates
      if (!localComments.value.find(c => c.id === e.comment.id)) {
        localComments.value.push(e.comment);
        scrollToBottom();
      }
    });
    echoChannel.listenForWhisper('typing', (e) => {
      logTicketChat('received typing whisper', e);
      // Don't show typing indicator for own user
      if (e.user_id && e.user_id === page.props.auth?.user?.id) return;
      typingUser.value = { name: e.name, role: e.role };
      clearTimeout(typingTimeout);
      typingTimeout = setTimeout(() => { typingUser.value = null; }, 3000);
    });
    isListeningRealtime.value = true;
  }
});

onUnmounted(() => {
  if (echoChannel) {
    logTicketChat('leaving realtime room');
    echoChannel.stopListening('TicketCommentAdded');
    echoChannel.stopListeningForWhisper('typing');
    window.Echo?.leave(`ticket.${props.ticket.id}`);
  }
  clearTimeout(typingTimeout);
  clearTimeout(typingThrottle);
});
</script>
