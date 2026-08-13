<template>
  <div class="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-white">
    <Head title="Integration API Documentation" />

    <div v-if="!authorized" class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-12">
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.16),_transparent_36%),radial-gradient(circle_at_bottom_right,_rgba(59,130,246,0.12),_transparent_38%)]"></div>
      <div class="relative w-full max-w-md">
        <div class="mb-6 text-center">
          <img src="/images/logo.png" alt="TANOD" class="mx-auto h-14 w-auto" />
          <div class="mt-5 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-3 py-1 text-xs font-semibold text-emerald-700 shadow-sm">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
            Third-party Integration API
          </div>
          <h1 class="mt-4 text-3xl font-bold tracking-tight">Developer documentation</h1>
          <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Enter the API token issued by a TANOD administrator to access the endpoint reference and testing console.</p>
        </div>

        <form class="rounded-3xl border border-white/60 bg-white/90 p-6 shadow-2xl shadow-slate-200/70 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90 dark:shadow-black/20" @submit.prevent="authenticate">
          <label for="documentation-token" class="mb-2 block text-xs font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">Issued API token</label>
          <div class="relative">
            <input id="documentation-token" v-model="accessToken" :type="showAccessToken ? 'text' : 'password'" required autocomplete="off" placeholder="Paste your token here" class="w-full rounded-xl border-slate-200 bg-slate-50 py-3 pl-4 pr-12 font-mono text-xs focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950" />
            <button type="button" class="absolute inset-y-0 right-0 px-4 text-slate-400 hover:text-slate-700" @click="showAccessToken = !showAccessToken">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c-1.5 4-4.5 6-9 6s-7.5-2-9-6c1.5-4 4.5-6 9-6s7.5 2 9 6z"/></svg>
            </button>
          </div>
          <p v-if="accessError" class="mt-2 text-xs leading-5 text-red-600">{{ accessError }}</p>
          <button type="submit" :disabled="authenticating" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 hover:bg-emerald-700 disabled:opacity-60">
            <svg v-if="authenticating" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
            {{ authenticating ? 'Verifying token…' : 'Access documentation' }}
          </button>
          <div class="mt-5 flex items-start gap-2 rounded-xl bg-slate-50 p-3 text-[11px] leading-5 text-slate-500 dark:bg-slate-950 dark:text-slate-400">
            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4"/></svg>
            Your token is verified securely and is not included in the page URL.
          </div>
        </form>
      </div>
    </div>

    <template v-else>
      <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/90 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/90">
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 sm:px-6 lg:px-8">
          <img src="/images/logo.png" alt="TANOD" class="h-9 w-auto" />
          <div class="min-w-0 flex-1 border-l border-slate-200 pl-4 dark:border-slate-700">
            <p class="truncate text-sm font-bold">Integration API</p>
            <p class="truncate text-[11px] text-slate-500">Authenticated as {{ tokenName }}</p>
          </div>
          <span class="hidden rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-bold text-emerald-700 sm:inline dark:bg-emerald-950 dark:text-emerald-300">v1 · Read only</span>
          <button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="logout">Exit docs</button>
        </div>
      </header>

      <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <section class="relative overflow-hidden rounded-3xl bg-slate-950 px-6 py-10 text-white shadow-xl sm:px-10">
          <div class="absolute -right-20 -top-24 h-72 w-72 rounded-full bg-emerald-500/20 blur-3xl"></div>
          <div class="relative max-w-3xl">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-400">TANOD Developer Platform</p>
            <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-5xl">Build with live tractor intelligence.</h1>
            <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-300 sm:text-base">Use a stable, read-only API for fleet status, tractor records, live locations, route history, mileage, maintenance, and alerts.</p>
            <div class="mt-6 flex flex-wrap gap-2">
              <span class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs"><strong>12</strong> endpoints</span>
              <span class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs"><strong>120</strong> requests/minute</span>
              <span class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs"><strong>Bearer</strong> authentication</span>
            </div>
          </div>
        </section>

        <div class="mt-8 grid gap-8 lg:grid-cols-[230px_minmax(0,1fr)]">
          <aside class="lg:sticky lg:top-20 lg:self-start">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
              <label class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Search reference</label>
              <input v-model="search" placeholder="Search endpoints…" class="mt-2 w-full rounded-lg border-slate-200 bg-slate-50 py-2 text-xs focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950" />
              <nav class="mt-4 space-y-1">
                <button v-for="category in apiCategories" :key="category" type="button" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-xs font-semibold" :class="selectedCategory === category ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'" @click="selectedCategory = category">
                  {{ category }}
                  <span class="opacity-70">{{ categoryCount(category) }}</span>
                </button>
              </nav>
            </div>
          </aside>

          <div class="min-w-0 space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
              <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                  <p class="text-xs font-bold uppercase tracking-wide text-emerald-600">Quick start</p>
                  <h2 class="mt-1 text-xl font-bold">Authenticate with a Bearer token</h2>
                  <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Send the issued token in every request. Keep it on a secure backend and never embed it in public client-side code.</p>
                </div>
                <button type="button" class="shrink-0 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800" @click="copy(curlExample, 'curl')">{{ copied === 'curl' ? 'Copied' : 'Copy cURL' }}</button>
              </div>
              <pre class="mt-4 overflow-x-auto rounded-xl bg-slate-950 p-5 text-xs leading-6 text-slate-300"><code>{{ curlExample }}</code></pre>
              <div class="mt-5">
                <label for="tester-token" class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-300">Token used by the built-in tester</label>
                <input id="tester-token" v-model="testToken" type="password" autocomplete="off" placeholder="Re-enter the token if this field is empty" class="w-full rounded-xl border-slate-200 bg-slate-50 font-mono text-xs focus:border-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950" />
                <p class="mt-1.5 text-[11px] text-slate-400">Stored only in this tab’s session storage and sent directly as a Bearer token.</p>
              </div>
            </section>

            <div class="flex items-end justify-between">
              <div><p class="text-xs font-bold uppercase tracking-wide text-emerald-600">Endpoint reference</p><h2 class="mt-1 text-2xl font-bold">{{ filteredEndpoints.length }} endpoints</h2></div>
              <code class="hidden text-xs text-slate-500 sm:block">{{ baseUrl }}</code>
            </div>

            <div v-if="filteredEndpoints.length" class="space-y-3">
              <article v-for="endpoint in filteredEndpoints" :key="endpoint.path" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <button type="button" class="flex w-full items-center gap-3 p-5 text-left" @click="toggleEndpoint(endpoint.path)">
                  <span class="rounded-lg bg-emerald-100 px-2.5 py-1 font-mono text-[11px] font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">GET</span>
                  <div class="min-w-0 flex-1"><code class="block truncate text-sm font-bold">{{ endpoint.path }}</code><p class="mt-1 text-xs text-slate-500">{{ endpoint.title }}</p></div>
                  <span class="hidden rounded-full bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-500 sm:inline dark:bg-slate-800">{{ endpoint.category }}</span>
                  <svg class="h-5 w-5 text-slate-400 transition" :class="{ 'rotate-180': openEndpoint === endpoint.path }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div v-if="openEndpoint === endpoint.path" class="border-t border-slate-100 p-5 dark:border-slate-800">
                  <p class="text-sm leading-6 text-slate-600 dark:text-slate-300">{{ endpoint.description }}</p>

                  <div v-if="endpoint.parameters.length" class="mt-5 overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
                    <table class="w-full min-w-[720px] text-left text-xs">
                      <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500 dark:bg-slate-950"><tr><th class="px-3 py-3">Parameter</th><th class="px-3 py-3">Location</th><th class="px-3 py-3">Type</th><th class="px-3 py-3">Required</th><th class="px-3 py-3">Possible values / format</th><th class="px-3 py-3">Description</th></tr></thead>
                      <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr v-for="parameter in endpoint.parameters" :key="`${parameter.in}-${parameter.name}`">
                          <td class="px-3 py-3 font-mono font-semibold text-emerald-700 dark:text-emerald-400">{{ parameter.name }}</td>
                          <td class="px-3 py-3 text-slate-500">{{ parameter.in }}</td>
                          <td class="px-3 py-3 font-mono text-slate-500">{{ parameter.type }}</td>
                          <td class="px-3 py-3"><span :class="parameter.required ? 'text-amber-700' : 'text-slate-400'">{{ parameter.required ? 'Yes' : 'No' }}</span></td>
                          <td class="px-3 py-3 text-slate-700 dark:text-slate-200"><span>{{ parameter.values }}</span><span v-if="parameter.default !== undefined" class="mt-1 block text-[10px] text-slate-400">Default: {{ parameter.default }}</span></td>
                          <td class="px-3 py-3 leading-5 text-slate-500">{{ parameter.description }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <p v-else class="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500 dark:bg-slate-950">This endpoint has no parameters.</p>

                  <section class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-900 dark:bg-emerald-950/20">
                    <div class="mb-3 flex items-center justify-between"><h3 class="text-xs font-bold uppercase tracking-wide text-emerald-800 dark:text-emerald-300">Try this endpoint</h3><span class="text-[10px] text-emerald-600">Real API request</span></div>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                      <div v-for="field in endpoint.testFields" :key="field.name">
                        <label class="mb-1 block font-mono text-[10px] font-semibold text-slate-600 dark:text-slate-300">{{ field.label || field.name }}<span v-if="field.required" class="text-red-500"> *</span></label>
                        <select v-if="field.options" v-model="testInputs[endpoint.path][field.name]" class="w-full rounded-lg border-emerald-200 bg-white py-2 text-xs dark:border-emerald-900 dark:bg-slate-950"><option v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</option></select>
                        <input v-else v-model="testInputs[endpoint.path][field.name]" :type="field.type || 'text'" :placeholder="field.placeholder || 'Optional'" class="w-full rounded-lg border-emerald-200 bg-white py-2 text-xs dark:border-emerald-900 dark:bg-slate-950" />
                      </div>
                    </div>
                    <button type="button" :disabled="testingEndpoint === endpoint.path" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 disabled:opacity-60" @click="runTest(endpoint)">{{ testingEndpoint === endpoint.path ? 'Testing…' : 'Test endpoint' }}</button>

                    <div v-if="testResults[endpoint.path]" class="mt-4 overflow-hidden rounded-xl bg-slate-950">
                      <div class="flex flex-wrap items-center gap-2 border-b border-white/10 px-4 py-3"><span class="rounded px-2 py-1 font-mono text-[11px] font-bold" :class="statusClass(testResults[endpoint.path].status)">{{ testResults[endpoint.path].status }} {{ testResults[endpoint.path].statusText }}</span><span class="text-[11px] text-slate-400">{{ testResults[endpoint.path].duration }} ms</span><button class="ml-auto text-[11px] text-slate-300" @click="copy(testResults[endpoint.path].formatted, endpoint.path)">{{ copied === endpoint.path ? 'Copied' : 'Copy JSON' }}</button></div>
                      <div class="border-b border-white/10 px-4 py-2 font-mono text-[10px] text-slate-500">{{ testResults[endpoint.path].url }}</div>
                      <pre class="max-h-96 overflow-auto p-4 text-[11px] leading-5 text-slate-300"><code>{{ testResults[endpoint.path].formatted }}</code></pre>
                      <div class="border-t border-white/10 px-4 py-3">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">What this means</p>
                        <p class="mt-1.5 text-xs leading-5 text-slate-300">{{ explainError(testResults[endpoint.path].status, testResults[endpoint.path].statusText, testResults[endpoint.path].message) }}</p>
                      </div>
                    </div>
                  </section>

                  <div class="mt-5"><div class="mb-2 flex items-center justify-between"><h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Example response</h3><span class="text-[11px] font-semibold text-emerald-600">200 OK</span></div><pre class="max-h-96 overflow-auto rounded-xl bg-slate-950 p-4 text-[11px] leading-5 text-slate-300"><code>{{ formatExample(endpoint.example) }}</code></pre></div>
                </div>
              </article>
            </div>
            <div v-else class="rounded-2xl border border-dashed border-slate-300 p-12 text-center text-sm text-slate-500">No endpoints match your search.</div>

            <section class="grid gap-3 sm:grid-cols-3">
              <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900"><code class="text-xs font-bold text-amber-600">401</code><p class="mt-2 text-xs text-slate-500">Missing, invalid, revoked, or expired token.</p></div>
              <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900"><code class="text-xs font-bold text-red-600">403</code><p class="mt-2 text-xs text-slate-500">Token lacks the integration:read ability.</p></div>
              <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900"><code class="text-xs font-bold text-violet-600">422 / 429</code><p class="mt-2 text-xs text-slate-500">Invalid parameters or rate limit exceeded.</p></div>
            </section>
          </div>
        </div>
      </main>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { apiCategories, apiEndpoints } from '@/apiIntegrationEndpoints';

const props = defineProps({
  authorized: { type: Boolean, required: true },
  tokenName: { type: String, default: null },
  baseUrl: { type: String, required: true },
});

const accessToken = ref('');
const accessError = ref('');
const authenticating = ref(false);
const showAccessToken = ref(false);
const testToken = ref('');
const search = ref('');
const selectedCategory = ref('All');
const openEndpoint = ref('/summary');
const copied = ref(null);
const testingEndpoint = ref(null);
const testResults = ref({});
const testInputs = ref(Object.fromEntries(apiEndpoints.map(endpoint => [endpoint.path, Object.fromEntries(endpoint.testFields.map(field => [field.name, field.default || '']))])));

const filteredEndpoints = computed(() => apiEndpoints.filter(endpoint => {
  const categoryMatches = selectedCategory.value === 'All' || endpoint.category === selectedCategory.value;
  const needle = search.value.trim().toLowerCase();
  const searchMatches = !needle || `${endpoint.title} ${endpoint.path} ${endpoint.description}`.toLowerCase().includes(needle);
  return categoryMatches && searchMatches;
}));

const curlExample = computed(() => `curl --request GET '${props.baseUrl}/tractors?active=1&per_page=25' --header 'Accept: application/json' --header 'Authorization: Bearer YOUR_TOKEN'`);

onMounted(() => {
  if (props.authorized) testToken.value = sessionStorage.getItem('tanod-api-doc-token') || '';
});

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

async function authenticate() {
  authenticating.value = true;
  accessError.value = '';
  try {
    const response = await fetch('/api-docs/authenticate', { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() }, body: JSON.stringify({ token: accessToken.value }) });
    const payload = await response.json();
    if (!response.ok) { accessError.value = payload.message || 'The token could not be verified.'; return; }
    sessionStorage.setItem('tanod-api-doc-token', accessToken.value);
    window.location.reload();
  } catch { accessError.value = 'The documentation service could not be reached.'; }
  finally { authenticating.value = false; }
}

