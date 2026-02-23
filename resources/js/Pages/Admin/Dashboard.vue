<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { usePolling } from '@/Composables/usePolling';

defineProps({
  totalQueuestoday: Number,
  totalCompletedToday: Number,
  totalWaitingNow: Number,
  totalUsers: Number,
  avgServiceMinutes: Number,
  busiestHour: Number,
  busiestHourCount: Number,
})

usePolling(() => {
  return router.reload({
    only: [
      'totalQueuestoday',
      'totalCompletedToday',
      'totalWaitingNow',
      'totalUsers',
      'avgServiceMinutes',
      'busiestHour',
      'busiestHourCount',
    ],
    preserveState: true,
    preserveScroll: true,
  });
}, 3000)
</script>

<template>
  <AuthenticatedLayout title="Admin Dashboard">
    <div class="space-y-6">
      <!-- Key Metrics Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Queues Today -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <p class="text-gray-600 text-sm font-medium">Queues Created Today</p>
          <p class="text-4xl font-bold text-[#800000] mt-2">{{ totalQueuestoday }}</p>
        </div>

        <!-- Completed Today -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <p class="text-gray-600 text-sm font-medium">Completed Today</p>
          <p class="text-4xl font-bold text-green-600 mt-2">{{ totalCompletedToday }}</p>
        </div>

        <!-- Currently Waiting -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <p class="text-gray-600 text-sm font-medium">Waiting Now</p>
          <p class="text-4xl font-bold text-[#FFC107] mt-2">{{ totalWaitingNow }}</p>
        </div>

        <!-- Total Users -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <p class="text-gray-600 text-sm font-medium">Total Users</p>
          <p class="text-4xl font-bold text-[#800000] mt-2">{{ totalUsers }}</p>
        </div>
      </div>

      <!-- Performance & Quick Actions -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Performance Metrics -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-xl font-semibold text-[#800000] mb-4">Performance Metrics</h2>
          <div class="space-y-4">
            <div class="border-b border-gray-200 pb-3">
              <p class="text-gray-600 text-sm">Average Service Time</p>
              <p class="text-3xl font-bold text-gray-900 mt-1">{{ avgServiceMinutes }} mins</p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Busiest Hour</p>
              <p class="text-3xl font-bold text-gray-900 mt-1">
                {{ busiestHour !== null ? `${busiestHour}:00 - ${busiestHour + 1}:00` : 'N/A' }}
              </p>
              <p class="text-sm text-gray-500 mt-1">{{ busiestHourCount }} queues served</p>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-xl font-semibold text-[#800000] mb-4">Quick Actions</h2>
          <div class="space-y-3">
            <Link 
              :href="route('admin.users.index')" 
              class="block px-4 py-3 border-2 border-[#800000] text-[#800000] rounded-lg hover:bg-[#800000] hover:text-white transition"
            >
              <span class="font-semibold">Manage Users</span>
              <p class="text-sm opacity-75">Add/edit staff accounts</p>
            </Link>
            <Link 
              :href="route('admin.service-categories.index')" 
              class="block px-4 py-3 border-2 border-[#800000] text-[#800000] rounded-lg hover:bg-[#800000] hover:text-white transition"
            >
              <span class="font-semibold">Service Categories</span>
              <p class="text-sm opacity-75">Configure service types</p>
            </Link>
            <Link 
              :href="route('display.index')" 
              class="block px-4 py-3 bg-[#FFC107] text-[#800000] rounded-lg hover:bg-[#FFB300] transition"
            >
              <span class="font-semibold">View Display Board</span>
              <p class="text-sm opacity-75">Public TV/monitor display</p>
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
