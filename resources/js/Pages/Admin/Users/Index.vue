<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, inject, reactive, ref } from 'vue';
import { usePolling } from '@/Composables/usePolling';
import { formatManilaDateTime } from '@/Utils/dateTime';

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

const lastLoginLabel = (user) => user.last_login_at ? formatManilaDateTime(user.last_login_at) : 'Never';

const userStatus = (user) => {
  if (user.deleted_at) return 'deleted';
  if (user.archived_at) return 'archived';
  if (user.disabled_at) return 'disabled';
  return 'active';
};

const STATUS_LABELS = {
  active: 'Active',
  disabled: 'Disabled',
  archived: 'Archived',
  deleted: 'Deleted',
};

const STATUS_BADGE_CLASSES = {
  active: 'bg-green-100 text-green-700',
  disabled: 'bg-red-100 text-red-700',
  archived: 'bg-amber-100 text-amber-700',
  deleted: 'bg-gray-700 text-white',
};

const statusFilter = ref('all');
const statusTabs = [
  { value: 'all', label: 'All' },
  { value: 'active', label: 'Active' },
  { value: 'disabled', label: 'Disabled' },
  { value: 'archived', label: 'Archived' },
  { value: 'deleted', label: 'Deleted' },
];

const filteredUsers = computed(() => {
  if (statusFilter.value === 'all') {
    return props.users;
  }

  return props.users.filter((user) => userStatus(user) === statusFilter.value);
});

// Shared confirm-dialog + request runner for every lifecycle action below.
const runLifecycleAction = async (user, { routeName, method, confirmTitle, confirmText, confirmButtonText, successTitle, successText, confirmButtonColor = '#b91c1c' }) => {
  if (user.id === props.authUserId) {
    return;
  }

  const confirmation = await swal?.fire({
    icon: 'warning',
    title: confirmTitle,
    text: confirmText,
    showCancelButton: true,
    confirmButtonText,
    cancelButtonText: 'Cancel',
    confirmButtonColor,
    cancelButtonColor: '#6b7280',
    reverseButtons: true,
  });

  if (confirmation && !confirmation.isConfirmed) {
    return;
  }

  deletingUserId.value = user.id;

  router[method](route(routeName, user.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      swal?.fire({ icon: 'success', title: successTitle, text: successText });
    },
    onError: (errors) => {
      swal?.fire({
        icon: 'error',
        title: 'Action failed',
        text: errors?.user || 'Unable to update this account right now.',
      });
    },
    onFinish: () => {
      deletingUserId.value = null;
    },
  });
};

const disableUser = (user) => runLifecycleAction(user, {
  routeName: 'admin.users.destroy',
  method: 'delete',
  confirmTitle: 'Disable this account?',
  confirmText: `This action will disable ${user.name}'s account and block future sign-ins.`,
  confirmButtonText: 'Yes, disable account',
  successTitle: 'User disabled',
  successText: 'The user account has been disabled.',
});

const enableUser = (user) => runLifecycleAction(user, {
  routeName: 'admin.users.enable',
  method: 'patch',
  confirmTitle: 'Enable this account?',
  confirmText: `This action will enable ${user.name}'s account and allow sign-ins again.`,
  confirmButtonText: 'Yes, enable account',
  successTitle: 'User enabled',
  successText: 'The user account has been enabled.',
  confirmButtonColor: '#15803d',
});

const archiveUser = (user) => runLifecycleAction(user, {
  routeName: 'admin.users.archive',
  method: 'patch',
  confirmTitle: 'Archive this account?',
  confirmText: `This will archive ${user.name}'s account. It will be scheduled for automatic deletion after continued inactivity.`,
  confirmButtonText: 'Yes, archive account',
  successTitle: 'User archived',
  successText: 'The user account has been archived.',
});

const unarchiveUser = (user) => runLifecycleAction(user, {
  routeName: 'admin.users.unarchive',
  method: 'patch',
  confirmTitle: 'Restore from archive?',
  confirmText: `This will move ${user.name}'s account back to Disabled. You can enable it separately afterward.`,
  confirmButtonText: 'Yes, restore',
  successTitle: 'User restored',
  successText: 'The user account has been restored from the archive.',
  confirmButtonColor: '#15803d',
});

const deleteUserNow = (user) => runLifecycleAction(user, {
  routeName: 'admin.users.delete-now',
  method: 'delete',
  confirmTitle: 'Delete this account now?',
  confirmText: `This will delete ${user.name}'s account immediately instead of waiting for the automatic lifecycle. It can still be restored afterward.`,
  confirmButtonText: 'Yes, delete now',
  successTitle: 'User deleted',
  successText: 'The user account has been deleted.',
});

