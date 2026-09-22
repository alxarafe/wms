import { mockFindFamilyByCode } from './catalogue';
import { mockFindUomByCode } from './uoms';
import type { CreateItemRequest, Item, OperationFailure, OperationResult } from '../api/types';

// −−− Capa de simulacros de artículos −−−
// Devuelve el mismo contrato que GET/POST /api/items en PHP y Java.
// Los artículos base replican las familias y unidades que presentan los
// simulacros de catálogo y unidades de la demo.

let items: Item[] = [
  {
    id: '01a0aca9-bc00-7a01-8000-000000000001',
    sku: 'YOGUR FRESA',
    name: 'Yogur de fresa refrigerado',
    familyCode: 'REFRIGERATED_FOOD',
    baseUomCode: 'EA',
    isBatchManaged: true,
    isExpirable: true,
  },
  {
    id: '01a0aca9-bc00-7a02-8000-000000000002',
    sku: 'PALITOS CANGREJO',
    name: 'Palitos de cangrejo congelados',
    familyCode: 'REFRIGERATED_FOOD',
    baseUomCode: 'EA',
    isBatchManaged: true,
    isExpirable: true,
  },
  {
    id: '01a0aca9-bc00-7a03-8000-000000000003',
    sku: 'ARROZ LARGO',
    name: 'Arroz de grano largo',
    familyCode: 'DRY_FOOD',
    baseUomCode: 'EA',
    isBatchManaged: false,
    isExpirable: false,
  },
  {
    id: '01a0aca9-bc00-7a04-8000-000000000004',
    sku: 'LEJIA BLANCA',
    name: 'Lejía blanca',
    familyCode: 'CHEMICAL',
    baseUomCode: 'PAL',
    isBatchManaged: false,
    isExpirable: false,
  },
  {
    id: '01a0aca9-bc00-7a05-8000-000000000005',
    sku: 'AGUA MINERAL',
    name: 'Agua mineral sin gas',
    familyCode: 'DRY_FOOD',
    baseUomCode: 'EA',
    isBatchManaged: false,
    isExpirable: false,
  },
];

function clone<T>(value: T): T {
  return JSON.parse(JSON.stringify(value)) as T;
}

function failure(status: number, error: string): OperationFailure {
  return { ok: false, status, error, details: null };
}

export function mockFetchItems(): OperationResult<Item[]> {
  const sorted = [...items].sort((a, b) => a.sku.localeCompare(b.sku));
  return { ok: true, status: 200, data: clone(sorted) };
}

export function mockCreateItem(request: CreateItemRequest): OperationResult<Item> {
  const sku = request.sku.trim();
  const name = request.name.trim();
  if (sku === '') {
    return failure(400, 'SKU cannot be empty.');
  }
  if (sku.length > 50) {
    return failure(400, `SKU must not exceed 50 characters. Got: ${sku}`);
  }
  if (name === '') {
    return failure(400, 'Item name cannot be empty.');
  }
  if (name.length > 255) {
    return failure(400, 'Item name cannot exceed 255 characters.');
  }
  if (mockFindFamilyByCode(request.familyCode) === null) {
    return failure(400, 'Item family code does not exist.');
  }
  if (mockFindUomByCode(request.baseUomCode) === null) {
    return failure(400, 'Base unit of measure code does not exist.');
  }
  if (request.isExpirable && !request.isBatchManaged) {
    return failure(400, 'An expirable item must also be batch-managed.');
  }
  if (items.some((item) => item.sku === sku)) {
    return failure(409, 'Item sku already exists.');
  }
  const item: Item = {
    id: crypto.randomUUID(),
    sku,
    name,
    familyCode: request.familyCode.trim(),
    baseUomCode: request.baseUomCode.trim(),
    isBatchManaged: request.isBatchManaged,
    isExpirable: request.isExpirable,
  };
  items = [...items, item];
  return { ok: true, status: 201, data: clone(item) };
}