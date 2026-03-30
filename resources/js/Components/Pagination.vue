<template>
  <nav class="flex items-center justify-between pt-4" v-if="links.length > 3" aria-label="Pagination">
    <div class="flex flex-1 justify-between sm:hidden">
      <Link v-if="links[0]?.url" :href="links[0].url" class="relative inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
        Previous
      </Link>
      <Link v-if="links[links.length - 1]?.url" :href="links[links.length - 1].url" class="relative ml-3 inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
        Next
      </Link>
    </div>
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-center">
      <ul class="inline-flex -space-x-px text-sm">
        <li>
          <Link v-if="links[0]?.url" :href="links[0].url" class="flex items-center justify-center px-3 h-8 ms-0 leading-tight text-gray-500 bg-white border border-e-0 border-gray-300 rounded-s-lg hover:bg-gray-100 hover:text-gray-700">
            <svg class="w-2.5 h-2.5 rtl:rotate-180" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 1 1 5l4 4"/></svg>
          </Link>
          <span v-else class="flex items-center justify-center px-3 h-8 ms-0 leading-tight text-gray-300 bg-white border border-e-0 border-gray-300 rounded-s-lg cursor-not-allowed">
            <svg class="w-2.5 h-2.5 rtl:rotate-180" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 1 1 5l4 4"/></svg>
          </span>
        </li>
        <li v-for="(link, index) in links.slice(1, -1)" :key="index">
          <Link v-if="link.url" :href="link.url" :class="[
            link.active
              ? 'flex items-center justify-center px-3 h-8 text-blue-600 border border-gray-300 bg-blue-50 hover:bg-blue-100 hover:text-blue-700'
              : 'flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700'
          ]" v-html="link.label" />
          <span v-else :class="[
            link.active
              ? 'flex items-center justify-center px-3 h-8 text-blue-600 border border-gray-300 bg-blue-50'
              : 'flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300'
          ]" v-html="link.label" />
        </li>
        <li>
          <Link v-if="links[links.length - 1]?.url" :href="links[links.length - 1].url" class="flex items-center justify-center px-3 h-8 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg hover:bg-gray-100 hover:text-gray-700">
            <svg class="w-2.5 h-2.5 rtl:rotate-180" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/></svg>
          </Link>
          <span v-else class="flex items-center justify-center px-3 h-8 leading-tight text-gray-300 bg-white border border-gray-300 rounded-e-lg cursor-not-allowed">
            <svg class="w-2.5 h-2.5 rtl:rotate-180" fill="none" viewBox="0 0 6 10"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/></svg>
          </span>
        </li>
      </ul>
    </div>
  </nav>
</template>

<script setup>
defineProps({
  links: {
    type: Array,
    required: true,
  },
});
</script>
