<script setup>
import { computed, inject, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import ChatbotWidget from '@/Components/ChatbotWidget.vue'
import { usePolling } from '@/Composables/usePolling'
import { formatManilaTime } from '@/Utils/dateTime'

const props = defineProps({ queue_number: String, tracking_token: String })

const queueData = ref(null)
const liveData = ref({ windows: [] })
const loading = ref(true)
const error = ref(null)
const cancelling = ref(false)
const swal = inject('$swal')

const nowServingQueues = computed(() => {
  return (liveData.value?.windows || [])
    .filter((window) => window.current)
    .map((window) => ({
      windowName: window.name,
      queueNumber: window.current.queue_number,
      serviceCategory: window.current.service_category,
      clientType: window.current.client_type,
      transactionServiceCategories: window.current.transaction_service_categories,
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
      return `Skipped — you will automatically return to the queue in about 5 minutes (attempt ${queueData.value.skip_count || 1} of ${queueData.value.max_attempts || 2}).`
    case 'expired':
      return 'This queue has expired after reaching the maximum number of automatic reinstatements. Please proceed to the frontdesk for assistance.'
    case 'waiting':
      if (queueData.value.skip_count > 0) {
        return `Automatically reinstated — please stay near the cashier area. #${queueData.value.position || '-'} in line`
      }
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
      return 'bg-amber-50 text-amber-700 border-amber-200'
    case 'expired':
      return 'bg-red-50 text-red-700 border-red-200'
    default:
      return 'bg-[#fdf2f2] text-[#800000] border-[#f7cccc]'
  }
})

const estimatedServedTimeLabel = computed(() => {
  const value = queueData.value?.estimated_served_at
  return formatManilaTime(value)
})

const fetchQueueData = async () => {
  try {
    const response = await fetch(window.route('api.queue.status', { queue_number: props.queue_number, token: props.tracking_token }))

    if (!response.ok) {
      if (response.status === 410) {
        const payload = await response.json().catch(() => ({}))
        error.value = payload?.error || 'This queue link has expired.'
      } else {
        error.value = 'Queue not found'
      }
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

const queueTheme = (clientType) => {
  if (clientType === 'senior_citizen' || clientType === 'high_priority') {
    return {
      numberText: 'text-blue-700',
      calledBg: 'bg-blue-700',
    }
  }

  // student, parent, and visitor are all the same (non-priority) tier.
  return {
    numberText: 'text-[#800000]',
    calledBg: 'bg-[#800000]',
  }
}

const serviceCategoryLabel = (item) => {
  if (Array.isArray(item?.transactionServiceCategories) && item.transactionServiceCategories.length) {
    return item.transactionServiceCategories.join(', ')
  }

  if (Array.isArray(item?.transaction_service_categories) && item.transaction_service_categories.length) {
    return item.transaction_service_categories.join(', ')
  }

  return item?.serviceCategory || item?.service_category || 'General Service'
}

const cancelQueue = async () => {
  if (!queueData.value || queueData.value.status !== 'waiting' || cancelling.value) return

  const firstConfirm = await swal?.fire({
    icon: 'warning',
    title: 'Cancel your queue?',
    text: 'You are about to cancel this queue number.',
    showCancelButton: true,
    confirmButtonText: 'Continue',
    cancelButtonText: 'Keep Queue',
  })
  if (!firstConfirm?.isConfirmed) return

  const secondConfirm = await swal?.fire({
    icon: 'question',
    title: 'Confirm cancellation',
    text: 'This action cannot be undone.',
    showCancelButton: true,
    confirmButtonText: 'Yes, cancel queue',
    cancelButtonText: 'Back',
    confirmButtonColor: '#dc2626',
  })
  if (!secondConfirm?.isConfirmed) return

  cancelling.value = true

  try {
    await window.axios.post(window.route('api.queue.cancel', { queue_number: props.queue_number, token: props.tracking_token }))
    await fetchAll()
    await swal?.fire({
      icon: 'success',
      title: 'Queue cancelled',
      text: 'Your queue has been cancelled successfully.',
      timer: 1800,
      showConfirmButton: false,
    })
  } catch (requestError) {
    const message = requestError?.response?.data?.message || 'Unable to cancel queue right now.'
    await swal?.fire({
      icon: 'error',
      title: 'Cancellation failed',
      text: message,
    })
  } finally {
    cancelling.value = false
  }
}

usePolling(fetchAll, 2000)
</script>

<template>
  <div class="min-h-screen bg-white py-4 px-3 sm:px-4">
    <div class="max-w-md mx-auto space-y-4">
      <div class="bg-[#800000] rounded-xl shadow-sm border border-[#800000] p-4 text-center text-white">
        <p class="text-xs uppercase tracking-wide text-yellow-200">Batangas Eastern Colleges</p>
        <h1 class="text-lg font-semibold">Queue Status Tracker</h1>
        <p class="text-xs text-yellow-100 mt-1">Live updates every 2 seconds</p>
      </div>

      <div v-if="loading && !queueData && !error" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center text-gray-500">
        Loading queue details...
      </div>

      <div v-else-if="error" class="bg-white rounded-xl shadow-sm border border-red-200 p-6 text-center">
        <h2 class="text-lg font-semibold text-red-700">{{ error }}</h2>
        <p class="text-sm text-gray-600 mt-2">Queue Number: <span class="font-semibold text-[#800000]">{{ queue_number }}</span></p>
      </div>

      <template v-else>
        <div class="bg-white rounded-xl shadow-sm border border-[#e8d7d7] p-4">
          <p class="text-xs uppercase tracking-wide text-gray-500 mb-2">Now Serving</p>
          <div v-if="firstNowServing" class="text-white rounded-lg p-4 text-center" :class="queueTheme(firstNowServing.clientType).calledBg">
            <p class="text-xs mb-1">{{ firstNowServing.windowName }}</p>
            <p class="text-4xl font-bold leading-none text-white">{{ firstNowServing.queueNumber }}</p>
            <p class="text-xs mt-2 text-yellow-100">{{ serviceCategoryLabel(firstNowServing) }}</p>
          </div>
          <div v-else class="bg-gray-50 rounded-lg p-4 text-center text-gray-500 text-sm">
            No active queue being served right now.
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-[#e8d7d7] p-4">
          <p class="text-xs uppercase tracking-wide text-gray-500">Your Queue Number</p>
          <div
            class="mt-2 rounded-lg p-4 text-center"
            :class="queueData?.status === 'called'
              ? `${queueTheme(queueData?.client_type).calledBg} border border-transparent`
              : 'bg-[#fff4cc] border border-[#FFC107]'"
          >
            <p
              class="text-5xl font-bold leading-none"
              :class="queueData?.status === 'called' ? 'text-white' : queueTheme(queueData?.client_type).numberText"
            >
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
              <p class="font-semibold text-gray-800">{{ serviceCategoryLabel(queueData) }}</p>
            </div>
            <div v-if="queueData?.skip_count > 0" class="bg-gray-50 rounded-lg px-3 py-2 col-span-2">
              <p class="text-gray-500 text-xs">Attempts Used</p>
              <p class="font-semibold text-[#800000]">
                {{ queueData.skip_count }} / {{ queueData.max_attempts }}
                <span class="text-xs text-gray-500 font-normal">({{ queueData.remaining_attempts }} remaining)</span>
              </p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-[#e8d7d7] p-4">
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
              <p
                class="text-sm font-semibold self-end sm:self-auto px-2 py-1 rounded"
                :class="window.current ? `${queueTheme(window.current?.client_type).calledBg} text-white` : 'text-gray-800'"
              >
                {{ window.current?.queue_number || 'Idle' }}
              </p>
            </div>
          </div>
        </div>

        <div class="pt-1">
          <button
            v-if="queueData?.status === 'waiting'"
            type="button"
            class="w-full rounded-lg border-2 border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 transition hover:bg-red-100 disabled:opacity-50"
            :disabled="cancelling"
            @click="cancelQueue"
          >
            {{ cancelling ? 'Cancelling...' : 'Cancel Queue' }}
          </button>
        </div>
      </template>
    </div>

    <div class="sm:hidden">
      <ChatbotWidget />
    </div>
  </div>
</template>
