<script setup>
import { ref } from 'vue'
import { usePolling } from '@/Composables/usePolling'

const data = ref({ windows: [], next_queues: [], timestamp: null })

const fetchData = async () => {
  try {
    const res = await fetch('/display/data')
    data.value = await res.json()
  } catch (error) {
    console.error('Failed to fetch display data:', error)
  }
}

usePolling(fetchData, 5000)
</script>

<template>
  <div class="min-h-screen bg-gray-900 text-white p-8">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-5xl font-bold mb-2">Queue Display System</h1>
      <p class="text-gray-400">{{ data.timestamp ? new Date(data.timestamp).toLocaleTimeString() : '—' }}</p>
    </div>

    <!-- Windows Grid -->
    <div class="grid grid-cols-3 gap-6 mb-12">
      <div 
        v-for="window in data.windows" 
        :key="window.id"
        class="bg-gray-800 rounded-lg p-6 border-2 border-blue-500"
      >
        <h2 class="text-3xl font-bold mb-4">{{ window.name }}</h2>
        <div class="mb-4">
          <p class="text-sm text-gray-400">Now Serving</p>
          <p class="text-4xl font-bold text-green-400">
            {{ window.current?.queue_number ?? '—' }}
          </p>
          <p class="text-lg text-gray-300">{{ window.current?.client_name ?? 'Waiting for next' }}</p>
        </div>
        <div class="text-sm text-gray-400">
          <p>Service: {{ window.current?.service_category ?? '—' }}</p>
          <p>Staff: {{ window.assigned_user ?? '—' }}</p>
        </div>
      </div>
    </div>

    <!-- Next in Queue -->
    <div class="bg-gray-800 rounded-lg p-6 border-2 border-purple-500">
      <h3 class="text-2xl font-bold mb-4">Next in Queue</h3>
      <div class="grid grid-cols-5 gap-4">
        <div 
          v-for="(queue, idx) in data.next_queues" 
          :key="queue.queue_number"
          class="bg-gray-700 p-4 rounded text-center"
        >
          <p class="text-xs text-gray-400 mb-2">Position {{ idx + 1 }}</p>
          <p class="text-2xl font-bold text-yellow-400">{{ queue.queue_number }}</p>
          <p class="text-sm text-gray-300 mt-2">{{ queue.client_name }}</p>
          <p class="text-xs text-gray-400">{{ queue.service_category }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