async function logout() {
  sessionStorage.removeItem('tanod-api-doc-token');
  await fetch('/api-docs/logout', { method: 'POST', credentials: 'same-origin', headers: { Accept: 'text/html', 'X-CSRF-TOKEN': csrfToken() } });
  window.location.href = '/api-docs';
}

function categoryCount(category) { return category === 'All' ? apiEndpoints.length : apiEndpoints.filter(endpoint => endpoint.category === category).length; }
function toggleEndpoint(path) { openEndpoint.value = openEndpoint.value === path ? null : path; }
function formatExample(example) { return JSON.stringify(example, null, 2); }

async function runTest(endpoint) {
  if (!testToken.value.trim()) { setResult(endpoint.path, 0, 'TOKEN REQUIRED', 0, props.baseUrl + endpoint.path, { message: 'Enter the issued token in the tester field above.' }); return; }
  let path = endpoint.path;
  const query = new URLSearchParams();
  for (const field of endpoint.testFields) {
    const value = String(testInputs.value[endpoint.path][field.name] ?? '').trim();
    if (field.required && !value) { setResult(endpoint.path, 0, 'PARAMETER REQUIRED', 0, props.baseUrl + endpoint.path, { message: `${field.label || field.name} is required.` }); return; }
    if (!value) continue;
    if (field.in === 'path') path = path.replace(`{${field.name}}`, encodeURIComponent(value)); else query.set(field.name, value);
  }
  const queryString = query.toString();
  const url = `${props.baseUrl}${path}${queryString ? `?${queryString}` : ''}`;
  const startedAt = performance.now();
  testingEndpoint.value = endpoint.path;
  try {
    const response = await fetch(url, { method: 'GET', credentials: 'omit', headers: { Accept: 'application/json', Authorization: `Bearer ${testToken.value.trim()}` } });
    const text = await response.text();
    let payload;
    try { payload = text ? JSON.parse(text) : null; } catch { payload = { response: text }; }
    setResult(endpoint.path, response.status, response.statusText, Math.round(performance.now() - startedAt), url, payload);
  } catch (error) { setResult(endpoint.path, 0, 'REQUEST FAILED', Math.round(performance.now() - startedAt), url, { message: error.message || 'Request failed.' }); }
  finally { testingEndpoint.value = null; }
}

