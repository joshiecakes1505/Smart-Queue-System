<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { usePolling } from '@/Composables/usePolling'
import { Head } from '@inertiajs/vue3'
import {
  announceQueue,
  getLastAnnouncedQueues,
  setLastAnnouncedQueues,
} from '@/Services/QueueAnnouncement'

const data = ref({ windows: [], next_queues: [], timestamp: null })
const refreshIntervalMs = 1000
const isFullscreen = ref(false)
const hasAnnouncementBaseline = ref(false)
const lastAnnouncedByWindow = ref({})
const ANNOUNCEMENT_STORAGE_KEY = 'display-board:last-announced'
const schoolLogoUrl = document.querySelector('meta[name="app-logo-url"]')?.getAttribute('content')
  || `${window.location.origin}/images/school-logo.png`

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

const fetchData = async () => {
  try {
    const res = await fetch(window.route('display.data'))
    if (!res.ok) return

    const latestData = await res.json()
    data.value = latestData
    announceQueueUpdates(latestData?.windows || [])
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

const queueTheme = (clientType) => {
  if (clientType === 'senior_citizen' || clientType === 'high_priority') {
    return {
      numberText: 'text-blue-700',
      calledBg: 'bg-blue-700',
    }
  }

  if (clientType === 'visitor' || clientType === 'parent') {
    return {
      numberText: 'text-orange-600',
      calledBg: 'bg-orange-500',
    }
  }

  return {
    numberText: 'text-[#800000]',
    calledBg: 'bg-[#800000]',
  }
}

const serviceCategoryLabel = (queue) => {
  if (Array.isArray(queue?.transaction_service_categories) && queue.transaction_service_categories.length) {
    return queue.transaction_service_categories.join(', ')
  }

  return queue?.service_category || 'N/A'
}

const clientTypeLabel = (clientType) => {
  if (!clientType) return 'General'

  return `${clientType}`
    .split('_')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ')
}

const isWindowClosed = (windowData) => {
  return !windowData?.assigned_user
}

const windowPrimaryStatus = (windowData) => {
  if (isWindowClosed(windowData)) {
    return 'Closed'
  }

  if (windowData?.current) {
    return clientTypeLabel(windowData.current.client_type)
  }

  return 'Waiting for next'
}

const windowSecondaryStatus = (windowData) => {
  if (isWindowClosed(windowData)) {
    return '—'
  }

  if (windowData?.current) {
    return serviceCategoryLabel(windowData.current)
  }

  return '—'
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
    <header class="relative bg-[#800000] text-white py-3 sm:py-4">
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
          <div class="text-left lg:text-right lg:mr-10">
            <p class="text-lg sm:text-xl text-yellow-200">Current Time</p>
            <p class="mt-1 text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight">{{ formatTime(data.timestamp) }}</p>
          </div>
        </div>
      </div>
      <button
        type="button"
        class="absolute right-4 top-3 sm:right-8 sm:top-4 inline-flex items-center rounded-md border border-white px-2.5 py-1.5 text-sm font-semibold text-white hover:bg-white hover:text-[#800000]"
        @click="toggleFullscreen"
        :title="isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'"
        :aria-label="isFullscreen ? 'Exit fullscreen' : 'Enter fullscreen'"
      >
        <span class="text-lg leading-none" aria-hidden="true">{{ isFullscreen ? '🗗' : '⛶' }}</span>
      </button>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 sm:px-8 py-6 sm:py-8">
      <!-- Windows Grid -->
      <div class="mb-12">
        <h2 class="text-2xl sm:text-3xl font-bold text-[#800000] mb-5">Now Serving</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-5">
          <div 
            v-for="window in data.windows" 
            :key="window.id"
            class="bg-white border-4 border-[#800000] rounded-lg p-4 sm:p-6"
          >
            <!-- Window Name -->
            <div class="bg-[#800000] text-white text-center py-2.5 rounded-lg mb-4">
              <h3 class="text-2xl sm:text-3xl lg:text-4xl font-bold">{{ window.name }}</h3>
            </div>

            <!-- Current Queue -->
            <div class="text-center mb-3">
              <p class="text-sm text-gray-600 mb-2">NOW SERVING</p>
              <div
                class="rounded-lg py-6 sm:py-7 lg:py-8"
                :class="window.current ? queueTheme(window.current?.client_type).calledBg : 'bg-[#FFC107]'"
              >
                <p
                  class="text-6xl sm:text-7xl lg:text-8xl font-bold leading-none"
                  :class="window.current ? 'text-white' : 'text-[#800000]'"
                >
                  {{ window.current?.queue_number ?? '—' }}
                </p>
              </div>
            </div>

            <!-- Queue Details -->
            <div class="text-center text-gray-700 space-y-1">
              <p class="text-lg font-semibold" :class="isWindowClosed(window) ? 'text-gray-500' : 'text-gray-700'">{{ windowPrimaryStatus(window) }}</p>
              <p class="text-sm text-gray-500">{{ windowSecondaryStatus(window) }}</p>
            </div>
          </div>
        </div>

        <!-- No Windows Message -->
        <div v-if="data.windows.length === 0" class="text-center py-12">
          <p class="text-gray-500 text-xl">No active windows</p>
        </div>
      </div>

      <!-- Next in Queue -->
      <div class="bg-gray-50 border-2 border-gray-200 rounded-lg p-5 sm:p-6">
        <h3 class="text-xl sm:text-2xl font-bold text-[#800000] mb-5 text-center">Next in Queue</h3>

        <div v-if="data.next_queues.length > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4">
          <div
            v-for="(queue, idx) in data.next_queues.slice(0, 5)"
            :key="queue.queue_number"
            class="bg-white border-2 border-[#800000] rounded-lg p-4 sm:p-6 text-center"
          >
            <p class="text-xs text-gray-500 mb-2">Position {{ idx + 1 }}</p>
            <p class="text-3xl sm:text-4xl font-bold leading-tight" :class="queueTheme(queue.client_type).numberText">{{ queue.queue_number }}</p>
            <p class="text-sm text-gray-600 mt-2">{{ serviceCategoryLabel(queue) }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ clientTypeLabel(queue.client_type) }}</p>
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
