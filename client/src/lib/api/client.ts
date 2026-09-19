import { apiBaseUrl } from './config';
import type { OperationResult } from './types';

interface HttpOptions {
  method: 'GET' | 'POST' | 'PUT' | 'DELETE';
  body?: unknown;
}

export async function httpCall<T>(path: string, options: HttpOptions): Promise<OperationResult<T>> {
  const base = apiBaseUrl();
  if (!base) {
    return { ok: false, status: 0, error: 'Modo simulado activo: no hay base URL.', details: null };
  }

  try {
    const response = await fetch(`${base}${path}`, {
      method: options.method,
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: options.body === undefined ? undefined : JSON.stringify(options.body),
    });
    const text = await response.text();
    const body: unknown = text ? JSON.parse(text) : null;

    if (!response.ok) {
      const error = (body as { error?: string } | null)?.error ?? `Error HTTP ${response.status}`;
      return { ok: false, status: response.status, error, details: body };
    }
    return { ok: true, status: response.status, data: body as T };
  } catch (err) {
    return {
      ok: false,
      status: 0,
      error: err instanceof Error ? err.message : String(err),
      details: null,
    };
  }
}