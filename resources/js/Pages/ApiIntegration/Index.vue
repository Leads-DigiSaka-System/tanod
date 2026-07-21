<template>
  <AppLayout>
    <Head title="API Integration" />

    <div class="mx-auto max-w-7xl space-y-6">
      <section class="relative overflow-hidden rounded-3xl bg-gray-950 px-6 py-8 text-white shadow-xl sm:px-9">
        <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-emerald-500/20 blur-3xl"></div>
        <div class="absolute -bottom-24 left-1/3 h-52 w-52 rounded-full bg-yellow-400/10 blur-3xl"></div>
        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div class="max-w-3xl">
            <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-300">
              <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
              Third-party API · Version 1
            </div>
            <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">API Integration</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-gray-300 sm:text-base">
              Secure read-only access to TANOD tractor details, live positions, and alerts for trusted external systems.
            </p>
          </div>
          <div class="grid grid-cols-3 gap-2 text-center sm:gap-3">
            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur">
              <p class="text-xl font-bold">12</p>
              <p class="text-[11px] text-gray-400">Endpoints</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur">
              <p class="text-xl font-bold">120</p>
              <p class="text-[11px] text-gray-400">Req / min</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur">
              <p class="text-xl font-bold text-emerald-400">GET</p>
              <p class="text-[11px] text-gray-400">Read only</p>
            </div>
          </div>
        </div>
      </section>

      <section v-if="newToken" class="rounded-2xl border border-amber-300 bg-amber-50 p-5 shadow-sm dark:border-amber-700/60 dark:bg-amber-950/30">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <div class="flex items-center gap-2 text-sm font-bold text-amber-900 dark:text-amber-200">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0-1.1.9-2 2-2s2 .9 2 2-2 2-2 2m-2-2H5m0 0l2-2m-2 2l2 2m10-8a7 7 0 11-7 7"/></svg>
              Copy your new token now
            </div>
            <p class="mt-1 text-xs text-amber-800/80 dark:text-amber-300/80">For security, the plaintext token will disappear when you leave or refresh this page.</p>
          </div>
          <button type="button" class="shrink-0 rounded-lg bg-amber-900 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-800" @click="copy(newToken, 'token')">
            {{ copied === 'token' ? 'Copied!' : 'Copy token' }}
          </button>
        </div>
        <code class="mt-4 block overflow-x-auto rounded-xl border border-amber-200 bg-white/80 p-4 text-xs text-amber-950 dark:border-amber-800 dark:bg-gray-950 dark:text-amber-200">{{ newToken }}</code>
      </section>

      <div class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
        <div class="space-y-6">
          <section class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-100 p-5 dark:border-gray-700">
              <h2 class="font-semibold text-gray-900 dark:text-white">Generate access token</h2>
              <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">Create one token per third-party system so access can be revoked independently.</p>
            </div>
            <form class="space-y-4 p-5" @submit.prevent="generateToken">
              <div>
                <label for="token-name" class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Integration name</label>
                <input id="token-name" v-model="tokenForm.name" required maxlength="100" placeholder="e.g. DA Monitoring Portal" class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm text-gray-900 focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
                <p v-if="tokenForm.errors.name" class="mt-1 text-xs text-red-600">{{ tokenForm.errors.name }}</p>
              </div>
              <div>
                <label for="token-expiry" class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Expires after</label>
                <select id="token-expiry" v-model="tokenForm.expires_in_days" class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm text-gray-900 focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                  <option :value="30">30 days</option>
                  <option :value="90">90 days</option>
                  <option :value="180">180 days</option>
                  <option :value="365">1 year</option>
                  <option :value="null">Never expires</option>
                </select>
              </div>
              <button type="submit" :disabled="tokenForm.processing" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:cursor-wait disabled:opacity-60">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 11-4 0 2 2 0 014 0zm-2 2v4m0 0h4m-4 0l-3 3m7-3a4 4 0 10-8 0"/></svg>
                {{ tokenForm.processing ? 'Generating…' : 'Generate token' }}
              </button>
            </form>
          </section>

          <section class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 p-5 dark:border-gray-700">
              <div>
                <h2 class="font-semibold text-gray-900 dark:text-white">Created integration tokens</h2>
                <p class="mt-1 text-xs text-gray-500">{{ tokens.length }} token {{ tokens.length === 1 ? 'record' : 'records' }} across all administrators</p>
              </div>
            </div>
            <div v-if="tokens.length" class="divide-y divide-gray-100 dark:divide-gray-700">
              <div v-for="token in tokens" :key="token.id" class="p-5">
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <div class="flex items-center gap-2">
                      <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ token.name }}</p>
                      <span v-if="token.is_expired" class="rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-300">Expired</span>
                      <span v-else class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">Active</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Created {{ formatDate(token.created_at) }}</p>
                    <p class="mt-0.5 text-xs text-gray-400">Created by: {{ token.created_by?.name || 'Deleted administrator' }}</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                      <span class="rounded-md bg-gray-100 px-2 py-1 font-mono text-[10px] text-gray-500 dark:bg-gray-900 dark:text-gray-400">Token #{{ token.id }}</span>
                      <span class="rounded-md bg-violet-50 px-2 py-1 font-mono text-[10px] text-violet-600 dark:bg-violet-950/30 dark:text-violet-300">{{ token.scope }}</span>
                    </div>
                    <p class="mt-0.5 text-xs text-gray-400">Last used: {{ token.last_used_at ? formatDate(token.last_used_at) : 'Never' }}</p>
                    <p class="mt-0.5 text-xs text-gray-400">Expires: {{ token.expires_at ? formatDate(token.expires_at) : 'Never' }}</p>
                  </div>
                  <div class="flex shrink-0 items-center gap-1">
                    <button v-if="token.can_reveal" type="button" class="rounded-lg p-2 text-gray-400 hover:bg-emerald-50 hover:text-emerald-600 dark:hover:bg-emerald-950/30" :title="revealedTokens[token.id]?.value ? 'Hide token' : 'Reveal token'" @click="revealToken(token)">
                      <svg v-if="revealingToken !== token.id" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c-1.5 4-4.5 6-9 6s-7.5-2-9-6c1.5-4 4.5-6 9-6s7.5 2 9 6z"/></svg>
                      <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                    </button>
                    <button type="button" class="rounded-lg p-2 text-gray-400 hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-blue-950/30" title="Rotate token" @click="rotateToken(token)">
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5.5 15a7 7 0 0011.5 2M18.5 9A7 7 0 007 7"/></svg>
                    </button>
                    <button type="button" class="rounded-lg p-2 text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/30" title="Revoke token" @click="revokeToken(token)">
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4h6v3m-10 0h14"/></svg>
                    </button>
                  </div>
                </div>
                <div v-if="revealedTokens[token.id]?.value" class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-900/60 dark:bg-emerald-950/20">
                  <div class="mb-2 flex items-center justify-between gap-3">
                    <p class="text-[11px] font-semibold text-emerald-800 dark:text-emerald-300">Token secret</p>
                    <button type="button" class="rounded-md bg-emerald-700 px-2 py-1 text-[10px] font-semibold text-white hover:bg-emerald-800" @click="copy(revealedTokens[token.id].value, `stored-token-${token.id}`)">{{ copied === `stored-token-${token.id}` ? 'Copied' : 'Copy' }}</button>
                  </div>
                  <code class="block overflow-x-auto break-all text-[11px] leading-5 text-emerald-950 dark:text-emerald-200">{{ revealedTokens[token.id].value }}</code>
                </div>
                <div v-else-if="revealedTokens[token.id]?.error" class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-xs text-red-700 dark:border-red-900/60 dark:bg-red-950/20 dark:text-red-300">{{ revealedTokens[token.id].error }}</div>
                <div v-else-if="!token.can_reveal" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-900/60 dark:bg-amber-950/20">
                  <p class="text-xs leading-5 text-amber-800 dark:text-amber-300">This older token’s secret was not retained. Rotation invalidates it and creates a revealable replacement.</p>
                  <button type="button" class="mt-2 rounded-lg bg-amber-800 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-amber-900" @click="rotateToken(token)">Rotate and reveal</button>
                </div>
              </div>
            </div>
            <div v-else class="p-8 text-center">
              <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-gray-100 text-gray-400 dark:bg-gray-700">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4"/></svg>
              </div>
              <p class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300">No integration tokens yet</p>
            </div>
          </section>
        </div>

        <div class="space-y-6">
          <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Quick start</p>
                <h2 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">Authenticate every request</h2>
              </div>
              <span class="rounded-lg bg-gray-100 px-3 py-1.5 font-mono text-xs text-gray-600 dark:bg-gray-900 dark:text-gray-300">application/json</span>
            </div>
            <p class="mt-4 text-sm leading-6 text-gray-600 dark:text-gray-300">Send the token as a Bearer credential. Keep it server-side and never expose it in browser or mobile application source code.</p>
            <div class="relative mt-4 overflow-hidden rounded-xl bg-gray-950">
              <button type="button" class="absolute right-3 top-3 rounded-md bg-white/10 px-2 py-1 text-[11px] text-gray-300 hover:bg-white/20" @click="copy(curlExample, 'curl')">{{ copied === 'curl' ? 'Copied' : 'Copy' }}</button>
              <pre class="overflow-x-auto p-5 pr-20 text-xs leading-6 text-gray-300"><code>{{ curlExample }}</code></pre>
            </div>
            <div class="mt-4 flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-950/20">
              <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <p class="text-xs leading-5 text-blue-800 dark:text-blue-300">Live tracking is polling-based. Use <code>/live/tractors</code> every 15 seconds for a fleet map, passing the previous <code>next_changed_since</code> value to receive only changed markers.</p>
            </div>
          </section>

          <section class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-5 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/20 sm:p-6">
            <div class="flex items-start gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l-3 3 3 3m8-6l3 3-3 3m-5 3l2-12"/></svg>
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Built-in API console</p>
                <h2 class="mt-1 text-lg font-bold text-gray-900 dark:text-white">Test endpoints directly</h2>
                <p class="mt-1 text-xs leading-5 text-gray-600 dark:text-gray-300">Paste a valid integration token once, open any endpoint below, adjust its parameters, and select Test endpoint.</p>
              </div>
            </div>
            <div class="mt-4">
              <label for="test-token" class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Bearer token</label>
              <div class="flex gap-2">
                <div class="relative min-w-0 flex-1">
                  <input id="test-token" v-model="testToken" :type="showTestToken ? 'text' : 'password'" autocomplete="off" placeholder="Paste an integration token" class="w-full rounded-xl border-emerald-200 bg-white pr-11 font-mono text-xs text-gray-900 focus:border-emerald-500 focus:ring-emerald-500 dark:border-emerald-800 dark:bg-gray-900 dark:text-white" />
                  <button type="button" class="absolute inset-y-0 right-0 px-3 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" :title="showTestToken ? 'Hide token' : 'Show token'" @click="showTestToken = !showTestToken">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c-1.5 4-4.5 6-9 6s-7.5-2-9-6c1.5-4 4.5-6 9-6s7.5 2 9 6z"/></svg>
                  </button>
                </div>
                <button v-if="newToken" type="button" class="rounded-xl border border-emerald-200 bg-white px-3 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-gray-900 dark:text-emerald-300" @click="testToken = newToken">Use new token</button>
              </div>
              <p class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">The token stays only in this browser page and is sent directly to the selected TANOD endpoint.</p>
            </div>
          </section>

          <section>
            <div class="mb-4 flex items-end justify-between">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Reference</p>
                <h2 class="mt-1 text-xl font-bold text-gray-900 dark:text-white">Endpoint documentation</h2>
              </div>
              <span class="text-xs text-gray-500">Base URL · {{ baseUrl }}</span>
            </div>

            <div class="space-y-3">
              <article v-for="(endpoint, index) in endpoints" :key="endpoint.path" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <button type="button" class="flex w-full items-center gap-3 p-5 text-left" @click="toggleEndpoint(index)">
                  <span class="rounded-lg bg-emerald-100 px-2.5 py-1 font-mono text-[11px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">GET</span>
                  <div class="min-w-0 flex-1">
                    <code class="block truncate text-sm font-semibold text-gray-900 dark:text-white">{{ endpoint.path }}</code>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ endpoint.title }}</p>
                  </div>
                  <span v-if="endpoint.nice" class="hidden rounded-full bg-violet-100 px-2 py-1 text-[10px] font-semibold text-violet-700 sm:inline dark:bg-violet-900/30 dark:text-violet-300">Recommended</span>
                  <svg class="h-5 w-5 shrink-0 text-gray-400 transition-transform" :class="{ 'rotate-180': openEndpoint === index }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div v-if="openEndpoint === index" class="border-t border-gray-100 p-5 dark:border-gray-700">
                  <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">{{ endpoint.description }}</p>

                  <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/20">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                      <div v-for="field in endpoint.testFields" :key="field.name" class="min-w-0 flex-1">
                        <label :for="`test-${index}-${field.name}`" class="mb-1 block font-mono text-[11px] font-semibold text-gray-600 dark:text-gray-300">{{ field.label || field.name }}<span v-if="field.required" class="text-red-500"> *</span></label>
                        <select v-if="field.options" :id="`test-${index}-${field.name}`" v-model="testInputs[index][field.name]" class="w-full rounded-lg border-emerald-200 bg-white py-2 text-xs text-gray-900 focus:border-emerald-500 focus:ring-emerald-500 dark:border-emerald-800 dark:bg-gray-900 dark:text-white">
                          <option v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                        <input v-else :id="`test-${index}-${field.name}`" v-model="testInputs[index][field.name]" :type="field.type || 'text'" :placeholder="field.placeholder || 'Optional'" class="w-full rounded-lg border-emerald-200 bg-white py-2 text-xs text-gray-900 focus:border-emerald-500 focus:ring-emerald-500 dark:border-emerald-800 dark:bg-gray-900 dark:text-white" />
                      </div>
                      <button type="button" :disabled="testingEndpoint === index" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 disabled:cursor-wait disabled:opacity-60" @click="runTest(endpoint, index)">
                        <svg v-if="testingEndpoint !== index" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.75 2.75l6.5 6.5-12 12h-6.5v-6.5l12-12zm-8.5 13.5l1.5 1.5m3-6l1.5 1.5"/></svg>
                        <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        {{ testingEndpoint === index ? 'Testing…' : 'Test endpoint' }}
                      </button>
                    </div>

                    <div v-if="testResults[index]" class="mt-4 overflow-hidden rounded-xl border border-gray-800 bg-gray-950 shadow-inner">
                      <div class="flex flex-wrap items-center gap-2 border-b border-white/10 px-4 py-3">
                        <span class="rounded-md px-2 py-1 font-mono text-[11px] font-bold" :class="resultStatusClass(testResults[index].status)">{{ testResults[index].status }} {{ testResults[index].statusText }}</span>
                        <span class="text-[11px] text-gray-400">{{ testResults[index].duration }} ms</span>
                        <span v-if="testResults[index].remaining !== null" class="text-[11px] text-gray-500">{{ testResults[index].remaining }} requests remaining</span>
                        <button type="button" class="ml-auto rounded-md bg-white/10 px-2 py-1 text-[11px] text-gray-300 hover:bg-white/20" @click="copy(testResults[index].formatted, `result-${index}`)">{{ copied === `result-${index}` ? 'Copied' : 'Copy JSON' }}</button>
                      </div>
                      <div class="border-b border-white/10 px-4 py-2 font-mono text-[10px] text-gray-500">{{ testResults[index].url }}</div>
                      <pre class="max-h-96 overflow-auto p-4 text-[11px] leading-5 text-gray-300"><code>{{ testResults[index].formatted }}</code></pre>
                    </div>
                  </div>

                  <div v-if="endpoint.parameters.length" class="mt-5">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-gray-500">Parameters</h3>
                    <div class="mt-2 overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                      <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                          <tr><th class="px-3 py-2 font-semibold">Name</th><th class="px-3 py-2 font-semibold">In</th><th class="px-3 py-2 font-semibold">Description</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                          <tr v-for="parameter in endpoint.parameters" :key="parameter.name">
                            <td class="whitespace-nowrap px-3 py-2 font-mono text-emerald-700 dark:text-emerald-400">{{ parameter.name }}</td>
                            <td class="px-3 py-2 text-gray-500">{{ parameter.in }}</td>
                            <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ parameter.description }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <div class="mt-5">
                    <div class="mb-2 flex items-center justify-between">
                      <h3 class="text-xs font-bold uppercase tracking-wide text-gray-500">Example response</h3>
                      <span class="text-[11px] font-semibold text-emerald-600">200 OK</span>
                    </div>
                    <pre class="max-h-80 overflow-auto rounded-xl bg-gray-950 p-4 text-[11px] leading-5 text-gray-300"><code>{{ endpoint.response }}</code></pre>
                  </div>
                </div>
              </article>
            </div>
          </section>

          <section class="grid gap-3 sm:grid-cols-3">
            <div v-for="item in responseNotes" :key="item.code" class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
              <span class="font-mono text-xs font-bold" :class="item.color">{{ item.code }}</span>
              <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">{{ item.text }}</p>
            </div>
          </section>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  tokens: { type: Array, default: () => [] },
  newToken: { type: String, default: null },
  baseUrl: { type: String, required: true },
});

