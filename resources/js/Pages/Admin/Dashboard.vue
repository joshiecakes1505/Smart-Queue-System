<script setup>
import { Link } from '@inertiajs/vue3';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

defineProps({
  totalQueuestoday: Number,
  totalCompletedToday: Number,
  totalWaitingNow: Number,
  totalUsers: Number,
  avgServiceMinutes: Number,
  busiestHour: Number,
  busiestHourCount: Number,
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Admin Dashboard</h1>
        <p class="text-gray-600">Smart Queue System Overview</p>
      </div>

      <!-- Key Metrics Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Queues Today -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-600 text-sm font-medium">Queues Created Today</p>
              <p class="text-3xl font-bold text-blue-600 mt-2">{{ totalQueuestoday }}</p>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Completed Today -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-600 text-sm font-medium">Completed Today</p>
              <p class="text-3xl font-bold text-green-600 mt-2">{{ totalCompletedToday }}</p>
            </div>
            <div class="bg-green-100 rounded-full p-3">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Currently Waiting -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-600 text-sm font-medium">Waiting Now</p>
              <p class="text-3xl font-bold text-orange-600 mt-2">{{ totalWaitingNow }}</p>
            </div>
            <div class="bg-orange-100 rounded-full p-3">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>
        </div>

        <!-- Total Users -->
        <div class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-600 text-sm font-medium">Total Users</p>
              <p class="text-3xl font-bold text-purple-600 mt-2">{{ totalUsers }}</p>
            </div>
            <div class="bg-purple-100 rounded-full p-3">
              <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 4H9m3 13H9m3 0h6m2 2H3m18 0H3"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Performance Metrics -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Average Service Time -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-bold text-gray-900 mb-4">Performance Metrics</h2>
          <div class="space-y-4">
            <div>
              <p class="text-gray-600 text-sm">Average Service Time</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">{{ avgServiceMinutes }} mins</p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Busiest Hour</p>
              <p class="text-2xl font-bold text-gray-900 mt-1">
                {{ busiestHour !== null ? `${busiestHour}:00 - ${busiestHour + 1}:00` : 'N/A' }}
                <span class="text-sm text-gray-600 ml-2">({{ busiestHourCount }} queues)</span>
              </p>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow p-6">
          <h2 class="text-lg font-bold text-gray-900 mb-4">Quick Actions</h2>
          <div class="space-y-3">
            <Link href="/admin/users" class="block px-4 py-3 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition">
              <span class="font-medium">Manage Users</span>
              <p class="text-sm text-blue-600">Add/edit staff</p>
            </Link>
            <Link href="/admin/service-categories" class="block px-4 py-3 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition">
              <span class="font-medium">Service Categories</span>
              <p class="text-sm text-green-600">Configure services</p>
            </Link>
            <Link href="/display" class="block px-4 py-3 bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition">
              <span class="font-medium">View Display Board</span>
              <p class="text-sm text-purple-600">TV/monitor display</p>
            </Link>
          </div>
        </div>

        <div class="mt-3 space-y-1">
            <ResponsiveNavLink
                :href="route('logout')"
                method="post"
                as="button"
            >
                Log Out
            </ResponsiveNavLink>
        </div>
      </div>
    </div>
  </div>
</template>
