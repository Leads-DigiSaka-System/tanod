<template>
  <div class="bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 relative">
    <!-- Loading overlay slot -->
    <slot name="loading" />
    <div class="overflow-x-auto scrollbar-hidden hover:scrollbar-visible">
      <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
          <slot name="head" />
        </thead>
        <tbody>
          <slot name="body" />
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.scrollbar-hidden {
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.scrollbar-hidden::-webkit-scrollbar {
  height: 4px;
}
.scrollbar-hidden::-webkit-scrollbar-track {
  background: transparent;
}
.scrollbar-hidden::-webkit-scrollbar-thumb {
  background: transparent;
  border-radius: 4px;
}

.scrollbar-hidden:hover::-webkit-scrollbar-thumb {
  background: rgb(156 163 175 / 0.4);
}
.scrollbar-hidden:hover::-webkit-scrollbar-thumb:hover {
  background: rgb(107 114 128 / 0.6);
}

/* Firefox: show on hover */
.scrollbar-hidden:hover {
  scrollbar-width: thin;
  scrollbar-color: rgb(156 163 175 / 0.4) transparent;
}
</style>