const copied = ref(null);
const revealedTokens = ref({});
const revealingToken = ref(null);
const openEndpoint = ref(0);
const showTestToken = ref(false);
const testToken = ref(props.newToken || '');
const testingEndpoint = ref(null);
const testResults = ref({});
const tokenForm = useForm({ name: '', expires_in_days: 90 });

const curlExample = computed(() => `curl --request GET '${props.baseUrl}/tractors?active=1&per_page=25' --header 'Accept: application/json' --header 'Authorization: Bearer YOUR_TOKEN'`);

const endpoints = [
  {
    title: 'Fleet operational summary',
    path: '/summary',
    nice: true,
    description: 'A lightweight cross-fleet snapshot for dashboards and health monitors, including online tractors, open alerts, and maintenance workload.',
    parameters: [],
    testFields: [],
    response: `{
  "data": {
    "tractors": { "total": 108, "active": 104, "online": 91, "offline_or_stale": 13 },
    "devices": { "total": 108, "active": 105 },
    "alerts": { "unacknowledged": 7, "last_24_hours": 12 },
    "maintenance": { "open": 4, "completed_last_30_days": 18 },
    "generated_at": "2026-07-21T10:30:08+08:00"
  }
}`,
  },
  {
    title: 'Live view of all tractors',
    path: '/live/tractors',
    nice: true,
    description: 'Map-ready fleet snapshot with the latest position, online/moving/stale state, data age, and unacknowledged-alert count for every tractor. Use changed_since for lightweight incremental polling.',
    parameters: [
      { name: 'search', in: 'query', description: 'Matches tractor name, plate number, or IMEI.' },
      { name: 'active', in: 'query', description: 'Tractor active status. Defaults to true.' },
      { name: 'online', in: 'query', description: 'Optional boolean filter for online or offline/stale tractors.' },
      { name: 'include_without_location', in: 'query', description: 'Include tractors with no GPS position. Defaults to false.' },
      { name: 'changed_since', in: 'query', description: 'ISO-8601 cursor from the previous next_changed_since response.' },
      { name: 'stale_after_seconds', in: 'query', description: 'Online freshness window, from 30 to 86,400 seconds. Default: 300.' },
      { name: 'limit', in: 'query', description: 'Maximum markers returned, from 1 to 2,000. Default: 1,000.' },
    ],
    testFields: [
      { name: 'search', in: 'query', placeholder: 'Plate, IMEI, name…' },
      { name: 'online', in: 'query', options: [{ label: 'All states', value: '' }, { label: 'Online only', value: '1' }, { label: 'Offline / stale', value: '0' }] },
      { name: 'stale_after_seconds', in: 'query', label: 'Stale after (sec)', type: 'number', default: '300' },
      { name: 'limit', in: 'query', type: 'number', default: '100' },
    ],
    response: `{
  "data": [{
    "tractor": { "id": 42, "name": "Tractor North 1", "plate_number": "TRC-042", "active": true },
    "device": { "id": 27, "imei": "869066063771910", "name": "North GPS" },
    "position": { "latitude": 14.5995, "longitude": 120.9842, "speed_kph": 12.4, "direction_degrees": 92, "ignition_on": true },
    "status": { "online": true, "moving": true, "stale": false, "age_seconds": 8, "recorded_at": "2026-07-21T10:30:00+08:00" },
    "unacknowledged_alerts": 1
  }],
  "meta": {
    "returned": 108,
    "online": 91,
    "moving": 24,
    "stale": 17,
    "recommended_poll_interval_seconds": 15,
    "next_changed_since": "2026-07-21T10:30:08+08:00"
  }
}`,
  },
  {
    title: 'List and discover tractors',
    path: '/tractors',
    nice: true,
    description: 'Paginated tractor directory for discovering IDs before requesting details, tracking, or alerts.',
    parameters: [
      { name: 'search', in: 'query', description: 'Matches name, plate, IMEI, engine, or chassis number.' },
      { name: 'active', in: 'query', description: 'Boolean filter: 1/0 or true/false.' },
      { name: 'per_page', in: 'query', description: 'Results per page, from 1 to 100. Default: 25.' },
      { name: 'page', in: 'query', description: 'Page number, starting at 1.' },
    ],
    testFields: [
      { name: 'search', in: 'query', placeholder: 'Plate, IMEI, name…' },
      { name: 'active', in: 'query', options: [{ label: 'All statuses', value: '' }, { label: 'Active', value: '1' }, { label: 'Inactive', value: '0' }] },
      { name: 'per_page', in: 'query', type: 'number', default: '5' },
    ],
    response: `{
  "data": [{
    "id": 42,
    "name": "Tractor North 1",
    "plate_number": "TRC-042",
    "imei": "869066063771910",
    "machine": { "brand": "Kubota", "model": "L4708" },
    "active": true,
    "device": { "online": true, "last_seen_at": "2026-07-21T10:30:00+08:00" }
  }],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "per_page": 25, "total": 108 }
}`,
  },
  {
    title: 'Get complete tractor details',
    path: '/tractors/{tractor}',
    description: 'Returns identifiers, machine specifications, implements, usage, maintenance, delivery, insurance, device, assignment, groups, and images.',
    parameters: [{ name: 'tractor', in: 'path', description: 'Numeric tractor ID returned by the tractor directory.' }],
    testFields: [{ name: 'tractor', in: 'path', label: 'Tractor ID', required: true, type: 'number', default: '1' }],
    response: `{
  "data": {
    "id": 42,
    "name": "Tractor North 1",
    "plate_number": "TRC-042",
    "identifiers": { "engine_number": "EN-4221", "chassis_number": "CH-9012" },
    "machine": { "brand": "Kubota", "model": "L4708", "maximum_speed_kph": 40 },
    "usage": { "total_distance_km": 1862.5, "running_hours": 308.4, "pms_status": "ok" },
    "active": true
  }
}`,
  },
  {
    title: 'Get the latest live position',
    path: '/tractors/{tractor}/location',
    description: 'Returns the tractor’s most recent device position and freshness. Poll every 15–30 seconds for a live map.',
    parameters: [{ name: 'tractor', in: 'path', description: 'Numeric tractor ID.' }],
    testFields: [{ name: 'tractor', in: 'path', label: 'Tractor ID', required: true, type: 'number', default: '1' }],
    response: `{
  "data": {
    "tractor": { "id": 42, "name": "Tractor North 1", "plate_number": "TRC-042" },
    "position": { "latitude": 14.5995, "longitude": 120.9842, "speed_kph": 12.4, "direction_degrees": 92 },
    "ignition_on": true,
    "online": true,
    "recorded_at": "2026-07-21T10:30:00+08:00",
    "age_seconds": 8,
    "stale": false
  }
}`,
  },
  {
    title: 'Get tractor location history',
    path: '/tractors/{tractor}/location-history',
    description: 'Paginated GPS trail for route playback, trip analysis, and audit history. Results are newest first.',
    parameters: [
      { name: 'tractor', in: 'path', description: 'Numeric tractor ID.' },
      { name: 'from / to', in: 'query', description: 'Optional date or ISO-8601 date-time range.' },
      { name: 'per_page', in: 'query', description: 'Points per page, from 1 to 500. Default: 100.' },
    ],
    testFields: [
      { name: 'tractor', in: 'path', label: 'Tractor ID', required: true, type: 'number', default: '1' },
      { name: 'from', in: 'query', type: 'date' },
      { name: 'to', in: 'query', type: 'date' },
      { name: 'per_page', in: 'query', type: 'number', default: '10' },
    ],
    response: `{
  "data": [{
    "id": 8841,
    "latitude": 14.5995,
    "longitude": 120.9842,
    "speed_kph": 12.4,
    "ignition_on": true,
    "recorded_at": "2026-07-21T10:30:00+08:00"
  }],
  "meta": { "current_page": 1, "per_page": 100, "total": 420 }
}`,
  },
  {
    title: 'Get tractor mileage summary',
    path: '/tractors/{tractor}/mileage',
    description: 'Mileage and machine-runtime analytics from stored trip records, including selected-period totals, all-time odometer values, maximum speed, averages, and a daily breakdown.',
    parameters: [
      { name: 'tractor', in: 'path', description: 'Numeric tractor ID.' },
      { name: 'from / to', in: 'query', description: 'Optional inclusive date range. Without dates, all stored trip records are summarized.' },
    ],
    testFields: [
      { name: 'tractor', in: 'path', label: 'Tractor ID', required: true, type: 'number', default: '1' },
      { name: 'from', in: 'query', type: 'date' },
      { name: 'to', in: 'query', type: 'date' },
    ],
    response: `{
  "data": {
    "tractor": { "id": 42, "name": "Tractor North 1", "plate_number": "TRC-042" },
    "range": { "from": "2026-07-01", "to": "2026-07-21" },
    "summary": {
      "mileage_km": 286.4,
      "runtime_seconds": 64200,
      "runtime_hours": 17.83,
      "maximum_speed_kph": 42,
      "average_mileage_per_trip_km": 8.68,
      "trips": 33
    },
    "all_time": { "odometer_km": 1862.5, "running_hours": 308.4 },
    "daily": [{ "date": "2026-07-21", "mileage_km": 18.2, "runtime_hours": 1.4, "trips": 2 }]
  }
}`,
  },
  {
    title: 'Get tractor trip track data',
    path: '/tractors/{tractor}/track-data',
    description: 'Paginated trip segments from the tracking provider with start/end coordinates and timestamps, mileage, runtime, and maximum speed. Use location-history when individual GPS pings are required.',
    parameters: [
      { name: 'tractor', in: 'path', description: 'Numeric tractor ID.' },
      { name: 'from / to', in: 'query', description: 'Optional inclusive trip start-date range.' },
      { name: 'per_page', in: 'query', description: 'Trip records per page, from 1 to 100. Default: 25.' },
    ],
    testFields: [
      { name: 'tractor', in: 'path', label: 'Tractor ID', required: true, type: 'number', default: '1' },
      { name: 'from', in: 'query', type: 'date' },
      { name: 'to', in: 'query', type: 'date' },
      { name: 'per_page', in: 'query', type: 'number', default: '10' },
    ],
    response: `{
  "data": [{
    "id": 880,
    "tractor_id": 42,
    "device_id": 27,
    "start": { "latitude": 14.5995, "longitude": 120.9842, "recorded_at": "2026-07-21T08:00:00+08:00" },
    "end": { "latitude": 14.6500, "longitude": 121.0100, "recorded_at": "2026-07-21T09:00:00+08:00" },
    "mileage_km": 12.5,
    "runtime_seconds": 3600,
    "runtime_hours": 1,
    "maximum_speed_kph": 40
  }],
  "meta": { "current_page": 1, "per_page": 25, "total": 33 }
}`,
  },
  {
    title: 'List alerts for one tractor',
    path: '/tractors/{tractor}/alerts',
    description: 'Returns paginated alerts linked directly to the tractor or to its tracking device.',
    parameters: [
      { name: 'tractor', in: 'path', description: 'Numeric tractor ID.' },
      { name: 'type', in: 'query', description: 'Exact alert type, such as speed or geofence_breach.' },
      { name: 'acknowledged', in: 'query', description: 'Boolean acknowledgement filter.' },
      { name: 'from / to', in: 'query', description: 'Date or ISO-8601 date-time range.' },
      { name: 'per_page', in: 'query', description: 'Results per page, from 1 to 100.' },
    ],
    testFields: [
      { name: 'tractor', in: 'path', label: 'Tractor ID', required: true, type: 'number', default: '1' },
      { name: 'type', in: 'query', placeholder: 'speed' },
      { name: 'acknowledged', in: 'query', options: [{ label: 'All', value: '' }, { label: 'Unacknowledged', value: '0' }, { label: 'Acknowledged', value: '1' }] },
      { name: 'per_page', in: 'query', type: 'number', default: '10' },
    ],
    response: `{
  "data": [{
    "id": 991,
    "type": "speed",
    "title": "Speed threshold exceeded",
    "message": "Tractor reached 46 km/h.",
    "acknowledged": false,
    "created_at": "2026-07-21T10:28:12+08:00"
  }],
  "meta": { "current_page": 1, "per_page": 25, "total": 1 }
}`,
  },
  {
    title: 'Get tractor maintenance history',
    path: '/tractors/{tractor}/maintenance',
    description: 'Service and PMS records with issue type, status, costs, checklist, technician, readings, and supporting images.',
    parameters: [
      { name: 'tractor', in: 'path', description: 'Numeric tractor ID.' },
      { name: 'status', in: 'query', description: 'documentation, scheduled, in_progress, completed, or cancelled.' },
      { name: 'from / to', in: 'query', description: 'Maintenance date range.' },
      { name: 'per_page', in: 'query', description: 'Results per page, from 1 to 100.' },
    ],
    testFields: [
      { name: 'tractor', in: 'path', label: 'Tractor ID', required: true, type: 'number', default: '1' },
      { name: 'status', in: 'query', options: [{ label: 'All statuses', value: '' }, { label: 'Scheduled', value: 'scheduled' }, { label: 'In progress', value: 'in_progress' }, { label: 'Completed', value: 'completed' }, { label: 'Cancelled', value: 'cancelled' }] },
      { name: 'per_page', in: 'query', type: 'number', default: '10' },
    ],
    response: `{
  "data": [{
    "id": 71,
    "maintenance_date": "2026-07-18",
    "status": "completed",
    "issue_type": { "id": 1, "name": "Engine Oil" },
    "description": "250-hour PMS",
    "cost": 4800,
    "running_hours": 251.2
  }]
}`,
  },
  {
    title: 'List general alerts',
    path: '/alerts',
    description: 'Cross-fleet alert feed with tractor, type, acknowledgement, date-range, and pagination filters.',
    parameters: [
      { name: 'tractor_id', in: 'query', description: 'Limit results to one tractor.' },
      { name: 'type', in: 'query', description: 'Exact alert type.' },
      { name: 'acknowledged', in: 'query', description: 'Boolean acknowledgement filter.' },
      { name: 'from / to', in: 'query', description: 'Date or ISO-8601 date-time range.' },
      { name: 'per_page', in: 'query', description: 'Results per page, from 1 to 100.' },
    ],
    testFields: [
      { name: 'tractor_id', in: 'query', label: 'Tractor ID', type: 'number' },
      { name: 'type', in: 'query', placeholder: 'speed' },
      { name: 'acknowledged', in: 'query', options: [{ label: 'All', value: '' }, { label: 'Unacknowledged', value: '0' }, { label: 'Acknowledged', value: '1' }] },
      { name: 'per_page', in: 'query', type: 'number', default: '10' },
    ],
    response: `{
  "data": [{
    "id": 991,
    "type": "speed",
    "title": "Speed threshold exceeded",
    "tractor": { "id": 42, "name": "Tractor North 1", "plate_number": "TRC-042" },
    "device": { "id": 27, "imei": "869066063771910" },
    "acknowledged": false,
    "created_at": "2026-07-21T10:28:12+08:00"
  }]
}`,
  },
  {
    title: 'Discover available alert types',
    path: '/alert-types',
    description: 'Returns alert types currently present in TANOD with total and unacknowledged counts, useful for building dynamic filters.',
    parameters: [],
    testFields: [],
    response: `{
  "data": [
    { "type": "geofence_breach", "total": 18, "unacknowledged": 2 },
    { "type": "maintenance_due", "total": 9, "unacknowledged": 1 },
    { "type": "speed", "total": 42, "unacknowledged": 4 }
  ]
}`,
  },
];

