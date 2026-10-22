const tractorParameter = {
  name: 'tractor',
  in: 'path',
  required: true,
  type: 'string',
  values: 'Database ID, IMEI, or exact tractor name',
  description: 'A unique tractor identifier. URL-encode names containing spaces.',
};

const tractorTestField = {
  name: 'tractor',
  in: 'path',
  label: 'ID, IMEI, or exact name',
  required: true,
  placeholder: '42, 869066063771910, or Tractor North 1',
  default: '1',
};

const dateParameters = [
  { name: 'from', in: 'query', required: false, type: 'date', values: 'YYYY-MM-DD', description: 'Inclusive start date.' },
  { name: 'to', in: 'query', required: false, type: 'date', values: 'YYYY-MM-DD; must be on or after from', description: 'Inclusive end date.' },
];

const dateTestFields = [
  { name: 'from', in: 'query', type: 'date' },
  { name: 'to', in: 'query', type: 'date' },
];

const paginationParameters = (maximum, defaultValue) => [
  { name: 'per_page', in: 'query', required: false, type: 'integer', values: `1–${maximum}`, default: defaultValue, description: 'Number of records returned per page.' },
  { name: 'page', in: 'query', required: false, type: 'integer', values: '1 or greater', default: 1, description: 'Requested result page.' },
];

const alertFilterParameters = [
  { name: 'type', in: 'query', required: false, type: 'string', values: 'geofence_breach, maintenance_due, inactive, speed, custom, or a value returned by /alert-types', description: 'Exact alert type.' },
  { name: 'acknowledged', in: 'query', required: false, type: 'boolean', values: 'true, false, 1, 0', description: 'Filter by acknowledgement state.' },
  ...dateParameters,
  ...paginationParameters(100, 25),
];

const alertTestFields = [
  { name: 'type', in: 'query', placeholder: 'speed' },
  { name: 'acknowledged', in: 'query', options: [{ label: 'All states', value: '' }, { label: 'Unacknowledged', value: '0' }, { label: 'Acknowledged', value: '1' }] },
  { name: 'per_page', in: 'query', type: 'number', default: '10' },
];

