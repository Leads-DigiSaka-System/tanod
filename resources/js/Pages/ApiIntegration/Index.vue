<template>
  <AppLayout>
    <Head title="API Integration" />

    <div class="mx-auto max-w-6xl space-y-6">
      <section class="relative overflow-hidden rounded-3xl bg-gray-950 px-6 py-8 text-white shadow-xl sm:px-9">
        <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-emerald-500/20 blur-3xl"></div>
        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs font-semibold text-emerald-300">
              <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
              Administration
            </div>
            <h1 class="text-3xl font-bold tracking-tight">API Integration</h1>
            <p class="mt-3 max-w-xl text-sm leading-6 text-gray-300">Issue, inspect, rotate, and revoke access tokens for trusted third-party systems.</p>
          </div>
          <a :href="documentationUrl" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm hover:bg-emerald-50">
            Open public API docs
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5h5v5m0-5L10 14m-3-7H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2"/></svg>
          </a>
        </div>
      </section>

      <section v-if="newToken" class="rounded-2xl border border-amber-300 bg-amber-50 p-5 shadow-sm dark:border-amber-700/60 dark:bg-amber-950/30">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <p class="text-sm font-bold text-amber-900 dark:text-amber-200">New token issued successfully</p>
            <p class="mt-1 text-xs text-amber-800/80 dark:text-amber-300/80">Copy it and send the public documentation URL to the third party.</p>
          </div>
          <button type="button" class="rounded-lg bg-amber-900 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-800" @click="copy(newToken, 'new-token')">{{ copied === 'new-token' ? 'Copied!' : 'Copy token' }}</button>
        </div>
        <code class="mt-4 block overflow-x-auto rounded-xl border border-amber-200 bg-white/80 p-4 text-xs text-amber-950 dark:border-amber-800 dark:bg-gray-950 dark:text-amber-200">{{ newToken }}</code>
      </section>

      <div class="grid gap-6 lg:grid-cols-[350px_minmax(0,1fr)]">
        <div class="space-y-6">
          <section class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-100 p-5 dark:border-gray-700">
              <h2 class="font-semibold text-gray-900 dark:text-white">Issue a token</h2>
              <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">Create a separate token for each external system.</p>
            </div>
            <form class="space-y-4 p-5" @submit.prevent="generateToken">
              <div>
                <label for="token-name" class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Integration name</label>
                <input id="token-name" v-model="tokenForm.name" required maxlength="100" placeholder="e.g. DA Monitoring Portal" class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white" />
                <p v-if="tokenForm.errors.name" class="mt-1 text-xs text-red-600">{{ tokenForm.errors.name }}</p>
              </div>
              <div>
                <label for="token-expiry" class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Expires after</label>
                <select id="token-expiry" v-model="tokenForm.expires_in_days" class="w-full rounded-xl border-gray-200 bg-gray-50 text-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                  <option :value="30">30 days</option>
                  <option :value="90">90 days</option>
                  <option :value="180">180 days</option>
                  <option :value="365">1 year</option>
                  <option :value="null">Never expires</option>
                </select>
              </div>
              <button type="submit" :disabled="tokenForm.processing" class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">{{ tokenForm.processing ? 'Issuing…' : 'Issue integration token' }}</button>
            </form>
          </section>

          <section class="rounded-2xl border border-blue-200 bg-blue-50 p-5 dark:border-blue-900/60 dark:bg-blue-950/20">
            <p class="text-xs font-bold uppercase tracking-wide text-blue-700 dark:text-blue-300">Third-party onboarding</p>
            <ol class="mt-3 space-y-2 text-xs leading-5 text-blue-900 dark:text-blue-200">
              <li>1. Issue a uniquely named token.</li>
              <li>2. Send the token securely to the partner.</li>
              <li>3. Send them the public documentation URL.</li>
              <li>4. Rotate or revoke access when required.</li>
            </ol>
            <button type="button" class="mt-4 rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100 dark:border-blue-800 dark:bg-gray-900 dark:text-blue-300" @click="copy(documentationUrl, 'docs-url')">{{ copied === 'docs-url' ? 'URL copied' : 'Copy documentation URL' }}</button>
          </section>
        </div>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
          <div class="flex items-center justify-between border-b border-gray-100 p-5 dark:border-gray-700">
            <div>
              <h2 class="font-semibold text-gray-900 dark:text-white">Issued tokens</h2>
              <p class="mt-1 text-xs text-gray-500">{{ tokens.length }} {{ tokens.length === 1 ? 'token' : 'tokens' }} across all administrators</p>
            </div>
          </div>

          <div v-if="tokens.length" class="divide-y divide-gray-100 dark:divide-gray-700">
            <article v-for="token in tokens" :key="token.id" class="p-5">
              <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                  <div class="flex flex-wrap items-center gap-2">
                    <h3 class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ token.name }}</h3>
                    <span :class="token.is_expired ? 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'" class="rounded-full px-2 py-0.5 text-[10px] font-semibold">{{ token.is_expired ? 'Expired' : 'Active' }}</span>
                  </div>
                  <p class="mt-1 text-xs text-gray-500">Created by {{ token.created_by?.name || 'Deleted administrator' }} · {{ formatDate(token.created_at) }}</p>
                  <div class="mt-2 flex flex-wrap gap-1.5">
                    <span class="rounded-md bg-gray-100 px-2 py-1 font-mono text-[10px] text-gray-500 dark:bg-gray-900 dark:text-gray-400">Token #{{ token.id }}</span>
                    <span class="rounded-md bg-violet-50 px-2 py-1 font-mono text-[10px] text-violet-600 dark:bg-violet-950/30 dark:text-violet-300">{{ token.scope }}</span>
                  </div>
                  <div class="mt-3 grid gap-1 text-xs text-gray-400 sm:grid-cols-2">
                    <p>Last used: {{ token.last_used_at ? formatDate(token.last_used_at) : 'Never' }}</p>
                    <p>Expires: {{ token.expires_at ? formatDate(token.expires_at) : 'Never' }}</p>
                  </div>
                </div>
                <div class="flex shrink-0 gap-1">
                  <button v-if="token.can_reveal" type="button" class="rounded-lg p-2 text-gray-400 hover:bg-emerald-50 hover:text-emerald-600" title="Reveal token" @click="revealToken(token)">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c-1.5 4-4.5 6-9 6s-7.5-2-9-6c1.5-4 4.5-6 9-6s7.5 2 9 6z"/></svg>
                  </button>
                  <button type="button" class="rounded-lg p-2 text-gray-400 hover:bg-blue-50 hover:text-blue-600" title="Rotate token" @click="rotateToken(token)">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5.5 15a7 7 0 0011.5 2M18.5 9A7 7 0 007 7"/></svg>
                  </button>
                  <button type="button" class="rounded-lg p-2 text-gray-400 hover:bg-red-50 hover:text-red-600" title="Revoke token" @click="revokeToken(token)">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4h6v3m-10 0h14"/></svg>
                  </button>
                </div>
              </div>

              <div v-if="revealedTokens[token.id]?.value" class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-900 dark:bg-emerald-950/20">
                <div class="mb-2 flex justify-between gap-3"><span class="text-[11px] font-semibold text-emerald-800 dark:text-emerald-300">Token secret</span><button class="text-[11px] font-semibold text-emerald-700" @click="copy(revealedTokens[token.id].value, `token-${token.id}`)">{{ copied === `token-${token.id}` ? 'Copied' : 'Copy' }}</button></div>
                <code class="block overflow-x-auto break-all text-[11px] leading-5 text-emerald-950 dark:text-emerald-200">{{ revealedTokens[token.id].value }}</code>
              </div>
              <div v-else-if="revealedTokens[token.id]?.error" class="mt-4 rounded-xl bg-red-50 p-3 text-xs text-red-700">{{ revealedTokens[token.id].error }}</div>
              <div v-else-if="!token.can_reveal" class="mt-4 flex flex-col gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-amber-800">Legacy secret unavailable. Rotate it to create a revealable replacement.</p>
                <button class="shrink-0 rounded-lg bg-amber-800 px-3 py-1.5 text-[11px] font-semibold text-white" @click="rotateToken(token)">Rotate token</button>
              </div>
            </article>
          </div>
          <div v-else class="p-12 text-center text-sm text-gray-500">No integration tokens have been issued.</div>
        </section>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  tokens: { type: Array, default: () => [] },
  newToken: { type: String, default: null },
  documentationUrl: { type: String, required: true },
});

