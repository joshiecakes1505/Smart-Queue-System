<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { inject } from 'vue';
import { usePolling } from '@/Composables/usePolling';

const swal = inject('$swal');

const props = defineProps({
  categories: {
    type: Array,
    default: () => [],
  },
  windows: {
    type: Array,
    default: () => [],
  },
});

const windowForm = useForm({ name: '' });

const addWindow = () => {
  windowForm.post(route('admin.cashier-windows.store'), {
    preserveScroll: true,
    onSuccess: () => windowForm.reset('name'),
  });
};

const toggleWindow = (windowItem) => {
  router.patch(route('admin.cashier-windows.update', windowItem.id), {
    active: !windowItem.active,
  }, {
    preserveScroll: true,
    onError: (errs) => swal?.fire({
      icon: 'error',
      title: 'Cannot change window',
      text: errs.active || 'Unable to update this window.',
    }),
  });
};

const renameWindow = async (windowItem) => {
  const result = await swal?.fire({
    icon: 'question',
    title: 'Rename window',
    input: 'text',
    inputValue: windowItem.name,
    showCancelButton: true,
    confirmButtonText: 'Save',
  });

  const name = result?.value?.trim();
  if (!name || name === windowItem.name) return;

  router.patch(route('admin.cashier-windows.update', windowItem.id), { name }, {
    preserveScroll: true,
    onError: (errs) => swal?.fire({
      icon: 'error',
      title: 'Rename failed',
      text: errs.name || 'Unable to rename this window.',
    }),
  });
};

const destroyWindow = async (windowItem) => {
  const decision = await swal?.fire({
    icon: 'warning',
    title: `Delete ${windowItem.name}?`,
    text: 'Past transactions stay in the reports. This cannot be undone.',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete',
  });

  if (swal && !decision?.isConfirmed) return;

  router.delete(route('admin.cashier-windows.destroy', windowItem.id), {
    preserveScroll: true,
    onError: (errs) => swal?.fire({
      icon: 'error',
      title: 'Delete failed',
      text: errs.window || 'Unable to delete this window.',
    }),
  });
};

const destroyCategory = async (id) => {
  const decision = await swal?.fire({
    icon: 'warning',
    title: 'Delete category?',
    text: 'This action cannot be undone.',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete',
  });

  if (swal && !decision?.isConfirmed) return;

  router.delete(route('admin.service-categories.destroy', id), {
    preserveScroll: true,
    onSuccess: () => {
      swal?.fire({
        icon: 'success',
        title: 'Deleted',
        text: 'Service category deleted successfully.',
      });
    },
    onError: () => {
      swal?.fire({
        icon: 'error',
        title: 'Delete failed',
        text: 'Unable to delete service category.',
      });
    },
  });
};

usePolling(() => {
  return router.reload({
    only: ['categories', 'windows'],
    preserveState: true,
    preserveScroll: true,
  });
}, 5000);
</script>

