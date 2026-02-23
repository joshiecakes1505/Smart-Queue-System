<script setup>
defineProps({
  window: Object,
  queue: Object,
})
</script>

<template>
  <div class="p-4">
    <h2 class="text-lg">{{ window.name }}</h2>
    <div class="mt-2">
      <p>Current: {{ queue?.queue_number ?? '—' }}</p>
    </div>
  </div>
</template>
