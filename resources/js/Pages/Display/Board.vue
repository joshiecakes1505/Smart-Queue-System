<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { usePolling } from '@/Composables/usePolling'
import { Head } from '@inertiajs/vue3'

const data = ref({ windows: [], next_queues: [], timestamp: null })
const refreshIntervalMs = 2000
const isFullscreen = ref(false)
const schoolLogoUrl = document.querySelector('meta[name="app-logo-url"]')?.getAttribute('content')
  || `${window.location.origin}/images/school-logo.png`

const fetchData = async () => {
  try {
    const res = await fetch(window.route('display.data'))
    data.value = await res.json()
  } catch (error) {
    console.error('Failed to fetch display data:', error)
  }
}

usePolling(fetchData, refreshIntervalMs)

const formatTime = (timestamp) => {
  if (!timestamp) return '—';
  return new Date(timestamp).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
};

const syncFullscreenState = () => {
  isFullscreen.value = !!document.fullscreenElement
}

const toggleFullscreen = async () => {
  try {
    if (!document.fullscreenElement) {
      await document.documentElement.requestFullscreen()
    } else {
      await document.exitFullscreen()
    }
  } catch (error) {
    console.error('Failed to toggle fullscreen:', error)
  }
}

onMounted(() => {
  syncFullscreenState()
  document.addEventListener('fullscreenchange', syncFullscreenState)
})

onBeforeUnmount(() => {
  document.removeEventListener('fullscreenchange', syncFullscreenState)
})
</script>

<template>
  <div class="min-h-screen bg-white">
    <Head title="Queue Display" />
    
    <!-- Maroon Header -->
    <header class="bg-[#800000] text-white py-4 sm:py-6">
      <div class="container mx-auto px-4 sm:px-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:justify-between lg:items-center">
          <div>
            <div class="flex items-center gap-4">
              <img
                :src="schoolLogoUrl"
                alt="School Logo"
                class="h-12 w-12 sm:h-16 sm:w-16 object-contain"
              />
              <div>
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold leading-tight">Smart Queuing System</h1>
                <p class="text-yellow-200 text-sm sm:text-base lg:text-lg mt-1">Batangas Eastern Colleges</p>
              </div>
            </div>
          </div>
          <div class="text-left lg:text-right">
            <p class="text-sm text-yellow-200">Current Time</p>
            <p class="text-2xl sm:text-3xl font-bold">{{ formatTime(data.timestamp) }}</p>
            <button
              type="button"
              class="mt-3 inline-flex items-center rounded-md border border-white px-3 py-2 text-sm font-semibold text-white hover:bg-white hover:text-[#800000]"
              @click="toggleFullscreen"
              :title="isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'"
              :aria-label="isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'"
            >
              <span class="text-lg leading-none" aria-hidden="true">{{ isFullscreen ? '🗗' : '⛶' }}</span>
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 sm:px-8 py-6 sm:py-8">
      <!-- Windows Grid -->
      <div class="mb-12">
        <h2 class="text-2xl sm:text-3xl font-bold text-[#800000] mb-6">Now Serving</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-6">
          <div 
            v-for="window in data.windows" 
            :key="window.id"
            class="bg-white border-4 border-[#800000] rounded-lg p-5 sm:p-8"
          >
            <!-- Window Name -->
            <div class="bg-[#800000] text-white text-center py-3 rounded-lg mb-6">
              <h3 class="text-2xl sm:text-3xl font-bold">{{ window.name }}</h3>
            </div>

            <!-- Current Queue -->
            <div class="text-center mb-4">
              <p class="text-sm text-gray-600 mb-2">NOW SERVING</p>
              <div class="bg-[#FFC107] rounded-lg py-6 sm:py-8">
                <p class="text-5xl sm:text-6xl lg:text-7xl font-bold text-[#800000] leading-none">
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
      <div class="bg-gray-50 border-2 border-gray-200 rounded-lg p-5 sm:p-8">
        <h3 class="text-xl sm:text-2xl font-bold text-[#800000] mb-6 text-center">Next in Queue</h3>
        
        <div v-if="data.next_queues.length > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
          <div 
            v-for="(queue, idx) in data.next_queues" 
            :key="queue.queue_number"
            class="bg-white border-2 border-[#800000] rounded-lg p-4 sm:p-6 text-center"
          >
            <p class="text-xs text-gray-500 mb-2">Position {{ idx + 1 }}</p>
            <p class="text-3xl sm:text-4xl font-bold text-[#800000] leading-tight">{{ queue.queue_number }}</p>
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
      <div class="container mx-auto px-4 sm:px-8 text-center">
        <p class="text-sm text-gray-600">Auto-refreshing every 2 seconds</p>
      </div>
    </footer>
  </div>
</template>
