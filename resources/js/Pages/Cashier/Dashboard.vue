<script setup>
defineProps({
  window: Object,
  current: Object,
  next: Array,
})
</script>

<template>
  <div class="p-4">
    <h1 class="text-2xl font-bold">Cashier Dashboard</h1>
    <div class="mt-4">
      <p>Window: {{ window?.name ?? 'Unassigned' }}</p>
      <p>Now Serving: {{ current?.queue_number ?? '—' }}</p>
      <p>Next: <span v-if="next.length">{{ next[0].queue_number }}</span></p>
    </div>
  </div>
</template>
