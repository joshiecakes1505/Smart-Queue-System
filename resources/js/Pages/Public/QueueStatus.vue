<script setup>
import { computed, ref } from 'vue'
import { usePolling } from '@/Composables/usePolling'

const props = defineProps({ queue_number: String })

const queueData = ref(null)
const liveData = ref({ windows: [] })
const loading = ref(true)
const error = ref(null)

const nowServingQueues = computed(() => {
  return (liveData.value?.windows || [])
    .filter((window) => window.current)
    .map((window) => ({
      windowName: window.name,
      queueNumber: window.current.queue_number,
      serviceCategory: window.current.service_category,
    }))
})

const firstNowServing = computed(() => nowServingQueues.value[0] || null)

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

const statusText = computed(() => {
  if (!queueData.value) return 'Loading...'

  switch (queueData.value.status) {
    case 'called':
      return `Now called at ${queueData.value.cashier_window || 'Cashier Window'}`
    case 'completed':
      return 'Service completed'
    case 'skipped':
      return 'Skipped (please see frontdesk)'
    case 'waiting':
      return `Waiting • #${queueData.value.position || '-'} in line`
    default:
      return `Status: ${queueData.value.status}`
  }
})

const statusClass = computed(() => {
  if (!queueData.value) return 'bg-gray-100 text-gray-700 border-gray-200'

  switch (queueData.value.status) {
    case 'called':
      return 'bg-[#fff4cc] text-[#800000] border-[#FFC107]'
    case 'completed':
      return 'bg-green-50 text-green-700 border-green-200'
    case 'skipped':
      return 'bg-gray-100 text-gray-700 border-gray-200'
    default:
      return 'bg-[#fdf2f2] text-[#800000] border-[#f7cccc]'
  }
})

const estimatedServedTimeLabel = computed(() => {
  const value = queueData.value?.estimated_served_at
  if (!value) return '—'

  return new Date(value).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
  })
})

const fetchQueueData = async () => {
  try {
    const response = await fetch(window.route('api.queue.status', { queue_number: props.queue_number }))

    if (!response.ok) {
      error.value = 'Queue not found'
      queueData.value = null
      return
    }

    queueData.value = await response.json()
    error.value = null
  } catch (fetchError) {
    console.error('Failed to fetch queue data:', fetchError)
    error.value = 'Unable to load queue status'
  }
}

const fetchLiveData = async () => {
  try {
    const response = await fetch(window.route('public.live'))
    if (!response.ok) return
    liveData.value = await response.json()
  } catch (fetchError) {
    console.error('Failed to fetch live data:', fetchError)
  }
}

const fetchAll = async () => {
  loading.value = true
  await Promise.all([fetchQueueData(), fetchLiveData()])
  loading.value = false
}

usePolling(fetchAll, 5000)
</script>

<template>
  <div class="min-h-screen bg-[#f7f5f3] py-4 px-3 sm:px-4">
    <div class="max-w-md mx-auto space-y-4">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center">
        <p class="text-xs uppercase tracking-wide text-gray-500">Batangas Eastern Colleges</p>
        <h1 class="text-lg font-semibold text-[#800000]">Queue Status Tracker</h1>
        <p class="text-xs text-gray-500 mt-1">Live updates every 5 seconds</p>
      </div>

      <div v-if="loading && !queueData && !error" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center text-gray-500">
        Loading queue details...
      </div>

      <div v-else-if="error" class="bg-white rounded-xl shadow-sm border border-red-200 p-6 text-center">
        <h2 class="text-lg font-semibold text-red-700">{{ error }}</h2>
        <p class="text-sm text-gray-600 mt-2">Queue Number: <span class="font-semibold text-[#800000]">{{ queue_number }}</span></p>
      </div>

      <template v-else>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
          <p class="text-xs uppercase tracking-wide text-gray-500 mb-2">Now Serving</p>
          <div v-if="firstNowServing" class="bg-[#800000] text-white rounded-lg p-4 text-center">
            <p class="text-xs mb-1">{{ firstNowServing.windowName }}</p>
            <p class="text-4xl font-bold leading-none">{{ firstNowServing.queueNumber }}</p>
            <p class="text-xs mt-2 text-[#ffe8a3]">{{ firstNowServing.serviceCategory || 'General Service' }}</p>
          </div>
          <div v-else class="bg-gray-50 rounded-lg p-4 text-center text-gray-500 text-sm">
            No active queue being served right now.
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
          <p class="text-xs uppercase tracking-wide text-gray-500">Your Queue Number</p>
          <div class="mt-2 bg-[#fff4cc] border border-[#FFC107] rounded-lg p-4 text-center">
            <p class="text-5xl font-bold text-[#800000] leading-none">
              {{ queueData?.queue_number || queue_number }}
            </p>
          </div>

          <div class="mt-3 border rounded-lg px-3 py-2 text-sm" :class="statusClass">
            {{ statusText }}
          </div>

          <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
            <div class="bg-gray-50 rounded-lg px-3 py-2">
              <p class="text-gray-500 text-xs">Position</p>
              <p class="font-semibold text-[#800000]">
                <template v-if="queueData?.position">
                  {{ queueData.position }}<span class="text-xs">{{ positionSuffix }}</span>
                </template>
                <template v-else>
                  —
                </template>
              </p>
            </div>
            <div class="bg-gray-50 rounded-lg px-3 py-2">
              <p class="text-gray-500 text-xs">Estimated Wait</p>
              <p class="font-semibold text-[#800000]">
                {{ queueData?.eta_minutes === 0 ? 'Next' : (queueData?.eta_minutes !== null ? `~${queueData?.eta_minutes} min` : '—') }}
              </p>
            </div>
            <div class="bg-gray-50 rounded-lg px-3 py-2 col-span-2">
              <p class="text-gray-500 text-xs">Estimated Serving Time</p>
              <p class="font-semibold text-[#800000]">{{ estimatedServedTimeLabel }}</p>
            </div>
            <div class="bg-gray-50 rounded-lg px-3 py-2 col-span-2">
              <p class="text-gray-500 text-xs">Queues Ahead</p>
              <p class="font-semibold text-[#800000]">
                {{ queueData?.queues_ahead ?? '—' }}
              </p>
              <p class="text-xs text-gray-500 mt-1" v-if="queueData?.queues_ahead !== null">
                {{ queueData?.waiting_ahead ?? 0 }} waiting + {{ queueData?.active_called_ahead ?? 0 }} currently serving
              </p>
            </div>
            <div class="bg-gray-50 rounded-lg px-3 py-2 col-span-2">
              <p class="text-gray-500 text-xs">Service Category</p>
              <p class="font-semibold text-gray-800">{{ queueData?.service_category || 'General Service' }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
          <p class="text-xs uppercase tracking-wide text-gray-500 mb-3">All Windows</p>
          <div class="space-y-2">
            <div
              v-for="window in liveData.windows"
              :key="window.id"
              class="rounded-lg border border-gray-100 bg-gray-50 px-3 py-2 flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between"
            >
              <div class="min-w-0 w-full sm:w-auto">
                <p class="text-sm font-semibold text-[#800000] break-words">{{ window.name }}</p>
                <p class="text-xs text-gray-500 break-words">{{ window.assigned_user || 'Unassigned' }}</p>
              </div>
              <p class="text-sm font-semibold text-gray-800 self-end sm:self-auto">{{ window.current?.queue_number || 'Idle' }}</p>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
