<script lang="ts">
  import { onMount } from 'svelte';
  import { createUom, fetchUoms } from '../api/endpoints';
  import type { CreateUomRequest, OperationResult, Uom } from '../api/types';

  let { onResult }: { onResult: (result: OperationResult<unknown>) => void } = $props();

  let uoms = $state<Uom[]>([]);
  let code = $state('');
  let description = $state('');

  const ready = $derived(Boolean(code.trim()) && Boolean(description.trim()));

  async function load(): Promise<void> {
    const result = await fetchUoms();
    if (result.ok) {
      uoms = [...result.data].sort((a, b) => a.code.localeCompare(b.code));
    } else {
      onResult(result);
    }
  }

  async function create(): Promise<void> {
    if (!ready) {
      return;
    }
    const request: CreateUomRequest = {
      code: code.trim(),
      description: description.trim(),
    };
    const result = await createUom(request);
    onResult(result);
    if (result.ok) {
      code = '';
      description = '';
      await load();
    }
  }

  onMount(() => {
    void load();
  });
</script>

<section class="panel">
  <header>
    <h3>Unidades de medida</h3>
    <p class="meta">Código de 2 a 10 caracteres en mayúsculas; EA, BOX, PAL y KG vienen de la semilla.</p>
  </header>

  <div class="list">
    {#if uoms.length === 0}
      <p class="empty">Sin unidades registradas.</p>
    {:else}
      {#each uoms as uom (uom.code)}
        <div class="uom">
          <strong>{uom.code}</strong>
          <span>{uom.description}</span>
        </div>
      {/each}
    {/if}
  </div>

  <div class="form">
    <h4>Alta de unidad</h4>
    <label class="field">
      <span>Código</span>
      <input bind:value={code} placeholder="BOX" />
    </label>
    <label class="field">
      <span>Descripción</span>
      <input bind:value={description} placeholder="Standard Box" />
    </label>
    <button class="run" disabled={!ready} onclick={() => void create()}>Crear unidad</button>
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

  .uom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    min-width: 0;
  }

  .uom strong {
    flex-shrink: 0;
  }

  .uom span {
    color: var(--text-dim);
    font-size: 0.85rem;
    min-width: 0;
    overflow-wrap: anywhere;
    text-align: right;
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