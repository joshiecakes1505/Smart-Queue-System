<script setup>
defineProps({
  todayTotal: Number,
  waitingCount: Number,
  serviceCategories: Array,
})
</script>

<template>
  <div class="p-4">
    <h1 class="text-2xl font-bold">Front Desk — Dashboard</h1>
    <div class="mt-4">
      <p>Today total: {{ todayTotal ?? 0 }}</p>
      <p>Waiting: {{ waitingCount ?? 0 }}</p>
    </div>
  </div>
</template>
