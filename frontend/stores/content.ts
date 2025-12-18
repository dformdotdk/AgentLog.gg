import { defineStore } from 'pinia';
import type { ContentResponse, Mission } from '~/composables/agentlogApi';
import { useAgentlogApi } from '~/composables/agentlogApi';

export const useContentStore = defineStore('content', {
  state: () => ({
    manifest: null as ContentResponse | null,
    loading: false,
  }),
  getters: {
    missionByNo: (state) => (missionNo: number): Mission | undefined =>
      state.manifest?.missions.find((m) => m.mission_no === missionNo),
    missionById: (state) => (missionId: number): Mission | undefined =>
      state.manifest?.missions.find((m) => m.id === missionId),
  },
  actions: {
    async loadManifest(series: string, book: string, seasonNo: number) {
      if (
        this.manifest &&
        this.manifest.series.slug === series &&
        this.manifest.book.slug === book &&
        this.manifest.season.season_no === seasonNo
      )
        return;
      this.loading = true;
      try {
        const api = useAgentlogApi();
        this.manifest = await api.getContent(series, book, seasonNo);
      } finally {
        this.loading = false;
      }
    },
  },
});