function setResult(key, status, statusText, duration, url, payload) {
  testResults.value = {
    ...testResults.value,
    [key]: {
      status,
      statusText,
      duration,
      url,
      formatted: JSON.stringify(payload, null, 2),
      message: payload?.message ?? payload?.error ?? null,
    },
  };
}

function explainError(status, statusText, message) {
  if (status === 0) {
    if (statusText === 'TOKEN REQUIRED') return 'No token was entered for the built-in tester. Paste the issued token into the tester field at the top of this page, then test again.';
    if (statusText === 'PARAMETER REQUIRED') return message || 'A required parameter is missing. Fill in the required fields marked with * before testing.';
    if (statusText === 'REQUEST FAILED') return 'The request could not reach the server (network/CORS error). Check your connection and the base URL.';
  }

  if (status >= 200 && status < 300) return 'Success. The endpoint returned data using the format shown in the example above.';

  const explanations = {
    401: 'Authentication failed. The token is missing, invalid, revoked, or expired — re-issue it from a TANOD administrator and paste it again.',
    403: 'The token is valid but lacks the integration:read ability. Ask an administrator to enable that ability for the token.',
    404: 'The requested resource was not found. The tractor may have been deleted (including deleted from TANOD), renamed, or the ID/IMEI/serial is incorrect.',
    409: 'Multiple tractors share that name. Use the tractor database ID or IMEI instead of the name.',
    422: 'One or more parameters failed validation. Review the required format and possible values in the parameter table above.',
    429: 'Rate limit exceeded (120 requests per minute). Wait a few seconds and try again.',
  };

  if (explanations[status]) return message ? `${explanations[status]} Server message: ${message}` : explanations[status];

  if (status >= 500) return 'The server crashed while processing this request. This is a server-side problem — report it to the system administrator and include the request URL and timestamp.';

  return message || 'Unexpected response from the server.';
}
function statusClass(status) { if (status >= 200 && status < 300) return 'bg-emerald-400/15 text-emerald-300'; if (status >= 400 && status < 500) return 'bg-amber-400/15 text-amber-300'; return 'bg-red-400/15 text-red-300'; }
async function copy(value, key) { await navigator.clipboard.writeText(value); copied.value = key; window.setTimeout(() => { copied.value = null; }, 1800); }
</script>
