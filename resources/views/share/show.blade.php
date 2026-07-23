<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Live Location Share — TANOD</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .pulse-ring { animation: pulse-ring 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite; }
        @keyframes pulse-ring {
            0% { transform: scale(0.5); opacity: 0.8; }
            80%, 100% { transform: scale(2.5); opacity: 0; }
        }
    </style>
</head>
<body class="h-full bg-gray-50">
    <!-- Top Bar -->
    <div class="fixed top-0 left-0 right-0 z-50 bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 h-14 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-sm font-bold text-gray-900">{{ $share->device_name }}</h1>
                    <p class="text-xs text-gray-500">Shared Live Location</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div id="statusBadge" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                    <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                    Loading...
                </div>
                <div id="expiryBadge" class="text-xs text-gray-500 font-medium">
                    <svg class="w-3.5 h-3.5 inline mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span id="expiryText">—</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Container -->
    <div id="map" class="w-full h-full pt-14"></div>

    <!-- Info Card (bottom-left overlay) -->
    <div id="infoCard" class="fixed bottom-6 left-4 right-4 sm:left-6 sm:right-auto sm:w-96 bg-white rounded-xl shadow-2xl border border-gray-200 overflow-hidden z-40 transition-all">
        <!-- Device Header -->
        <div class="px-5 py-4 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div id="deviceIcon" class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                        <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-1h3.05a2.5 2.5 0 014.9 0H19a1 1 0 001-1v-5a1 1 0 00-.293-.707l-3-3A1 1 0 0016 4H3z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h2 id="deviceName" class="text-base font-bold text-gray-900 truncate">{{ $share->device_name }}</h2>
                    <p id="deviceImei" class="text-xs text-gray-500 font-mono">{{ $share->imei }}</p>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-3 divide-x divide-gray-100 border-b border-gray-100">
            <div class="px-4 py-3 text-center">
                <p class="text-xs text-gray-500 mb-0.5">Speed</p>
                <p id="speedValue" class="text-lg font-bold text-gray-900">—</p>
                <p class="text-[10px] text-gray-400">km/h</p>
            </div>
            <div class="px-4 py-3 text-center">
                <p class="text-xs text-gray-500 mb-0.5">Direction</p>
                <p id="directionValue" class="text-lg font-bold text-gray-900">—</p>
                <p class="text-[10px] text-gray-400">degrees</p>
            </div>
            <div class="px-4 py-3 text-center">
                <p class="text-xs text-gray-500 mb-0.5">Last Seen</p>
                <p id="lastSeenValue" class="text-sm font-bold text-gray-900">—</p>
                <p class="text-[10px] text-gray-400" id="lastSeenAgo">—</p>
            </div>
        </div>

        <!-- Location -->
        <div class="px-5 py-3">
            <div class="flex items-start gap-2">
                <svg class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <div>
                    <p id="addressText" class="text-sm text-gray-700 leading-snug">Loading address...</p>
                    <p id="coordsText" class="text-xs text-gray-400 font-mono mt-0.5">—</p>
                </div>
            </div>
        </div>

        <!-- Refresh indicator -->
        <div class="px-5 py-2 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
            <span class="text-xs text-gray-400 flex items-center gap-1">
                <svg class="w-3 h-3 animate-spin" id="refreshSpinner" style="display:none;" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span id="refreshText">Auto-refresh: <span id="countdown">20</span>s</span>
            </span>
            <span class="text-xs font-medium text-indigo-600">TANOD Tracker</span>
        </div>
    </div>

    <script>
        const TOKEN = "{{ $share->token }}";
        const GOOGLE_MAP_KEY = "{{ $googleMapKey }}";
        const EXPIRES_AT = new Date("{{ $share->expires_at->toIso8601String() }}");

        const DISPLAY_TIME_ZONE = 'Asia/Manila';
        const GOOGLE_MAP_DEMO_ID = 'DEMO_MAP_ID';
        const TRACTOR_MARKER_IMAGES = {
            moving: @json(asset('images/green_tractor.png')),
            idling: @json(asset('images/yellow_tractor.png')),
            parked: @json(asset('images/yellow_tractor.png')),
            offline: @json(asset('images/red_tractor.png')),
        };

        let map, marker, infoWindow;
        let AdvancedMarkerElementClass = null;
        let refreshTimer = null;
        let countdownTimer = null;
        let countdownValue = 20;
        let currentDevice = @json($device);

        // Load Google Maps
        function loadGoogleMaps() {
            return new Promise((resolve, reject) => {
                if (window.google && window.google.maps) { resolve(); return; }
                window.initMap = () => { resolve(); delete window.initMap; };
                const s = document.createElement('script');
                s.src = `https://maps.googleapis.com/maps/api/js?key=${GOOGLE_MAP_KEY}&libraries=marker&loading=async&v=weekly&callback=initMap`;
                s.async = true; s.defer = true; s.onerror = reject;
                document.head.appendChild(s);
            });
        }

        async function loadAdvancedMarkerLibrary() {
            if (AdvancedMarkerElementClass) {
                return;
            }

            const { AdvancedMarkerElement } = await google.maps.importLibrary('marker');
            AdvancedMarkerElementClass = AdvancedMarkerElement;
        }

        // Init
        async function init() {
            await loadGoogleMaps();
            await loadAdvancedMarkerLibrary();

            const lat = currentDevice?.lat ? parseFloat(currentDevice.lat) : 14.17;
            const lng = currentDevice?.lng ? parseFloat(currentDevice.lng) : 121.29;

            map = new google.maps.Map(document.getElementById('map'), {
                center: { lat, lng },
                zoom: 16,
                mapTypeId: 'satellite',
                mapTypeControl: true,
                streetViewControl: false,
                fullscreenControl: true,
                zoomControl: true,
                mapId: GOOGLE_MAP_DEMO_ID,
            });

            if (currentDevice?.lat && currentDevice?.lng) {
                createMarker(lat, lng);
                updateUI(currentDevice);
                reverseGeocode(lat, lng);
            }

            updateExpiryBadge();
            startRefreshCycle();
        }

        function createStatusMarkerContent(status) {
            const wrapper = document.createElement('div');
            wrapper.style.width = '38px';
            wrapper.style.height = '38px';
            wrapper.style.display = 'flex';
            wrapper.style.alignItems = 'center';
            wrapper.style.justifyContent = 'center';
            wrapper.style.transform = 'translate(-50%, -100%)';
            wrapper.style.userSelect = 'none';

            const image = document.createElement('img');
            image.src = TRACTOR_MARKER_IMAGES[status] || TRACTOR_MARKER_IMAGES.offline;
            image.alt = `${status || 'offline'} tractor`;
            image.width = 38;
            image.height = 38;
            image.draggable = false;
            image.style.display = 'block';
            image.style.width = '38px';
            image.style.height = '38px';
            image.style.objectFit = 'contain';
            const statusFilter = status === 'parked' ? 'hue-rotate(155deg) saturate(0.9)' : '';
            image.style.filter = `${statusFilter} drop-shadow(0 8px 14px rgba(15, 23, 42, 0.32))`.trim();

            wrapper.appendChild(image);

            return wrapper;
        }

        function createMarker(lat, lng) {
            marker = new AdvancedMarkerElementClass({
                position: { lat, lng },
                map: map,
                content: createStatusMarkerContent(currentDevice?.status || 'offline'),
                title: currentDevice?.device_name || 'Device',
            });
        }

        function updateUI(device) {
            if (!device) return;
            currentDevice = device;

            // Status badge
            const badge = document.getElementById('statusBadge');
            const statusColors = {
                moving: { bg: 'bg-green-100', text: 'text-green-800', dot: 'bg-green-500', label: 'Moving' },
                idling: { bg: 'bg-yellow-100', text: 'text-yellow-800', dot: 'bg-yellow-500', label: 'Idling' },
                parked: { bg: 'bg-sky-100', text: 'text-sky-800', dot: 'bg-sky-500', label: 'Parked' },
                offline: { bg: 'bg-red-100', text: 'text-red-800', dot: 'bg-red-500', label: 'Offline' },
            };
            const sc = statusColors[device.status] || statusColors.offline;
            badge.className = `inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ${sc.bg} ${sc.text}`;
            badge.innerHTML = `<span class="w-2 h-2 rounded-full ${sc.dot}"></span>${sc.label}`;

            // Device icon
            const icon = document.getElementById('deviceIcon');
            const iconColors = { moving: 'bg-green-100', idling: 'bg-yellow-100', parked: 'bg-sky-100', offline: 'bg-red-100' };
            const iconTextColors = { moving: 'text-green-600', idling: 'text-yellow-600', parked: 'text-sky-600', offline: 'text-red-600' };
            icon.className = `w-12 h-12 ${iconColors[device.status] || 'bg-gray-100'} rounded-xl flex items-center justify-center flex-shrink-0`;
            icon.querySelector('svg').className = `w-6 h-6 ${iconTextColors[device.status] || 'text-gray-500'}`;

            // Stats
            document.getElementById('speedValue').textContent = device.status === 'moving' ? (device.speed ?? '—') : '—';
            document.getElementById('directionValue').textContent = device.direction ?? '—';

            if (device.heartbeat_at) {
                const dt = new Date(device.heartbeat_at);
                document.getElementById('lastSeenValue').textContent = dt.toLocaleTimeString('en-PH', {
                    timeZone: DISPLAY_TIME_ZONE,
                    hour: '2-digit',
                    minute: '2-digit',
                });
                const ago = Math.round((Date.now() - dt.getTime()) / 60000);
                document.getElementById('lastSeenAgo').textContent = ago < 60 ? `${ago}m ago` : `${Math.floor(ago/60)}h ago`;
            }

            // Coords
            if (device.lat && device.lng) {
                document.getElementById('coordsText').textContent =
                    `${parseFloat(device.lat).toFixed(6)}, ${parseFloat(device.lng).toFixed(6)}`;
            }

            // Update marker
            if (device.lat && device.lng) {
                const pos = new google.maps.LatLng(parseFloat(device.lat), parseFloat(device.lng));

                if (!marker) {
                    createMarker(pos.lat(), pos.lng());
                }

                marker.position = pos;
                marker.content = createStatusMarkerContent(device.status);
                map.panTo(pos);
            }
        }

        function reverseGeocode(lat, lng) {
            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({ location: { lat, lng } }, (results, status) => {
                const el = document.getElementById('addressText');
                if (status === 'OK' && results?.[0]) {
                    el.textContent = results[0].formatted_address;
                } else {
                    el.textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                }
            });
        }

        function updateExpiryBadge() {
            const now = new Date();
            const diff = EXPIRES_AT - now;
            const el = document.getElementById('expiryText');

            if (diff <= 0) {
                el.textContent = 'Expired';
                window.location.reload();
                return;
            }

            const mins = Math.floor(diff / 60000);
            const hrs = Math.floor(mins / 60);
            if (hrs > 0) {
                el.textContent = `Expires in ${hrs}h ${mins % 60}m`;
            } else {
                el.textContent = `Expires in ${mins}m`;
            }
        }

        async function fetchData() {
            try {
                document.getElementById('refreshSpinner').style.display = '';
                const res = await fetch(`/share/${TOKEN}/data`);

                if (res.status === 410) {
                    window.location.reload();
                    return;
                }

                const data = await res.json();
                if (data.expired) {
                    window.location.reload();
                    return;
                }

                if (data.device) {
                    updateUI(data.device);
                    if (data.device.lat && data.device.lng) {
                        reverseGeocode(parseFloat(data.device.lat), parseFloat(data.device.lng));
                    }
                }

                updateExpiryBadge();
            } catch (e) {
                console.error('Refresh failed:', e);
            } finally {
                document.getElementById('refreshSpinner').style.display = 'none';
            }
        }

        function startRefreshCycle() {
            countdownValue = 20;
            countdownTimer = setInterval(() => {
                countdownValue = Math.max(0, countdownValue - 1);
                document.getElementById('countdown').textContent = countdownValue;
            }, 1000);

            refreshTimer = setInterval(() => {
                fetchData();
                countdownValue = 20;
            }, 20000);
        }

        init();
    </script>
</body>
</html>
