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
const showHint = ref(false);
const showRewardOverlay = ref(false);
const rewardDetails = reactive({
  xpGained: 0,
  unlocks: [] as Record<string, any>[],
  successCopy: '',
  nextMissionNo: null as number | null,
});
const rewardButtonRef = ref<HTMLButtonElement | null>(null);

const mission = computed(() => content.missionByNo(missionNoParam.value));
const totalMissions = computed(() => content.manifest?.missions.length || 0);
const missionStatus = computed(() => {
  if (!mission.value) return 'locked';
  return progress.missionsStatus[mission.value.id] || 'locked';
});

const missionTitle = computed(
  () => mission.value?.content?.title || mission.value?.slug.replace('-', ' ') || 'Mission',
);
const briefingLines = computed(() => mission.value?.content?.briefing || []);
const objectiveLine = computed(() => mission.value?.content?.objective || 'Complete the mission objective.');
const taskPrompt = computed(() => mission.value?.content?.task?.prompt || 'Enter your answer');
const hintText = computed(() => mission.value?.content?.hint || 'Think back to the last training clip.');

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

const computeNextMissionNo = () => {
  if (progress.next_mission_id) {
    const nextMission = content.missionById(progress.next_mission_id);
    if (nextMission) return nextMission.mission_no;
  }

  const activeMission = content.manifest?.missions.find(
    (m) => progress.missionsStatus[m.id] === 'active' && m.id !== mission.value?.id,
  );
  if (activeMission) return activeMission.mission_no;

  if (mission.value) {
    const fallback = mission.value.mission_no + 1;
    if (fallback <= totalMissions.value) return fallback;
  }

  return null;
};

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

    rewardDetails.xpGained = res.xp_gained;
    rewardDetails.unlocks = res.unlocks || [];
    rewardDetails.successCopy = mission.value?.content?.success_copy || 'Mission complete!';
    rewardDetails.nextMissionNo = computeNextMissionNo();
    showRewardOverlay.value = true;
    await nextTick();
    rewardButtonRef.value?.focus();
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

const closeRewardOverlay = () => {
  showRewardOverlay.value = false;
};

const continueToNextMission = () => {
  const targetNo = rewardDetails.nextMissionNo ?? mission.value?.mission_no ?? 1;
  showRewardOverlay.value = false;
  if (targetNo && (!mission.value || targetNo !== mission.value.mission_no)) {
    goToMission(targetNo);
  }
};
</script>

