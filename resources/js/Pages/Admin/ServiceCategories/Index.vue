<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { inject } from 'vue';
import { usePolling } from '@/Composables/usePolling';

const swal = inject('$swal');

const props = defineProps({
  categories: {
    type: Array,
    default: () => [],
  },
});

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
    only: ['categories'],
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
              <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="props.categories.length === 0">
              <td colspan="5" class="text-center py-8 text-gray-500">No service categories found.</td>
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
  </AuthenticatedLayout>
</template>
