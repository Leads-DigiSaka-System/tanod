<template>
  <AppLayout>
    <Head :title="`Ticket #${ticket.id}`" />

    <!-- Page Header -->
    <div class="mb-6">
      <Link href="/tickets" class="inline-flex items-center text-sm font-medium text-emerald-600 hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
        Back to Tickets
      </Link>
      <div class="mt-3 flex items-start justify-between">
        <div>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ ticket.subject }}</h1>
            <span class="text-sm font-mono text-gray-400 dark:text-gray-500">#{{ ticket.id }}</span>
          </div>
          <div class="mt-2 flex items-center gap-2 flex-wrap">
            <StatusBadge :status="ticket.status" />
            <StatusBadge :status="ticket.priority" />
            <span v-if="ticket.category" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 capitalize">{{ ticket.category }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- Left Column - Main Content -->
      <div class="lg:col-span-2 space-y-6">

        <!-- Description Card -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
          <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" /></svg>
              Description
            </h3>
          </div>
          <div class="px-6 py-4">
            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ ticket.description || 'No description provided.' }}</p>
          </div>
        </div>

        <!-- Issue Photo -->
        <div v-if="ticket.photo_url" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
          <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
              Issue Photo
            </h3>
          </div>
          <div class="p-4">
            <a :href="ticket.photo_url" target="_blank" class="block">
              <img :src="ticket.photo_url" alt="Issue photo" class="w-full max-h-96 object-contain rounded-lg bg-gray-50 dark:bg-gray-900 cursor-zoom-in hover:opacity-90 transition-opacity" />
            </a>
          </div>
        </div>

        <!-- Resolution Card -->
        <div v-if="ticket.status === 'resolved' || ticket.status === 'closed'" class="bg-white rounded-xl border border-emerald-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-emerald-900">
          <div class="px-6 py-4 border-b border-emerald-100 dark:border-emerald-900 bg-emerald-50 dark:bg-emerald-900/20">
            <h3 class="text-sm font-semibold text-emerald-800 dark:text-emerald-300 flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              Resolution
            </h3>
          </div>
          <div class="px-6 py-4 space-y-3">
            <div class="flex items-center gap-4 text-sm">
              <span v-if="ticket.resolver" class="text-gray-500 dark:text-gray-400">Resolved by <strong class="text-gray-900 dark:text-white">{{ ticket.resolver.name }}</strong></span>
              <span v-if="ticket.resolved_at" class="text-gray-400 dark:text-gray-500">{{ formatDate(ticket.resolved_at) }}</span>
            </div>
            <p v-if="ticket.resolution_notes" class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ ticket.resolution_notes }}</p>
            <div v-if="ticket.resolution_photo_url" class="mt-3">
              <a :href="ticket.resolution_photo_url" target="_blank" class="block">
                <img :src="ticket.resolution_photo_url" alt="Resolution photo" class="w-full max-h-72 object-contain rounded-lg bg-gray-50 dark:bg-gray-900 cursor-zoom-in hover:opacity-90 transition-opacity" />
              </a>
            </div>
          </div>
        </div>

        <!-- Comments / Chat Section -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
          <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
              Discussion
              <span class="text-xs font-normal text-gray-400 dark:text-gray-500">({{ localComments.length }})</span>
              <span v-if="isListeningRealtime" class="ml-auto flex items-center gap-1 text-xs text-emerald-500">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                Live
              </span>
            </h3>
          </div>

          <!-- Messages -->
          <div ref="commentsContainer" class="px-4 py-4 max-h-[32rem] overflow-y-auto space-y-3 bg-gray-50 dark:bg-gray-900/30">
            <div v-if="!localComments.length" class="flex flex-col items-center justify-center py-12">
              <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
              <p class="text-sm text-gray-400 dark:text-gray-500">No messages yet. Start the conversation.</p>
            </div>

            <template v-for="(comment, index) in localComments" :key="comment.id">
              <!-- Date separator (optional: show when date changes) -->
              <div v-if="index === 0 || !isSameDay(comment.created_at, localComments[index - 1].created_at)"
                class="flex items-center gap-3 py-2">
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
                <span class="text-[10px] font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">{{ formatDayLabel(comment.created_at) }}</span>
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
              </div>

              <!-- Chat bubble -->
              <div class="flex" :class="comment.user?.id === currentUserId ? 'justify-end' : 'justify-start'">
                <!-- Avatar (other users only) -->
                <div v-if="comment.user?.id !== currentUserId" class="h-7 w-7 rounded-full flex items-center justify-center shrink-0 mr-2 mt-auto bg-indigo-100 dark:bg-indigo-900/50">
                  <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-300">{{ comment.user?.name?.charAt(0) || '?' }}</span>
                </div>
                <div class="max-w-[75%] group">
                  <!-- Name (other users only) -->
                  <p v-if="comment.user?.id !== currentUserId" class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-0.5 ml-1">{{ comment.user?.name || 'Unknown' }}</p>
                  <!-- Bubble -->
                  <div class="relative px-3.5 py-2.5 rounded-2xl shadow-sm text-sm leading-relaxed"
                    :class="comment.user?.id === currentUserId
                      ? 'bg-emerald-600 text-white rounded-br-md'
                      : 'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-600 rounded-bl-md'">
                    <!-- Attachment image -->
                    <a v-if="comment.attachment_url && isImage(comment.attachment_url)" :href="comment.attachment_url" target="_blank" class="block mb-2 -mx-1 -mt-1">
                      <img :src="comment.attachment_url" alt="Attachment" class="max-w-full max-h-56 rounded-xl object-cover cursor-zoom-in hover:opacity-90 transition-opacity" />
                    </a>
                    <!-- Attachment file (non-image) -->
                    <a v-else-if="comment.attachment_url" :href="comment.attachment_url" target="_blank"
                      class="flex items-center gap-2 mb-2 px-3 py-2 rounded-lg transition-colors"
                      :class="comment.user?.id === currentUserId ? 'bg-emerald-700/50 hover:bg-emerald-700/70' : 'bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500'">
                      <svg class="w-5 h-5 shrink-0" :class="comment.user?.id === currentUserId ? 'text-emerald-200' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                      <span class="text-xs font-medium truncate" :class="comment.user?.id === currentUserId ? 'text-emerald-100' : 'text-gray-600 dark:text-gray-300'">Attachment</span>
                    </a>
                    <p v-if="comment.body" class="whitespace-pre-wrap wrap-break-word">{{ comment.body }}</p>
                    <span class="block text-[10px] mt-1 text-right"
                      :class="comment.user?.id === currentUserId ? 'text-emerald-200' : 'text-gray-400 dark:text-gray-500'">{{ formatTime(comment.created_at) }}</span>
                  </div>
                </div>
              </div>
            </template>
          </div>

          <!-- Comment Input -->
          <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800">
            <!-- Typing indicator -->
            <div v-if="typingUser" class="flex items-center gap-2 mb-2 text-xs text-gray-400 dark:text-gray-500">
              <span class="flex gap-0.5">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-bounce" style="animation-delay: 0ms"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-bounce" style="animation-delay: 150ms"></span>
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-bounce" style="animation-delay: 300ms"></span>
              </span>
              <span class="italic">{{ typingUser.role }} {{ typingUser.name }} is typing...</span>
            </div>
            <!-- Attachment preview -->
            <div v-if="attachmentPreview" class="mb-2 relative inline-block">
              <img v-if="attachmentPreview.isImage" :src="attachmentPreview.url" class="h-20 rounded-lg border border-gray-200 dark:border-gray-600 object-cover" />
              <div v-else class="flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                <span class="text-xs text-gray-600 dark:text-gray-300 truncate max-w-[160px]">{{ attachmentPreview.name }}</span>
              </div>
              <button @click="removeAttachment" class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-500 text-white flex items-center justify-center text-xs hover:bg-red-600 shadow">
                &times;
              </button>
            </div>
            <form @submit.prevent="addComment" class="flex items-end gap-2">
              <input ref="fileInput" type="file" accept="image/*,.pdf" class="hidden" @change="onFileSelected" />
              <button type="button" @click="$refs.fileInput.click()" class="p-2.5 rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors shrink-0" title="Attach file">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
              </button>
              <input :value="commentForm.body" @input="onCommentInput" type="text" placeholder="Write a message..."
                class="flex-1 bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block w-full px-4 py-2.5 dark:bg-gray-900 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                @keydown.enter.exact.prevent="addComment" />
              <button type="submit" :disabled="commentForm.processing || (!commentForm.body.trim() && !selectedFile)"
                class="p-2.5 text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 focus:ring-4 focus:ring-emerald-300 disabled:opacity-40 disabled:cursor-not-allowed transition-colors shrink-0 dark:bg-emerald-500 dark:hover:bg-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Right Column - Sidebar -->
      <div class="space-y-6">
        <!-- Info Card -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Details</h3>
          </div>
          <div class="px-5 py-4 space-y-4">
            <!-- Reporter -->
            <div class="flex items-start gap-3">
              <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
              </div>
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Reporter</p>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ ticket.submitter?.name || '—' }}</p>
              </div>
            </div>

            <!-- Tractor -->
            <div v-if="ticket.tractor" class="flex items-start gap-3">
              <div class="h-8 w-8 rounded-full bg-amber-100 dark:bg-amber-900 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-amber-600 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
              </div>
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Tractor</p>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ ticket.tractor.no_plate }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ ticket.tractor.brand }} {{ ticket.tractor.model }}</p>
              </div>
            </div>

            <!-- Assigned TPS -->
            <div class="flex items-start gap-3">
              <div class="h-8 w-8 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
              </div>
              <div class="flex-1">
                <p class="text-xs text-gray-500 dark:text-gray-400">Assigned TPS</p>
                <div v-if="ticket.assignees?.length" class="mt-1 flex flex-wrap gap-1.5">
                  <span v-for="a in ticket.assignees" :key="a.id" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                    {{ a.name }}
                  </span>
                </div>
                <p v-else class="text-sm text-gray-400 dark:text-gray-500">Unassigned</p>
              </div>
            </div>

            <!-- Created Date -->
            <div class="flex items-start gap-3">
              <div class="h-8 w-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center shrink-0 mt-0.5">
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              </div>
              <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Created</p>
                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDate(ticket.created_at) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions Card -->
        <div v-if="canManage" class="bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700">
          <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</h3>
          </div>
          <div class="px-5 py-4 space-y-5">
            <!-- Update Status -->
            <div>
              <label class="block mb-1.5 text-xs font-medium text-gray-700 dark:text-gray-300">Update Status</label>
              <div class="flex gap-2">
                <select v-model="statusForm.status" class="flex-1 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block w-full p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                  <option value="open">Open</option>
                  <option value="in_progress">In Progress</option>
                  <option value="resolved">Resolved</option>
                  <option value="closed">Closed</option>
                </select>
                <button @click="updateStatus" :disabled="statusForm.processing"
                  class="px-3 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50 transition-colors whitespace-nowrap">Save</button>
              </div>
            </div>

            <!-- Assign TPS (multi-select with checkboxes) -->
            <div>
              <label class="block mb-1.5 text-xs font-medium text-gray-700 dark:text-gray-300">Assign TPS Personnel</label>
              <div class="max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
                <label v-for="tps in tpsUsers" :key="tps.id" class="flex items-center gap-3 px-3 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors">
                  <input type="checkbox" :value="tps.id" v-model="selectedAssignees"
                    class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700" />
                  <span class="text-sm text-gray-900 dark:text-white">{{ tps.name }}</span>
                </label>
                <div v-if="!tpsUsers?.length" class="px-3 py-4 text-center text-sm text-gray-400">No TPS personnel available</div>
              </div>
              <button @click="assignTicket" :disabled="assignForm.processing || !selectedAssignees.length"
                class="mt-2 w-full px-3 py-2 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 disabled:opacity-50 transition-colors">
                Update Assignees ({{ selectedAssignees.length }})
              </button>
            </div>
          </div>
        </div>

        <!-- Assistance Requests Card -->
        <div v-if="assistanceRequests?.length" class="bg-white rounded-xl border border-orange-200 shadow-sm dark:bg-gray-800 dark:border-orange-900">
          <div class="px-5 py-4 border-b border-orange-100 dark:border-orange-900 bg-orange-50 dark:bg-orange-900/20">
            <h3 class="text-xs font-semibold text-orange-700 dark:text-orange-300 uppercase tracking-wider flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3" /></svg>
              Assistance Requests ({{ assistanceRequests.length }})
            </h3>
          </div>
          <div class="divide-y divide-orange-100 dark:divide-orange-900/40 max-h-64 overflow-y-auto">
            <div v-for="req in assistanceRequests" :key="req.id" class="px-5 py-3">
              <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ req.body }}</p>
              <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ formatDate(req.created_at) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
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

