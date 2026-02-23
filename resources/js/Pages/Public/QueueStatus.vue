<script setup>
import { ref, computed } from 'vue'
import { usePolling } from '@/Composables/usePolling'

const props = defineProps({ queue_number: String })

const queueData = ref(null)
const liveData = ref({ windows: [] })
const loading = ref(true)
const error = ref(null)

// Calculate position ordinal suffix
const positionSuffix = computed(() => {
  if (!queueData.value?.position) return ''
  const pos = queueData.value.position
  if (pos % 100 >= 11 && pos % 100 <= 13) return 'th'
  switch (pos % 10) {
    case 1: return 'st'
    case 2: return 'nd'
    case 3: return 'rd'
    default: return 'th'
  }
})

// Status-based messaging
const statusMessage = computed(() => {
  if (!queueData.value) return 'Loading...'
  const status = queueData.value.status
  
  switch (status) {
    case 'called':
      return `Being served at ${queueData.value.cashier_window || 'Cashier'}`
    case 'completed':
      return 'Service completed'
    case 'skipped':
      return 'Skipped'
    case 'waiting':
      return `Position ${queueData.value.position} in queue`
    default:
      return `Status: ${status}`
  }
})

// Fetch queue specific data
const fetchQueueData = async () => {
  try {
    const res = await fetch(`/api/queue/${props.queue_number}/status`)
    if (!res.ok) {
      error.value = 'Queue not found'
      return
    }
    queueData.value = await res.json()
    error.value = null
  } catch (err) {
    console.error('Failed to fetch queue data:', err)
    error.value = 'Connection error'
  }
}

// Fetch live windows data
const fetchLiveData = async () => {
  try {
    const res = await fetch('/public/live')
    liveData.value = await res.json()
  } catch (err) {
    console.error('Failed to fetch live data:', err)
  }
}

// Start polling both endpoints
usePolling(async () => {
  await fetchQueueData()
  await fetchLiveData()
}, 5000)
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Error State -->
      <div v-if="error" class="bg-white rounded-2xl shadow-2xl p-8">
        <div class="text-center">
          <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <h2 class="text-xl font-bold text-gray-800 mb-2">{{ error }}</h2>
          <p class="text-gray-600">Queue number: <strong>{{ queue_number }}</strong></p>
        </div>
      </div>

      <!-- Main Queue Card -->
      <div v-else class="bg-white rounded-2xl shadow-2xl p-8">
        <div class="text-center mb-6">
          <p class="text-gray-600 text-sm uppercase tracking-wide">Your Queue Number</p>
          <h1 class="text-6xl font-bold text-blue-600 mt-2">{{ queue_number }}</h1>
        </div>

        <!-- QR Code -->
        <div class="flex justify-center mb-8 p-4 bg-gray-50 rounded-lg">
          <img 
            :src="`/qr/${queue_number}`"
            alt="QR Code"
            class="w-48 h-48 border border-gray-200 rounded"
          />
        </div>

        <!-- Status Section -->
        <div v-if="queueData" class="space-y-4 mb-8 border-t border-gray-200 pt-6">
          <!-- Status Badge -->
          <div class="text-center">
            <p 
              class="inline-block px-4 py-2 rounded-full font-semibold text-sm"
              :class="{
                'bg-green-100 text-green-800': queueData.status === 'waiting',
                'bg-blue-100 text-blue-800': queueData.status === 'called',
                'bg-purple-100 text-purple-800': queueData.status === 'completed',
                'bg-gray-100 text-gray-800': queueData.status === 'skipped',
              }"
            >
              {{ statusMessage }}
            </p>
          </div>

          <!-- Position -->
          <div v-if="queueData.position" class="flex justify-between items-center">
            <span class="text-gray-600">Position in Queue:</span>
            <span class="text-3xl font-bold text-green-600">
              {{ queueData.position }}<span class="text-lg">{{ positionSuffix }}</span>
            </span>
          </div>

          <!-- Wait Time -->
          <div v-if="queueData.eta_minutes !== null" class="flex justify-between items-center">
            <span class="text-gray-600">Estimated Wait:</span>
            <span class="text-2xl font-semibold text-orange-600">
              {{ queueData.eta_minutes === 0 ? 'Next' : `~${queueData.eta_minutes} min` }}
            </span>
          </div>

          <!-- Windows Status -->
          <div v-if="liveData.windows?.length" class="mt-6 border-t border-gray-200 pt-4">
            <p class="text-gray-600 font-semibold mb-3">Windows Status</p>
            <div class="space-y-2">
              <div 
                v-for="window in liveData.windows" 
                :key="window.id"
                class="flex justify-between items-center p-3 bg-gray-50 rounded"
              >
                <div>
                  <p class="font-medium text-gray-800">{{ window.name }}</p>
                  <p class="text-xs text-gray-500">{{ window.assigned_user || '—' }}</p>
                </div>
                <span class="text-sm">
                  <span v-if="window.current" class="text-green-600 font-bold text-lg">
                    {{ window.current.queue_number }}
                  </span>
                  <span v-else class="text-gray-400">Idle</span>
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Info Message -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center text-sm text-blue-800">
          <p class="font-semibold">Live Updates Every 5 Seconds</p>
          <p class="text-xs text-blue-600 mt-1">Keep this page open for real-time status updates</p>
        </div>
      </div>
    </div>
  </div>
</template>
