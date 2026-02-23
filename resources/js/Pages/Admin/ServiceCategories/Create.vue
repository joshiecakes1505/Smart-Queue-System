<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({ name: '', description: '', max_queues_per_day: '', avg_service_seconds: '' })

function submit() {
  form.post(route('admin.service-categories.store'))
}
</script>

<template>
  <div class="p-4">
    <h1 class="text-xl">Create Service Category</h1>
  </div>
</template>