export const apiEndpoints = [
  {
    category: 'Fleet', title: 'Fleet operational summary', path: '/summary', recommended: true,
    description: 'Lightweight totals for fleet dashboards, including tractor connectivity, alerts, and maintenance workload.',
    parameters: [], testFields: [],
    example: { data: { tractors: { total: 108, active: 104, online: 91, offline_or_stale: 13 }, devices: { total: 108, active: 105 }, alerts: { unacknowledged: 7, last_24_hours: 12 }, maintenance: { open: 4, completed_last_30_days: 18 }, generated_at: '2026-07-21T10:30:08+08:00' } },
  },
  {
    category: 'Fleet', title: 'Live view of all tractors', path: '/live/tractors', recommended: true,
    description: 'Map-ready latest positions and online, moving, stale, and alert status. Poll every 15 seconds and reuse next_changed_since for delta updates.',
    parameters: [
      { name: 'search', in: 'query', required: false, type: 'string', values: 'Any tractor name, plate, or IMEI fragment', description: 'Filters the live fleet.' },
      { name: 'active', in: 'query', required: false, type: 'boolean', values: 'true, false, 1, 0', default: true, description: 'Filters tractor active state.' },
      { name: 'online', in: 'query', required: false, type: 'boolean', values: 'true, false, 1, 0', description: 'Filters live connectivity state.' },
      { name: 'include_without_location', in: 'query', required: false, type: 'boolean', values: 'true, false, 1, 0', default: false, description: 'Includes tractors that have never reported GPS.' },
      { name: 'changed_since', in: 'query', required: false, type: 'datetime', values: 'ISO-8601 timestamp from meta.next_changed_since', description: 'Returns only markers changed after the cursor.' },
      { name: 'stale_after_seconds', in: 'query', required: false, type: 'integer', values: '30–86400', default: 300, description: 'Age at which a location is considered stale.' },
      { name: 'limit', in: 'query', required: false, type: 'integer', values: '1–2000', default: 1000, description: 'Maximum returned markers.' },
    ],
    testFields: [
      { name: 'search', in: 'query', placeholder: 'Plate, IMEI, or name' },
      { name: 'online', in: 'query', options: [{ label: 'All states', value: '' }, { label: 'Online only', value: '1' }, { label: 'Offline / stale', value: '0' }] },
      { name: 'stale_after_seconds', in: 'query', label: 'Stale seconds', type: 'number', default: '300' },
      { name: 'limit', in: 'query', type: 'number', default: '100' },
    ],
    example: { data: [{ tractor: { id: 42, name: 'Tractor North 1', plate_number: 'TRC-042', active: true }, device: { id: 27, imei: '869066063771910' }, position: { latitude: 14.5995, longitude: 120.9842, speed_kph: 12.4, direction_degrees: 92 }, status: { online: true, moving: true, stale: false, age_seconds: 8 }, unacknowledged_alerts: 1 }], meta: { returned: 1, recommended_poll_interval_seconds: 15, next_changed_since: '2026-07-21T10:30:08+08:00' } },
  },
  {
    category: 'Tractors', title: 'List and discover tractors', path: '/tractors', recommended: true,
    description: 'Paginated tractor directory used to discover IDs, IMEIs, names, specifications, and availability.',
    parameters: [
      { name: 'search', in: 'query', required: false, type: 'string', values: 'Any name, plate, IMEI, engine, or chassis fragment', description: 'Searches tractor identity fields.' },
      { name: 'active', in: 'query', required: false, type: 'boolean', values: 'true, false, 1, 0', description: 'Filters active status.' },
      ...paginationParameters(100, 25),
    ],
    testFields: [{ name: 'search', in: 'query', placeholder: 'Plate, IMEI, or name' }, { name: 'active', in: 'query', options: [{ label: 'All statuses', value: '' }, { label: 'Active', value: '1' }, { label: 'Inactive', value: '0' }] }, { name: 'per_page', in: 'query', type: 'number', default: '5' }],
    example: { data: [{ id: 42, name: 'Tractor North 1', plate_number: 'TRC-042', imei: '869066063771910', machine: { brand: 'Kubota', model: 'L4708' }, active: true }], meta: { current_page: 1, per_page: 25, total: 108 } },
  },
  {
    category: 'Tractors', title: 'Complete tractor details', path: '/tractors/{tractor}',
    description: 'Complete identifiers, specifications, implements, usage, maintenance status, delivery, insurance, device, groups, and images.',
    parameters: [tractorParameter], testFields: [tractorTestField],
    example: { data: { id: 42, name: 'Tractor North 1', plate_number: 'TRC-042', identifiers: { engine_number: 'EN-4221', chassis_number: 'CH-9012' }, machine: { brand: 'Kubota', model: 'L4708' }, usage: { total_distance_km: 1862.5, running_hours: 308.4, pms_status: 'ok' }, active: true } },
  },
  {
    category: 'Tracking', title: 'Latest live position', path: '/tractors/{tractor}/location',
    description: 'Most recent position, speed, direction, ignition, connectivity, and freshness for one tractor.',
    parameters: [tractorParameter], testFields: [tractorTestField],
    example: { data: { tractor: { id: 42, name: 'Tractor North 1' }, position: { latitude: 14.5995, longitude: 120.9842, speed_kph: 12.4, direction_degrees: 92 }, ignition_on: true, online: true, age_seconds: 8, stale: false } },
  },
  {
    category: 'Tracking', title: 'GPS location history', path: '/tractors/{tractor}/location-history',
    description: 'Paginated GPS pings for route playback and audit history, returned newest first.',
    parameters: [tractorParameter, ...dateParameters, ...paginationParameters(500, 100)],
    testFields: [tractorTestField, ...dateTestFields, { name: 'per_page', in: 'query', type: 'number', default: '10' }],
    example: { data: [{ id: 8841, latitude: 14.5995, longitude: 120.9842, speed_kph: 12.4, direction_degrees: 92, ignition_on: true, recorded_at: '2026-07-21T10:30:00+08:00' }], meta: { current_page: 1, per_page: 100, total: 420 } },
  },
  {
    category: 'Tracking', title: 'Mileage summary', path: '/tractors/{tractor}/mileage',
    description: 'Period totals, all-time odometer and running hours, trip averages, maximum speed, and daily mileage rollups.',
    parameters: [tractorParameter, ...dateParameters], testFields: [tractorTestField, ...dateTestFields],
    example: { data: { tractor: { id: 42, name: 'Tractor North 1' }, range: { from: '2026-07-01', to: '2026-07-21' }, summary: { mileage_km: 286.4, runtime_hours: 17.83, maximum_speed_kph: 42, trips: 33 }, all_time: { odometer_km: 1862.5, running_hours: 308.4 }, daily: [{ date: '2026-07-21', mileage_km: 18.2, runtime_hours: 1.4, trips: 2 }] } },
  },
  {
    category: 'Tracking', title: 'Trip track data', path: '/tractors/{tractor}/track-data',
    description: 'Paginated provider trip segments with start/end positions, mileage, runtime, and maximum speed.',
    parameters: [tractorParameter, ...dateParameters, ...paginationParameters(100, 25)],
    testFields: [tractorTestField, ...dateTestFields, { name: 'per_page', in: 'query', type: 'number', default: '10' }],
    example: { data: [{ id: 880, tractor_id: 42, start: { latitude: 14.5995, longitude: 120.9842, recorded_at: '2026-07-21T08:00:00+08:00' }, end: { latitude: 14.65, longitude: 121.01, recorded_at: '2026-07-21T09:00:00+08:00' }, mileage_km: 12.5, runtime_hours: 1, maximum_speed_kph: 40 }] },
  },
  {
    category: 'Alerts', title: 'Alerts for one tractor', path: '/tractors/{tractor}/alerts',
    description: 'Paginated alerts linked to a tractor or its tracking device.',
    parameters: [tractorParameter, ...alertFilterParameters], testFields: [tractorTestField, ...alertTestFields],
    example: { data: [{ id: 991, type: 'speed', title: 'Speed threshold exceeded', message: 'Tractor reached 46 km/h.', acknowledged: false, created_at: '2026-07-21T10:28:12+08:00' }] },
  },
  {
    category: 'Maintenance', title: 'Maintenance history', path: '/tractors/{tractor}/maintenance',
    description: 'Service and PMS records with issue types, status, costs, checklist, technician, readings, and images.',
    parameters: [tractorParameter, { name: 'status', in: 'query', required: false, type: 'enum', values: 'documentation, scheduled, in_progress, completed, cancelled', description: 'Exact maintenance workflow status.' }, ...dateParameters, ...paginationParameters(100, 25)],
    testFields: [tractorTestField, { name: 'status', in: 'query', options: [{ label: 'All statuses', value: '' }, { label: 'Documentation', value: 'documentation' }, { label: 'Scheduled', value: 'scheduled' }, { label: 'In progress', value: 'in_progress' }, { label: 'Completed', value: 'completed' }, { label: 'Cancelled', value: 'cancelled' }] }, { name: 'per_page', in: 'query', type: 'number', default: '10' }],
    example: { data: [{ id: 71, maintenance_date: '2026-07-18', status: 'completed', issue_type: { id: 1, name: 'Engine Oil' }, description: '250-hour PMS', cost: 4800, running_hours: 251.2 }] },
  },
  {
    category: 'Alerts', title: 'General fleet alerts', path: '/alerts',
    description: 'Cross-fleet alert feed with tractor, type, acknowledgement, date, and pagination filters.',
    parameters: [{ name: 'tractor_id', in: 'query', required: false, type: 'integer', values: 'Existing tractor database ID', description: 'Limits results to one tractor.' }, ...alertFilterParameters],
    testFields: [{ name: 'tractor_id', in: 'query', label: 'Tractor ID', type: 'number' }, ...alertTestFields],
    example: { data: [{ id: 991, type: 'speed', title: 'Speed threshold exceeded', tractor: { id: 42, name: 'Tractor North 1' }, acknowledged: false, created_at: '2026-07-21T10:28:12+08:00' }] },
  },
  {
    category: 'Alerts', title: 'Available alert types', path: '/alert-types',
    description: 'Alert types currently stored in TANOD with total and unacknowledged counts for dynamic filters.',
    parameters: [], testFields: [],
    example: { data: [{ type: 'geofence_breach', total: 18, unacknowledged: 2 }, { type: 'maintenance_due', total: 9, unacknowledged: 1 }, { type: 'speed', total: 42, unacknowledged: 4 }] },
  },

  // ═══════════════════════════════════════════
  // RCEF Integration Endpoints
  // ═══════════════════════════════════════════
  {
    category: 'RCEF', title: 'GetLocation', path: '/tractors/{tractor}/location-history', recommended: false,
    description: 'Get list of recorded locations (longitude & latitude) and other details per machine on a specified date range.',
    parameters: [
      { name: 'tractor', in: 'path', required: true, type: 'string', values: 'Tractor ID, IMEI, or Serial Number (id_no)', description: 'Identifies the machine.' },
      { name: 'date_from', in: 'query', required: true, type: 'datetime', values: 'YYYY-MM-DD', description: 'Start date.' },
      { name: 'date_to', in: 'query', required: true, type: 'datetime', values: 'YYYY-MM-DD', description: 'End date.' },
    ],
    testFields: [
      { name: 'tractor', in: 'path', required: true, label: 'ID, IMEI, or Serial Number', default: '1' },
      { name: 'date_from', in: 'query', type: 'date' },
      { name: 'date_to', in: 'query', type: 'date' },
    ],
    example: { data: [{ latitude: 14.5995, longitude: 120.9842, speed_kph: 12.4, ignition_on: true, recorded_at: '2026-07-21T10:30:08+00:00', Status: 'Moving', Serial_no: 'SN-2024-001' }] },
  },
  {
    category: 'RCEF', title: 'GetCurrentLocation', path: '/tractors/{tractor}/location', recommended: false,
    description: 'Get current location (longitude & latitude) and other details of a specific machine.',
    parameters: [
      { name: 'tractor', in: 'path', required: true, type: 'string', values: 'Tractor ID, IMEI, or Serial Number (id_no)', description: 'Identifies the machine.' },
    ],
    testFields: [
      { name: 'tractor', in: 'path', required: true, label: 'ID, IMEI, or Serial Number', default: '1' },
    ],
    example: { data: { position: { latitude: 14.5995, longitude: 120.9842, speed_kph: 12.4 }, ignition_on: true, online: true, recorded_at: '2026-07-21T10:30:08+00:00', Status: 'Moving', Serial_no: 'SN-2024-001' } },
  },
  {
    category: 'RCEF', title: 'GetMachineStatus', path: '/tractors/{tractor}/status-summary', recommended: false,
    description: 'Get list of machine statuses (Working, Off, Idle, Key On, Long Idle, etc.) with corresponding total accumulated time in minutes on a specified date range.',
    parameters: [
      { name: 'tractor', in: 'path', required: true, type: 'string', values: 'Tractor ID, IMEI, or Serial Number (id_no)', description: 'Identifies the machine.' },
      { name: 'date_from', in: 'query', required: true, type: 'datetime', values: 'YYYY-MM-DD', description: 'Start date.' },
      { name: 'date_to', in: 'query', required: true, type: 'datetime', values: 'YYYY-MM-DD', description: 'End date.' },
    ],
    testFields: [
      { name: 'tractor', in: 'path', required: true, label: 'ID, IMEI, or Serial Number', default: '1' },
      { name: 'date_from', in: 'query', type: 'date' },
      { name: 'date_to', in: 'query', type: 'date' },
    ],
    example: { data: [{ Status: 'Moving', Time_minutes: 493, Serial_no: 'SN-2024-001' }, { Status: 'Idle', Time_minutes: 271, Serial_no: 'SN-2024-001' }] },
  },
  {
    category: 'RCEF', title: 'GetMachineInfo', path: '/tractors/{tractor}', recommended: false,
    description: 'Get current information of a specific machine including brand, type, model, engine hours, odometer, and last known position.',
    parameters: [
      { name: 'tractor', in: 'path', required: true, type: 'string', values: 'Tractor ID, IMEI, or Serial Number (id_no)', description: 'Identifies the machine.' },
    ],
    testFields: [
      { name: 'tractor', in: 'path', required: true, label: 'ID, IMEI, or Serial Number', default: '1' },
    ],
    example: { data: { id: 42, name: 'Tractor North 1', machine: { brand: 'Kubota', model: 'M9540' }, usage: { total_distance_km: 8420.3, running_hours: 1250.5 }, Last_Position: { Longitude: 120.9842, Latitude: 14.5995 }, identifiers: { id_number: 'SN-2024-001' } } },
  },
  {
    category: 'RCEF', title: 'GetTotalUtilizationData', path: '/tractors/{tractor}/utilization', recommended: false,
    description: 'Get list of utilization data totals (working hours, engine hours, travel time, operating time, distance) on a specified date range.',
    parameters: [
      { name: 'tractor', in: 'path', required: true, type: 'string', values: 'Tractor ID, IMEI, or Serial Number (id_no)', description: 'Identifies the machine.' },
      { name: 'date_from', in: 'query', required: true, type: 'datetime', values: 'YYYY-MM-DD', description: 'Start date.' },
      { name: 'date_to', in: 'query', required: true, type: 'datetime', values: 'YYYY-MM-DD', description: 'End date.' },
    ],
    testFields: [
      { name: 'tractor', in: 'path', required: true, label: 'ID, IMEI, or Serial Number', default: '1' },
      { name: 'date_from', in: 'query', type: 'date' },
      { name: 'date_to', in: 'query', type: 'date' },
    ],
    example: { data: { Working_Hours: 45.2, Engine_Hours: 48.0, Travel_Time_minutes: 180, Operating_Time_minutes: 2700, Distance_km: 320.5, Serial_no: 'SN-2024-001' } },
  },
  {
    category: 'RCEF', title: 'GetMaintenanceStatus', path: '/tractors/{tractor}/maintenance-status', recommended: false,
    description: 'Get maintenance status (Due, Due Soon, Not Due) for a specific machine.',
    parameters: [
      { name: 'tractor', in: 'path', required: true, type: 'string', values: 'Tractor ID, IMEI, or Serial Number (id_no)', description: 'Identifies the machine.' },
    ],
    testFields: [
      { name: 'tractor', in: 'path', required: true, label: 'ID, IMEI, or Serial Number', default: '1' },
    ],
    example: { data: { Status: 'Due', Serial_No: 'SN-2024-001' } },
  },
  {
    category: 'RCEF', title: 'GetEventsNotifications', path: '/tractors/{tractor}/events', recommended: false,
    description: 'Get list of events/notifications on a specific machine on a specified date range.',
    parameters: [
      { name: 'tractor', in: 'path', required: true, type: 'string', values: 'Tractor ID, IMEI, or Serial Number (id_no)', description: 'Identifies the machine.' },
      { name: 'date_from', in: 'query', required: false, type: 'datetime', values: 'YYYY-MM-DD', description: 'Start date.' },
      { name: 'date_to', in: 'query', required: false, type: 'datetime', values: 'YYYY-MM-DD', description: 'End date.' },
    ],
    testFields: [
      { name: 'tractor', in: 'path', required: true, label: 'ID, IMEI, or Serial Number', default: '1' },
      { name: 'date_from', in: 'query', type: 'date' },
      { name: 'date_to', in: 'query', type: 'date' },
    ],
    example: { data: [{ Text: 'Geofence breach detected', Type: 'geofence_breach', Timestamp: '2026-07-21T10:30:08+08:00', Received_Time: '2026-07-21T10:30:12+08:00', Serial_No: 'SN-2024-001' }] },
  },
  {
    category: 'RCEF', title: 'CheckIfWithinBoundaries', path: '/tractors/{tractor}/within-boundaries', recommended: false,
    description: 'Check if a machine is within the boundaries of a specific province. Uses Google Geocoding to determine the province from the tractor\'s current location.',
    parameters: [
      { name: 'tractor', in: 'path', required: true, type: 'string', values: 'Tractor ID, IMEI, or Serial Number (id_no)', description: 'Identifies the machine.' },
      { name: 'province', in: 'query', required: false, type: 'string', values: 'Province name (e.g. Laguna, Batangas, Pampanga)', description: 'Province to check against.' },
    ],
    testFields: [
      { name: 'tractor', in: 'path', required: true, label: 'ID, IMEI, or Serial Number', default: '1' },
      { name: 'province', in: 'query', options: [
        { label: 'Select Province', value: '' },
        { label: 'Abra', value: 'Abra' },
        { label: 'Agusan del Norte', value: 'Agusan del Norte' },
        { label: 'Agusan del Sur', value: 'Agusan del Sur' },
        { label: 'Aklan', value: 'Aklan' },
        { label: 'Albay', value: 'Albay' },
        { label: 'Antique', value: 'Antique' },
        { label: 'Apayao', value: 'Apayao' },
        { label: 'Aurora', value: 'Aurora' },
        { label: 'Basilan', value: 'Basilan' },
        { label: 'Bataan', value: 'Bataan' },
        { label: 'Batanes', value: 'Batanes' },
        { label: 'Batangas', value: 'Batangas' },
        { label: 'Benguet', value: 'Benguet' },
        { label: 'Biliran', value: 'Biliran' },
        { label: 'Bohol', value: 'Bohol' },
        { label: 'Bukidnon', value: 'Bukidnon' },
        { label: 'Bulacan', value: 'Bulacan' },
        { label: 'Cagayan', value: 'Cagayan' },
        { label: 'Camarines Norte', value: 'Camarines Norte' },
        { label: 'Camarines Sur', value: 'Camarines Sur' },
        { label: 'Camiguin', value: 'Camiguin' },
        { label: 'Capiz', value: 'Capiz' },
        { label: 'Catanduanes', value: 'Catanduanes' },
        { label: 'Cavite', value: 'Cavite' },
        { label: 'Cebu', value: 'Cebu' },
        { label: 'Cotabato', value: 'Cotabato' },
        { label: 'Davao de Oro', value: 'Davao de Oro' },
        { label: 'Davao del Norte', value: 'Davao del Norte' },
        { label: 'Davao del Sur', value: 'Davao del Sur' },
        { label: 'Davao Occidental', value: 'Davao Occidental' },
        { label: 'Davao Oriental', value: 'Davao Oriental' },
        { label: 'Dinagat Islands', value: 'Dinagat Islands' },
        { label: 'Eastern Samar', value: 'Eastern Samar' },
        { label: 'Guimaras', value: 'Guimaras' },
        { label: 'Ifugao', value: 'Ifugao' },
        { label: 'Ilocos Norte', value: 'Ilocos Norte' },
        { label: 'Ilocos Sur', value: 'Ilocos Sur' },
        { label: 'Iloilo', value: 'Iloilo' },
        { label: 'Isabela', value: 'Isabela' },
        { label: 'Kalinga', value: 'Kalinga' },
        { label: 'La Union', value: 'La Union' },
        { label: 'Laguna', value: 'Laguna' },
        { label: 'Lanao del Norte', value: 'Lanao del Norte' },
        { label: 'Lanao del Sur', value: 'Lanao del Sur' },
        { label: 'Leyte', value: 'Leyte' },
        { label: 'Maguindanao', value: 'Maguindanao' },
        { label: 'Marinduque', value: 'Marinduque' },
        { label: 'Masbate', value: 'Masbate' },
        { label: 'Misamis Occidental', value: 'Misamis Occidental' },
        { label: 'Misamis Oriental', value: 'Misamis Oriental' },
        { label: 'Mountain Province', value: 'Mountain Province' },
        { label: 'Negros Occidental', value: 'Negros Occidental' },
        { label: 'Negros Oriental', value: 'Negros Oriental' },
        { label: 'Northern Samar', value: 'Northern Samar' },
        { label: 'Nueva Ecija', value: 'Nueva Ecija' },
        { label: 'Nueva Vizcaya', value: 'Nueva Vizcaya' },
        { label: 'Occidental Mindoro', value: 'Occidental Mindoro' },
        { label: 'Oriental Mindoro', value: 'Oriental Mindoro' },
        { label: 'Palawan', value: 'Palawan' },
        { label: 'Pampanga', value: 'Pampanga' },
        { label: 'Pangasinan', value: 'Pangasinan' },
        { label: 'Quezon', value: 'Quezon' },
        { label: 'Quirino', value: 'Quirino' },
        { label: 'Rizal', value: 'Rizal' },
        { label: 'Romblon', value: 'Romblon' },
        { label: 'Samar', value: 'Samar' },
        { label: 'Sarangani', value: 'Sarangani' },
        { label: 'Siquijor', value: 'Siquijor' },
        { label: 'Sorsogon', value: 'Sorsogon' },
        { label: 'South Cotabato', value: 'South Cotabato' },
        { label: 'Southern Leyte', value: 'Southern Leyte' },
        { label: 'Sultan Kudarat', value: 'Sultan Kudarat' },
        { label: 'Sulu', value: 'Sulu' },
        { label: 'Surigao del Norte', value: 'Surigao del Norte' },
        { label: 'Surigao del Sur', value: 'Surigao del Sur' },
        { label: 'Tarlac', value: 'Tarlac' },
        { label: 'Tawi-Tawi', value: 'Tawi-Tawi' },
        { label: 'Zambales', value: 'Zambales' },
        { label: 'Zamboanga del Norte', value: 'Zamboanga del Norte' },
        { label: 'Zamboanga del Sur', value: 'Zamboanga del Sur' },
        { label: 'Zamboanga Sibugay', value: 'Zamboanga Sibugay' },
      ] },
    ],
    example: { data: { Within_Boundaries: true, Current_Longitude: 120.9842, Current_Latitude: 14.5995 } },
  },
];

export const apiCategories = ['All', 'Fleet', 'Tractors', 'Tracking', 'Alerts', 'Maintenance', 'RCEF'];
