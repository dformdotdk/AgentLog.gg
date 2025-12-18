import { FetchError } from 'ofetch';
import { useSessionStore } from '~/stores/session';

type ApiError = { error_code: string; message: string; status?: number };

type ApiFetchOptions<T> = Parameters<typeof $fetch<T>>[1];

export const useApi = () => {
  const config = useRuntimeConfig();
  const session = useSessionStore();

  const apiFetch = async <T>(path: string, options: ApiFetchOptions<T> = {}) => {
    const headers: Record<string, string> = {
      ...(options?.headers as Record<string, string> | undefined),
    };

    if (session.token) {
      headers['Authorization'] = `Bearer ${session.token}`;
    }

    try {
      return await $fetch<T>(path, {
        baseURL: config.public.apiBaseUrl,
        ...options,
        headers,
      });
    } catch (err: unknown) {
      if (err instanceof FetchError) {
        const data = (err.data as any) || {};
        const mapped: ApiError = {
          error_code: data.error_code || 'API_ERROR',
          message: data.message || err.message,
          status: err.response?.status,
        };
        throw mapped;
      }
      throw { error_code: 'NETWORK_ERROR', message: 'Unable to reach server' } as ApiError;
    }
  };

  return { apiFetch };
};
