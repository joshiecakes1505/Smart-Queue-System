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
});

const selectedCashierByWindow = reactive(
  props.cashierWindows.reduce((accumulator, window) => {
    accumulator[window.id] = window.assigned_user_id ?? '';
    return accumulator;
  }, {})
);

const processingWindowId = ref(null);
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
              </tr>
            </thead>
            <tbody>
              <tr v-if="users.length === 0">
                <td colspan="3" class="text-center py-8 text-gray-500">No users found</td>
              </tr>
              <tr v-for="user in users" :key="user.id" class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-3 px-4 font-medium text-gray-900">{{ user.name }}</td>
                <td class="py-3 px-4 text-gray-700">{{ user.email }}</td>
                <td class="py-3 px-4 capitalize">{{ roleLabel(user) }}</td>
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
