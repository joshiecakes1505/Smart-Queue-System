<script setup>
import { useForm } from '@inertiajs/vue3'
import { inject } from 'vue'

const swal = inject('$swal')

const form = useForm({ name: '', email: '', password: '', role_id: '' })

function submit() {
  form.post(route('admin.users.store'), {
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
    <h1 class="text-xl">Create User</h1>
  </div>
</template>
