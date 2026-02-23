<script setup>
defineProps({
  window: Object,
  current: Object,
  next: Array,
})
</script>

<template>
  <div class="p-4">
    <h1 class="text-2xl font-bold">Cashier</h1>

    <div class="mt-4">
      <p><strong>Window:</strong> {{ window?.name ?? 'Unassigned' }}</p>
      <p class="mt-2"><strong>Now Serving:</strong> {{ current?.queue_number ?? '—' }}</p>

      <h3 class="mt-4">Next</h3>
      <ul>
        <li v-for="q in next" :key="q.id">{{ q.queue_number }} — {{ q.client_name ?? 'Guest' }}</li>
      </ul>
    </div>
  </div>
</template>
