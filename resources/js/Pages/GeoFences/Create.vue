<template>
  <AppLayout>
    <Head title="Create Geo-Fence" />

    <!-- Page Header -->
    <div class="mb-6">
      <Link href="/geofences" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Geo-Fences
      </Link>
      <div class="mt-2">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create Geo-Fence</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Define a new geographic boundary for device tracking alerts.</p>
      </div>
    </div>

    <form @submit.prevent="submit" class="space-y-6">
      <!-- Settings Card -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Fence Settings</h3>
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <div class="sm:col-span-2 lg:col-span-3">
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Name *</label>
            <input v-model="form.name" type="text" placeholder="e.g. Farm Area Boundary"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400" />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
          </div>
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Devices *</label>
            <div class="relative">
              <button type="button" @click="deviceDropdownOpen = !deviceDropdownOpen"
                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-left flex items-center justify-between">
                <span v-if="form.device_ids.length" class="truncate">{{ form.device_ids.length }} device{{ form.device_ids.length > 1 ? 's' : '' }} selected</span>
                <span v-else class="text-gray-400 dark:text-gray-500">Select devices...</span>
                <svg class="w-4 h-4 ml-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </button>
              <div v-if="deviceDropdownOpen" class="absolute z-20 mt-1 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-64 overflow-hidden">
                <div class="p-2 border-b border-gray-200 dark:border-gray-600">
                  <input v-model="deviceSearchQuery" type="text" placeholder="Search devices..."
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:text-white dark:placeholder-gray-400" />
                </div>
                <div class="p-2 border-b border-gray-200 dark:border-gray-600 flex gap-2">
                  <button type="button" @click="form.device_ids = filteredDevices.map(d => d.id)" class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-medium">Select all</button>
                  <span class="text-gray-300 dark:text-gray-600">|</span>
                  <button type="button" @click="form.device_ids = []" class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 font-medium">Clear</button>
                </div>
                <ul class="overflow-y-auto max-h-48 py-1">
                  <li v-for="d in filteredDevices" :key="d.id"
                    @click="toggleDevice(d.id)"
                    class="flex items-center gap-2 px-3 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 text-sm">
                    <input type="checkbox" :checked="form.device_ids.includes(d.id)" class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 dark:bg-gray-600 dark:border-gray-500" readonly />
                    <span class="text-gray-900 dark:text-white">{{ d.device_name || d.imei }}</span>
                    <span v-if="d.tractor" class="text-xs text-gray-400 dark:text-gray-500">{{ d.tractor.no_plate }}</span>
                  </li>
                  <li v-if="!filteredDevices.length" class="px-3 py-4 text-sm text-center text-gray-400 dark:text-gray-500">No devices found</li>
                </ul>
              </div>
            </div>
            <!-- Selected device tags -->
            <div v-if="form.device_ids.length" class="flex flex-wrap gap-1.5 mt-2">
              <span v-for="did in form.device_ids" :key="did"
                class="inline-flex items-center bg-indigo-100 text-indigo-800 text-xs font-medium px-2 py-0.5 rounded dark:bg-indigo-900 dark:text-indigo-300">
                {{ getDeviceLabel(did) }}
                <button type="button" @click="toggleDevice(did)" class="ml-1 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
              </span>
            </div>
            <p v-if="form.errors.device_ids" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.device_ids }}</p>
          </div>
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Shape *</label>
            <select v-model="form.shape" @change="onShapeChange"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
              <option value="circle">Circle</option>
              <option value="polygon">Polygon</option>
            </select>
          </div>
          <div>
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Alert On *</label>
            <select v-model="form.alert_on"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
              <option value="enter">Enter</option>
              <option value="exit">Exit</option>
              <option value="both">Both</option>
            </select>
          </div>
          <div v-if="form.shape === 'circle'">
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Radius (meters) *</label>
            <input v-model.number="form.radius" type="number" min="1"
              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white" />
            <p v-if="form.errors.radius" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.radius }}</p>
          </div>
        </div>
      </div>

      <!-- Map Card -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ form.shape === 'circle' ? 'Set Center Point' : 'Draw Polygon' }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
              {{ form.shape === 'circle' ? 'Click on the map to place the center of the geo-fence circle.' : 'Click on the map to add polygon points. Close the shape by clicking the first point.' }}
            </p>
          </div>
          <button v-if="(form.shape === 'circle' && centerLat) || (form.shape === 'polygon' && polygonCoords.length > 0)"
            type="button" @click="clearDrawing"
            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium inline-flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Clear
          </button>
        </div>

        <!-- Map -->
        <div ref="mapContainer" class="w-full h-[450px]"></div>

        <!-- Map loading overlay -->
        <div v-if="!mapReady" class="w-full h-[450px] -mt-[450px] relative bg-gray-50 dark:bg-gray-900 flex items-center justify-center">
          <div class="text-center">
            <svg class="mx-auto h-10 w-10 text-indigo-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            <p class="mt-2 text-sm font-medium text-gray-500 dark:text-gray-400">Loading Map...</p>
          </div>
        </div>

        <!-- Coordinate display bar -->
        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
          <div v-if="form.shape === 'circle'" class="flex items-center gap-6 text-sm">
            <div class="flex items-center gap-2">
              <span class="text-gray-500 dark:text-gray-400">Lat:</span>
              <span class="font-mono text-gray-900 dark:text-white">{{ centerLat ? Number(centerLat).toFixed(6) : '—' }}</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-gray-500 dark:text-gray-400">Lng:</span>
              <span class="font-mono text-gray-900 dark:text-white">{{ centerLng ? Number(centerLng).toFixed(6) : '—' }}</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-gray-500 dark:text-gray-400">Radius:</span>
              <span class="font-mono text-gray-900 dark:text-white">{{ form.radius }}m</span>
            </div>
          </div>
          <div v-else class="text-sm">
            <div class="flex items-center gap-2 mb-1">
              <span class="text-gray-500 dark:text-gray-400">Points:</span>
              <span class="font-medium text-gray-900 dark:text-white">{{ polygonCoords.length }}</span>
              <span v-if="polygonCoords.length < 3" class="text-yellow-600 dark:text-yellow-400 text-xs">(need at least 3)</span>
              <span v-else class="text-green-600 dark:text-green-400 text-xs flex items-center gap-1">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                valid
              </span>
            </div>
            <div v-if="polygonCoords.length" class="flex flex-wrap gap-2">
              <span v-for="(c, i) in polygonCoords" :key="i"
                class="inline-flex items-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md px-2 py-0.5 text-xs font-mono text-gray-700 dark:text-gray-300">
                P{{ i + 1 }}: {{ Number(c.lat).toFixed(4) }}, {{ Number(c.lng).toFixed(4) }}
                <button type="button" @click="removePolygonPoint(i)" class="ml-1.5 text-red-400 hover:text-red-600">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
              </span>
            </div>
          </div>
          <p v-if="form.errors.coordinates" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ form.errors.coordinates }}</p>
        </div>
      </div>

      <!-- Submit -->
      <div class="flex justify-end space-x-3">
        <Link href="/geofences" class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-700">Cancel</Link>
        <button type="submit" :disabled="form.processing"
          class="text-white bg-indigo-700 hover:bg-indigo-800 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 disabled:opacity-50 dark:bg-indigo-600 dark:hover:bg-indigo-700 dark:focus:ring-indigo-800 inline-flex items-center gap-2">
          <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          Create Geo-Fence
        </button>
      </div>
    </form>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ devices: Array, googleMapKey: String });

