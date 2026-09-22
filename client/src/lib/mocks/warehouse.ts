import type {
  AisleState,
  IssueRequest,
  LocationState,
  LocationRole,
  OperationFailure,
  OperationResult,
  ReceiptRequest,
  ReferenceStock,
  WarehouseState,
  ZoneState,
} from '../api/types';

// −−− Capa de simulacros −−−
// Reproduce las reglas del ejercicio documentadas en
// docs/architecture/location-capacity-and-replenishment.md:
//   - 1 HU por hueco en cualquier rol, HU monoreferencia (vacío / ocupado / bloqueado).
// Cuando existan los endpoints reales, esta capa se sustituye sin tocar las vistas.

const ITEMS: Record<string, { id: string; name: string; unit: string; isBatchManaged: boolean }> = {
  'YOGUR FRESA': { id: 'it-YOGUR-FRESA', name: 'Yogur de fresa refrigerado', unit: 'EA', isBatchManaged: true },
  'PALITOS CANGREJO': { id: 'it-PALITOS-CANGREJO', name: 'Palitos de cangrejo congelados', unit: 'EA', isBatchManaged: true },
  'ARROZ LARGO': { id: 'it-ARROZ-LARGO', name: 'Arroz de grano largo', unit: 'EA', isBatchManaged: false },
  'LEJIA BLANCA': { id: 'it-LEJIA-BLANCA', name: 'Lejía blanca', unit: 'EA', isBatchManaged: false },
  'AGUA MINERAL': { id: 'it-AGUA-MINERAL', name: 'Agua mineral sin gas', unit: 'EA', isBatchManaged: false },
};

const BATCHES: Record<string, string | null> = {
  'L-YOG-001': '2026-12-31T00:00:00Z',
  'L-PAL-001': null,
};

function makeReference(
  itemCode: string,
  quantity: number,
  unit: string,
  batchCode: string | null,
  huCode: string | null,
): ReferenceStock {
  const item = ITEMS[itemCode];
  return {
    itemId: item.id,
    itemCode,
    name: item.name,
    quantity,
    unit: unit || item.unit,
    batchCode,
    huCode,
  };
}

function makeLocation(
  warehouseCode: string,
  aisleNumber: number,
  zoneCode: string,
  aisleCode: string,
  bay: number,
  level: number,
  role: LocationRole,
): LocationState {
  return {
    id: `loc-${zoneCode}-${aisleCode}-${bay}-${level}`,
    code: [warehouseCode, String(aisleNumber), String(bay).padStart(2, '0'), String(level)].join('.'),
    bay,
    level,
    role,
    status: 'ACTIVE',
    blocked: false,
    references: [],
  };
}

function makeAisle(
  warehouseCode: string,
  aisleNumber: number,
  zoneCode: string,
  aisleCode: string,
  bays: number,
  levels: number,
  role: LocationRole,
): AisleState {
  const locations: LocationState[] = [];
  for (let level = levels; level >= 1; level -= 1) {
    for (let bay = 1; bay <= bays; bay += 1) {
      locations.push(makeLocation(warehouseCode, aisleNumber, zoneCode, aisleCode, bay, level, role));
    }
  }
  return {
    id: `aisle-${zoneCode}-${aisleCode}`,
    code: `${zoneCode}-${aisleCode}`,
    zoneId: `zone-${zoneCode}`,
    bays,
    levels,
    isBlocked: false,
    locations,
  };
}

function buildWarehouse(): WarehouseState {
  const pickingZone: ZoneState = {
    id: '01a0aca9-bc00-7011-8000-000000000001',
    code: 'P',
    zoneTypeCode: 'PICKING',
    allowsMultiSku: true,
    isOperative: true,
    aisles: [makeAisle('A', 1, 'P', 'A', 4, 2, 'PICKING')],
  };
  const bulkZone: ZoneState = {
    id: '01a0aca9-bc00-7011-8000-000000000002',
    code: 'B',
    zoneTypeCode: 'BULK',
    allowsMultiSku: false,
    isOperative: true,
    aisles: [makeAisle('A', 2, 'B', 'B', 6, 3, 'RESERVE')],
  };

  const warehouse: WarehouseState = {
    id: '01a0aca9-bc00-7010-8000-000000000001',
    code: 'A',
    name: 'Demostración sin zonas',
    zones: [pickingZone, bulkZone],
  };

  const picking1 = findLocation(warehouse, 'A.1.01.1');
  picking1.references = [makeReference('YOGUR FRESA', 30, 'EA', 'L-YOG-001', '3400000000000000001')];
  const picking2 = findLocation(warehouse, 'A.1.01.2');
  picking2.references = [makeReference('ARROZ LARGO', 40, 'EA', null, '3400000000000000002')];
  const reserve1 = findLocation(warehouse, 'A.2.01.1');
  reserve1.references = [makeReference('LEJIA BLANCA', 12, 'PAL', null, '3400000000000000003')];

  return warehouse;
}

function findLocation(warehouse: WarehouseState, code: string): LocationState {
  for (const zone of warehouse.zones) {
    for (const aisle of zone.aisles) {
      for (const location of aisle.locations) {
        if (location.code === code) {
          return location;
        }
      }
    }
  }
  throw new Error(`Hueco de simulación inexistente: ${code}`);
}

