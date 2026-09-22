// −−− Contrato (borrador) −−−
// Esta forma es la que aplica hoy la capa de simulacros. Cuando existan los
// endpoints reales en PHP y Java, deberán devolver la misma forma (paridad de
// contrato). La decisión de ocupación/capacidad pertenece al WMS; el cliente
// muestra el estado que la API le devuelve.

export type LocationRole = 'RESERVE' | 'PICKING';
export type LocationStatus = 'ACTIVE' | 'BLOCKED' | 'DISABLED';

export interface ReferenceStock {
  itemId: string;
  itemCode: string;
  name: string;
  quantity: number;
  unit: string;
  batchCode: string | null;
  huCode: string | null;
}

export interface LocationState {
  id: string;
  code: string;
  bay: number;
  level: number;
  role: LocationRole;
  status: LocationStatus;
  blocked: boolean;
  references: ReferenceStock[];
}

export interface AisleState {
  id: string;
  code: string;
  zoneId: string;
  bays: number;
  levels: number;
  isBlocked: boolean;
  locations: LocationState[];
}

export interface ZoneState {
  id: string;
  code: string;
  zoneTypeCode: string;
  allowsMultiSku: boolean;
  isOperative: boolean;
  aisles: AisleState[];
}

export interface WarehouseState {
  id: string;
  code: string;
  name: string;
  zones: ZoneState[];
}

export interface CreateItemFamilyRequest {
  code: string;
  name: string;
  attributes: string[];
}

export interface ItemFamily {
  id: string;
  code: string;
  name: string;
  attributes: string[];
}

export interface CreateUomRequest {
  code: string;
  description: string;
}

export interface Uom {
  id: string;
  code: string;
  description: string;
}

export interface CreateItemRequest {
  sku: string;
  name: string;
  familyCode: string;
  baseUomCode: string;
  isBatchManaged: boolean;
  isExpirable: boolean;
}

export interface Item {
  id: string;
  sku: string;
  name: string;
  familyCode: string;
  baseUomCode: string;
  isBatchManaged: boolean;
  isExpirable: boolean;
}

export interface ReceiptRequest {
  locationId: string;
  itemCode: string;
  quantity: number;
  unit: string;
  batchCode?: string | null;
  expirationDate?: string | null;
}

export interface IssueRequest {
  locationId: string;
  itemCode: string;
  quantity: number;
  unit: string;
}

export interface OperationSuccess<T> {
  ok: true;
  status: number;
  data: T;
}

export interface OperationFailure {
  ok: false;
  status: number;
  error: string;
  details: unknown;
}

export type OperationResult<T = unknown> = OperationSuccess<T> | OperationFailure;