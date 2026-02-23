<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({
  name: '',
  prefix: '',
  description: '',
  max_queues_per_day: '',
  avg_service_seconds: '',
});

function submit() {
  form.post(route('admin.service-categories.store'));
}
</script>

<template>
  <AuthenticatedLayout title="Create Service Category">
    <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-sm p-6">
      <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold text-[#800000]">Create Service Category</h1>
        <Link
          :href="route('admin.service-categories.index')"
          class="text-sm text-gray-600 hover:text-[#800000]"
        >
          Back to list
        </Link>
      </div>

      <form class="space-y-4" @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000]"
              required
            />
            <p v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Prefix (1 Letter)</label>
            <input
              v-model="form.prefix"
              type="text"
              maxlength="1"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 uppercase focus:outline-none focus:ring-2 focus:ring-[#800000]"
              required
            />
            <p v-if="form.errors.prefix" class="text-red-500 text-sm mt-1">{{ form.errors.prefix }}</p>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
          <textarea
            v-model="form.description"
            rows="3"
            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000]"
          />
          <p v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Max Queues Per Day</label>
            <input
              v-model="form.max_queues_per_day"
              type="number"
              min="1"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000]"
            />
            <p v-if="form.errors.max_queues_per_day" class="text-red-500 text-sm mt-1">{{ form.errors.max_queues_per_day }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Average Service Seconds</label>
            <input
              v-model="form.avg_service_seconds"
              type="number"
              min="1"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000]"
            />
            <p v-if="form.errors.avg_service_seconds" class="text-red-500 text-sm mt-1">{{ form.errors.avg_service_seconds }}</p>
          </div>
        </div>

        <div class="pt-2">
          <button
            type="submit"
            :disabled="form.processing"
            class="bg-[#FFC107] hover:bg-[#FFB300] text-[#800000] px-6 py-3 rounded-lg font-semibold transition disabled:opacity-50"
          >
            Create Category
          </button>
        </div>
      </form>
    </div>
  </AuthenticatedLayout>
</template>
