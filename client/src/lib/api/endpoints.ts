import { apiMode } from './config';
import { httpCall } from './client';
import { mockCreateItemFamily, mockFetchItemFamilies } from '../mocks/catalogue';
import { mockCreateItem, mockFetchItems } from '../mocks/items';
import { mockCreateUom, mockFetchUoms } from '../mocks/uoms';
import {
  mockFetchWarehouseState,
  mockSubmitIssue,
  mockSubmitReceipt,
} from '../mocks/warehouse';
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

// Punto único de acceso: en modo 'mock' se atiende con la capa simulada,
// en modo 'php'/'java' con la API real. Las vistas no saben en qué modo están.

export async function fetchWarehouseState(warehouseId: string): Promise<OperationResult<WarehouseState>> {
  if (apiMode === 'mock') {
    return mockFetchWarehouseState(warehouseId);
  }
  return httpCall<WarehouseState>(`/api/warehouses/${warehouseId}/state`, { method: 'GET' });
}

export async function fetchItemFamilies(): Promise<OperationResult<ItemFamily[]>> {
  if (apiMode === 'mock') {
    return mockFetchItemFamilies();
  }
  return httpCall<ItemFamily[]>('/api/item-families', { method: 'GET' });
}

export async function createItemFamily(request: CreateItemFamilyRequest): Promise<OperationResult<ItemFamily>> {
  if (apiMode === 'mock') {
    return mockCreateItemFamily(request);
  }
  return httpCall<ItemFamily>('/api/item-families', { method: 'POST', body: request });
}

export async function fetchUoms(): Promise<OperationResult<Uom[]>> {
  if (apiMode === 'mock') {
    return mockFetchUoms();
  }
  return httpCall<Uom[]>('/api/uoms', { method: 'GET' });
}

export async function createUom(request: CreateUomRequest): Promise<OperationResult<Uom>> {
  if (apiMode === 'mock') {
    return mockCreateUom(request);
  }
  return httpCall<Uom>('/api/uoms', { method: 'POST', body: request });
}

export async function fetchItems(): Promise<OperationResult<Item[]>> {
  if (apiMode === 'mock') {
    return mockFetchItems();
  }
  return httpCall<Item[]>('/api/items', { method: 'GET' });
}

export async function createItem(request: CreateItemRequest): Promise<OperationResult<Item>> {
  if (apiMode === 'mock') {
    return mockCreateItem(request);
  }
  return httpCall<Item>('/api/items', { method: 'POST', body: request });
}

export async function submitReceipt(request: ReceiptRequest): Promise<OperationResult<LocationState>> {
  if (apiMode === 'mock') {
    return mockSubmitReceipt(request);
  }
  return httpCall<LocationState>('/api/receipts', { method: 'POST', body: request });
}

export async function submitIssue(request: IssueRequest): Promise<OperationResult<LocationState>> {
  if (apiMode === 'mock') {
    return mockSubmitIssue(request);
  }
  return httpCall<LocationState>('/api/issues', { method: 'POST', body: request });
}