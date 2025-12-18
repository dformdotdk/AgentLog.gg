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

session.hydrate();

const seriesParam = computed(() => route.params.series as string);
const bookParam = computed(() => route.params.book as string);
const seasonParam = computed(() => Number(route.params.season));
const missionNoParam = computed(() => Number(route.params.missionNo));

const answer = ref('');
const feedback = ref<string | null>(null);
const error = ref<string | null>(null);
const submitting = ref(false);
const locked = ref(false);
const loading = ref(true);

const mission = computed(() => content.missionByNo(missionNoParam.value));

const ensureSession = async () => {
  if (!session.token) {
    await router.push({
      path: '/start',
      query: {
        series: seriesParam.value,
        book: bookParam.value,
        season: seasonParam.value,
        return: route.fullPath,
      },
    });
    return false;
  }
  return true;
};

const loadData = async () => {
  loading.value = true;
  error.value = null;
  locked.value = false;
  const hasSession = await ensureSession();
  if (!hasSession) return;

  try {
    await content.loadManifest(seriesParam.value, bookParam.value, seasonParam.value);
    await progress.refreshStatus(session.seasonId);
  } catch (err: any) {
    error.value = err.message || 'Failed to load mission';
  } finally {
    loading.value = false;
  }
};

onMounted(loadData);
watch(() => route.params.missionNo, loadData);

const submitAnswer = async () => {
  if (!mission.value) return;
  submitting.value = true;
  feedback.value = null;
  error.value = null;
  locked.value = false;
  try {
    const res = await api.attemptMission(mission.value.id, answer.value);
    if (!res.success) {
      if (res.error_code === 'LOCKED') locked.value = true;
      feedback.value = res.error_code === 'WRONG_ANSWER' ? 'Wrong answer. Try again!' : res.error_code;
      return;
    }

    feedback.value = `Mission complete! +${res.xp_gained} XP`;
    progress.xp_total = res.new_xp;
    progress.level = Math.max(1, Math.floor(res.new_xp / 100) + 1);
    if (progress.missionsStatus[mission.value.id]) {
      progress.missionsStatus[mission.value.id] = 'completed';
    }
    if (res.next_mission_id) {
      progress.missionsStatus[res.next_mission_id] = 'active';
      progress.next_mission_id = res.next_mission_id;
    }
    await progress.refreshStatus(session.seasonId);
  } catch (err: any) {
    error.value = err.message || 'Submission failed';
  } finally {
    submitting.value = false;
  }
};

const goToMission = (missionNo: number) => {
  const padded = missionNo.toString().padStart(2, '0');
  router.push(`/${seriesParam.value}/${bookParam.value}/s/${seasonParam.value}/m/${padded}`);
};

const missionStatus = computed(() => {
  if (!mission.value) return 'locked';
  return progress.missionsStatus[mission.value.id] || 'locked';
});

const nextMissionFromStatus = computed(() => progress.next_mission_id);
</script>

