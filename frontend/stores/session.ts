import { defineStore } from 'pinia';

type SessionPayload = {
  token: string;
  agentKey: string;
  seriesSlug: string;
  bookSlug: string;
  seasonNo: number;
};

const STORAGE_KEY = 'agentlog-session';

type SessionState = {
  token: string | null;
  agentKey: string | null;
  seriesSlug: string;
  bookSlug: string;
  seasonNo: number;
  seasonId: number;
};

const loadFromStorage = (): SessionState => {
  if (!process.client) {
    return {
      token: null,
      agentKey: null,
      seriesSlug: 'agent-academy',
      bookSlug: 'mission-math',
      seasonNo: 1,
      seasonId: 1,
    };
  }

  const raw = localStorage.getItem(STORAGE_KEY);
  if (!raw) {
    return {
      token: null,
      agentKey: null,
      seriesSlug: 'agent-academy',
      bookSlug: 'mission-math',
      seasonNo: 1,
      seasonId: 1,
    };
  }

  try {
    const parsed = JSON.parse(raw);
    return {
      token: parsed.token ?? null,
      agentKey: parsed.agentKey ?? null,
      seriesSlug: parsed.seriesSlug ?? 'agent-academy',
      bookSlug: parsed.bookSlug ?? 'mission-math',
      seasonNo: parsed.seasonNo ?? 1,
      seasonId: parsed.seasonId ?? 1,
    } satisfies SessionState;
  } catch (error) {
    console.warn('Failed to parse session storage', error);
    return {
      token: null,
      agentKey: null,
      seriesSlug: 'agent-academy',
      bookSlug: 'mission-math',
      seasonNo: 1,
      seasonId: 1,
    };
  }
};

const persist = (state: SessionState) => {
  if (!process.client) return;
  localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
};

export const useSessionStore = defineStore('session', {
  state: (): SessionState => loadFromStorage(),
  actions: {
    setSession(payload: SessionPayload) {
      this.token = payload.token;
      this.agentKey = payload.agentKey;
      this.seriesSlug = payload.seriesSlug;
      this.bookSlug = payload.bookSlug;
      this.seasonNo = payload.seasonNo;
      this.seasonId = 1; // placeholder from backend seed
      persist(this.$state);
    },
    clearSession() {
      this.token = null;
      this.agentKey = null;
      this.seriesSlug = 'agent-academy';
      this.bookSlug = 'mission-math';
      this.seasonNo = 1;
      this.seasonId = 1;
      persist(this.$state);
    },
    hydrate() {
      const loaded = loadFromStorage();
      this.$patch(loaded);
    },
  },
});
