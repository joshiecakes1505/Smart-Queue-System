<script setup>
import CreateQueue from '@/Pages/FrontDesk/CreateQueue.vue'

defineProps({ serviceCategories: Array })
</script>

<template>
  <div class="p-4">
    <h1 class="text-2xl font-bold">Front Desk — Queues</h1>
    <div class="mt-4">
      <CreateQueue :serviceCategories="serviceCategories" />
    </div>
  </div>
</template>
