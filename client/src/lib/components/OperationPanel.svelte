<script lang="ts">
  import { submitIssue, submitReceipt } from '../api/endpoints';
  import type {
    IssueRequest,
    LocationState,
    OperationResult,
    ReceiptRequest,
  } from '../api/types';

  let { location, onResult }: {
    location: LocationState | null;
    onResult: (result: OperationResult<LocationState>) => void;
  } = $props();

  let operation = $state<'receive' | 'issue'>('receive');
  let itemCode = $state('');
  let quantity = $state<number | null>(null);
  let unit = $state('EA');
  let batchCode = $state('');
  let expirationDate = $state('');

  const ready = $derived(Boolean(itemCode.trim()) && quantity !== null && quantity > 0);

  const available = $derived(
    location?.references.find((ref) => ref.itemCode === itemCode.trim())?.quantity ?? 0,
  );
  const unitOptions = ['EA', 'BOX', 'PAL', 'KG'];

  function reset(): void {
    itemCode = '';
    quantity = null;
    batchCode = '';
    expirationDate = '';
  }

  async function run(): Promise<void> {
    if (!location || !ready) {
      return;
    }
    const base = {
      locationId: location.id,
      itemCode: itemCode.trim(),
      quantity: quantity as number,
      unit: unit.trim() || 'EA',
    };
    if (operation === 'receive') {
      const request: ReceiptRequest = {
        ...base,
        batchCode: batchCode.trim() ? batchCode.trim() : null,
        expirationDate: expirationDate.trim() ? expirationDate.trim() : null,
      };
      const result = await submitReceipt(request);
      onResult(result);
      if (result.ok) {
        reset();
      }
    } else {
      const request: IssueRequest = base;
      const result = await submitIssue(request);
      onResult(result);
      if (result.ok) {
        reset();
      }
    }
  }
</script>

{#if location}
  <section class="panel">
    <header>
      <h3>Hueco {location.code}</h3>
      <p class="meta">
        {location.role} · {location.status}{location.blocked ? ' · bloqueado' : ''}
      </p>
    </header>

    <div class="tabs">
      <button class={operation === 'receive' ? 'active' : ''} onclick={() => (operation = 'receive')}>
        Entrada
      </button>
      <button class={operation === 'issue' ? 'active' : ''} onclick={() => (operation = 'issue')}>
        Salida
      </button>
    </div>

    <label class="field">
      <span>Artículo (código)</span>
      <input bind:value={itemCode} list="items" placeholder="YOGUR FRESA, PALITOS CANGREJO, …" />
      <datalist id="items">
        <option value="YOGUR FRESA">Yogur de fresa refrigerado</option>
        <option value="PALITOS CANGREJO">Palitos de cangrejo congelados</option>
        <option value="ARROZ LARGO">Arroz de grano largo</option>
        <option value="LEJIA BLANCA">Lejía blanca</option>
        <option value="AGUA MINERAL">Agua mineral sin gas</option>
      </datalist>
    </label>

    <div class="row">
      <label class="field">
        <span>Cantidad</span>
        <input type="number" bind:value={quantity} min="0" step="any" placeholder="0" />
      </label>
      <label class="field">
        <span>Unidad</span>
        <input bind:value={unit} list="units" />
        <datalist id="units">
          {#each unitOptions as option}
            <option value={option}>{option}</option>
          {/each}
        </datalist>
      </label>
    </div>

    {#if operation === 'receive'}
      <label class="field">
        <span>Lote (opcional)</span>
        <input bind:value={batchCode} placeholder="L-2026-001" />
      </label>
      <label class="field">
        <span>Caducidad (opcional)</span>
        <input type="date" bind:value={expirationDate} />
      </label>
    {/if}

    {#if operation === 'issue'}
      <p class="hint">
        La salida desocupa la HU completa ({available} {unit}). El hueco admite una única HU.
      </p>
    {/if}

    <button class="run" disabled={!ready} onclick={() => void run()}>
      {operation === 'receive' ? 'Registrar entrada' : 'Registrar salida'}
    </button>
  </section>
{:else}
  <section class="panel">
    <h3>Operaciones</h3>
    <p class="meta">Selecciona un hueco de la calle para operar con él.</p>
  </section>
{/if}

<style>
  .panel {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .meta {
    margin: 4px 0 0;
    color: var(--text-dim);
    font-size: 0.85rem;
  }

  .tabs {
    display: flex;
    gap: 8px;
  }

  .tabs button {
    flex: 1;
    padding: 8px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--surface);
  }

  .tabs button.active {
    background: var(--accent);
    border-color: var(--accent);
    color: white;
  }

  .field {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 0.85rem;
    color: var(--text-dim);
  }

  .row {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
  }

  input {
    width: 100%;
    min-width: 0;
    padding: 8px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font: inherit;
  }

  .hint {
    margin: 0;
    font-size: 0.8rem;
    color: var(--accent);
  }

  .run {
    padding: 10px;
    border: none;
    border-radius: 8px;
    background: var(--ok);
    color: white;
    font-weight: 600;
  }

  .run:disabled {
    background: var(--border);
    cursor: not-allowed;
  }
</style>