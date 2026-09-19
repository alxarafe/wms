<script lang="ts">
  let { loc }: { loc: { references: { quantity: number }[] } } = $props();
  const total = $derived.by(() => loc.references.reduce((sum, ref) => sum + ref.quantity, 0));
</script>

<p>{total}</p>