<template>
  <div class="space-y-4">
    <div class="glow-card p-4 flex flex-wrap items-center justify-between gap-4">
      <div>
        <p class="text-xs uppercase tracking-[0.25em] text-slate-400">XP: {{ progress.xp_total }} · Level {{ progress.level }}</p>
        <h1 class="font-display text-2xl">Mission {{ mission?.mission_no?.toString().padStart(2, '0') }}</h1>
        <p class="text-sm text-slate-400">{{ mission?.slug }}</p>
      </div>
      <div class="text-right text-sm text-slate-300">
        <p>Next mission ID: {{ progress.next_mission_id || '—' }}</p>
        <p>Milestones: {{ progress.milestones_unlocked.join(', ') || 'None' }}</p>
      </div>
    </div>

    <div v-if="loading" class="glow-card p-6 text-center text-slate-300">Loading mission...</div>
    <div v-else-if="!mission" class="glow-card p-6 text-center text-red-200">Mission not found.</div>
    <div v-else class="grid gap-4 lg:grid-cols-[1.4fr,0.9fr]">
      <section class="glow-card p-6 space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Mission #{{ mission.mission_no }}</p>
            <h2 class="font-display text-xl">{{ mission.slug.replace('-', ' ') }}</h2>
          </div>
          <div class="rounded-lg border border-cyan-400/40 bg-cyan-400/10 px-3 py-1 text-xs text-cyan-100">
            +{{ mission.xp_reward }} XP
          </div>
        </div>

        <div class="space-y-2">
          <h3 class="text-sm uppercase tracking-[0.2em] text-slate-400">Briefing videos</h3>
          <div class="grid gap-3 md:grid-cols-2">
            <div
              v-for="video in mission.videos.filter((v) => !v.parent_only)"
              :key="video.provider_id"
              class="rounded-lg border border-slate-800 bg-slate-900/60 p-3 text-sm text-slate-200"
            >
              <p class="font-semibold text-cyan-200">{{ video.title }}</p>
              <p class="text-xs text-slate-400">Provider: {{ video.provider }} · {{ video.duration_seconds }}s</p>
            </div>
            <p v-if="!mission.videos.length" class="text-slate-500 text-sm">No videos for this mission.</p>
          </div>
        </div>

        <div class="space-y-3">
          <h3 class="text-sm uppercase tracking-[0.2em] text-slate-400">Enter your answer</h3>
          <input v-model="answer" class="input-dark" placeholder="Type your answer" />
          <div class="flex flex-wrap gap-3">
            <button class="btn-primary" :disabled="submitting" @click="submitAnswer">
              <span v-if="submitting" class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-cyan-200 border-t-transparent" />
              Submit answer
            </button>
            <button class="btn-secondary" @click="answer = ''">Clear</button>
          </div>
          <p v-if="locked" class="rounded-lg border border-amber-500/50 bg-amber-900/30 px-3 py-2 text-sm text-amber-100">
            Mission locked. Complete previous tasks first.
          </p>
          <p v-if="feedback" class="rounded-lg border border-green-500/50 bg-green-900/30 px-3 py-2 text-sm text-green-100">
            {{ feedback }}
          </p>
          <p v-if="error" class="rounded-lg border border-red-500/60 bg-red-900/40 px-3 py-2 text-sm text-red-100">
            {{ error }}
          </p>
        </div>

        <div v-if="missionStatus === 'completed' && nextMissionFromStatus" class="pt-2">
          <button class="btn-primary" @click="goToMission(content.missionById(nextMissionFromStatus)?.mission_no || mission.mission_no + 1)">
            Next mission
          </button>
        </div>
      </section>

      <aside class="glow-card p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-display text-lg">Mission log</h3>
          <NuxtLink to="/log" class="text-sm text-cyan-200 hover:text-cyan-100">Open log</NuxtLink>
        </div>
        <div class="space-y-2 text-sm">
          <div
            v-for="m in content.manifest?.missions"
            :key="m.id"
            class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-900/60 px-3 py-2"
          >
            <div>
              <p class="font-semibold">Mission {{ m.mission_no.toString().padStart(2, '0') }}</p>
              <p class="text-xs text-slate-400">{{ m.slug }}</p>
            </div>
            <div class="flex items-center gap-2">
              <span
                class="h-2 w-2 rounded-full"
                :class="{
                  'bg-emerald-400': progress.missionsStatus[m.id] === 'completed',
                  'bg-cyan-300': progress.missionsStatus[m.id] === 'active',
                  'bg-slate-600': !progress.missionsStatus[m.id] || progress.missionsStatus[m.id] === 'locked',
                }"
              />
              <button
                class="btn-secondary text-xs px-2 py-1"
                :disabled="progress.missionsStatus[m.id] === 'locked'"
                @click="goToMission(m.mission_no)">
                Open
              </button>
            </div>
          </div>
        </div>
      </aside>
    </div>
  </div>
</template>