const testInputs = ref(endpoints.map(endpoint => Object.fromEntries(
  endpoint.testFields.map(field => [field.name, field.default || '']),
)));

watch(() => props.newToken, (token) => {
  if (token) testToken.value = token;
});

const responseNotes = [
  { code: '401', color: 'text-amber-600', text: 'Token is missing, invalid, or expired.' },
  { code: '403', color: 'text-red-600', text: 'Token does not have the integration:read scope.' },
  { code: '422 / 429', color: 'text-violet-600', text: 'Invalid parameters or rate limit exceeded.' },
];

function generateToken() {
  tokenForm.post('/api-integration/tokens', {
    preserveScroll: true,
    onSuccess: () => tokenForm.reset('name'),
  });
}

function revokeToken(token) {
  if (window.confirm(`Revoke access for “${token.name}”? Requests using this token will stop immediately.`)) {
    router.delete(`/api-integration/tokens/${token.id}`, { preserveScroll: true });
  }
}

async function revealToken(token) {
  if (revealedTokens.value[token.id]?.value) {
    const nextTokens = { ...revealedTokens.value };
    delete nextTokens[token.id];
    revealedTokens.value = nextTokens;
    return;
  }

  revealingToken.value = token.id;

  try {
    const response = await fetch(`/api-integration/tokens/${token.id}/reveal`, {
      headers: { Accept: 'application/json' },
    });
    const payload = await response.json();

    revealedTokens.value = {
      ...revealedTokens.value,
      [token.id]: response.ok
        ? { value: payload.token }
        : { error: payload.message || 'The token could not be revealed.' },
    };
  } catch {
    revealedTokens.value = {
      ...revealedTokens.value,
      [token.id]: { error: 'The token could not be revealed.' },
    };
  } finally {
    revealingToken.value = null;
  }
}

