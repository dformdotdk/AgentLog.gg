<template>
  <div class="min-h-screen bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 text-slate-100">
    <header class="border-b border-slate-800 bg-slate-950/70 backdrop-blur sticky top-0 z-20">
      <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
        <NuxtLink to="/start" class="flex items-center gap-2 font-display text-lg tracking-wide text-cyan-300">
          <span class="inline-block h-3 w-3 rounded-full bg-cyan-400 shadow shadow-cyan-500"></span>
          AgentLog
        </NuxtLink>
        <nav class="flex items-center gap-4 text-sm uppercase tracking-wide text-slate-300">
          <NuxtLink to="/start" class="hover:text-cyan-200">Start</NuxtLink>
          <NuxtLink to="/log" class="hover:text-cyan-200">Log</NuxtLink>
        </nav>
      </div>
    </header>
    <main class="mx-auto max-w-5xl px-4 py-6">
      <slot />
    </main>
  </div>
</template>
