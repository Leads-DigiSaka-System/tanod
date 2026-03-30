<script setup>
defineProps({
  show: { type: Boolean, default: false },
  maxWidth: { type: String, default: 'lg' },
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
});

defineEmits(['close']);

const widthClass = {
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg',
  xl: 'max-w-xl',
  '2xl': 'max-w-2xl',
  '3xl': 'max-w-3xl',
  '4xl': 'max-w-4xl',
};
</script>

<template>
  <!-- Backdrop -->
  <Transition
    enter-active-class="transition-opacity duration-300 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition-opacity duration-200 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0">
    <div v-if="show" class="fixed inset-0 z-40 bg-gray-900/50" @click="$emit('close')"></div>
  </Transition>

  <!-- Panel -->
  <Transition
    enter-active-class="transition-transform duration-300 ease-out"
    enter-from-class="translate-x-full"
    enter-to-class="translate-x-0"
    leave-active-class="transition-transform duration-200 ease-in"
    leave-from-class="translate-x-0"
    leave-to-class="translate-x-full">
    <div v-if="show" class="fixed inset-y-0 right-0 z-50 w-full" :class="widthClass[maxWidth] || 'max-w-lg'">
      <div class="flex h-full flex-col bg-white shadow-2xl dark:bg-gray-800">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700" style="background-color: #007f3d;">
          <div>
            <h2 class="text-lg font-semibold text-white">{{ title }}</h2>
            <p v-if="subtitle" class="text-sm text-green-100">{{ subtitle }}</p>
          </div>
          <button @click="$emit('close')" class="rounded-lg p-1.5 text-green-100 hover:text-white hover:bg-white/20 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>

        <!-- Body -->
        <slot />
      </div>
    </div>
  </Transition>
</template>
