<script lang="ts">
  import type { AisleState, LocationState } from '../api/types';
  import LocationCell from './LocationCell.svelte';

  let { aisle, selectedLocationId, onSelect } = $props<{
    aisle: AisleState;
    selectedLocationId: string | null;
    onSelect: (location: LocationState) => void;
  }>();

  const levels = $derived(Array.from({ length: aisle.levels }, (_, i) => aisle.levels - i));
  const bays = $derived(Array.from({ length: aisle.bays }, (_, i) => i + 1));
  const locationAt = $derived.by(() => {
    const map = new Map<string, LocationState>();
    for (const location of aisle.locations) {
      map.set(`${location.bay}-${location.level}`, location);
    }
    return map;
  });
</script>

<section class="aisle">
  <header class="aisle-header">
    <h2>Calle {aisle.code}</h2>
    {#if aisle.isBlocked}
      <span class="badge">Pasillo bloqueado</span>
    {/if}
  </header>

  <div class="grid" style="grid-template-columns: repeat({aisle.bays}, minmax(64px, 1fr));">
    {#each levels as level}
      {#each bays as bay}
        {@const loc = locationAt.get(`${bay}-${level}`)}
        {#if loc}
          <LocationCell {loc} selected={loc.id === selectedLocationId} onSelect={() => onSelect(loc)} />
        {/if}
      {/each}
    {/each}
  </div>

  <div class="legend">
    <span class="legend-item"><span class="sw reserve-empty"></span>Vacío</span>
    <span class="legend-item"><span class="sw reserve-full"></span>Ocupado (1 HU)</span>
    <span class="legend-item"><span class="sw blocked"></span>Bloqueado</span>
  </div>
</section>

<style>
  .aisle {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 16px;
  }

  .aisle-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
  }

  .badge {
    background: var(--danger);
    color: white;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 999px;
  }

  .grid {
    display: grid;
    gap: 6px;
  }

  .legend {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    margin-top: 12px;
    font-size: 0.8rem;
    color: var(--text-dim);
  }

  .legend-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }

  .sw {
    display: inline-block;
    width: 14px;
    height: 14px;
    border-radius: 3px;
    border: 1px solid var(--border);
  }

  .sw.reserve-empty {
    background: var(--cell-empty);
    border-style: dashed;
  }

  .sw.reserve-full {
    background: var(--cell-reserve-full);
  }

  .sw.blocked {
    background: var(--cell-blocked);
  }
</style>