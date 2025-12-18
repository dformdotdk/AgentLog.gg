<script setup lang="ts">
import { useAgentlogApi } from '~/composables/agentlogApi';
import { useContentStore } from '~/stores/content';
import { useProgressStore } from '~/stores/progress';
import { useSessionStore } from '~/stores/session';

const route = useRoute();
const router = useRouter();
const api = useAgentlogApi();
const session = useSessionStore();
const content = useContentStore();
const progress = useProgressStore();
const runtimeConfig = useRuntimeConfig();

session.hydrate();

const series = ref<string>((route.query.series as string) || session.seriesSlug || 'agent-academy');
const book = ref<string>((route.query.book as string) || session.bookSlug || 'mission-math');
const season = ref<number>(Number(route.query.season) || session.seasonNo || 1);
const token = ref<string>((route.query.token as string) || 'BOOK-TOKEN-1');
const showDevTools = runtimeConfig.public.showDevTools;

const loading = ref(false);
const errorMessage = ref<string | null>(null);
const successMessage = ref<string | null>(null);
const showDevOptions = ref(false);

useHead({ title: 'Start | AgentLog' });

const startPairing = async () => {
  loading.value = true;
  errorMessage.value = null;
  successMessage.value = null;
  try {
    const res = await api.pair({
      series_slug: series.value,
      book_slug: book.value,
      season_no: season.value,
      book_token: token.value,
    });

    session.setSession({
      token: res.session_token,
      agentKey: res.agent_key,
      seriesSlug: series.value,
      bookSlug: book.value,
      seasonNo: season.value,
    });

    await content.loadManifest(series.value, book.value, season.value);
    await progress.refreshStatus(session.seasonId);

    const nextMissionNo = res.agent_state.next_mission_no || 1;
    const padded = nextMissionNo.toString().padStart(2, '0');
    successMessage.value = 'Paired successfully! Launching missions...';
    await router.push(`/${series.value}/${book.value}/s/${season.value}/m/${padded}`);
  } catch (err: any) {
    errorMessage.value = err.message || 'Pairing failed';
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-[1.2fr,0.9fr]">
    <section class="glow-card p-6 space-y-6">
      <div class="flex items-start gap-3">
        <div class="h-12 w-12 rounded-2xl bg-cyan-500/20 ring-2 ring-cyan-400/60"></div>
        <div class="space-y-1">
          <p class="text-sm uppercase tracking-[0.2em] text-slate-400">Join the Academy</p>
          <h1 class="font-display text-3xl">Start your mission book</h1>
          <p class="text-slate-300 text-sm">Kids can scan or enter their code with a parent nearby.</p>
        </div>
      </div>

      <div class="grid gap-4">
        <button class="btn-secondary justify-center" type="button">Scan book code (coming soon)</button>

        <label class="space-y-1 text-sm">
          <span class="text-slate-300">Enter book code</span>
          <input v-model="token" class="input-dark" placeholder="BOOK-XXXX" />
        </label>

        <div class="flex flex-wrap items-center gap-3 pt-2">
          <button class="btn-primary" :disabled="loading" @click="startPairing">
            <span v-if="loading" class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-cyan-200 border-t-transparent" />
            <span>Launch missions</span>
          </button>
          <p v-if="session.token" class="text-xs text-slate-400">Session active (Agent {{ session.agentKey }})</p>
        </div>

        <p v-if="errorMessage" class="rounded-lg border border-red-500/60 bg-red-900/40 px-3 py-2 text-sm text-red-100">
          {{ errorMessage }}
        </p>
        <p v-if="successMessage" class="rounded-lg border border-green-500/50 bg-green-900/30 px-3 py-2 text-sm text-green-100">
          {{ successMessage }}
        </p>
      </div>
    </section>

    <section v-if="showDevTools" class="glow-card p-6 space-y-4">
      <details
        class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 text-sm text-slate-200"
        :open="showDevOptions"
        @toggle="showDevOptions = !showDevOptions"
      >
        <summary class="cursor-pointer text-cyan-200">Developer options</summary>
        <p class="mt-2 text-slate-400 text-sm">Advanced controls stay hidden unless you need them.</p>
        <div class="mt-4 grid gap-3">
          <label class="space-y-1 text-sm">
            <span class="text-slate-300">Series slug</span>
            <input v-model="series" class="input-dark" />
          </label>
          <label class="space-y-1 text-sm">
            <span class="text-slate-300">Book slug</span>
            <input v-model="book" class="input-dark" />
          </label>
          <label class="space-y-1 text-sm">
            <span class="text-slate-300">Season</span>
            <input v-model.number="season" type="number" min="1" class="input-dark" />
          </label>
          <div class="rounded-lg border border-slate-800 bg-slate-900/80 p-3 text-xs text-slate-300 space-y-2">
            <div class="flex items-center justify-between">
              <span>Content endpoint</span>
              <code class="rounded bg-slate-800 px-2 py-1 text-xs text-cyan-200">/api/v1/content/{{ series }}/{{ book }}/s/{{ season }}</code>
            </div>
            <div class="flex items-center justify-between">
              <span>Pair endpoint</span>
              <code class="rounded bg-slate-800 px-2 py-1 text-xs text-cyan-200">/api/v1/pair</code>
            </div>
          </div>
        </div>
      </details>

      <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-4 text-sm text-slate-300 space-y-2">
        <p class="font-semibold text-cyan-100">Helpful defaults</p>
        <p>
          Leave defaults to pair instantly with seeded data:<br />
          <span class="font-mono text-cyan-200">series=agent-academy</span>,
          <span class="font-mono text-cyan-200">book=mission-math</span>,
          <span class="font-mono text-cyan-200">season=1</span>,
          <span class="font-mono text-cyan-200">token=BOOK-TOKEN-1</span>.
        </p>
      </div>
    </section>
  </div>
</template>
