<script setup>
import { useForm } from '@inertiajs/vue3'

defineProps({ category: Object })

const form = useForm({ name: category.name, description: category.description, max_queues_per_day: category.max_queues_per_day })

function submit() {
  form.put(route('admin.service-categories.update', category.id))
}
</script>

<template>
  <div class="p-4">
    <h1 class="text-xl">Edit Service Category</h1>
  </div>
</template>
