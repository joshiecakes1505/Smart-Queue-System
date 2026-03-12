<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { inject } from 'vue'

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
  roles: {
    type: Array,
    default: () => [],
  },
  authUserId: {
    type: Number,
    default: null,
  },
})

const swal = inject('$swal')

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  role_id: String(props.user.role_id),
})

function submit() {
  form.put(route('admin.users.update', props.user.id), {
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
        title: 'User updated',
        text: 'The user details have been updated successfully.',
      })
    },
  })
}

async function resetPassword() {
  const confirmation = await swal?.fire({
    icon: 'warning',
    title: 'Reset this password?',
    text: 'This will set the user password to BECQueue@2026 and send a new account email.',
    showCancelButton: true,
    confirmButtonText: 'Yes, reset password',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#800000',
    cancelButtonColor: '#6b7280',
    reverseButtons: true,
  })

  if (confirmation && !confirmation.isConfirmed) {
    return
  }

  router.post(route('admin.users.reset-password', props.user.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      swal?.fire({
        icon: 'success',
        title: 'Password reset',
        text: 'The password has been reset to the default value and the login email was sent.',
      })
    },
    onError: () => {
      swal?.fire({
        icon: 'error',
        title: 'Reset failed',
        text: 'Unable to reset the password right now.',
      })
    },
  })
}
</script>

<template>
  <AuthenticatedLayout title="Edit User">
    <div class="max-w-3xl">
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="mb-6 flex items-center justify-between gap-4">
          <div>
            <h1 class="text-2xl font-semibold text-[#800000]">Edit User</h1>
            <p class="mt-1 text-sm text-gray-600">Update the user's account details. Password reset will be handled separately.</p>
          </div>
          <Link
            :href="route('admin.users.index')"
            class="rounded-lg bg-gray-500 px-4 py-2 font-semibold text-white transition hover:bg-gray-600"
          >
            Back to Users
          </Link>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">Name</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000]"
              placeholder="Enter full name"
            />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">Email</label>
            <input
              v-model="form.email"
              type="email"
              class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000]"
              placeholder="Enter email address"
            />
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
          </div>

          <div>
            <label class="mb-2 block text-sm font-medium text-gray-700">Role</label>
            <select
              v-model="form.role_id"
              class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000]"
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
              class="rounded-lg border border-gray-300 px-4 py-2 font-semibold text-gray-700 transition hover:bg-gray-50"
            >
              Cancel
            </Link>
            <button
              type="submit"
              :disabled="form.processing"
              class="rounded-lg bg-[#FFC107] px-5 py-2 font-semibold text-[#800000] transition hover:bg-[#FFB300] disabled:opacity-50"
            >
              <span v-if="form.processing">Saving...</span>
              <span v-else>Save Changes</span>
            </button>
          </div>
        </form>

        <div class="mt-8 rounded-lg border border-gray-200 bg-gray-50 p-5">
          <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
              <h2 class="text-lg font-semibold text-[#800000]">Password Reset</h2>
              <p class="mt-1 text-sm text-gray-600">
                Resetting a password sets it to <span class="font-semibold">BECQueue@2026</span> and sends a fresh email notification.
              </p>
            </div>
            <button
              v-if="props.user.id !== authUserId"
              type="button"
              class="rounded-lg border border-[#800000] px-4 py-2 font-semibold text-[#800000] transition hover:bg-[#800000] hover:text-white"
              @click="resetPassword"
            >
              Reset to Default Password
            </button>
            <p v-else class="text-sm font-medium text-gray-500">
              Password reset for the current signed-in admin is disabled for now.
            </p>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
