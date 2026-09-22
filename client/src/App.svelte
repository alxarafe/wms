<script lang="ts">
  import { onMount } from 'svelte';
  import { apiMode, setApiMode, DEMO_WAREHOUSE_ID } from './lib/api/config';
  import { fetchWarehouseState } from './lib/api/endpoints';
  import type {
    AisleState,
    LocationState,
    OperationResult,
    WarehouseState,
  } from './lib/api/types';
  import AisleView from './lib/components/AisleView.svelte';
  import CatalogPanel from './lib/components/CatalogPanel.svelte';
  import ItemPanel from './lib/components/ItemPanel.svelte';
  import OperationPanel from './lib/components/OperationPanel.svelte';
  import UomPanel from './lib/components/UomPanel.svelte';

  let warehouse: WarehouseState | null = $state(null);
  let selectedAisleCode = $state('');
  let selectedLocationId: string | null = $state(null);
  let view = $state<'warehouse' | 'catalogue'>('warehouse');
  let catalogueTab = $state<'families' | 'uoms' | 'items'>('families');
  let message = $state('');
  let messageKind: 'success' | 'failure' | 'info' = $state('info');

  const aisles = $derived.by(() =>
    (warehouse?.zones ?? []).flatMap((zone) =>
      zone.aisles.map((aisle) => ({
        label: `${zone.code} / ${aisle.code} · ${aisle.bays}×${aisle.levels}`,
        code: aisle.code,
        role: zone.zoneTypeCode,
      })),
    ),
  );

  const currentAisleState = $derived.by<AisleState | null>(() =>
    (warehouse?.zones ?? [])
      .flatMap((zone) => zone.aisles)
      .find((aisle) => aisle.code === selectedAisleCode) ?? null,
  );

  const selectedLocation = $derived.by<LocationState | null>(() =>
    currentAisleState?.locations.find((location) => location.id === selectedLocationId) ?? null,
  );

  function show(text: string, kind: 'success' | 'failure' | 'info'): void {
    message = text;
    messageKind = kind;
  }

  function applyState(result: OperationResult<WarehouseState>): void {
    if (result.ok) {
      warehouse = result.data;
      if (!selectedAisleCode && warehouse.zones.length > 0 && warehouse.zones[0].aisles.length > 0) {
        selectedAisleCode = warehouse.zones[0].aisles[0].code;
      }
      if (!selectedLocationId && currentAisleState) {
        const first = currentAisleState.locations[0];
        selectedLocationId = first ? first.id : null;
      }
    } else {
      show(`${result.status}: ${result.error}`, 'failure');
    }
  }

  async function reload(): Promise<void> {
    if (!warehouse) {
      return;
    }
    const result = await fetchWarehouseState(warehouse.id);
    if (!result.ok) {
      show(`${result.status}: ${result.error}`, 'failure');
      return;
    }
    warehouse = result.data;
    const exists = (warehouse.zones ?? [])
      .flatMap((zone) => zone.aisles)
      .some((aisle) => aisle.code === selectedAisleCode);
    if (!exists) {
      selectedAisleCode = warehouse.zones[0]?.aisles[0]?.code ?? '';
    }
    const locationExists =
      currentAisleState?.locations.some((location) => location.id === selectedLocationId) ?? false;
    if (!locationExists) {
      selectedLocationId = currentAisleState?.locations[0]?.id ?? null;
    }
  }

  function changeApiMode(mode: 'mock' | 'php' | 'java'): void {
    if (mode === apiMode) {
      return;
    }
    setApiMode(mode);
    show(
      mode === 'mock'
        ? 'Modo simulado: el estado y las operaciones se atienden localmente.'
        : `Conectando a la API ${mode.toUpperCase()} (${mode === 'php' ? ':28080' : ':38080'}).`, 
      'info',
    );
    void reload();
  }

  function handleOperationResult(result: OperationResult<LocationState>): void {
    if (result.ok) {
      show('Operación correcta.', 'success');
      void reload();
    } else {
      show(`${result.status}: ${result.error}`, 'failure');
    }
  }

  function handleCatalogResult(result: OperationResult<unknown>): void {
    if (result.ok) {
      show('Catálogo actualizado.', 'success');
    } else {
      show(`${result.status}: ${result.error}`, 'failure');
    }
  }

  function onSelectLocation(location: LocationState): void {
    selectedLocationId = location.id;
  }

  onMount(() => {
    void fetchWarehouseState(DEMO_WAREHOUSE_ID).then(applyState);
  });
</script>