const form = useForm({
  name: '', device_ids: [], shape: 'circle', alert_on: 'both',
  center_lat: null, center_lng: null, coordinates: [], radius: 500,
});

const mapContainer = ref(null);
const mapReady = ref(false);
const centerLat = ref('');
const centerLng = ref('');
const polygonCoords = ref([]);
const deviceDropdownOpen = ref(false);
const deviceSearchQuery = ref('');

// Device multi-select helpers
const filteredDevices = computed(() => {
  if (!deviceSearchQuery.value) return props.devices || [];
  const q = deviceSearchQuery.value.toLowerCase();
  return (props.devices || []).filter(d =>
    (d.device_name && d.device_name.toLowerCase().includes(q)) ||
    (d.imei && d.imei.toLowerCase().includes(q)) ||
    (d.tractor && d.tractor.no_plate && d.tractor.no_plate.toLowerCase().includes(q))
  );
});

function toggleDevice(id) {
  const idx = form.device_ids.indexOf(id);
  if (idx === -1) { form.device_ids.push(id); }
  else { form.device_ids.splice(idx, 1); }
}

function getDeviceLabel(id) {
  const d = (props.devices || []).find(dev => dev.id === id);
  if (!d) return `#${id}`;
  return d.device_name || d.imei || `#${id}`;
}

