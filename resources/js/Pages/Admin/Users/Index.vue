<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { inject, reactive, ref } from 'vue';
import { usePolling } from '@/Composables/usePolling';

const props = defineProps({
  users: {
    type: Array,
    default: () => [],
  },
  cashiers: {
    type: Array,
    default: () => [],
  },
  cashierWindows: {
    type: Array,
    default: () => [],
  },
  authUserId: {
    type: Number,
    default: null,
  },
});

const selectedCashierByWindow = reactive(
  props.cashierWindows.reduce((accumulator, window) => {
    accumulator[window.id] = window.assigned_user_id ?? '';
    return accumulator;
  }, {})
);

const processingWindowId = ref(null);
const deletingUserId = ref(null);
const message = ref('');
const swal = inject('$swal');

const assignCashier = (windowId) => {
  processingWindowId.value = windowId;
  message.value = '';

  const selectedValue = selectedCashierByWindow[windowId];

  router.post(route('admin.cashier-windows.assign', windowId), {
    cashier_user_id: selectedValue === '' ? null : selectedValue,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      message.value = 'Cashier assignment updated.';
      swal?.fire({
        icon: 'success',
        title: 'Saved',
        text: 'Cashier assignment updated.',
      });
      router.reload({ only: ['cashierWindows'] });
    },
    onError: () => {
      swal?.fire({
        icon: 'error',
        title: 'Update failed',
        text: 'Unable to update cashier assignment. Please try again.',
      });
    },
    onFinish: () => {
      processingWindowId.value = null;
    },
  });
};

const roleLabel = (user) => user?.role?.name || 'n/a';

const toggleUserStatus = async (user) => {
  if (user.id === props.authUserId) {
    return;
  }

  const isDisabled = user.disabled_at !== null;

  const confirmation = await swal?.fire({
    icon: 'warning',
    title: isDisabled ? 'Enable this account?' : 'Disable this account?',
    text: isDisabled
      ? `This action will enable ${user.name}'s account and allow sign-ins again.`
      : `This action will disable ${user.name}'s account and block future sign-ins.`,
    showCancelButton: true,
    confirmButtonText: isDisabled ? 'Yes, enable account' : 'Yes, disable account',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#b91c1c',
    cancelButtonColor: '#6b7280',
    reverseButtons: true,
  });

  if (confirmation && !confirmation.isConfirmed) {
    return;
  }

  deletingUserId.value = user.id;

  const request = isDisabled
    ? router.patch(route('admin.users.enable', user.id), {}, {
      preserveScroll: true,
      onSuccess: () => {
        swal?.fire({
          icon: 'success',
          title: 'User enabled',
          text: 'The user account has been enabled.',
        });
      },
      onError: (errors) => {
        swal?.fire({
          icon: 'error',
          title: 'Enable failed',
          text: errors?.user || 'Unable to enable this account right now.',
        });
      },
      onFinish: () => {
        deletingUserId.value = null;
      },
    })
    : router.delete(route('admin.users.destroy', user.id), {
    preserveScroll: true,
    onSuccess: () => {
      swal?.fire({
        icon: 'success',
        title: 'User disabled',
        text: 'The user account has been disabled.',
      });
    },
    onError: (errors) => {
      swal?.fire({
        icon: 'error',
        title: 'Disable failed',
        text: errors?.user || 'Unable to disable this account right now.',
      });
    },
    onFinish: () => {
      deletingUserId.value = null;
    },
  });

  return request;
};

usePolling(() => {
  return router.reload({
    only: ['users', 'cashiers', 'cashierWindows'],
    preserveState: true,
    preserveScroll: true,
  });
}, 5000);
</script>

<template>
  <AuthenticatedLayout title="Manage Users">
    <div class="space-y-6">
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between gap-4 mb-4">
          <h1 class="text-2xl font-semibold text-[#800000]">Users</h1>
          <Link
            :href="route('admin.users.create')"
            class="bg-[#FFC107] hover:bg-[#FFB300] text-[#800000] px-4 py-2 rounded-lg font-semibold transition"
          >
            Add User
          </Link>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Name</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Email</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Role</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="users.length === 0">
                <td colspan="4" class="text-center py-8 text-gray-500">No users found</td>
              </tr>
              <tr v-for="user in users" :key="user.id" class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-3 px-4 font-medium text-gray-900">{{ user.name }}</td>
                <td class="py-3 px-4 text-gray-700">{{ user.email }}</td>
                <td class="py-3 px-4 capitalize">{{ roleLabel(user) }}</td>
                <td class="py-3 px-4">
                  <div class="flex items-center gap-2">
                    <Link
                      :href="route('admin.users.edit', user.id)"
                      class="inline-flex items-center rounded-lg border border-[#800000] px-4 py-2 text-sm font-semibold text-[#800000] transition hover:bg-[#800000] hover:text-white"
                    >
                      Edit
                    </Link>
                    <button
                      v-if="user.id !== authUserId"
                      type="button"
                      :disabled="deletingUserId === user.id"
                      class="inline-flex items-center rounded-lg border border-red-600 px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-600 hover:text-white disabled:opacity-50"
                      @click="toggleUserStatus(user)"
                    >
                      <span v-if="deletingUserId === user.id">
                        {{ user.disabled_at ? 'Enabling...' : 'Disabling...' }}
                      </span>
                      <span v-else>
                        {{ user.disabled_at ? 'Enable' : 'Disable' }}
                      </span>
                    </button>
                    <span
                      v-if="user.id === authUserId"
                      class="text-xs font-medium uppercase tracking-wide text-gray-400"
                    >
                      Current Account
                    </span>
                    <span
                      v-else-if="user.disabled_at"
                      class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700"
                    >
                      Disabled
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-xl font-semibold text-[#800000] mb-4">Assign Cashier Per Window</h2>

        <div
          v-if="message"
          class="mb-4 border border-green-200 bg-green-50 text-green-800 rounded-lg px-4 py-3 text-sm"
        >
          {{ message }}
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Window</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Current Cashier</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Assign Cashier</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="cashierWindows.length === 0">
                <td colspan="4" class="text-center py-8 text-gray-500">No cashier windows available</td>
              </tr>
              <tr
                v-for="window in cashierWindows"
                :key="window.id"
                class="border-b border-gray-100 hover:bg-gray-50"
              >
                <td class="py-3 px-4 font-semibold text-[#800000]">{{ window.name }}</td>
                <td class="py-3 px-4 text-gray-700">{{ window.assigned_user?.name || 'Unassigned' }}</td>
                <td class="py-3 px-4">
                  <select
                    v-model="selectedCashierByWindow[window.id]"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000]"
                  >
                    <option value="">Unassign</option>
                    <option v-for="cashier in cashiers" :key="cashier.id" :value="cashier.id">
                      {{ cashier.name }} ({{ cashier.email }})
                    </option>
                  </select>
                </td>
                <td class="py-3 px-4">
                  <button
                    @click="assignCashier(window.id)"
                    :disabled="processingWindowId === window.id"
                    class="border-2 border-[#800000] hover:bg-[#800000] hover:text-white text-[#800000] px-4 py-2 rounded-lg font-semibold transition disabled:opacity-50"
                  >
                    Save
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
