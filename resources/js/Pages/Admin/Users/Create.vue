<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({ name: '', email: '', password: '', role_id: '' })

function submit() {
  form.post(route('admin.users.store'))
}
</script>

<template>
  <div class="p-4">
    <h1 class="text-xl">Create User</h1>
  </div>
</template>
