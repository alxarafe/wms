<script lang="ts">
  import { onMount } from 'svelte';
  import { createItemFamily, fetchItemFamilies } from '../api/endpoints';
  import type { CreateItemFamilyRequest, ItemFamily, OperationResult } from '../api/types';

  let { onResult }: { onResult: (result: OperationResult<unknown>) => void } = $props();

  let families = $state<ItemFamily[]>([]);
  let code = $state('');
  let name = $state('');
  let attributes = $state('');

  const ready = $derived(Boolean(code.trim()) && Boolean(name.trim()));

  async function load(): Promise<void> {
    const result = await fetchItemFamilies();
    if (result.ok) {
      families = [...result.data].sort((a, b) => a.code.localeCompare(b.code));
    } else {
      onResult(result);
    }
  }

  async function create(): Promise<void> {
    if (!ready) {
      return;
    }
    const request: CreateItemFamilyRequest = {
      code: code.trim(),
      name: name.trim(),
      attributes: attributes.split(',').map((value) => value.trim()).filter(Boolean),
    };
    const result = await createItemFamily(request);
    onResult(result);
    if (result.ok) {
      code = '';
      name = '';
      attributes = '';
      await load();
    }
  }

  onMount(() => {
    void load();
  });
</script>

<section class="panel">
  <header>
    <h3>Familias de artículos</h3>
    <p class="meta">Conjunto de artículos con atributos comunes (IS_FOOD, IS_REFRIGERATED…).</p>
  </header>

  <div class="list">
    {#if families.length === 0}
      <p class="empty">Sin familias registradas.</p>
    {:else}
      {#each families as family (family.code)}
        <div class="family">
          <div>
            <strong>{family.code}</strong>
            <p>{family.name}</p>
          </div>
          <div class="tags">
            {#each family.attributes as attribute}
              <span>{attribute}</span>
            {/each}
          </div>
        </div>
      {/each}
    {/if}
  </div>

  <div class="form">
    <h4>Alta de familia</h4>
    <label class="field">
      <span>Código</span>
      <input bind:value={code} placeholder="DRY_FOOD" />
    </label>
    <label class="field">
      <span>Nombre</span>
      <input bind:value={name} placeholder="Alimentos secos" />
    </label>
    <label class="field">
      <span>Atributos (separados por coma, opcional)</span>
      <input bind:value={attributes} placeholder="IS_FOOD, IS_REFRIGERATED" />
    </label>
    <button class="run" disabled={!ready} onclick={() => void create()}>Crear familia</button>
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

  .family {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
  }

  .family p {
    margin: 2px 0 0;
    color: var(--text-dim);
    font-size: 0.85rem;
  }

  .tags {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    justify-content: flex-end;
  }

  .tags span {
    font-size: 0.7rem;
    padding: 2px 8px;
    border-radius: 999px;
    background: var(--border);
    color: var(--text-dim);
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

  .field {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 0.85rem;
    color: var(--text-dim);
  }

  input {
    padding: 8px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font: inherit;
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