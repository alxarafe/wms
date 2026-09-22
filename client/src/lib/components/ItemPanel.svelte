<script lang="ts">
  import { onMount } from 'svelte';
  import { fetchItemFamilies, fetchItems, fetchUoms, createItem } from '../api/endpoints';
  import type { CreateItemRequest, Item, ItemFamily, OperationResult, Uom } from '../api/types';

  let { onResult }: { onResult: (result: OperationResult<unknown>) => void } = $props();

  let items = $state<Item[]>([]);
  let families = $state<ItemFamily[]>([]);
  let uoms = $state<Uom[]>([]);

  let sku = $state('');
  let name = $state('');
  let familyCode = $state('');
  let baseUomCode = $state('');
  let isBatchManaged = $state(false);
  let isExpirable = $state(false);

  const ready = $derived(
    Boolean(sku.trim()) && Boolean(name.trim()) && Boolean(familyCode) && Boolean(baseUomCode),
  );

  function onExpirableChange(): void {
    if (isExpirable) {
      isBatchManaged = true;
    }
  }

  async function load(): Promise<void> {
    const [itemsResult, familiesResult, uomsResult] = await Promise.all([
      fetchItems(),
      fetchItemFamilies(),
      fetchUoms(),
    ]);
    if (itemsResult.ok) {
      items = [...itemsResult.data].sort((a, b) => a.sku.localeCompare(b.sku));
    } else {
      onResult(itemsResult);
    }
    if (familiesResult.ok) {
      families = [...familiesResult.data].sort((a, b) => a.code.localeCompare(b.code));
      if (!familyCode && families.length > 0) {
        familyCode = families[0].code;
      }
    } else {
      onResult(familiesResult);
    }
    if (uomsResult.ok) {
      uoms = [...uomsResult.data].sort((a, b) => a.code.localeCompare(b.code));
      if (!baseUomCode && uoms.length > 0) {
        baseUomCode = uoms[0].code;
      }
    } else {
      onResult(uomsResult);
    }
  }

  async function create(): Promise<void> {
    if (!ready) {
      return;
    }
    const request: CreateItemRequest = {
      sku: sku.trim(),
      name: name.trim(),
      familyCode,
      baseUomCode,
      isBatchManaged,
      isExpirable,
    };
    const result = await createItem(request);
    onResult(result);
    if (result.ok) {
      sku = '';
      name = '';
      isBatchManaged = false;
      isExpirable = false;
      await load();
    }
  }

  onMount(() => {
    void load();
  });
</script>

<section class="panel">
  <header>
    <h3>Artículos</h3>
    <p class="meta">
      Un SKU identifica un artículo; la familia y la unidad base vienen del catálogo. Si caduca,
      también tiene lote.
    </p>
  </header>

  <div class="list">
    {#if items.length === 0}
      <p class="empty">Sin artículos registrados.</p>
    {:else}
      {#each items as item (item.sku)}
        <div class="item">
          <div class="head">
            <strong>{item.sku}</strong>
          </div>
          <div class="detail">
            {item.name} · {item.familyCode} · {item.baseUomCode}
            {#if item.isBatchManaged}<span class="tag">lote</span>{/if}
            {#if item.isExpirable}<span class="tag">caduca</span>{/if}
          </div>
        </div>
      {/each}
    {/if}
  </div>

  <div class="form">
    <h4>Alta de artículo</h4>
    <label class="field">
      <span>SKU</span>
      <input bind:value={sku} placeholder="BOLSA50" />
    </label>
    <label class="field">
      <span>Nombre</span>
      <input bind:value={name} placeholder="Bolsa de 50 kg" />
    </label>
    <div class="row">
      <label class="field">
        <span>Familia</span>
        <select bind:value={familyCode}>
          {#each families as family (family.code)}
            <option value={family.code}>{family.code} · {family.name}</option>
          {/each}
        </select>
      </label>
      <label class="field">
        <span>Unidad base</span>
        <select bind:value={baseUomCode}>
          {#each uoms as uom (uom.code)}
            <option value={uom.code}>{uom.code}</option>
          {/each}
        </select>
      </label>
    </div>
    <label class="check">
      <input type="checkbox" bind:checked={isBatchManaged} />
      <span>Gestionado por lotes</span>
    </label>
    <label class="check">
      <input type="checkbox" bind:checked={isExpirable} onchange={onExpirableChange} />
      <span>Caducable</span>
    </label>
    <button class="run" disabled={!ready} onclick={() => void create()}>Crear artículo</button>
  </div>
</section>

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

  .list {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .item {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
  }

  .head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
  }

  .detail {
    color: var(--text-dim);
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .tag {
    border: 1px solid var(--border);
    border-radius: 999px;
    padding: 1px 8px;
    font-size: 0.75rem;
  }

  .empty {
    color: var(--text-dim);
    padding: 16px;
    text-align: center;
    border: 1px dashed var(--border);
    border-radius: 8px;
  }

  .form {
    display: flex;
    flex-direction: column;
    gap: 10px;
    border-top: 1px solid var(--border);
    padding-top: 12px;
  }

  .form h4 {
    margin: 0;
  }

  .row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
  }

  .field {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 0.85rem;
    color: var(--text-dim);
  }

  input,
  select {
    padding: 8px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font: inherit;
    background: var(--surface);
  }

  .check {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
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

  @media (max-width: 600px) {
    .row {
      grid-template-columns: 1fr;
    }
  }
</style>