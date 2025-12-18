<script setup lang="ts">
import { useContentStore } from '~/stores/content';
import { useProgressStore } from '~/stores/progress';
import { useSessionStore } from '~/stores/session';

const session = useSessionStore();
const content = useContentStore();
const progress = useProgressStore();
const router = useRouter();

session.hydrate();

const load = async () => {
  if (!session.token) {
    await router.push('/start');
    return;
  }
  await content.loadManifest(session.seriesSlug, session.bookSlug, session.seasonNo);
  await progress.refreshStatus(session.seasonId);
};

onMounted(load);

const openMission = (missionNo: number) => {
  const padded = missionNo.toString().padStart(2, '0');
  router.push(`/${session.seriesSlug}/${session.bookSlug}/s/${session.seasonNo}/m/${padded}`);
};
</script>

<template>
  <div class="space-y-4">
    <div class="glow-card p-4 flex items-center justify-between">
      <div>
        <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Mission log</p>
        <h1 class="font-display text-2xl">Progress overview</h1>
      </div>
      <div class="text-sm text-slate-300">
        <p>XP: {{ progress.xp_total }}</p>
        <p>Level {{ progress.level }}</p>
      </div>
    </div>

    <div class="grid gap-3 md:grid-cols-2">
      <div
        v-for="mission in content.manifest?.missions"
        :key="mission.id"
        class="glow-card p-4 flex flex-col gap-2"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Mission {{ mission.mission_no.toString().padStart(2, '0') }}</p>
            <h2 class="font-display text-lg">{{ mission.slug }}</h2>
          </div>
          <div class="flex items-center gap-2 text-sm">
            <span
              class="h-2 w-2 rounded-full"
              :class="{
                'bg-emerald-400': progress.missionsStatus[mission.id] === 'completed',
                'bg-cyan-300': progress.missionsStatus[mission.id] === 'active',
                'bg-slate-600': !progress.missionsStatus[mission.id] || progress.missionsStatus[mission.id] === 'locked',
              }"
            />
            <span class="text-slate-400">{{ progress.missionsStatus[mission.id] || 'locked' }}</span>
          </div>
        </div>
        <p class="text-sm text-slate-400">Reward: {{ mission.xp_reward }} XP</p>
        <button
          class="btn-secondary self-start text-sm"
          :disabled="progress.missionsStatus[mission.id] === 'locked'"
          @click="openMission(mission.mission_no)"
        >
          Open mission
        </button>
      </div>
    </div>
  </div>
</template>
