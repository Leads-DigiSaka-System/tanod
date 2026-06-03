<template>
  <Teleport to="body">
    <Transition name="confirm">
      <div v-if="visible" class="fixed inset-0 z-60 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div @click="cancel" class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity" />

        <!-- Dialog -->
        <div class="relative w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-6 text-center transform transition-all">
          <!-- Icon -->
          <div :class="iconBgClass" class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl mb-4 shadow-[inset_0_2px_6px_rgba(0,0,0,0.08)]">
            <svg class="w-7 h-7" :class="iconColorClass" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path v-if="variant === 'danger'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>

          <!-- Title -->
          <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1.5">{{ title }}</h3>

          <!-- Message -->
          <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">{{ message }}</p>

          <!-- Buttons -->
          <div class="flex gap-3">
            <button
              @click="cancel"
              class="flex-1 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl transition-colors"
            >Cancel</button>
            <button
              @click="confirm"
              :class="confirmButtonClass"
              class="flex-1 py-2.5 text-sm font-semibold text-white rounded-xl shadow-sm transition-colors"
            >{{ confirmLabel }}</button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
  title: { type: String, default: 'Are you sure?' },
  message: { type: String, default: '' },
  confirmLabel: { type: String, default: 'Delete' },
  variant: { type: String, default: 'danger' }, // 'danger' | 'warning' | 'info'
});

const emit = defineEmits(['confirm', 'cancel']);

const visible = ref(false);

const iconBgClass = computed(() => ({
  danger: 'bg-red-100 dark:bg-red-900/30',
  warning: 'bg-amber-100 dark:bg-amber-900/30',
  info: 'bg-blue-100 dark:bg-blue-900/30',
}[props.variant] || 'bg-red-100 dark:bg-red-900/30'));

const iconColorClass = computed(() => ({
  danger: 'text-red-600 dark:text-red-400',
  warning: 'text-amber-600 dark:text-amber-400',
  info: 'text-blue-600 dark:text-blue-400',
}[props.variant] || 'text-red-600 dark:text-red-400'));

const confirmButtonClass = computed(() => ({
  danger: 'bg-red-600 hover:bg-red-500',
  warning: 'bg-amber-600 hover:bg-amber-500',
  info: 'bg-blue-600 hover:bg-blue-500',
}[props.variant] || 'bg-red-600 hover:bg-red-500'));

const confirm = () => {
  emit('confirm');
  visible.value = false;
};

const cancel = () => {
  emit('cancel');
  visible.value = false;
};

const show = () => { visible.value = true; };

watch(() => visible.value, (val) => {
  document.body.style.overflow = val ? 'hidden' : '';
});

defineExpose({ show });
</script>

<style scoped>
.confirm-enter-active { transition: all 0.2s ease-out; }
.confirm-leave-active { transition: all 0.15s ease-in; }
.confirm-enter-from { opacity: 0; }
.confirm-enter-from > div:last-child { transform: scale(0.95); opacity: 0; }
.confirm-leave-to { opacity: 0; }
.confirm-leave-to > div:last-child { transform: scale(0.95); opacity: 0; }
</style>
