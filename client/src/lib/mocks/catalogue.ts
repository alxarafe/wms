import type {
  CreateItemFamilyRequest,
  ItemFamily,
  OperationFailure,
  OperationResult,
} from '../api/types';

// −−− Capa de simulacros del catálogo −−−
// Devuelve el mismo contrato que GET/POST /api/item-families en PHP y Java.
// Los atributos FAMILY disponibles son los que siembra la migración 001.

const FAMILY_ATTRIBUTES = new Set(['IS_FOOD', 'IS_REFRIGERATED', 'IS_CHEMICAL', 'IS_FROZEN']);

let families: ItemFamily[] = [
  {
    id: '01a0aca9-bc00-7701-8000-000000000001',
    code: 'REFRIGERATED_FOOD',
    name: 'Alimentos refrigerados',
    attributes: ['IS_FOOD', 'IS_REFRIGERATED'],
  },
  {
    id: '01a0aca9-bc00-7702-8000-000000000002',
    code: 'DRY_FOOD',
    name: 'Alimentos secos',
    attributes: ['IS_FOOD'],
  },
  {
    id: '01a0aca9-bc00-7703-8000-000000000003',
    code: 'CHEMICAL',
    name: 'Productos químicos',
    attributes: ['IS_CHEMICAL'],
  },
];

function clone<T>(value: T): T {
  return JSON.parse(JSON.stringify(value)) as T;
}

function failure(status: number, error: string): OperationFailure {
  return { ok: false, status, error, details: null };
}

export function mockFetchItemFamilies(): OperationResult<ItemFamily[]> {
  const sorted = [...families].sort((a, b) => a.code.localeCompare(b.code));
  return { ok: true, status: 200, data: clone(sorted) };
}

export function mockFindFamilyByCode(code: string): ItemFamily | null {
  return families.find((family) => family.code === code.trim()) ?? null;
}

export function mockCreateItemFamily(request: CreateItemFamilyRequest): OperationResult<ItemFamily> {
  const code = request.code.trim();
  if (code === '') {
    return failure(422, 'El código no puede estar vacío.');
  }
  if (request.name.trim() === '') {
    return failure(422, 'El nombre no puede estar vacío.');
  }
  if (families.some((family) => family.code === code)) {
    return failure(409, 'Item family code already exists.');
  }
  for (const attribute of request.attributes) {
    if (!FAMILY_ATTRIBUTES.has(attribute)) {
      return failure(400, `Unknown FAMILY attribute: ${attribute}`);
    }
  }
  const family: ItemFamily = {
    id: crypto.randomUUID(),
    code,
    name: request.name.trim(),
    attributes: [...request.attributes].sort(),
  };
  families = [...families, family];
  return { ok: true, status: 201, data: clone(family) };
}