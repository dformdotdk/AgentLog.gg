import { defineStore } from 'pinia';
import type { StatusResponse } from '~/composables/agentlogApi';
import { useAgentlogApi } from '~/composables/agentlogApi';

export const useProgressStore = defineStore('progress', {
  state: () => ({
    xp_total: 0,
    level: 1,
    next_mission_id: null as number | null,
    milestones_unlocked: [] as number[],
    missionsStatus: {} as Record<number, 'locked' | 'active' | 'completed'>,
    loading: false,
  }),
  actions: {
    async refreshStatus(seasonId: number) {
      this.loading = true;
      try {
        const api = useAgentlogApi();
        const status: StatusResponse = await api.getStatus(seasonId);
        this.xp_total = status.xp_total;
        this.level = status.level;
        this.next_mission_id = status.next_mission_id;
        this.milestones_unlocked = status.milestones_unlocked;
        const mapping: Record<number, 'locked' | 'active' | 'completed'> = {};
        status.missions.forEach((m) => {
          mapping[m.mission_id] = m.status;
        });
        this.missionsStatus = mapping;
      } finally {
        this.loading = false;
      }
    },
  },
});
