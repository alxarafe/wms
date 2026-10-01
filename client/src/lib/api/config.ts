export type ApiMode = 'php' | 'java';

export const API_BASE_URLS: Record<'php' | 'java', string> = {
  php: 'http://localhost:28080',
  java: 'http://localhost:38080',
};

// Almacén sembrado por database/migrations/002_seed_demo_data.sql (actualizado por 006).
export const DEMO_WAREHOUSE_ID = '01a0aca9-bc00-7010-8000-000000000001';

function initialMode(): ApiMode {
  const fromEnv = import.meta.env.VITE_API_MODE as ApiMode | undefined;
  return fromEnv === 'java' ? 'java' : 'php';
}

export let apiMode: ApiMode = initialMode();

export function setApiMode(mode: ApiMode): void {
  apiMode = mode;
}

export function apiBaseUrl(): string {
  return API_BASE_URLS[apiMode];
}