<template>
  <div class="space-y-4">
    <div class="glow-card p-4 flex flex-wrap items-center justify-between gap-4">
      <div>
        <p class="text-xs uppercase tracking-[0.25em] text-slate-400">XP: {{ progress.xp_total }} · Level {{ progress.level }}</p>
        <h1 class="font-display text-2xl">Mission {{ mission?.mission_no?.toString().padStart(2, '0') || '--' }}</h1>
        <p class="text-sm text-slate-400">Mission {{ mission?.mission_no || missionNoParam }}/{{ totalMissions || '—' }} · {{ mission?.slug }}</p>
      </div>
      <div class="text-right text-sm text-slate-300">
        <p class="rounded-lg border border-cyan-400/50 bg-cyan-400/10 px-3 py-1 text-xs text-cyan-100">+{{ mission?.xp_reward || 0 }} XP reward</p>
        <p class="text-xs text-slate-400">Status: {{ missionStatus }}</p>
      </div>
    </div>

    <div v-if="loading" class="glow-card p-6 text-center text-slate-300">Loading mission...</div>
    <div v-else-if="!mission" class="glow-card p-6 text-center text-red-200">Mission not found.</div>
    <div v-else class="grid gap-4 lg:grid-cols-[1.5fr,0.9fr]">
      <section class="space-y-4">
        <div class="glow-card p-6 space-y-4">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Mission #{{ mission.mission_no }}</p>
              <h2 class="font-display text-2xl text-cyan-50">{{ missionTitle }}</h2>
              <p class="text-sm text-slate-400">{{ mission.slug }}</p>
            </div>
            <div class="flex flex-col items-end gap-2">
              <div class="rounded-lg border border-cyan-400/40 bg-cyan-400/10 px-3 py-1 text-xs text-cyan-100">+{{ mission.xp_reward }} XP</div>
              <span
                class="rounded-full px-3 py-1 text-xs"
                :class="{
                  'border border-emerald-400/60 bg-emerald-500/10 text-emerald-100': missionStatus === 'completed',
                  'border border-cyan-400/60 bg-cyan-500/10 text-cyan-100': missionStatus === 'active',
                  'border border-slate-700 bg-slate-800 text-slate-200': missionStatus === 'locked',
                }"
              >
                {{ missionStatus === 'locked' ? 'Locked' : missionStatus === 'completed' ? 'Completed' : 'Active' }}
              </span>
            </div>
          </div>

          <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <p class="text-xs uppercase tracking-[0.15em] text-slate-400">Briefing</p>
            <ul class="mt-2 list-disc space-y-1 pl-4 text-slate-200">
              <li v-for="line in briefingLines" :key="line">{{ line }}</li>
            </ul>
          </div>

          <div class="rounded-xl border border-cyan-500/30 bg-cyan-500/5 p-4 text-slate-100">
            <p class="text-xs uppercase tracking-[0.2em] text-cyan-200">Objective</p>
            <p class="mt-2 text-base leading-relaxed">{{ objectiveLine }}</p>
          </div>
        </div>

        <div class="glow-card p-6 space-y-4">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Task</p>
              <h3 class="font-display text-xl leading-snug">{{ taskPrompt }}</h3>
              <p class="text-xs text-slate-500">Answer format: {{ mission.content?.task?.answer_format || 'text' }}</p>
            </div>
            <div class="rounded-lg border border-cyan-400/40 bg-cyan-400/10 px-3 py-1 text-xs text-cyan-100">+{{ mission.xp_reward }} XP</div>
          </div>

          <div class="space-y-3">
            <input v-model="answer" class="input-dark" :disabled="missionStatus === 'locked'" placeholder="Type your answer" />
            <div class="flex flex-wrap gap-3">
              <button class="btn-primary" :disabled="submitting || missionStatus === 'locked'" @click="submitAnswer">
                <span v-if="submitting" class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-cyan-200 border-t-transparent" />
                Submit
              </button>
              <button class="btn-secondary" type="button" @click="answer = ''">Clear</button>
              <button class="btn-secondary" type="button" @click="showHint = !showHint">{{ showHint ? 'Hide hint' : 'Need intel?' }}</button>
            </div>

            <p
              v-if="missionStatus === 'locked' || locked"
              class="rounded-lg border border-amber-500/50 bg-amber-900/30 px-3 py-2 text-sm text-amber-100"
            >
              Mission locked. Complete previous mission first.
            </p>
            <p v-if="showHint" class="rounded-lg border border-cyan-500/40 bg-cyan-500/10 px-3 py-2 text-sm text-cyan-50">{{ hintText }}</p>
            <p v-if="feedback" class="rounded-lg border border-green-500/50 bg-green-900/30 px-3 py-2 text-sm text-green-100">{{ feedback }}</p>
            <p v-if="error" class="rounded-lg border border-red-500/60 bg-red-900/40 px-3 py-2 text-sm text-red-100">{{ error }}</p>
          </div>

          <details class="rounded-xl border border-slate-800 bg-slate-900/60 p-4 text-sm text-slate-200">
            <summary class="cursor-pointer text-cyan-200">Watch briefing</summary>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
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
          </details>
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
            class="flex flex-col gap-2 rounded-lg border border-slate-800 bg-slate-900/60 px-3 py-2"
          >
            <div class="flex items-center justify-between gap-2">
              <div>
                <p class="font-semibold">Mission {{ m.mission_no.toString().padStart(2, '0') }}</p>
                <p class="text-xs text-slate-400">{{ m.content?.title || m.slug }}</p>
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
                  @click="goToMission(m.mission_no)"
                >
                  {{
                    progress.missionsStatus[m.id] === 'completed'
                      ? 'Replay'
                      : progress.missionsStatus[m.id] === 'active'
                        ? 'Continue'
                        : 'Locked'
                  }}
                </button>
              </div>
            </div>
            <p v-if="progress.missionsStatus[m.id] === 'locked'" class="text-xs text-amber-200 flex items-center gap-1">
              <span aria-hidden="true">🔒</span> Complete previous mission first.
            </p>
          </div>
        </div>
      </aside>
    </div>

    <div
      v-if="showRewardOverlay"
      ref="overlayContainer"
      class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/80 px-4 backdrop-blur"
      role="dialog"
      aria-modal="true"
      @keydown.esc.prevent="closeRewardOverlay"
      @click.self="closeRewardOverlay"
      tabindex="-1"
    >
      <div class="w-full max-w-lg rounded-2xl border border-cyan-400/40 bg-slate-900/90 p-6 shadow-2xl">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="text-xs uppercase tracking-[0.2em] text-emerald-200">Mission success</p>
            <h3 class="font-display text-2xl text-cyan-50">+{{ rewardDetails.xpGained }} XP gained</h3>
            <p class="text-sm text-slate-300">{{ rewardDetails.successCopy }}</p>
          </div>
          <button class="btn-secondary px-2 py-1 text-xs" type="button" @click="closeRewardOverlay">Close</button>
        </div>
        <div class="mt-4 space-y-2 text-sm text-slate-200">
          <p v-if="rewardDetails.unlocks?.length" class="text-cyan-100">Unlocks:</p>
          <ul v-if="rewardDetails.unlocks?.length" class="list-disc space-y-1 pl-5 text-slate-100">
            <li v-for="(unlock, idx) in rewardDetails.unlocks" :key="idx">{{ unlock.title || JSON.stringify(unlock) }}</li>
          </ul>
          <p v-else class="text-slate-400">No new items unlocked this time.</p>
        </div>
        <button
          ref="rewardButtonRef"
          class="btn-primary mt-6 w-full justify-center"
          type="button"
          @click="continueToNextMission"
        >
          Continue
        </button>
      </div>
    </div>
  </div>
</template>