const copied = ref(null);
const revealedTokens = ref({});
const tokenForm = useForm({ name: '', expires_in_days: 90 });

function generateToken() {
  tokenForm.post('/api-integration/tokens', { preserveScroll: true, onSuccess: () => tokenForm.reset('name') });
}

async function revealToken(token) {
  if (revealedTokens.value[token.id]?.value) {
    const next = { ...revealedTokens.value };
    delete next[token.id];
    revealedTokens.value = next;
    return;
  }

  try {
    const response = await fetch(`/api-integration/tokens/${token.id}/reveal`, { headers: { Accept: 'application/json' } });
    const payload = await response.json();
    revealedTokens.value = { ...revealedTokens.value, [token.id]: response.ok ? { value: payload.token } : { error: payload.message } };
  } catch {
    revealedTokens.value = { ...revealedTokens.value, [token.id]: { error: 'The token could not be revealed.' } };
  }
}

function rotateToken(token) {
  if (window.confirm(`Rotate “${token.name}”? The current token will stop working immediately.`)) {
    router.post(`/api-integration/tokens/${token.id}/rotate`, {}, { preserveScroll: true });
  }
}

function revokeToken(token) {
  if (window.confirm(`Revoke “${token.name}”? Requests using it will stop immediately.`)) {
    router.delete(`/api-integration/tokens/${token.id}`, { preserveScroll: true });
  }
}

async function copy(value, key) {
  await navigator.clipboard.writeText(value);
  copied.value = key;
  window.setTimeout(() => { copied.value = null; }, 1800);
}

function formatDate(value) {
  return new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}
</script>
