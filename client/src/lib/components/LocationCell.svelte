<script lang="ts">
  import type { LocationState } from '../api/types';

  let { loc, selected, onSelect }: {
    loc: LocationState;
    selected: boolean;
    onSelect: () => void;
  } = $props();

  const blocked = $derived(loc.status !== 'ACTIVE' || loc.blocked);
  const occupied = $derived(loc.references.length > 0);
  const reference = $derived(occupied ? loc.references[0] : null);
</script>

<button
  class="cell {blocked ? 'blocked' : ''} {occupied ? 'filled' : 'empty'} {selected
    ? 'selected'
    : ''}"
  onclick={onSelect}
  title={reference
    ? `${loc.code} · ${reference.itemCode} · ${reference.quantity} ${reference.unit}${reference.batchCode ? ` · lote ${reference.batchCode}` : ''}${reference.huCode ? ` · HU ${reference.huCode}` : ''}`
    : `${loc.code} · ${blocked ? 'Bloqueado' : 'Vacío'}`}
>
  <span class="code">{loc.code}</span>

  {#if blocked}
    <span class="state">Bloqueado</span>
  {:else if occupied}
    <span class="hu">1 HU</span>
    <span class="ref">{reference!.itemCode}</span>
    <span class="state">{reference!.quantity} {reference!.unit}</span>
  {:else}
    <span class="state">Vacío</span>
  {/if}
</button>

<style>
  .cell {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    justify-content: space-between;
    width: 100%;
    min-height: 86px;
    padding: 6px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--cell-empty);
    text-align: left;
    overflow: hidden;
    transition:
      transform 0.1s ease,
      box-shadow 0.1s ease;
  }

  .cell:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgb(0 0 0 / 0.12);
  }

  .cell.selected {
    outline: 3px solid var(--accent);
    outline-offset: 1px;
  }

  .cell.filled:not(.blocked) {
    background: var(--cell-reserve-full);
  }

  .cell.empty {
    border-style: dashed;
  }

  .cell.blocked {
    background: var(--cell-blocked);
    opacity: 0.75;
    border-style: solid;
  }

  .code {
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--text-dim);
  }

  .hu {
    font-size: 0.75rem;
    font-weight: 700;
  }

  .ref {
    font-size: 0.8rem;
    font-weight: 600;
  }

  .state {
    font-size: 0.75rem;
    font-weight: 600;
  }
</style>