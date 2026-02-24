<script setup>
import { computed, ref } from 'vue'
import { usePolling } from '@/Composables/usePolling'

const liveData = ref({ windows: [], next: [] })

const nowServing = computed(() => liveData.value.windows || [])
const nextQueues = computed(() => liveData.value.next || [])

const fetchLiveData = async () => {
  try {
    const response = await fetch('/public/live')
    if (!response.ok) return
    liveData.value = await response.json()
  } catch (error) {
    console.error('Failed to fetch public live data:', error)
  }
}

usePolling(fetchLiveData, 3000)
</script>

<template>
  <div class="min-h-screen bg-[#f7f5f3] py-4 px-3 sm:px-4">
    <div class="max-w-5xl mx-auto space-y-4">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-xs uppercase tracking-wide text-gray-500">Batangas Eastern Colleges</p>
        <h1 class="text-xl font-semibold text-[#800000]">Now Serving</h1>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div
          v-for="window in nowServing"
          :key="window.id"
          class="bg-white rounded-xl shadow-sm border border-gray-100 p-4"
        >
          <p class="text-sm font-semibold text-[#800000]">{{ window.name }}</p>
          <div class="mt-3 bg-[#800000] text-white rounded-lg p-4 text-center">
            <p class="text-4xl font-bold leading-none">{{ window.current?.queue_number || '—' }}</p>
          </div>
          <p class="text-xs text-gray-500 mt-2">{{ window.assigned_user || 'Unassigned' }}</p>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-sm font-semibold text-[#800000] mb-3">Next in Queue</p>
        <div v-if="nextQueues.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2">
          <div v-for="queue in nextQueues" :key="queue.queue_number" class="bg-gray-50 rounded-lg px-3 py-2 text-center">
            <p class="text-lg font-bold text-[#800000]">{{ queue.queue_number }}</p>
            <p class="text-xs text-gray-500">{{ queue.service_category || 'General' }}</p>
          </div>
        </div>
        <p v-else class="text-sm text-gray-500">No waiting queues</p>
      </div>
    </div>
  </div>
</template>
