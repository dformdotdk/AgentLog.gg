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
const feedbackType = ref<'success' | 'error' | null>(null);
const error = ref<string | null>(null);
const submitting = ref(false);
const locked = ref(false);
const loading = ref(true);
const showHint = ref(false);
const showVideoModal = ref(false);
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
const missionNumberLabel = computed(
  () => mission.value?.mission_no?.toString().padStart(2, '0') || missionNoParam.value.toString().padStart(2, '0'),
);
const missionMetaLabel = computed(() => {
  const slug = mission.value?.slug || 'intel-loading';
  const missionNo = mission.value?.mission_no || missionNoParam.value;
  const total = totalMissions.value || '—';
  return `${slug} · ${missionNo}/${total}`;
});
const briefingLines = computed(() => mission.value?.content?.briefing || []);
const objectiveLine = computed(() => mission.value?.content?.objective || '');
const taskPrompt = computed(() => mission.value?.content?.task?.prompt || '');
const hintText = computed(() => mission.value?.content?.hint || '');
const hasBriefingContent = computed(() => briefingLines.value.length || objectiveLine.value);
const hasTaskContent = computed(() => taskPrompt.value);
const missionVideos = computed(() => mission.value?.videos?.filter((v) => !v.parent_only) || []);
const briefingVideo = computed(() => {
  const introVideo = missionVideos.value.find((v) => v.type === 'intro' && v.provider === 'youtube');
  if (introVideo) return introVideo;
  return missionVideos.value.find((v) => v.provider === 'youtube') || null;
});

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
  feedbackType.value = null;
  error.value = null;
  locked.value = false;
  rewardDetails.xpGained = 0;
  rewardDetails.unlocks = [];
  rewardDetails.successCopy = '';
  rewardDetails.nextMissionNo = null;
  try {
    const res = await api.attemptMission(mission.value.id, answer.value);
    if (!res.success) {
      if (res.error_code === 'LOCKED') locked.value = true;
      feedbackType.value = 'error';
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
    feedbackType.value = 'success';
    feedback.value = rewardDetails.successCopy;
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
    <div class="glow-card p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="text-xs uppercase tracking-[0.22em] text-slate-400">Mission {{ missionNumberLabel }}</p>
            <h1 class="font-display text-2xl text-cyan-50">{{ missionTitle }}</h1>
            <p class="text-xs text-slate-500">{{ missionMetaLabel }}</p>
          </div>
          <div class="flex flex-col items-end gap-2 text-sm">
            <span class="rounded-lg border border-cyan-400/50 bg-cyan-400/10 px-3 py-1 text-cyan-100">+{{ mission?.xp_reward || 0 }} XP</span>
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
    </div>

    <div v-if="loading" class="glow-card p-6 text-center text-slate-300">Loading mission...</div>
    <div v-else-if="!mission" class="glow-card p-6 text-center text-red-200">Mission not found.</div>
    <div v-else class="grid gap-4 lg:grid-cols-[1.5fr,0.9fr]">
      <section class="space-y-4">
        <div v-if="hasBriefingContent" class="glow-card p-6 space-y-4">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Briefing</p>
              <h2 class="font-display text-xl text-cyan-50">{{ missionTitle }}</h2>
            </div>
            <button
              v-if="briefingVideo"
              class="btn-primary"
              type="button"
              @click="showVideoModal = true"
            >
              Play briefing
            </button>
          </div>

          <div class="rounded-xl border border-slate-800 bg-slate-900/60 p-4">
            <ul class="list-disc space-y-2 pl-5 text-slate-200">
              <li v-for="line in briefingLines" :key="line">{{ line }}</li>
            </ul>
          </div>

          <div v-if="objectiveLine" class="rounded-xl border border-cyan-500/30 bg-cyan-500/5 p-4 text-slate-100">
            <p class="text-xs uppercase tracking-[0.2em] text-cyan-200">Objective</p>
            <p class="mt-2 text-base leading-relaxed">{{ objectiveLine }}</p>
          </div>
        </div>
        <div v-else class="glow-card p-4 text-sm text-slate-300">Intel not loaded yet.</div>

        <div v-if="hasTaskContent" class="glow-card p-6 space-y-4">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="space-y-1">
              <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Task</p>
              <h3 class="font-display text-2xl leading-snug text-cyan-50">{{ taskPrompt }}</h3>
            </div>
            <div class="rounded-lg border border-cyan-400/40 bg-cyan-400/10 px-3 py-1 text-xs text-cyan-100">+{{ mission.xp_reward }} XP</div>
          </div>

          <div class="space-y-3">
            <input v-model="answer" class="input-dark" :disabled="missionStatus === 'locked'" placeholder="Type your answer" />
            <div class="flex flex-wrap gap-3">
              <button class="btn-primary" :disabled="submitting || missionStatus === 'locked'" @click="submitAnswer">
                <span
                  v-if="submitting"
                  class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-cyan-200 border-t-transparent"
                ></span>
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
            <p v-if="showHint && hintText" class="rounded-lg border border-cyan-500/40 bg-cyan-500/10 px-3 py-2 text-sm text-cyan-50">{{ hintText }}</p>
            <p v-if="showHint && !hintText" class="text-sm text-slate-400">Hint intel not loaded.</p>

            <div
              v-if="feedback"
              class="space-y-2 rounded-lg px-3 py-2 text-sm"
              :class="{
                'border border-green-500/50 bg-green-900/30 text-green-100': feedbackType === 'success',
                'border border-red-500/60 bg-red-900/40 text-red-100': feedbackType === 'error',
              }"
            >
              <p>{{ feedback }}</p>
              <div v-if="feedbackType === 'success'" class="space-y-1 text-xs">
                <p class="text-emerald-100">Unlocks:</p>
                <ul v-if="rewardDetails.unlocks?.length" class="list-disc space-y-1 pl-4">
                  <li v-for="(unlock, idx) in rewardDetails.unlocks" :key="idx">{{ unlock.title || JSON.stringify(unlock) }}</li>
                </ul>
                <p v-else class="text-emerald-50/80">No new unlocks this time.</p>
                <button class="btn-primary mt-2 w-full justify-center" type="button" @click="continueToNextMission">Continue</button>
              </div>
            </div>
            <p v-if="error" class="rounded-lg border border-red-500/60 bg-red-900/40 px-3 py-2 text-sm text-red-100">{{ error }}</p>
          </div>
        </div>
        <div v-else class="glow-card p-4 text-sm text-slate-300">Intel not loaded yet.</div>
      </section>

      <aside class="glow-card p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-display text-lg">Mission log</h3>
          <NuxtLink to="/log" class="text-xs text-cyan-200 hover:text-cyan-100">View log</NuxtLink>
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
                ></span>
                <button
                  class="btn-secondary text-xs px-2 py-1"
                  :disabled="progress.missionsStatus[m.id] === 'locked'"
                  @click="goToMission(m.mission_no)"
                >
                  {{
                    progress.missionsStatus[m.id] === 'completed'
                      ? 'Review'
                      : progress.missionsStatus[m.id] === 'active'
                        ? 'Continue'
                        : '🔒 Locked'
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
      v-if="showVideoModal && briefingVideo"
      class="fixed inset-0 z-30 flex items-center justify-center bg-slate-950/80 px-4 backdrop-blur"
      role="dialog"
      aria-modal="true"
      @click.self="showVideoModal = false"
    >
      <div class="w-full max-w-3xl space-y-4 rounded-2xl border border-cyan-400/40 bg-slate-900/90 p-4 shadow-2xl">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="text-xs uppercase tracking-[0.2em] text-cyan-200">Briefing playback</p>
            <h3 class="font-display text-xl text-cyan-50">{{ briefingVideo.title }}</h3>
          </div>
          <button class="btn-secondary px-3 py-1 text-xs" type="button" @click="showVideoModal = false">Close</button>
        </div>
          <div class="aspect-video overflow-hidden rounded-xl border border-slate-800 bg-slate-950">
            <iframe
              class="h-full w-full"
              :src="`https://www.youtube-nocookie.com/embed/${briefingVideo.provider_id}?rel=0&modestbranding=1`"
              :title="briefingVideo.title"
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              allowfullscreen
            ></iframe>
          </div>
      </div>
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
