<script setup>
import { router } from '@inertiajs/vue3'
import { usePolling } from '@/Composables/usePolling'

defineProps({
  window: Object,
  queue: Object,
})

usePolling(() => {
  return router.reload({
    only: ['window', 'queue'],
    preserveState: true,
    preserveScroll: true,
  })
}, 2500)
</script>

<template>
  <div class="p-4">
    <h2 class="text-lg">{{ window.name }}</h2>
    <div class="mt-2">
      <p>Current: {{ queue?.queue_number ?? '—' }}</p>
    </div>
  </div>
</template>
