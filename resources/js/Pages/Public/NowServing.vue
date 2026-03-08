<script setup>
import { computed, ref } from 'vue'
import { usePolling } from '@/Composables/usePolling'
import {
  announceQueue,
  getLastAnnouncedQueues,
  setLastAnnouncedQueues,
} from '@/Services/QueueAnnouncement'

const liveData = ref({ windows: [], next: [] })
const hasAnnouncementBaseline = ref(false)
const lastAnnouncedByWindow = ref({})

const ANNOUNCEMENT_STORAGE_KEY = 'public-now-serving:last-announced'

const nowServing = computed(() => liveData.value.windows || [])
const nextQueues = computed(() => liveData.value.next || [])

const getWindowAnnouncementKey = (windowData) => {
  if (windowData?.id) return `${windowData.id}`
  if (windowData?.name) return windowData.name
  return ''
}

const getWindowAnnouncementLabel = (windowData) => {
  const rawValue = windowData?.window_number || windowData?.name || (windowData?.id ? `${windowData.id}` : '')
  if (!rawValue) return ''

  return `${rawValue}`.replace(/^(window|counter)\s*/i, '').trim()
    || `${rawValue}`.trim()
}

const buildCurrentQueueMap = (windows = []) => {
  return windows.reduce((queueMap, windowData) => {
    const key = getWindowAnnouncementKey(windowData)
    const queueNumber = windowData?.current?.queue_number
    const updatedAt = windowData?.current?.updated_at
    const announcementToken = updatedAt ? `${queueNumber}|${updatedAt}` : queueNumber

    if (key && queueNumber && announcementToken) {
      queueMap[key] = announcementToken
    }

    return queueMap
  }, {})
}

const initializeAnnouncementBaseline = (windows = []) => {
  if (hasAnnouncementBaseline.value) return

  const persistedMap = getLastAnnouncedQueues(ANNOUNCEMENT_STORAGE_KEY)

  if (Object.keys(persistedMap).length) {
    lastAnnouncedByWindow.value = persistedMap
  } else {
    const currentMap = buildCurrentQueueMap(windows)
    lastAnnouncedByWindow.value = currentMap
    setLastAnnouncedQueues(currentMap, ANNOUNCEMENT_STORAGE_KEY)
  }

  hasAnnouncementBaseline.value = true
}

const announceQueueUpdates = (windows = []) => {
  initializeAnnouncementBaseline(windows)

  let hasChanges = false
  const nextAnnouncedMap = { ...lastAnnouncedByWindow.value }

  windows.forEach((windowData) => {
    const key = getWindowAnnouncementKey(windowData)
    const windowLabel = getWindowAnnouncementLabel(windowData)
    const queueNumber = windowData?.current?.queue_number
    const updatedAt = windowData?.current?.updated_at
    const announcementToken = updatedAt ? `${queueNumber}|${updatedAt}` : queueNumber

    if (!key || !windowLabel || !queueNumber || !announcementToken) return

    if (nextAnnouncedMap[key] !== announcementToken) {
      announceQueue(queueNumber, windowLabel)
      nextAnnouncedMap[key] = announcementToken
      hasChanges = true
    }
  })

  if (hasChanges) {
    lastAnnouncedByWindow.value = nextAnnouncedMap
    setLastAnnouncedQueues(nextAnnouncedMap, ANNOUNCEMENT_STORAGE_KEY)
  }
}

const fetchLiveData = async () => {
  try {
    const response = await fetch('/public/live')
    if (!response.ok) return

    const latestData = await response.json()
    liveData.value = latestData
    announceQueueUpdates(latestData?.windows || [])
  } catch (error) {
    console.error('Failed to fetch public live data:', error)
  }
}

usePolling(fetchLiveData, 1000)
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
