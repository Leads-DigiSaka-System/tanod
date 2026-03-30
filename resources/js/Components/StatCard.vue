<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 dark:bg-gray-800 dark:border-gray-700">
    <div class="flex items-center justify-between mb-2">
      <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ title }}</span>
      <div :class="[iconBg, 'rounded-lg p-2']">
        <slot name="icon">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
          </svg>
        </slot>
      </div>
    </div>
    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">{{ value }}</h3>
    <div v-if="$slots.footer" class="mt-2">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: { type: String, required: true },
  value: { type: [String, Number], required: true },
  color: { type: String, default: 'indigo' },
});

const iconBg = computed(() => ({
  indigo: 'bg-indigo-500',
  green: 'bg-green-500',
  yellow: 'bg-yellow-500',
  red: 'bg-red-500',
  blue: 'bg-blue-500',
  gray: 'bg-gray-500',
}[props.color] || 'bg-indigo-500'));
</script>
