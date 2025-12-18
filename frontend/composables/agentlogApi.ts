import { useApi } from './useApi';

type MissionVideo = {
  type: string;
  parent_only: boolean;
  teacher_only: boolean;
  title: string;
  provider: string;
  provider_id: string;
  duration_seconds: number;
  subtitles: Record<string, string> | null;
};

type Mission = {
  id: number;
  mission_no: number;
  slug: string;
  xp_reward: number;
  is_boss: boolean;
  topic_tags: string[];
  assets: Record<string, unknown> | null;
  videos: MissionVideo[];
};

type ContentResponse = {
  series: Record<string, any>;
  book: Record<string, any>;
  season: Record<string, any>;
  missions: Mission[];
};

type PairRequest = {
  series_slug: string;
  book_slug: string;
  season_no: number;
  book_token?: string;
  device_agent_id?: string;
};

type PairResponse = {
  session_token: string;
  agent_key: string;
  agent_state: {
    xp_total: number;
    level: number;
    next_mission_no: number;
  };
};

type StatusResponse = {
  xp_total: number;
  level: number;
  next_mission_id: number | null;
  milestones_unlocked: number[];
  missions: { mission_id: number; status: 'locked' | 'active' | 'completed' }[];
};

type AttemptResponse =
  | { success: false; error_code: string; hint_available?: boolean }
  | {
      success: true;
      xp_gained: number;
      new_xp: number;
      unlocks: Record<string, any>[];
      reward_available: boolean;
      next_mission_id?: number | null;
    };

export const useAgentlogApi = () => {
  const { apiFetch } = useApi();

  const getContent = async (series: string, book: string, seasonNo: number) =>
    apiFetch<ContentResponse>(`/api/v1/content/${series}/${book}/s/${seasonNo}`);

  const pair = async (payload: PairRequest) =>
    apiFetch<PairResponse>('/api/v1/pair', { method: 'POST', body: payload });

  const getStatus = async (seasonId: number) =>
    apiFetch<StatusResponse>('/api/v1/agent/status', { query: { season_id: seasonId } });

  const attemptMission = async (missionId: number, answer: string) =>
    apiFetch<AttemptResponse>(`/api/v1/missions/${missionId}/attempt`, {
      method: 'POST',
      body: { answer },
    });

  return { getContent, pair, getStatus, attemptMission };
};

export type { Mission, ContentResponse, PairResponse, StatusResponse, AttemptResponse };
