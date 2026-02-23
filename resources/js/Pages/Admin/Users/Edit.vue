<script setup>
import { useForm } from '@inertiajs/vue3'

defineProps({ user: Object })

const form = useForm({ name: user.name, email: user.email, role_id: user.role_id })

function submit() {
  form.put(route('admin.users.update', user.id))
}
</script>

<template>
  <div class="p-4">
    <h1 class="text-xl">Edit User</h1>
  </div>
</template>