<template>
  <AuthenticatedLayout title="Service Categories">
    <div class="bg-white rounded-lg shadow-sm p-6">
      <div class="flex items-center justify-between gap-4 mb-4">
        <h1 class="text-2xl font-semibold text-[#800000]">Service Categories</h1>
        <Link
          :href="route('admin.service-categories.create')"
          class="bg-[#FFC107] hover:bg-[#FFB300] text-[#800000] px-4 py-2 rounded-lg font-semibold transition"
        >
          Add Category
        </Link>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-3 px-4 font-semibold text-gray-700">Name</th>
              <th class="text-left py-3 px-4 font-semibold text-gray-700">Prefix</th>
              <th class="text-left py-3 px-4 font-semibold text-gray-700">Avg Service (min)</th>
              <th class="text-left py-3 px-4 font-semibold text-gray-700">Max / Day</th>
              <th class="text-left py-3 px-4 font-semibold text-gray-700">Queue Rule</th>
              <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="props.categories.length === 0">
              <td colspan="6" class="text-center py-8 text-gray-500">No service categories found.</td>
            </tr>

            <tr
              v-for="category in props.categories"
              :key="category.id"
              class="border-b border-gray-100 hover:bg-gray-50"
            >
              <td class="py-3 px-4 font-medium text-gray-900">{{ category.name }}</td>
              <td class="py-3 px-4 font-semibold text-[#800000]">{{ category.prefix }}</td>
              <td class="py-3 px-4 text-gray-700">
                {{ category.avg_service_seconds ? (category.avg_service_seconds / 60).toFixed(1) : '—' }}
              </td>
              <td class="py-3 px-4 text-gray-700">{{ category.max_queues_per_day || '—' }}</td>
              <td class="py-3 px-4 text-gray-700">{{ category.regulars_per_priority_cycle ?? 1 }} regular : 1 priority</td>
              <td class="py-3 px-4">
                <div class="flex gap-2">
                  <Link
                    :href="route('admin.service-categories.edit', category.id)"
                    class="border-2 border-[#800000] hover:bg-[#800000] hover:text-white text-[#800000] px-3 py-1 rounded-lg font-semibold transition"
                  >
                    Edit
                  </Link>
                  <button
                    @click="destroyCategory(category.id)"
                    class="border-2 border-red-600 hover:bg-red-600 hover:text-white text-red-600 px-3 py-1 rounded-lg font-semibold transition"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Cashier windows -->
    <div class="mt-6 bg-white rounded-lg shadow-sm p-6">
      <div class="mb-4">
        <h2 class="text-2xl font-semibold text-[#800000]">Cashier Windows</h2>
        <p class="mt-1 text-sm text-gray-600">
          The display board follows this list automatically. Closed windows are hidden from the board.
        </p>
      </div>

      <form class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start" @submit.prevent="addWindow">
        <div class="flex-1">
          <input
            v-model="windowForm.name"
            type="text"
            placeholder="Window name, e.g. Window 4"
            maxlength="60"
            class="w-full rounded-lg border-gray-300 text-sm focus:border-[#800000] focus:ring-[#800000]"
          />
          <p v-if="windowForm.errors.name" class="mt-1 text-sm text-red-600">{{ windowForm.errors.name }}</p>
        </div>
        <button
          type="submit"
          :disabled="windowForm.processing || !windowForm.name.trim()"
          class="rounded-lg bg-[#800000] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#600000] disabled:cursor-not-allowed disabled:opacity-60"
        >
          {{ windowForm.processing ? 'Adding...' : 'Add Window' }}
        </button>
      </form>

      <div v-if="!windows.length" class="rounded-lg border border-dashed border-gray-300 py-8 text-center text-sm text-gray-500">
        No cashier windows yet. Add one above.
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500">
              <th class="px-3 py-2">Window</th>
              <th class="px-3 py-2">Assigned cashier</th>
              <th class="px-3 py-2">In progress</th>
              <th class="px-3 py-2">Status</th>
              <th class="px-3 py-2 text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="windowItem in windows" :key="windowItem.id" class="border-b border-gray-100 last:border-0">
              <td class="px-3 py-2.5 font-semibold text-gray-800">{{ windowItem.name }}</td>
              <td class="px-3 py-2.5 text-gray-600">{{ windowItem.assigned_user?.name || 'Unassigned' }}</td>
              <td class="px-3 py-2.5 text-gray-600">{{ windowItem.live_queues_count ?? 0 }}</td>
              <td class="px-3 py-2.5">
                <span
                  class="inline-block rounded-full px-2.5 py-1 text-xs font-semibold"
                  :class="windowItem.active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
                >
                  {{ windowItem.active ? 'Open' : 'Closed' }}
                </span>
              </td>
              <td class="px-3 py-2.5">
                <div class="flex flex-wrap justify-end gap-2">
                  <button
                    type="button"
                    class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-200"
                    @click="renameWindow(windowItem)"
                  >
                    Rename
                  </button>
                  <button
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                    :class="windowItem.active
                      ? 'bg-amber-100 text-amber-800 hover:bg-amber-200'
                      : 'bg-green-100 text-green-800 hover:bg-green-200'"
                    @click="toggleWindow(windowItem)"
                  >
                    {{ windowItem.active ? 'Close' : 'Open' }}
                  </button>
                  <button
                    type="button"
                    class="rounded-lg bg-red-100 px-3 py-1.5 text-xs font-semibold text-red-800 transition hover:bg-red-200"
                    @click="destroyWindow(windowItem)"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
