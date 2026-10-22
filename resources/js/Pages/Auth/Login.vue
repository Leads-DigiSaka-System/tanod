<template>
  <GuestLayout>
    <Head title="Login — TanodTractor" />

    <!-- ─── LEFT: Animated Hero Panel ─── -->
    <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden" style="background: linear-gradient(135deg, #007f3d 0%, #007f3d 25%, #0d4f0d 65%, #1a6e1a 100%);">

      <!-- Animated rice field pattern (bottom) -->
      <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <!-- Rice paddy rows -->
        <svg class="absolute bottom-0 w-full" viewBox="0 0 1200 400" preserveAspectRatio="none" style="height: 55%;">
          <!-- Sky gradient -->
          <defs>
            <linearGradient id="skyGrad" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#87CEEB" stop-opacity="0.3"/>
              <stop offset="100%" stop-color="#2d8a2d" stop-opacity="0"/>
            </linearGradient>
            <linearGradient id="fieldGrad" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#4CAF50"/>
              <stop offset="100%" stop-color="#2E7D32"/>
            </linearGradient>
          </defs>
          <!-- Hills -->
          <ellipse cx="300" cy="180" rx="400" ry="80" fill="#388E3C" opacity="0.5"/>
          <ellipse cx="900" cy="200" rx="350" ry="70" fill="#388E3C" opacity="0.4"/>
          <!-- Paddy fields -->
          <rect x="0" y="200" width="1200" height="200" fill="url(#fieldGrad)"/>
          <!-- Rice rows -->
          <g opacity="0.3">
            <path d="M0 220 Q300 210 600 225 Q900 240 1200 220" stroke="#1B5E20" stroke-width="1" fill="none"/>
            <path d="M0 245 Q300 235 600 250 Q900 265 1200 245" stroke="#1B5E20" stroke-width="1" fill="none"/>
            <path d="M0 270 Q300 260 600 275 Q900 290 1200 270" stroke="#1B5E20" stroke-width="1" fill="none"/>
            <path d="M0 295 Q300 285 600 300 Q900 315 1200 295" stroke="#1B5E20" stroke-width="1" fill="none"/>
            <path d="M0 320 Q300 310 600 325 Q900 340 1200 320" stroke="#1B5E20" stroke-width="1" fill="none"/>
            <path d="M0 345 Q300 335 600 350 Q900 365 1200 345" stroke="#1B5E20" stroke-width="1" fill="none"/>
          </g>
          <!-- Rice plant stalks -->
          <g class="rice-sway">
            <g v-for="n in 30" :key="'rice-'+n" :transform="`translate(${n * 40}, ${220 + (n % 5) * 25})`">
              <line x1="0" y1="0" x2="-3" y2="-18" stroke="#66BB6A" stroke-width="1.5"/>
              <line x1="0" y1="0" x2="3" y2="-16" stroke="#66BB6A" stroke-width="1.5"/>
              <circle cx="-3" cy="-20" r="2" fill="#A5D6A7"/>
              <circle cx="3" cy="-18" r="2" fill="#A5D6A7"/>
            </g>
          </g>
        </svg>

        <!-- Animated tractor 1 (moves left to right) -->
        <div class="tractor-move-lr absolute" style="bottom: 22%; left: -120px;">
          <svg width="120" height="70" viewBox="0 0 120 70">
            <!-- Tractor body -->
            <rect x="30" y="15" width="55" height="30" rx="4" fill="#F9A825"/>
            <rect x="25" y="25" width="15" height="20" rx="2" fill="#F57F17"/>
            <!-- Cab -->
            <rect x="55" y="2" width="28" height="25" rx="3" fill="#FDD835" stroke="#F9A825" stroke-width="1"/>
            <rect x="58" y="5" width="22" height="14" rx="2" fill="#81D4FA" opacity="0.7"/>
            <!-- Exhaust -->
            <rect x="33" y="8" width="4" height="10" rx="1" fill="#424242"/>
            <circle class="exhaust-puff" cx="35" cy="5" r="3" fill="#9E9E9E" opacity="0.5"/>
            <!-- Big rear wheel -->
            <circle cx="40" cy="50" r="18" fill="#37474F"/>
            <circle cx="40" cy="50" r="14" fill="#546E7A"/>
            <circle cx="40" cy="50" r="5" fill="#78909C"/>
            <!-- Rear tire treads -->
            <g class="wheel-spin">
              <line x1="40" y1="33" x2="40" y2="37" stroke="#37474F" stroke-width="2"/>
              <line x1="54" y1="43" x2="51" y2="45" stroke="#37474F" stroke-width="2"/>
              <line x1="54" y1="57" x2="51" y2="55" stroke="#37474F" stroke-width="2"/>
              <line x1="40" y1="63" x2="40" y2="67" stroke="#37474F" stroke-width="2"/>
              <line x1="26" y1="57" x2="29" y2="55" stroke="#37474F" stroke-width="2"/>
              <line x1="26" y1="43" x2="29" y2="45" stroke="#37474F" stroke-width="2"/>
            </g>
            <!-- Small front wheel -->
            <circle cx="82" cy="50" r="11" fill="#37474F"/>
            <circle cx="82" cy="50" r="8" fill="#546E7A"/>
            <circle cx="82" cy="50" r="3" fill="#78909C"/>
            <g class="wheel-spin-fast">
              <line x1="82" y1="40" x2="82" y2="43" stroke="#37474F" stroke-width="1.5"/>
              <line x1="91" y1="47" x2="88" y2="48" stroke="#37474F" stroke-width="1.5"/>
              <line x1="91" y1="53" x2="88" y2="52" stroke="#37474F" stroke-width="1.5"/>
              <line x1="82" y1="60" x2="82" y2="57" stroke="#37474F" stroke-width="1.5"/>
              <line x1="73" y1="53" x2="76" y2="52" stroke="#37474F" stroke-width="1.5"/>
              <line x1="73" y1="47" x2="76" y2="48" stroke="#37474F" stroke-width="1.5"/>
            </g>
          </svg>
        </div>

        <!-- Animated tractor 2 (moves right to left, upper) -->
        <div class="tractor-move-rl absolute" style="bottom: 35%; right: -100px; transform: scaleX(-1);">
          <svg width="90" height="55" viewBox="0 0 120 70" opacity="0.6">
            <rect x="30" y="15" width="55" height="30" rx="4" fill="#FF8F00"/>
            <rect x="25" y="25" width="15" height="20" rx="2" fill="#E65100"/>
            <rect x="55" y="2" width="28" height="25" rx="3" fill="#FFB300"/>
            <rect x="58" y="5" width="22" height="14" rx="2" fill="#81D4FA" opacity="0.5"/>
            <rect x="33" y="8" width="4" height="10" rx="1" fill="#424242"/>
            <circle cx="40" cy="50" r="18" fill="#37474F"/>
            <circle cx="40" cy="50" r="14" fill="#546E7A"/>
            <circle cx="40" cy="50" r="5" fill="#78909C"/>
            <circle cx="82" cy="50" r="11" fill="#37474F"/>
            <circle cx="82" cy="50" r="8" fill="#546E7A"/>
            <circle cx="82" cy="50" r="3" fill="#78909C"/>
          </svg>
        </div>

        <!-- Floating particles (grain/dust) -->
        <div v-for="n in 12" :key="'p'+n"
          class="floating-particle absolute rounded-full"
          :style="{
            width: (3 + n % 4) + 'px',
            height: (3 + n % 4) + 'px',
            left: (n * 8) + '%',
            top: (20 + (n * 7) % 50) + '%',
            background: n % 3 === 0 ? '#FDD835' : '#A5D6A7',
            animationDelay: (n * 0.5) + 's',
            animationDuration: (4 + n % 3) + 's',
          }"
        />
      </div>

      <!-- Content overlay -->
      <div class="relative z-10 flex flex-col justify-between p-10 w-full h-full min-h-screen">
        <!-- Logo -->
        <div>
          <img src="/images/logo.png" alt="TanodTractor Logo" class="w-60 mb-4">
          <p class="text-green-200 text-sm max-w-sm leading-relaxed">Tractor Monitoring & Management System for Agricultural Development</p>
        </div>

        <!-- Info Cards -->
        <div class="space-y-4 my-8">
          <div class="info-card bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20" style="animation-delay: 0s;">
            <div class="flex items-start gap-4">
              <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-yellow-400/20 shrink-0">
                <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0H3m10 0h6m-6 0V6h4l3 4v6"/></svg>
              </div>
              <div>
                <h3 class="text-white font-semibold text-sm">Real-Time Tractor Tracking</h3>
                <p class="text-green-200/80 text-xs mt-1">GPS-powered live monitoring of all tractors with route history, speed alerts, and geo-fencing.</p>
              </div>
            </div>
          </div>

          <div class="info-card bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20" style="animation-delay: 0.15s;">
            <div class="flex items-start gap-4">
              <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-emerald-400/20 shrink-0">
                <svg class="w-6 h-6 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
              </div>
              <div>
                <h3 class="text-white font-semibold text-sm">FCA & Farmer Management</h3>
                <p class="text-green-200/80 text-xs mt-1">Complete oversight for FCAs managing farmer groups, tractor assignments, and service distribution.</p>
              </div>
            </div>
          </div>

          <div class="info-card bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20" style="animation-delay: 0.3s;">
            <div class="flex items-start gap-4">
              <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-blue-400/20 shrink-0">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              </div>
              <div>
                <h3 class="text-white font-semibold text-sm">Booking System</h3>
                <p class="text-green-200/80 text-xs mt-1">Streamlined tractor booking, scheduling, and availability tracking for farmers.</p>
              </div>
            </div>
          </div>

          <div class="info-card bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20" style="animation-delay: 0.45s;">
            <div class="flex items-start gap-4">
              <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-orange-400/20 shrink-0">
                <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
              </div>
              <div>
                <h3 class="text-white font-semibold text-sm">Reports & Analytics</h3>
                <p class="text-green-200/80 text-xs mt-1">Comprehensive dashboards with tractor usage, maintenance summaries, and performance insights.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="text-green-300/60 text-xs">
       
        </div>
      </div>
    </div>

    <!-- ─── RIGHT: Login Form ─── -->
    <div class="w-full lg:w-1/2 flex flex-col justify-between bg-gray-50 dark:bg-gray-900">
      <div class="flex-1 flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">

          <!-- Mobile logo (visible < lg) -->
          <div class="lg:hidden text-center mb-8">
            <div class="flex items-center justify-center gap-2 mb-2">
              <span class="text-2xl font-extrabold text-green-700 dark:text-green-400">Tanod</span>
              <span class="text-2xl font-extrabold text-yellow-500">Tractor</span>
              <svg class="w-7 h-7 text-yellow-500" viewBox="0 0 120 70" fill="currentColor">
                <rect x="30" y="15" width="55" height="30" rx="4"/>
                <rect x="55" y="2" width="28" height="25" rx="3"/>
                <circle cx="40" cy="50" r="16"/>
                <circle cx="40" cy="50" r="10" fill="#f0fdf4"/>
                <circle cx="82" cy="50" r="10"/>
                <circle cx="82" cy="50" r="6" fill="#f0fdf4"/>
              </svg>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Tractor Monitoring & Management System</p>
          </div>

          <div class="login-card-enter">
              <!-- Header with tractor icon -->
              <div class="mb-8">
                <div class="flex items-center gap-3 mb-2">
                  <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: linear-gradient(135deg, #1a6e1a, #2E7D32);">
                    <svg class="w-5 h-5 text-yellow-400" viewBox="0 0 120 70" fill="currentColor">
                      <rect x="30" y="15" width="55" height="30" rx="4"/>
                      <rect x="55" y="2" width="28" height="25" rx="3"/>
                      <circle cx="40" cy="50" r="16"/>
                      <circle cx="40" cy="50" r="10" fill="#1a6e1a"/>
                      <circle cx="82" cy="50" r="10"/>
                      <circle cx="82" cy="50" r="6" fill="#1a6e1a"/>
                    </svg>
                  </div>
                  <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Welcome back</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sign in to your dashboard</p>
                  </div>
                </div>
              </div>

              <!-- Status message -->
              <div v-if="$page.props.flash?.success" class="mb-5 flex items-center p-3.5 text-sm text-green-800 rounded-xl bg-green-50 border border-green-200 dark:bg-green-900/30 dark:border-green-800 dark:text-green-400">
                <svg class="w-4 h-4 mr-2 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                {{ $page.props.flash.success }}
              </div>

              <!-- Session Expired Warning -->
              <div v-if="showExpiredMessage" class="mb-5 p-4 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 dark:bg-amber-900/20 dark:border-amber-800 dark:text-amber-400 flex items-start gap-3">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Your session expired due to inactivity. Please log in again to continue.</span>
              </div>

              <form @submit.prevent="submit" class="space-y-5">
                <div>
                  <label for="email" class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">Email Address</label>
                  <div class="relative group">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none transition-colors">
                      <svg class="w-4 h-4 text-gray-400 group-focus-within:text-green-600" fill="currentColor" viewBox="0 0 20 16"><path d="m10.036 8.278 9.258-7.79A1.979 1.979 0 0 0 18 0H2A1.987 1.987 0 0 0 .641.541l9.395 7.737Z"/><path d="M11.241 9.817c-.36.275-.801.425-1.255.427-.428 0-.845-.138-1.187-.395L0 2.6V14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V2.5l-8.759 7.317Z"/></svg>
                    </div>
                    <input id="email" v-model="form.email" type="email" required autofocus placeholder="name@company.com"
                      class="bg-gray-50 border-2 border-gray-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-green-500/20 focus:border-green-600 block w-full ps-11 p-3.5 transition-all duration-200 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-green-500/20 dark:focus:border-green-500"
                      :class="{ 'border-red-400 focus:ring-red-500/20 focus:border-red-500': form.errors.email }" />
                  </div>
                  <p v-if="form.errors.email" class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ form.errors.email }}
                  </p>
                </div>

                <div>
                  <label for="password" class="block mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">Password</label>
                  <div class="relative group">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none transition-colors">
                      <svg class="w-4 h-4 text-gray-400 group-focus-within:text-green-600" fill="currentColor" viewBox="0 0 16 20"><path d="M14 7h-1.5V4.5a4.5 4.5 0 1 0-9 0V7H2a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2Zm-5 8a1 1 0 1 1-2 0v-3a1 1 0 1 1 2 0v3Zm1.5-8h-5V4.5a2.5 2.5 0 1 1 5 0V7Z"/></svg>
                    </div>
                    <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'" required placeholder="••••••••"
                      class="bg-gray-50 border-2 border-gray-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-green-500/20 focus:border-green-600 block w-full ps-11 pe-12 p-3.5 transition-all duration-200 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-green-500/20 dark:focus:border-green-500"
                      :class="{ 'border-red-400 focus:ring-red-500/20 focus:border-red-500': form.errors.password }" />
                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 end-0 flex items-center pe-4 text-gray-400 hover:text-green-600 transition-colors dark:hover:text-green-400">
                      <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                      <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"/></svg>
                    </button>
                  </div>
                  <p v-if="form.errors.password" class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ form.errors.password }}
                  </p>
                </div>

                <div class="flex items-center justify-between">
                  <label class="flex items-center cursor-pointer group">
                    <input v-model="form.remember" type="checkbox" class="w-4 h-4 border-2 border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-green-300 text-green-600 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-green-600 transition-colors" />
                    <span class="ms-2.5 text-sm text-gray-500 group-hover:text-gray-700 dark:text-gray-300 dark:group-hover:text-gray-200 transition-colors">Remember me</span>
                  </label>
                </div>

                <button type="submit" :disabled="form.processing"
                  class="w-full text-white font-semibold rounded-xl text-sm px-5 py-3.5 text-center transition-all duration-300 disabled:opacity-50 btn-login"
                  style="background: linear-gradient(135deg, #1a6e1a, #2E7D32);">
                  <span v-if="form.processing" class="inline-flex items-center justify-center">
                    <svg class="w-4 h-4 me-2 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Signing in...
                  </span>
                  <span v-else class="inline-flex items-center justify-center gap-2">
                    Sign in to Dashboard
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                  </span>
                </button>
              </form>

              <!-- Legal -->
              <div class="mt-6 text-center">
                <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                  By signing in, you agree to our
                  <Link href="/privacy-policy" class="font-medium text-green-700 dark:text-green-400 hover:underline">Privacy Policy</Link>
                  and
                  <Link href="/terms-and-conditions" class="font-medium text-green-700 dark:text-green-400 hover:underline">Terms &amp; Conditions</Link>
                </p>
              </div>

              <!-- Feature badges -->
              <div class="mt-8 grid grid-cols-3 gap-2">
                <div class="text-center p-2.5 rounded-xl bg-green-50 border border-green-100 dark:bg-green-900/20 dark:border-green-800/30 feature-badge" style="animation-delay: 0.1s;">
                  <svg class="w-5 h-5 mx-auto text-green-600 dark:text-green-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                  <p class="text-xs font-semibold text-green-700 dark:text-green-400">Secure</p>
                </div>
                <div class="text-center p-2.5 rounded-xl bg-yellow-50 border border-yellow-100 dark:bg-yellow-900/20 dark:border-yellow-800/30 feature-badge" style="animation-delay: 0.2s;">
                  <svg class="w-5 h-5 mx-auto text-yellow-600 dark:text-yellow-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  <p class="text-xs font-semibold text-yellow-700 dark:text-yellow-400">GPS Live</p>
                </div>
                <div class="text-center p-2.5 rounded-xl bg-blue-50 border border-blue-100 dark:bg-blue-900/20 dark:border-blue-800/30 feature-badge" style="animation-delay: 0.3s;">
                  <svg class="w-5 h-5 mx-auto text-blue-600 dark:text-blue-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                  <p class="text-xs font-semibold text-blue-700 dark:text-blue-400">Mobile</p>
                </div>
              </div>
            </div>
        </div>
      </div>

      <!-- ── Copyright Footer with Logos ── -->
      <div class="border-t border-gray-200 dark:border-gray-700 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm">
        <div class="max-w-md mx-auto px-6 py-4">
          <!-- Partner logos -->
          <div class="flex items-center justify-center gap-6 mb-3">
            <!-- Department of Agriculture / PhilMech logo -->
            <div class="flex items-center gap-2 opacity-70 hover:opacity-100 transition-opacity">
              <img src="/images/PHilMech.png" alt="PhilMech Logo" class="w-10 h-10 object-contain">
              <div class="leading-none">
                <p class="text-[9px] font-bold text-green-800 dark:text-green-300 tracking-wider uppercase">Department of</p>
                <p class="text-[9px] font-bold text-green-800 dark:text-green-300 tracking-wider uppercase">Agriculture</p>
                <p class="text-[8px] font-bold text-white mt-0.5">PHilMech</p>
              </div>
            </div>

            <!-- Divider -->
            <div class="h-8 w-px bg-gray-300 dark:bg-gray-600"></div>

            <!-- LeadsAgri logo -->
            <div class="flex items-center gap-2 opacity-70 hover:opacity-100 transition-opacity">
              <img src="/images/leads.png" alt="LeadsAgri Logo" class="w-10 h-10 object-contain">
              <div class="leading-none">
                <p class="text-[10px] font-bold text-green-800 dark:text-green-300 tracking-wide">Leads</p>
                <p class="text-[10px] font-bold text-yellow-600 dark:text-yellow-400 tracking-wide">Agri</p>
              </div>
            </div>
          </div>

          <!-- Copyright text -->
          <div class="text-center">
            <p class="text-[10px] text-gray-500 dark:text-gray-400 leading-relaxed">
              &copy; {{ new Date().getFullYear() }} <span class="font-semibold text-green-700 dark:text-green-400">TanodTractor</span>
              &middot; Department of Agriculture &middot; LAPC Program
            </p>
            <p class="text-[9px] text-gray-400 dark:text-gray-500 mt-0.5">Powered by PHilMech &amp; LeadsAgri</p>
          </div>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const showPassword = ref(false);
