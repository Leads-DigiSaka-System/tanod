<template>
  <AppLayout>
    <Head :title="geofence.name" />

    <!-- Page Header -->
    <div class="mb-6">
      <Link href="/geofences" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Geo-Fences
      </Link>
      <div class="mt-2 sm:flex sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ geofence.name }}</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Geo-fence details, coordinates and recent alerts.</p>
        </div>
        <Link :href="`/geofences/${geofence.id}`" method="delete" as="button"
          class="mt-3 sm:mt-0 text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900">
          Delete
        </Link>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
      <!-- Details Card -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Details</h3>
        <dl class="space-y-4">
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Devices</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
              <div v-if="geofence.devices && geofence.devices.length" class="flex flex-wrap gap-1.5">
                <Link v-for="dev in geofence.devices" :key="dev.id" :href="`/devices/${dev.id}`"
                  class="inline-flex items-center bg-indigo-100 text-indigo-800 text-xs font-medium px-2 py-0.5 rounded hover:bg-indigo-200 dark:bg-indigo-900 dark:text-indigo-300 dark:hover:bg-indigo-800">
                  {{ dev.device_name || dev.imei }}
                </Link>
              </div>
              <span v-else class="text-gray-400 dark:text-gray-500">—</span>
            </dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Shape</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white capitalize">{{ geofence.shape }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Alert On</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white capitalize">{{ geofence.alert_on?.replace(/_/g, ' ') }}</dd>
          </div>
          <div v-if="geofence.radius">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Radius</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ geofence.radius }}m</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ formatDate(geofence.created_at) }}</dd>
          </div>
        </dl>
      </div>

      <!-- Coordinates Card -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Coordinates</h3>
        <div v-if="parsedCoordinates || (geofence.center_lat && geofence.center_lng)">
          <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3 font-mono text-xs text-gray-600 dark:text-gray-400 overflow-auto max-h-32 border border-gray-200 dark:border-gray-700 mb-3">
            <template v-if="geofence.shape === 'circle'">
              <div><span class="text-gray-500">Center:</span> {{ Number(geofence.center_lat).toFixed(6) }}, {{ Number(geofence.center_lng).toFixed(6) }}</div>
              <div><span class="text-gray-500">Radius:</span> {{ geofence.radius }}m</div>
            </template>
            <template v-else>
              <div v-for="(c, i) in parsedCoordinates" :key="i">
                <span class="text-gray-500">{{ i + 1 }}.</span> {{ Number(c.lat).toFixed(6) }}, {{ Number(c.lng).toFixed(6) }}
              </div>
            </template>
          </div>
        </div>
        <div v-else class="flex flex-col items-center justify-center py-8">
          <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <p class="text-sm text-gray-400 dark:text-gray-500">No coordinates available</p>
        </div>
      </div>
    </div>

    <!-- Map Card (full width) -->
    <div v-if="hasMapData" class="mt-6 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-800 dark:border-gray-700">
      <div class="flex items-center p-6 pb-4">
        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900 mr-3">
          <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
          </svg>
        </div>
        <div>
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Geo-Fence Map</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            <span class="capitalize">{{ geofence.shape }}</span>
            <span v-if="geofence.shape === 'circle'"> &middot; {{ geofence.radius }}m radius</span>
            <span v-else-if="parsedCoordinates"> &middot; {{ parsedCoordinates.length }} points</span>
          </p>
        </div>
      </div>
      <div ref="mapContainer" class="w-full" style="height: 480px;"></div>
    </div>

    <!-- Recent Alerts -->
    <div v-if="geofence.alerts?.length" class="mt-6 bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Alerts</h3>
      <div class="space-y-2">
        <div v-for="alert in geofence.alerts" :key="alert.id" class="flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
          <div>
            <span class="text-sm font-medium text-gray-900 dark:text-white capitalize">{{ alert.type?.replace(/_/g, ' ') }}</span>
            <span class="text-xs text-gray-400 dark:text-gray-500 ml-2">{{ formatDate(alert.created_at) }}</span>
          </div>
          <span :class="['text-xs font-medium px-2.5 py-1 rounded-full', alert.is_acknowledged ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300']">
            {{ alert.is_acknowledged ? 'Acknowledged' : 'New' }}
          </span>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref, onMounted, nextTick } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { formatDate } from '@/utils/dateFormat';

