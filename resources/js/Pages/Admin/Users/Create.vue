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

const form = useForm({ name: '', email: '', role_id: '' })

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

          <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            New accounts are created with the default password <span class="font-semibold">BECQueue@2026</span>.
            The password is emailed automatically to the user after creation.
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
              class="inline-flex items-center gap-2 bg-[#FFC107] hover:bg-[#FFB300] text-[#800000] px-5 py-2 rounded-lg font-semibold transition disabled:opacity-50"
            >
              <svg v-if="form.processing" width="20" height="20" viewBox="0 0 38 38" aria-hidden="true">
                <g transform="translate(19 19)">
                  <g transform="rotate(0)"><circle cx="0" cy="12" r="3" fill="#800000" opacity="0.125"><animate attributeName="opacity" from="0.125" to="0.125" dur="1.2s" begin="0s" repeatCount="indefinite" keyTimes="0;1" values="1;0.125" /></circle></g>
                  <g transform="rotate(45)"><circle cx="0" cy="12" r="3" fill="#800000" opacity="0.25"><animate attributeName="opacity" from="0.25" to="0.25" dur="1.2s" begin="0.15s" repeatCount="indefinite" keyTimes="0;1" values="1;0.25" /></circle></g>
                  <g transform="rotate(90)"><circle cx="0" cy="12" r="3" fill="#800000" opacity="0.375"><animate attributeName="opacity" from="0.375" to="0.375" dur="1.2s" begin="0.3s" repeatCount="indefinite" keyTimes="0;1" values="1;0.375" /></circle></g>
                  <g transform="rotate(135)"><circle cx="0" cy="12" r="3" fill="#800000" opacity="0.5"><animate attributeName="opacity" from="0.5" to="0.5" dur="1.2s" begin="0.45s" repeatCount="indefinite" keyTimes="0;1" values="1;0.5" /></circle></g>
                  <g transform="rotate(180)"><circle cx="0" cy="12" r="3" fill="#800000" opacity="0.625"><animate attributeName="opacity" from="0.625" to="0.625" dur="1.2s" begin="0.6s" repeatCount="indefinite" keyTimes="0;1" values="1;0.625" /></circle></g>
                  <g transform="rotate(225)"><circle cx="0" cy="12" r="3" fill="#800000" opacity="0.75"><animate attributeName="opacity" from="0.75" to="0.75" dur="1.2s" begin="0.75s" repeatCount="indefinite" keyTimes="0;1" values="1;0.75" /></circle></g>
                  <g transform="rotate(270)"><circle cx="0" cy="12" r="3" fill="#800000" opacity="0.875"><animate attributeName="opacity" from="0.875" to="0.875" dur="1.2s" begin="0.9s" repeatCount="indefinite" keyTimes="0;1" values="1;0.875" /></circle></g>
                  <g transform="rotate(315)"><circle cx="0" cy="12" r="3" fill="#800000" opacity="1"><animate attributeName="opacity" from="1" to="1" dur="1.2s" begin="1.05s" repeatCount="indefinite" keyTimes="0;1" values="1;1" /></circle></g>
                </g>
              </svg>
              <span v-if="form.processing">Creating...</span>
              <span v-else>Create User</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