const showExpiredMessage = ref(false);

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};

onMounted(() => {
  const params = new URLSearchParams(window.location.search);
  if (params.get('expired') === '1') {
    showExpiredMessage.value = true;
  }
});
</script>

<style scoped>
/* Tractor moving left to right */
@keyframes moveLR {
  0% { transform: translateX(0); }
  100% { transform: translateX(calc(100vw / 2 + 150px)); }
}
.tractor-move-lr {
  animation: moveLR 18s linear infinite;
}

/* Tractor moving right to left */
@keyframes moveRL {
  0% { transform: scaleX(-1) translateX(0); }
  100% { transform: scaleX(-1) translateX(calc(100vw / 2 + 130px)); }
}
.tractor-move-rl {
  animation: moveRL 24s linear infinite;
}

/* Wheel spin */
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
.wheel-spin {
  transform-origin: 40px 50px;
  animation: spin 1s linear infinite;
}
.wheel-spin-fast {
  transform-origin: 82px 50px;
  animation: spin 0.7s linear infinite;
}

/* Exhaust puff */
@keyframes puff {
  0% { r: 3; opacity: 0.5; cy: 5; }
  100% { r: 8; opacity: 0; cy: -10; }
}
.exhaust-puff {
  animation: puff 1.5s ease-out infinite;
}