function rotateToken(token) {
  if (window.confirm(`Rotate “${token.name}”? The current token will stop working immediately.`)) {
    router.post(`/api-integration/tokens/${token.id}/rotate`, {}, {
      preserveScroll: true,
      onSuccess: () => {
        const nextTokens = { ...revealedTokens.value };
        delete nextTokens[token.id];
        revealedTokens.value = nextTokens;
      },
    });
  }
}

function toggleEndpoint(index) {
  openEndpoint.value = openEndpoint.value === index ? null : index;
}

async function runTest(endpoint, index) {
  if (!testToken.value.trim()) {
    testResults.value = {
      ...testResults.value,
      [index]: {
        status: 0,
        statusText: 'TOKEN REQUIRED',
        duration: 0,
        remaining: null,
        url: props.baseUrl + endpoint.path,
        formatted: JSON.stringify({ message: 'Paste a valid integration token in the API console above.' }, null, 2),
      },
    };
    return;
  }

  let path = endpoint.path;
  const query = new URLSearchParams();

  for (const field of endpoint.testFields) {
    const value = String(testInputs.value[index][field.name] ?? '').trim();

    if (field.required && !value) {
      testResults.value = {
        ...testResults.value,
        [index]: {
          status: 0,
          statusText: 'PARAMETER REQUIRED',
          duration: 0,
          remaining: null,
          url: props.baseUrl + endpoint.path,
          formatted: JSON.stringify({ message: `${field.label || field.name} is required.` }, null, 2),
        },
      };
      return;
    }

    if (!value) continue;

    if (field.in === 'path') {
      path = path.replace(`{${field.name}}`, encodeURIComponent(value));
    } else {
      query.set(field.name, value);
    }
  }

  const requestUrl = `${props.baseUrl}${path}${query.size ? `?${query.toString()}` : ''}`;
  const startedAt = performance.now();
  testingEndpoint.value = index;

  try {
    const response = await fetch(requestUrl, {
      method: 'GET',
      credentials: 'omit',
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${testToken.value.trim()}`,
      },
    });
    const rawResponse = await response.text();
    let payload;

    try {
      payload = rawResponse ? JSON.parse(rawResponse) : null;
    } catch {
      payload = { response: rawResponse };
    }

    testResults.value = {
      ...testResults.value,
      [index]: {
        status: response.status,
        statusText: response.statusText,
        duration: Math.round(performance.now() - startedAt),
        remaining: response.headers.get('X-RateLimit-Remaining'),
        url: requestUrl,
        formatted: JSON.stringify(payload, null, 2),
      },
    };
  } catch (error) {
    testResults.value = {
      ...testResults.value,
      [index]: {
        status: 0,
        statusText: 'REQUEST FAILED',
        duration: Math.round(performance.now() - startedAt),
        remaining: null,
        url: requestUrl,
        formatted: JSON.stringify({ message: error.message || 'The request could not be completed.' }, null, 2),
      },
    };
  } finally {
    testingEndpoint.value = null;
  }
}

function resultStatusClass(status) {
  if (status >= 200 && status < 300) return 'bg-emerald-400/15 text-emerald-300';
  if (status >= 400 && status < 500) return 'bg-amber-400/15 text-amber-300';
  return 'bg-red-400/15 text-red-300';
}

async function copy(value, key) {
  await navigator.clipboard.writeText(value);
  copied.value = key;
  window.setTimeout(() => { copied.value = null; }, 1800);
}

function formatDate(value) {
  return new Intl.DateTimeFormat('en-PH', {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value));
}
</script>