// Google Maps objects
let map = null;
let circleMarker = null;
let circleOverlay = null;
let polygonOverlay = null;
let polygonMarkers = [];

// Load Google Maps API
function loadGoogleMaps() {
  return new Promise((resolve, reject) => {
    if (window.google && window.google.maps) { resolve(); return; }
    if (!props.googleMapKey) { reject(new Error('No API key')); return; }
    window._initGeoMap = () => { resolve(); delete window._initGeoMap; };
    const s = document.createElement('script');
    s.src = `https://maps.googleapis.com/maps/api/js?key=${props.googleMapKey}&libraries=geometry,drawing&callback=_initGeoMap`;
    s.async = true; s.defer = true; s.onerror = reject;
    document.head.appendChild(s);
  });
}

// Initialize map
async function initMap() {
  try {
    await loadGoogleMaps();
    map = new google.maps.Map(mapContainer.value, {
      center: { lat: 14.17092, lng: 121.291831 },
      zoom: 7,
      mapTypeId: 'roadmap',
      mapTypeControl: true,
      mapTypeControlOptions: { style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR, position: google.maps.ControlPosition.TOP_RIGHT },
      streetViewControl: false,
      fullscreenControl: true,
    });
    mapReady.value = true;

    // Click handler
    map.addListener('click', (e) => {
      const lat = e.latLng.lat();
      const lng = e.latLng.lng();

      if (form.shape === 'circle') {
        setCircleCenter(lat, lng);
      } else {
        addPolygonPoint(lat, lng);
      }
    });
  } catch (e) {
    console.error('Failed to load Google Maps:', e);
  }
}

// ---- CIRCLE MODE ----
function setCircleCenter(lat, lng) {
  centerLat.value = lat;
  centerLng.value = lng;
  drawCircle();
}

function drawCircle() {
  if (!map || !centerLat.value || !centerLng.value) return;
  const pos = { lat: parseFloat(centerLat.value), lng: parseFloat(centerLng.value) };

  // Marker
  if (circleMarker) { circleMarker.setPosition(pos); }
  else {
    circleMarker = new google.maps.Marker({
      position: pos, map, draggable: true,
      icon: { path: google.maps.SymbolPath.CIRCLE, scale: 8, fillColor: '#4f46e5', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 2 },
    });
    circleMarker.addListener('dragend', (e) => {
      centerLat.value = e.latLng.lat();
      centerLng.value = e.latLng.lng();
      drawCircle();
    });
  }

  // Circle overlay
  if (circleOverlay) { circleOverlay.setCenter(pos); circleOverlay.setRadius(Number(form.radius) || 500); }
  else {
    circleOverlay = new google.maps.Circle({
      center: pos, radius: Number(form.radius) || 500, map,
      strokeColor: '#4f46e5', strokeOpacity: 0.8, strokeWeight: 2,
      fillColor: '#4f46e5', fillOpacity: 0.15, editable: true,
    });
    circleOverlay.addListener('radius_changed', () => {
      form.radius = Math.round(circleOverlay.getRadius());
    });
    circleOverlay.addListener('center_changed', () => {
      const c = circleOverlay.getCenter();
      centerLat.value = c.lat();
      centerLng.value = c.lng();
      if (circleMarker) circleMarker.setPosition(c);
    });
  }

  map.panTo(pos);
  if (map.getZoom() < 12) map.setZoom(14);
}

function clearCircle() {
  if (circleMarker) { circleMarker.setMap(null); circleMarker = null; }
  if (circleOverlay) { circleOverlay.setMap(null); circleOverlay = null; }
  centerLat.value = '';
  centerLng.value = '';
}

// ---- POLYGON MODE ----
function addPolygonPoint(lat, lng) {
  polygonCoords.value.push({ lat, lng });
  drawPolygon();
}

function removePolygonPoint(index) {
  polygonCoords.value.splice(index, 1);
  drawPolygon();
}

