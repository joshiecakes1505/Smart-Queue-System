<script setup>
import { useForm } from '@inertiajs/vue3'
import { inject } from 'vue'

defineProps({ user: Object })

const swal = inject('$swal')

const form = useForm({ name: user.name, email: user.email, role_id: user.role_id })

function submit() {
  form.put(route('admin.users.update', user.id), {
    onError: () => {
      swal?.fire({
        icon: 'error',
        title: 'Validation error',
        text: 'Please check the form fields and try again.',
      })
    },
  })
}
</script>

<template>
  <div class="p-4">
    <h1 class="text-xl">Edit User</h1>
  </div>
</template>
