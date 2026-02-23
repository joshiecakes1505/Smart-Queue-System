<script setup>
import { ref } from 'vue'
import { usePolling } from '@/Composables/usePolling'
import { Head } from '@inertiajs/vue3'

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

const formatTime = (timestamp) => {
  if (!timestamp) return '—';
  return new Date(timestamp).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
};
</script>

<template>
  <div class="min-h-screen bg-white">
    <Head title="Queue Display" />
    
    <!-- Maroon Header -->
    <header class="bg-[#800000] text-white py-6">
      <div class="container mx-auto px-8">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-4xl font-bold">Smart Queuing System</h1>
            <p class="text-yellow-200 text-lg mt-1">Batangas Eastern Colleges</p>
          </div>
          <div class="text-right">
            <p class="text-sm text-yellow-200">Current Time</p>
            <p class="text-3xl font-bold">{{ formatTime(data.timestamp) }}</p>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-8 py-8">
      <!-- Windows Grid -->
      <div class="mb-12">
        <h2 class="text-3xl font-bold text-[#800000] mb-6">Now Serving</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div 
            v-for="window in data.windows" 
            :key="window.id"
            class="bg-white border-4 border-[#800000] rounded-lg p-8"
          >
            <!-- Window Name -->
            <div class="bg-[#800000] text-white text-center py-3 rounded-lg mb-6">
              <h3 class="text-3xl font-bold">{{ window.name }}</h3>
            </div>

            <!-- Current Queue -->
            <div class="text-center mb-4">
              <p class="text-sm text-gray-600 mb-2">NOW SERVING</p>
              <div class="bg-[#FFC107] rounded-lg py-8">
                <p class="text-7xl font-bold text-[#800000]">
                  {{ window.current?.queue_number ?? '—' }}
                </p>
              </div>
            </div>

            <!-- Queue Details -->
            <div class="text-center text-gray-700 space-y-1">
              <p class="text-lg font-semibold">{{ window.current?.client_name || 'Waiting for next' }}</p>
              <p class="text-sm text-gray-500">{{ window.current?.service_category || '—' }}</p>
            </div>
          </div>
        </div>

        <!-- No Windows Message -->
        <div v-if="data.windows.length === 0" class="text-center py-12">
          <p class="text-gray-500 text-xl">No active windows</p>
        </div>
      </div>

      <!-- Next in Queue -->
      <div class="bg-gray-50 border-2 border-gray-200 rounded-lg p-8">
        <h3 class="text-2xl font-bold text-[#800000] mb-6 text-center">Next in Queue</h3>
        
        <div v-if="data.next_queues.length > 0" class="grid grid-cols-2 md:grid-cols-5 gap-4">
          <div 
            v-for="(queue, idx) in data.next_queues" 
            :key="queue.queue_number"
            class="bg-white border-2 border-[#800000] rounded-lg p-6 text-center"
          >
            <p class="text-xs text-gray-500 mb-2">Position {{ idx + 1 }}</p>
            <p class="text-4xl font-bold text-[#800000]">{{ queue.queue_number }}</p>
            <p class="text-sm text-gray-600 mt-3">{{ queue.service_category || 'N/A' }}</p>
          </div>
        </div>

        <div v-else class="text-center py-8">
          <p class="text-gray-500 text-lg">No queues waiting</p>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-100 py-4 mt-12">
      <div class="container mx-auto px-8 text-center">
        <p class="text-sm text-gray-600">Auto-refreshing every 5 seconds</p>
      </div>
    </footer>
  </div>
</template>