// Local comments for real-time updates
const localComments = ref([...(props.ticket.comments || [])]);
const commentsContainer = ref(null);
const isListeningRealtime = ref(false);
const fileInput = ref(null);
const selectedFile = ref(null);
const attachmentPreview = ref(null);

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
  formData.append('body', commentForm.body || '');
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
  if (r === 'tps') return 'TPS';
  if (r === 'fca') return 'FCA';
  if (r === 'farmer') return 'Farmer';
  return '';
};

const sendTyping = () => {
  if (typingThrottle) return;
  if (!echoChannel) return;
  const user = page.props.auth?.user;
  echoChannel.whisper('typing', {
    name: user?.name || 'Someone',
    role: displayRole(user?.roles),
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

onMounted(() => {
  scrollToBottom();

  if (window.Echo) {
    echoChannel = window.Echo.private(`ticket.${props.ticket.id}`);
    echoChannel.listen('TicketCommentAdded', (e) => {
      // Avoid duplicates
      if (!localComments.value.find(c => c.id === e.comment.id)) {
        localComments.value.push(e.comment);
        scrollToBottom();
      }
    });
    echoChannel.listenForWhisper('typing', (e) => {
      // Don't show typing indicator for own user
      if (e.name === page.props.auth?.user?.name) return;
      typingUser.value = { name: e.name, role: e.role };
      clearTimeout(typingTimeout);
      typingTimeout = setTimeout(() => { typingUser.value = null; }, 3000);
    });
    isListeningRealtime.value = true;
  }
});

onUnmounted(() => {
  if (echoChannel) {
    echoChannel.stopListening('TicketCommentAdded');
    echoChannel.stopListeningForWhisper('typing');
    window.Echo?.leave(`ticket.${props.ticket.id}`);
  }
  clearTimeout(typingTimeout);
  clearTimeout(typingThrottle);
});
</script>
