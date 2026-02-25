<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { inject } from 'vue'

const props = defineProps({
  roles: {
    type: Array,
    default: () => [],
  },
})

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
    onSuccess: () => {
      swal?.fire({
        icon: 'success',
        title: 'User created',
        text: 'The user account has been added successfully.',
      })
    },
  })
}

</script>

<template>
  <AuthenticatedLayout title="Create User">
    <div class="max-w-3xl">
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
          <h1 class="text-2xl font-semibold text-[#800000]">Create User</h1>
          <Link
            :href="route('admin.users.index')"
            class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-semibold transition"
          >
            Back to Users
          </Link>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000]"
              placeholder="Enter full name"
            />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
            <input
              v-model="form.email"
              type="email"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000]"
              placeholder="Enter email address"
            />
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
            <input
              v-model="form.password"
              type="password"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000]"
              placeholder="Minimum 8 characters"
            />
            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
            <select
              v-model="form.role_id"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000]"
            >
              <option value="" disabled>Select role</option>
              <option v-for="role in roles" :key="role.id" :value="String(role.id)">
                {{ role.name }}
              </option>
            </select>
            <p v-if="form.errors.role_id" class="mt-1 text-sm text-red-600">{{ form.errors.role_id }}</p>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <Link
              :href="route('admin.users.index')"
              class="border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg font-semibold transition"
            >
              Cancel
            </Link>
            <button
              type="submit"
              :disabled="form.processing"
              class="bg-[#FFC107] hover:bg-[#FFB300] text-[#800000] px-5 py-2 rounded-lg font-semibold transition disabled:opacity-50"
            >
              <span v-if="form.processing">Creating...</span>
              <span v-else>Create User</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