function findByCode(warehouse: WarehouseState, code: string): LocationState | null {
  for (const zone of warehouse.zones) {
    for (const aisle of zone.aisles) {
      for (const location of aisle.locations) {
        if (location.code === code) {
          return location;
        }
      }
    }
  }
  return null;
}

function findById(warehouse: WarehouseState, locationId: string): LocationState | null {
  for (const zone of warehouse.zones) {
    for (const aisle of zone.aisles) {
      const found = aisle.locations.find((location) => location.id === locationId);
      if (found) {
        return found;
      }
    }
  }
  return null;
}

function clone<T>(value: T): T {
  return JSON.parse(JSON.stringify(value)) as T;
}

function failure(status: number, error: string): OperationFailure {
  return { ok: false, status, error, details: null };
}

let warehouse: WarehouseState = buildWarehouse();

export function mockFetchWarehouseState(warehouseId: string): OperationResult<WarehouseState> {
  if (warehouse.id !== warehouseId) {
    return failure(404, 'Almacén no encontrado.');
  }
  return { ok: true, status: 200, data: clone(warehouse) };
}

export function mockSubmitReceipt(request: ReceiptRequest): OperationResult<LocationState> {
  if (!(request.itemCode in ITEMS)) {
    return failure(400, `Artículo desconocido: ${request.itemCode}.`);
  }
  if (!Number.isFinite(request.quantity) || request.quantity <= 0) {
    return failure(422, 'La cantidad debe ser mayor que cero.');
  }

  const location = findById(warehouse, request.locationId);
  if (!location) {
    return failure(404, 'Hueco no encontrado.');
  }
  if (location.status !== 'ACTIVE' || location.blocked) {
    return failure(409, 'El hueco está bloqueado o deshabilitado.');
  }

  const item = ITEMS[request.itemCode];
  const batchCode = request.batchCode?.trim() || null;
  const expirationDate = request.expirationDate?.trim() || null;

  if (expirationDate && !batchCode) {
    return failure(400, 'La caducidad exige un lote.');
  }
  let expirationInstant: string | null = null;
  if (expirationDate) {
    expirationInstant = parseISODate(expirationDate);
    if (expirationInstant === null) {
      return failure(400, 'La caducidad debe ser una fecha ISO-8601.');
    }
  }
  if (item.isBatchManaged && !batchCode) {
    return failure(400, 'El lote es obligatorio para artículos gestionados por lotes.');
  }
  if (!item.isBatchManaged && batchCode) {
    return failure(400, `El artículo ${request.itemCode} no se gestiona por lotes.`);
  }
  if (!item.isBatchManaged && expirationInstant) {
    return failure(400, 'La caducidad solo puede enviarse con lotes.');
  }
  if (batchCode) {
    if (!(batchCode in BATCHES)) {
      return failure(404, `Lote no encontrado para ${request.itemCode}.`);
    }
    const stored = BATCHES[batchCode];
    if (expirationInstant) {
      if (stored === null) {
        BATCHES[batchCode] = expirationDate;
      } else {
        const storedInstant = parseISODate(stored);
        if (storedInstant === null || storedInstant !== expirationInstant) {
          return failure(409, `La caducidad no coincide con la almacenada para ${batchCode}.`);
        }
      }
    }
  }

  if (location.references.length > 0) {
    return failure(409, 'El hueco ya está ocupado: admite una única HU monoreferencia.');
  }
  const huCode = `3${String(Math.floor(Math.random() * 1e17)).padStart(17, '0')}`;
  location.references = [
    makeReference(request.itemCode, request.quantity, request.unit, batchCode, huCode),
  ];

  return { ok: true, status: 201, data: clone(location) };
}

function parseISODate(value: string): string | null {
  const dateOnly = /^\d{4}-\d{2}-\d{2}$/;
  if (dateOnly.test(value)) {
    const [year, month, day] = value.split('-').map(Number);
    const utc = Date.UTC(year, month - 1, day);
    return Number.isNaN(utc) ? null : new Date(utc).toISOString();
  }
  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? null : parsed.toISOString();
}

export function mockSubmitIssue(request: IssueRequest): OperationResult<LocationState> {
  if (!Number.isFinite(request.quantity) || request.quantity <= 0) {
    return failure(422, 'La cantidad debe ser mayor que cero.');
  }

  const location = findById(warehouse, request.locationId);
  if (!location) {
    return failure(404, 'Hueco no encontrado.');
  }
  const reference = location.references.find((ref) => ref.itemCode === request.itemCode);
  if (!reference) {
    return failure(422, `La referencia ${request.itemCode} no se encuentra en el hueco.`);
  }
  if (request.quantity !== reference.quantity) {
    return failure(409, 'La salida debe desocupar la HU completa.');
  }

  const index = location.references.indexOf(reference);
  location.references.splice(index, 1);

  return { ok: true, status: 200, data: clone(location) };
}

export function mockWarehouseCodes(): string[] {
  return warehouse.zones.flatMap((zone) => zone.aisles.map((aisle) => aisle.code)).concat(
    [warehouse.code],
  );
}

export function getLocationByCode(code: string): LocationState | null {
  const found = findByCode(warehouse, code);
  return found ? clone(found) : null;
}