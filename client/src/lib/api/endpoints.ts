import { apiMode } from './config';
import { httpCall } from './client';
import { mockCreateItemFamily, mockFetchItemFamilies } from '../mocks/catalogue';
import {
  mockFetchWarehouseState,
  mockSubmitIssue,
  mockSubmitReceipt,
} from '../mocks/warehouse';
import type {
  CreateItemFamilyRequest,
  IssueRequest,
  ItemFamily,
  LocationState,
  OperationResult,
  ReceiptRequest,
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