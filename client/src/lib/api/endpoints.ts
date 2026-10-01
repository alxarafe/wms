import { httpCall } from './client';
import type {
  CreateItemFamilyRequest,
  CreateItemRequest,
  CreateUomRequest,
  IssueRequest,
  Item,
  ItemFamily,
  LocationState,
  OperationResult,
  ReceiptRequest,
  Uom,
  WarehouseState,
} from './types';

// Punto único de acceso a las APIs PHP y Java. Las vistas no conocen el transporte.

export async function fetchWarehouseState(warehouseId: string): Promise<OperationResult<WarehouseState>> {
  return httpCall<WarehouseState>(`/api/warehouses/${warehouseId}/state`, { method: 'GET' });
}

export async function fetchItemFamilies(): Promise<OperationResult<ItemFamily[]>> {
  return httpCall<ItemFamily[]>('/api/item-families', { method: 'GET' });
}

export async function createItemFamily(request: CreateItemFamilyRequest): Promise<OperationResult<ItemFamily>> {
  return httpCall<ItemFamily>('/api/item-families', { method: 'POST', body: request });
}

export async function fetchUoms(): Promise<OperationResult<Uom[]>> {
  return httpCall<Uom[]>('/api/uoms', { method: 'GET' });
}

export async function createUom(request: CreateUomRequest): Promise<OperationResult<Uom>> {
  return httpCall<Uom>('/api/uoms', { method: 'POST', body: request });
}

export async function fetchItems(): Promise<OperationResult<Item[]>> {
  return httpCall<Item[]>('/api/items', { method: 'GET' });
}

export async function createItem(request: CreateItemRequest): Promise<OperationResult<Item>> {
  return httpCall<Item>('/api/items', { method: 'POST', body: request });
}

export async function submitReceipt(request: ReceiptRequest): Promise<OperationResult<LocationState>> {
  return httpCall<LocationState>('/api/receipts', { method: 'POST', body: request });
}

export async function submitIssue(request: IssueRequest): Promise<OperationResult<LocationState>> {
  return httpCall<LocationState>('/api/issues', { method: 'POST', body: request });
}