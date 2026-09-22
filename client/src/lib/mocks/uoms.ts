import type { CreateUomRequest, OperationFailure, OperationResult, Uom } from '../api/types';

// −−− Capa de simulacros de unidades de medida −−−
// Devuelve el mismo contrato que GET/POST /api/uoms en PHP y Java.
// Las unidades base son las que siembra la migración 001.

let uoms: Uom[] = [
  { id: '01a0aca9-bc00-700c-8000-00000000000c', code: 'EA', description: 'Unit / Each' },
  { id: '01a0aca9-bc00-700d-8000-00000000000d', code: 'BOX', description: 'Standard Box' },
  { id: '01a0aca9-bc00-700e-8000-00000000000e', code: 'PAL', description: 'Standard Pallet' },
  { id: '01a0aca9-bc00-700f-8000-00000000000f', code: 'KG', description: 'Kilogram' },
];

function clone<T>(value: T): T {
  return JSON.parse(JSON.stringify(value)) as T;
}

function failure(status: number, error: string): OperationFailure {
  return { ok: false, status, error, details: null };
}

export function mockFetchUoms(): OperationResult<Uom[]> {
  const sorted = [...uoms].sort((a, b) => a.code.localeCompare(b.code));
  return { ok: true, status: 200, data: clone(sorted) };
}

export function mockFindUomByCode(code: string): Uom | null {
  return uoms.find((uom) => uom.code === code.trim()) ?? null;
}

export function mockCreateUom(request: CreateUomRequest): OperationResult<Uom> {
  const code = request.code.trim();
  if (!/^[A-Z][A-Z0-9]{1,9}$/.test(code)) {
    return failure(400, `UomCode must be uppercase alphanumeric (2-10 chars). Got: ${code || '(vacío)'}`);
  }
  if (request.description.length > 255) {
    return failure(400, 'Uom description cannot exceed 255 characters.');
  }
  if (uoms.some((uom) => uom.code === code)) {
    return failure(409, 'Uom code already exists.');
  }
  const uom: Uom = { id: crypto.randomUUID(), code, description: request.description };
  uoms = [...uoms, uom];
  return { ok: true, status: 201, data: clone(uom) };
}