function drawPolygon() {
  // Clear old markers
  polygonMarkers.forEach(m => m.setMap(null));
  polygonMarkers = [];

  if (!map) return;

  // Draw vertex markers (draggable)
  polygonCoords.value.forEach((coord, i) => {
    const marker = new google.maps.Marker({
      position: { lat: parseFloat(coord.lat), lng: parseFloat(coord.lng) },
      map, draggable: true,
      label: { text: String(i + 1), color: '#fff', fontWeight: 'bold', fontSize: '11px' },
      icon: { path: google.maps.SymbolPath.CIRCLE, scale: 14, fillColor: '#4f46e5', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 2 },
    });
    marker.addListener('dragend', (e) => {
      polygonCoords.value[i] = { lat: e.latLng.lat(), lng: e.latLng.lng() };
      drawPolygon();
    });
    polygonMarkers.push(marker);
  });

  // Remove old polygon
  if (polygonOverlay) { polygonOverlay.setMap(null); polygonOverlay = null; }

  // Draw polygon if 3+ points
  if (polygonCoords.value.length >= 3) {
    const path = polygonCoords.value.map(c => ({ lat: parseFloat(c.lat), lng: parseFloat(c.lng) }));
    polygonOverlay = new google.maps.Polygon({
      paths: path, map, editable: true,
      strokeColor: '#4f46e5', strokeOpacity: 0.8, strokeWeight: 2,
      fillColor: '#4f46e5', fillOpacity: 0.15,
    });

    // Listen for vertex edits on the polygon itself
    google.maps.event.addListener(polygonOverlay.getPath(), 'set_at', syncPolygonPath);
    google.maps.event.addListener(polygonOverlay.getPath(), 'insert_at', syncPolygonPath);
  }
}

function syncPolygonPath() {
  if (!polygonOverlay) return;
  const path = polygonOverlay.getPath();
  const newCoords = [];
  for (let i = 0; i < path.getLength(); i++) {
    const p = path.getAt(i);
    newCoords.push({ lat: p.lat(), lng: p.lng() });
  }
  polygonCoords.value = newCoords;
  // Redraw markers to match
  polygonMarkers.forEach(m => m.setMap(null));
  polygonMarkers = [];
  newCoords.forEach((coord, i) => {
    const marker = new google.maps.Marker({
      position: { lat: coord.lat, lng: coord.lng },
      map, draggable: true,
      label: { text: String(i + 1), color: '#fff', fontWeight: 'bold', fontSize: '11px' },
      icon: { path: google.maps.SymbolPath.CIRCLE, scale: 14, fillColor: '#4f46e5', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 2 },
    });
    marker.addListener('dragend', (e) => {
      polygonCoords.value[i] = { lat: e.latLng.lat(), lng: e.latLng.lng() };
      drawPolygon();
    });
    polygonMarkers.push(marker);
  });
}

function clearPolygon() {
  if (polygonOverlay) { polygonOverlay.setMap(null); polygonOverlay = null; }
  polygonMarkers.forEach(m => m.setMap(null));
  polygonMarkers = [];
  polygonCoords.value = [];
}

// ---- SHAPE SWITCH ----
function onShapeChange() {
  clearCircle();
  clearPolygon();
}

function clearDrawing() {
  if (form.shape === 'circle') clearCircle();
  else clearPolygon();
}

// Sync coordinates to form
watch([centerLat, centerLng, polygonCoords, () => form.shape, () => form.radius], () => {
  if (form.shape === 'circle') {
    form.center_lat = centerLat.value ? parseFloat(centerLat.value) : null;
    form.center_lng = centerLng.value ? parseFloat(centerLng.value) : null;
    form.coordinates = [];
  } else {
    form.center_lat = null;
    form.center_lng = null;
    form.coordinates = polygonCoords.value
      .filter(c => c.lat && c.lng)
      .map(c => ({ lat: parseFloat(c.lat), lng: parseFloat(c.lng) }));
  }
}, { deep: true });

// Redraw circle when radius changes
watch(() => form.radius, () => {
  if (form.shape === 'circle' && circleOverlay) {
    circleOverlay.setRadius(Number(form.radius) || 500);
  }
});

const submit = () => { form.post('/geofences'); };

onMounted(async () => {
  await nextTick();
  await initMap();
});
</script>
