<template>
  <div>
    <!-- Trigger button -->
    <button
      v-if="!hideTrigger"
      type="button"
      @click="open()"
      class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
    >
      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      <span>{{ label }}</span>
      <span
        class="inline-flex items-center justify-center rounded-full px-2 py-0.5 text-xs font-semibold"
        :class="count
          ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
          : 'bg-gray-100 text-gray-500 dark:bg-gray-600 dark:text-gray-300'"
      >
        {{ count }}
      </span>
    </button>

    <!-- Photos modal -->
    <Modal :show="show" max-width="3xl" @close="close">
      <template #header>
        <div class="flex items-center gap-2">
          <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <span class="text-lg font-semibold">{{ title }}</span>
        </div>
      </template>

      <!-- Enlarged single photo -->
      <div v-if="zoom" class="flex flex-col items-center gap-3">
        <img
          :src="zoom.url"
          :alt="zoom.label"
          class="max-h-[65vh] w-auto rounded-lg border border-gray-200 object-contain dark:border-gray-600"
        />
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ zoom.label }}</p>
        <div class="flex flex-wrap items-center justify-center gap-3">
          <a
            :href="zoom.url"
            :download="fileName(zoom)"
            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:bg-emerald-500 dark:hover:bg-emerald-600"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Download
          </a>
          <button
            type="button"
            @click="zoom = null"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to all photos
          </button>
        </div>
      </div>

      <template v-else>
        <div
          v-if="!visibleEntries.length"
          class="flex flex-col items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 py-12 dark:border-gray-600 dark:bg-gray-700/40"
        >
          <svg class="mb-3 h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <p class="text-sm text-gray-500 dark:text-gray-400">No implement photos uploaded yet.</p>
          <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Photos are captured from the mobile app (Edit Profile → Tractors &amp; Implements).</p>
        </div>

        <div v-else :class="activeType ? 'flex justify-center' : 'grid grid-cols-1 gap-4 sm:grid-cols-2'">
          <div
            v-for="entry in visibleEntries"
            :key="entry.key"
            class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-600 dark:bg-gray-700/40"
          >
            <div class="mb-2 flex items-center justify-between gap-2">
              <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ entry.label }}</p>
              <span v-if="!entry.image" class="text-[11px] font-medium text-gray-400 dark:text-gray-500">Not uploaded</span>
            </div>

            <div v-if="entry.image" class="group relative">
              <img
                :src="entry.image.url"
                :alt="entry.label"
                class="h-48 w-full cursor-zoom-in rounded-lg border border-gray-200 object-cover transition-transform duration-200 group-hover:scale-[1.02] dark:border-gray-600"
                @click="zoom = entry.image"
              />
              <a
                :href="entry.image.url"
                :download="fileName(entry.image)"
                class="absolute right-2 top-2 inline-flex items-center justify-center rounded-lg bg-gray-900/60 p-2 text-white shadow-sm transition-colors hover:bg-gray-900/85 focus:outline-none focus:ring-2 focus:ring-white/70"
                :title="`Download ${entry.label} photo`"
                @click.stop
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </a>
            </div>
            <div
              v-else
              class="flex h-48 w-full flex-col items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-700/40"
            >
              <svg class="mb-2 h-8 w-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <p class="text-xs text-gray-400 dark:text-gray-500">No photo</p>
            </div>
          </div>
        </div>
      </template>

      <template #footer>
        <button
          type="button"
          @click="close"
          class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500"
        >
          Close
        </button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import Modal from '@/Components/Modal.vue';

// The five implement fields captured by the mobile app (Edit Profile → Tractors & Implements).
const IMPLEMENT_FIELDS = [
  { key: 'id_no', label: 'Serial Number' },
  { key: 'engine_no', label: 'Engine Number' },
  { key: 'front_loader_sn', label: 'Front Loader SN' },
  { key: 'rotary_tiller_sn', label: 'Rotavator SN' },
  { key: 'disc_plow_sn', label: 'Disc Plow SN' },
];

const props = defineProps({
  images: { type: Array, default: () => [] },
  tractorName: { type: String, default: '' },
  label: { type: String, default: 'View Implement Photos' },
  hideTrigger: { type: Boolean, default: false },
});

const show = ref(false);
const zoom = ref(null);
const activeType = ref(null);
const overrideImages = ref(null);
const overrideName = ref('');

const resolveImages = computed(() => overrideImages.value ?? props.images ?? []);
const resolveName = computed(() => overrideName.value || props.tractorName || '');

const imageUrl = (img) => img?.url || (img?.path ? `/storage/${img.path}` : null);

/** Map the latest image of each implement type (backend keeps one row per type). */
const imageMap = computed(() => {
  const map = {};
  resolveImages.value.forEach((img) => {
    const type = img?.type;
    if (!type || map[type]) return;
    const url = imageUrl(img);
    if (url) map[type] = url;
  });
  return map;
});

const entries = computed(() => IMPLEMENT_FIELDS.map((field) => ({
  ...field,
  image: imageMap.value[field.key]
    ? { key: field.key, label: field.label, url: imageMap.value[field.key] }
    : null,
})));

const count = computed(() => entries.value.filter((e) => e.image).length);
const visibleEntries = computed(() => (activeType.value
  ? entries.value.filter((e) => e.key === activeType.value)
  : entries.value));

const title = computed(() => (resolveName.value
  ? `Implement Photos — ${resolveName.value}`
  : 'Implement Photos'));

/** Sanitize a value so it is safe to use inside a file name. */
const slug = (value) => String(value || '')
  .trim()
  .replace(/[^a-zA-Z0-9._-]+/g, '-')
  .replace(/^-+|-+$/g, '');

/** Keep the real extension of the stored file (jpg/png/webp). */
const fileExtension = (url) => {
  const match = String(url || '').split('?')[0].match(/\.([a-zA-Z0-9]{2,5})$/);
  return match ? match[1].toLowerCase() : 'jpg';
};

const fileName = (image) => {
  const tractor = slug(resolveName.value) || 'tractor';
  const implement = slug(image?.label || image?.key) || 'implement';
  return `${tractor}-${implement}.${fileExtension(image?.url)}`;
};

/**
 * Open the photo viewer.
 * @param {{type?: string|null, images?: Array|null, tractorName?: string|null}|string} [options]
 */
const open = (options = {}) => {
  const opts = typeof options === 'string' ? { type: options } : (options || {});
  activeType.value = opts.type ?? null;
  overrideImages.value = opts.images ?? null;
  overrideName.value = opts.tractorName ?? '';
  zoom.value = null;
  show.value = true;
};

const close = () => {
  show.value = false;
  zoom.value = null;
  activeType.value = null;
  overrideImages.value = null;
  overrideName.value = '';
};

defineExpose({ open, close });
</script>