const props = defineProps({ geoFence: Object, googleMapKey: String });
const geofence = computed(() => props.geoFence || {});
const mapContainer = ref(null);

const parsedCoordinates = computed(() => {
  try {
    const coords = geofence.value.coordinates;
    return typeof coords === 'string' ? JSON.parse(coords) : coords;
  } catch { return null; }
});

const hasMapData = computed(() => {
  const gf = geofence.value;
  if (gf.shape === 'circle' && gf.center_lat && gf.center_lng) return true;
  if (gf.shape === 'polygon' && parsedCoordinates.value?.length >= 3) return true;
  return false;
});

function loadGoogleMaps() {
  return new Promise((resolve) => {
    if (window.google?.maps) return resolve();
    const existing = document.querySelector('script[src*="maps.googleapis.com"]');
    if (existing) {
      existing.addEventListener('load', resolve);
      return;
    }
    window.__gmcb = resolve;
    const s = document.createElement('script');
    s.src = `https://maps.googleapis.com/maps/api/js?key=${props.googleMapKey}&callback=__gmcb`;
    s.async = true;
    document.head.appendChild(s);
  });
}

async function initMap() {
  if (!hasMapData.value) return;
  await loadGoogleMaps();
  await nextTick();

  const gf = geofence.value;
  const map = new google.maps.Map(mapContainer.value, {
    zoom: 14,
    mapTypeId: 'hybrid',
    disableDefaultUI: false,
    mapTypeControl: true,
    streetViewControl: false,
  });

  if (gf.shape === 'circle') {
    const center = { lat: parseFloat(gf.center_lat), lng: parseFloat(gf.center_lng) };
    const radius = Number(gf.radius) || 500;

    new google.maps.Circle({
      map,
      center,
      radius,
      strokeColor: '#4F46E5',
      strokeOpacity: 0.9,
      strokeWeight: 2,
      fillColor: '#4F46E5',
      fillOpacity: 0.2,
    });

    new google.maps.Marker({
      map,
      position: center,
      title: gf.name || 'Center',
      icon: {
        path: google.maps.SymbolPath.CIRCLE,
        scale: 7,
        fillColor: '#4F46E5',
        fillOpacity: 1,
        strokeColor: '#fff',
        strokeWeight: 2,
      },
    });

    // Fit to circle bounds
    const bounds = new google.maps.LatLngBounds();
    const earthRadius = 6371000;
    const latDelta = (radius / earthRadius) * (180 / Math.PI);
    const lngDelta = latDelta / Math.cos(center.lat * Math.PI / 180);
    bounds.extend({ lat: center.lat - latDelta, lng: center.lng - lngDelta });
    bounds.extend({ lat: center.lat + latDelta, lng: center.lng + lngDelta });
    map.fitBounds(bounds, 60);
  } else {
    // Polygon
    const coords = parsedCoordinates.value;
    const path = coords.map(c => ({ lat: parseFloat(c.lat), lng: parseFloat(c.lng) }));

    new google.maps.Polygon({
      map,
      paths: path,
      strokeColor: '#4F46E5',
      strokeOpacity: 0.9,
      strokeWeight: 2,
      fillColor: '#4F46E5',
      fillOpacity: 0.2,
    });

    // Add numbered markers for each vertex
    path.forEach((pos, i) => {
      new google.maps.Marker({
        map,
        position: pos,
        title: `Point ${i + 1}`,
        label: { text: String(i + 1), color: '#fff', fontSize: '11px', fontWeight: 'bold' },
        icon: {
          path: google.maps.SymbolPath.CIRCLE,
          scale: 12,
          fillColor: '#4F46E5',
          fillOpacity: 1,
          strokeColor: '#fff',
          strokeWeight: 2,
        },
      });
    });

    // Fit to polygon bounds
    const bounds = new google.maps.LatLngBounds();
    path.forEach(p => bounds.extend(p));
    map.fitBounds(bounds, 60);
  }
}

onMounted(() => {
  initMap();
});
</script>