/* Rice sway */
@keyframes riceSway {
  0%, 100% { transform: rotate(0deg); }
  50% { transform: rotate(2deg); }
}
.rice-sway {
  transform-origin: bottom center;
  animation: riceSway 3s ease-in-out infinite;
}

/* Floating particles */
@keyframes floatUp {
  0% { transform: translateY(0) translateX(0); opacity: 0.6; }
  50% { opacity: 1; }
  100% { transform: translateY(-80px) translateX(20px); opacity: 0; }
}
.floating-particle {
  animation: floatUp 4s ease-in-out infinite;
}

/* Info cards slide in */
@keyframes slideInLeft {
  from { opacity: 0; transform: translateX(-30px); }
  to { opacity: 1; transform: translateX(0); }
}
.info-card {
  animation: slideInLeft 0.6s ease-out both;
}

/* Login card entrance */
@keyframes cardEnter {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}
.login-card-enter {
  animation: cardEnter 0.5s ease-out;
}

/* Feature badge pop */
@keyframes badgePop {
  from { opacity: 0; transform: scale(0.8); }
  to { opacity: 1; transform: scale(1); }
}
.feature-badge {
  animation: badgePop 0.4s ease-out both;
}

/* Login button hover glow */
.btn-login {
  box-shadow: 0 4px 14px rgba(26, 110, 26, 0.4);
  position: relative;
  overflow: hidden;
}
.btn-login:hover:not(:disabled) {
  box-shadow: 0 6px 22px rgba(26, 110, 26, 0.55);
  transform: translateY(-1px);
}
.btn-login:active:not(:disabled) {
  transform: translateY(0);
  box-shadow: 0 2px 8px rgba(26, 110, 26, 0.4);
}
</style>
