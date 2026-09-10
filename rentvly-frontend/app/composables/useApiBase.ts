const FALLBACK_API_BASE = 'http://localhost:8080/api/v1'

export function useApiBase(): string {
  const config = useRuntimeConfig()
  const base = String(config.public.apiBase ?? '').replace(/\/$/, '')
  return base || FALLBACK_API_BASE
}