<main class="shell">
  <header class="topbar">
    <div>
      <h1>Cliente WMS</h1>
      <p class="subtitle">
        {warehouse?.code} · {warehouse?.name ?? 'cargando…'}
      </p>
    </div>
    <div class="modes">
      <button class={apiMode === 'mock' ? 'active' : ''} onclick={() => changeApiMode('mock')}>
        Simulado
      </button>
      <button class={apiMode === 'php' ? 'active' : ''} onclick={() => changeApiMode('php')}>
        PHP
      </button>
      <button
        class={apiMode === 'java' ? 'active' : ''}
        disabled
        title="Modo Java temporalmente desactivado"
        aria-label="Java (temporalmente desactivado)"
        onclick={() => changeApiMode('java')}
      >
        Java (próximamente)
      </button>
    </div>
  </header>

  {#if message}
    <aside class="message {messageKind}" role="status">
      {message}
      <button class="close" onclick={() => (message = '')} aria-label="Cerrar">×</button>
    </aside>
  {/if}

  <div class="toolbar">
    <div class="view-tabs">
      <button class={view === 'warehouse' ? 'active' : ''} onclick={() => (view = 'warehouse')}>
        Almacén
      </button>
      <button class={view === 'catalogue' ? 'active' : ''} onclick={() => (view = 'catalogue')}>
        Catálogo
      </button>
    </div>
    {#if view === 'warehouse'}
      <label class="aisle-picker">
        <span>Calle</span>
        <select bind:value={selectedAisleCode}>
          {#each aisles as aisle}
            <option value={aisle.code}>{aisle.label}</option>
          {/each}
        </select>
      </label>
    {/if}
  </div>

  {#if view === 'catalogue'}
    <div class="catalogue-tabs" role="tablist">
      <button class={catalogueTab === 'families' ? 'active' : ''} onclick={() => (catalogueTab = 'families')}>
        Familias
      </button>
      <button class={catalogueTab === 'uoms' ? 'active' : ''} onclick={() => (catalogueTab = 'uoms')}>
        Unidades
      </button>
      <button class={catalogueTab === 'items' ? 'active' : ''} onclick={() => (catalogueTab = 'items')}>
        Artículos
      </button>
    </div>
    {#if catalogueTab === 'families'}
      <CatalogPanel onResult={handleCatalogResult} />
    {:else if catalogueTab === 'uoms'}
      <UomPanel onResult={handleCatalogResult} />
    {:else}
      <ItemPanel onResult={handleCatalogResult} />
    {/if}
  {:else}
    <div class="layout">
      <div class="main-col">
        {#if currentAisleState}
          <AisleView
            aisle={currentAisleState}
            selectedLocationId={selectedLocationId}
            onSelect={onSelectLocation}
          />
        {:else}
          <p class="empty">Selecciona una calle para ver su estado.</p>
        {/if}
      </div>
      <div class="side-col">
        <OperationPanel location={selectedLocation} onResult={handleOperationResult} />
      </div>
    </div>
  {/if}
</main>

<style>
  .shell {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
  }

  .subtitle {
    margin: 2px 0 0;
    color: var(--text-dim);
    font-size: 0.9rem;
  }

  .modes {
    display: flex;
    gap: 8px;
  }

  .modes button {
    padding: 8px 14px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--surface);
  }

  .modes button.active {
    background: var(--accent);
    border-color: var(--accent);
    color: white;
  }

  .modes button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
  }

  .message {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--surface);
  }

  .message.success {
    border-color: var(--ok);
    color: var(--ok);
  }

  .message.failure {
    border-color: var(--danger);
    color: var(--danger);
  }

  .message.info {
    border-color: var(--accent);
    color: var(--accent);
  }

  .close {
    border: none;
    background: none;
    font-size: 1.1rem;
  }

  .toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
  }

  .view-tabs {
    display: flex;
    gap: 8px;
  }

  .view-tabs button {
    padding: 8px 16px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--surface);
  }

  .view-tabs button.active {
    background: var(--accent);
    border-color: var(--accent);
    color: white;
  }

  .aisle-picker {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    color: var(--text-dim);
  }

  .catalogue-tabs {
    display: flex;
    gap: 8px;
  }

  .catalogue-tabs button {
    padding: 8px 16px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--surface);
  }

  .catalogue-tabs button.active {
    background: var(--accent);
    border-color: var(--accent);
    color: white;
  }

  select {
    padding: 8px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font: inherit;
    background: var(--surface);
  }

  .layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 16px;
    align-items: start;
  }

  .side-col {
    position: sticky;
    top: 20px;
  }

  .empty {
    color: var(--text-dim);
    padding: 24px;
    background: var(--surface);
    border: 1px dashed var(--border);
    border-radius: 12px;
  }

  @media (max-width: 860px) {
    .layout {
      grid-template-columns: 1fr;
    }

    .side-col {
      position: static;
    }
  }
</style>