const restoreUser = (user) => runLifecycleAction(user, {
  routeName: 'admin.users.restore',
  method: 'patch',
  confirmTitle: 'Restore this account?',
  confirmText: `This will restore ${user.name}'s account out of Deleted, back into Archived.`,
  confirmButtonText: 'Yes, restore',
  successTitle: 'User restored',
  successText: 'The user account has been restored.',
  confirmButtonColor: '#15803d',
});

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

        <div class="flex flex-wrap gap-2 mb-4">
          <button
            v-for="tab in statusTabs"
            :key="tab.value"
            type="button"
            @click="statusFilter = tab.value"
            class="rounded-lg px-3 py-1.5 text-sm font-semibold transition"
            :class="statusFilter === tab.value
              ? 'bg-[#800000] text-white'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
          >
            {{ tab.label }}
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Name</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Email</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Role</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Last Login</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredUsers.length === 0">
                <td colspan="6" class="text-center py-8 text-gray-500">No users found</td>
              </tr>
              <tr v-for="user in filteredUsers" :key="user.id" class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-3 px-4 font-medium text-gray-900">{{ user.name }}</td>
                <td class="py-3 px-4 text-gray-700">{{ user.email }}</td>
                <td class="py-3 px-4 capitalize">{{ roleLabel(user) }}</td>
                <td class="py-3 px-4 text-gray-600 text-sm">{{ lastLoginLabel(user) }}</td>
                <td class="py-3 px-4">
                  <span
                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                    :class="STATUS_BADGE_CLASSES[userStatus(user)]"
                  >
                    {{ STATUS_LABELS[userStatus(user)] }}
                  </span>
                </td>
                <td class="py-3 px-4">
                  <div class="flex flex-wrap items-center gap-2">
                    <span
                      v-if="user.id === authUserId"
                      class="text-xs font-medium uppercase tracking-wide text-gray-400"
                    >
                      Current Account
                    </span>

                    <template v-else>
                      <Link
                        v-if="userStatus(user) !== 'deleted'"
                        :href="route('admin.users.edit', user.id)"
                        class="inline-flex items-center rounded-lg border border-[#800000] px-4 py-2 text-sm font-semibold text-[#800000] transition hover:bg-[#800000] hover:text-white"
                      >
                        Edit
                      </Link>

                      <button
                        v-if="userStatus(user) === 'active'"
                        type="button"
                        :disabled="deletingUserId === user.id"
                        class="inline-flex items-center rounded-lg border border-red-600 px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-600 hover:text-white disabled:opacity-50"
                        @click="disableUser(user)"
                      >
                        {{ deletingUserId === user.id ? 'Disabling...' : 'Disable' }}
                      </button>

                      <template v-if="userStatus(user) === 'disabled'">
                        <button
                          type="button"
                          :disabled="deletingUserId === user.id"
                          class="inline-flex items-center rounded-lg border border-green-600 px-4 py-2 text-sm font-semibold text-green-600 transition hover:bg-green-600 hover:text-white disabled:opacity-50"
                          @click="enableUser(user)"
                        >
                          {{ deletingUserId === user.id ? 'Enabling...' : 'Enable' }}
                        </button>
                        <button
                          type="button"
                          :disabled="deletingUserId === user.id"
                          class="inline-flex items-center rounded-lg border border-amber-600 px-4 py-2 text-sm font-semibold text-amber-600 transition hover:bg-amber-600 hover:text-white disabled:opacity-50"
                          @click="archiveUser(user)"
                        >
                          {{ deletingUserId === user.id ? 'Archiving...' : 'Archive' }}
                        </button>
                      </template>

                      <template v-if="userStatus(user) === 'archived'">
                        <button
                          type="button"
                          :disabled="deletingUserId === user.id"
                          class="inline-flex items-center rounded-lg border border-green-600 px-4 py-2 text-sm font-semibold text-green-600 transition hover:bg-green-600 hover:text-white disabled:opacity-50"
                          @click="unarchiveUser(user)"
                        >
                          {{ deletingUserId === user.id ? 'Restoring...' : 'Unarchive' }}
                        </button>
                        <button
                          type="button"
                          :disabled="deletingUserId === user.id"
                          class="inline-flex items-center rounded-lg border border-gray-700 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-700 hover:text-white disabled:opacity-50"
                          @click="deleteUserNow(user)"
                        >
                          {{ deletingUserId === user.id ? 'Deleting...' : 'Delete Now' }}
                        </button>
                      </template>

                      <button
                        v-if="userStatus(user) === 'deleted'"
                        type="button"
                        :disabled="deletingUserId === user.id"
                        class="inline-flex items-center rounded-lg border border-green-600 px-4 py-2 text-sm font-semibold text-green-600 transition hover:bg-green-600 hover:text-white disabled:opacity-50"
                        @click="restoreUser(user)"
                      >
                        {{ deletingUserId === user.id ? 'Restoring...' : 'Restore' }}
                      </button>
                    </template>
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
