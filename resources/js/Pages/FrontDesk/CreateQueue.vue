<script setup>
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  serviceCategories: Array,
  queueNumber: {
    type: String,
    default: null,
  },
  todayTotal: Number,
  waitingCount: Number,
})

const form = useForm({
  client_name: '',
  service_category_id: '',
  phone: '',
  note: '',
})

// Show QR if queueNumber exists in props
const showQR = computed(() => !!props.queueNumber)

function submit() {
  form.post(route('frontdesk.queues.store'))
}

function reset() {
  form.reset()
  window.location.href = route('frontdesk.queues.index')
}
</script>

<template>
   <div class="p-4">
    <h1 class="text-2xl font-bold">Front Desk — Dashboard</h1>
    <div class="mt-4">
      <p>Today total: {{ todayTotal ?? 0 }}</p>
      <p>Waiting: {{ waitingCount ?? 0 }}</p>
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

  <div class="min-h-screen bg-gradient-to-br from-green-50 to-blue-50">
    <!-- Form Section -->
    <div v-if="!showQR" class="container mx-auto p-8">
      <div class="max-w-lg mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-2xl font-bold mb-6 text-gray-800">Create New Queue</h1>
        
        <form @submit.prevent="submit" class="space-y-4">
          <!-- Service Category -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Service Category *</label>
            <select 
              v-model="form.service_category_id" 
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Select a service</option>
              <option v-for="c in serviceCategories" :key="c.id" :value="c.id">
                {{ c.name }}
              </option>
            </select>
            <p v-if="form.errors.service_category_id" class="text-red-600 text-sm mt-1">
              {{ form.errors.service_category_id }}
            </p>
          </div>

          <!-- Client Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Client Name *</label>
            <input 
              v-model="form.client_name" 
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Enter client name"
            />
            <p v-if="form.errors.client_name" class="text-red-600 text-sm mt-1">
              {{ form.errors.client_name }}
            </p>
          </div>

          <!-- Phone -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
            <input 
              v-model="form.phone" 
              type="tel"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="(Optional)"
            />
          </div>

          <!-- Note -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Additional Notes</label>
            <textarea 
              v-model="form.note" 
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              rows="3"
              placeholder="(Optional)"
            ></textarea>
          </div>

          <!-- Submit Button -->
          <button 
            type="submit" 
            :disabled="form.processing"
            class="w-full px-4 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
          >
            {{ form.processing ? 'Creating...' : 'Create Queue' }}
          </button>
        </form>
      </div>
    </div>

    <!-- QR Code Display Section -->
    <div v-else class="container mx-auto p-8 flex items-center justify-center min-h-screen">
      <div class="max-w-md mx-auto text-center">
        <!-- Success Message -->
        <div class="bg-white rounded-lg shadow-2xl p-8">
          <div class="mb-6">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
            </div>
            <h1 class="text-3xl font-bold text-green-600 mb-2">Queue Created!</h1>
            <p class="text-gray-600">Your queue number has been assigned</p>
          </div>

          <!-- Queue Number -->
          <div class="bg-blue-50 rounded-lg p-6 mb-6">
            <p class="text-gray-600 text-sm mb-2">Your Queue Number</p>
            <p class="text-5xl font-bold text-blue-600">{{ queueNumber }}</p>
          </div>

          <!-- QR Code -->
          <div class="bg-gray-50 p-6 rounded-lg mb-6">
            <p class="text-gray-600 text-sm mb-3">Scan for status updates</p>
            <img 
              :src="`/qr/${queueNumber}`"
              alt="Queue QR Code"
              class="w-full max-w-xs mx-auto border-2 border-gray-200 rounded"
            />
          </div>

          <!-- Instructions -->
          <div class="bg-blue-100 border border-blue-300 rounded-lg p-4 mb-6 text-left">
            <h3 class="font-semibold text-blue-900 mb-2">Next Steps:</h3>
            <ul class="text-sm text-blue-800 space-y-1">
              <li>✓ Remember your queue number: <strong>{{ queueNumber }}</strong></li>
              <li>✓ Watch the display boards for your turn</li>
              <li>✓ Scan the QR code for real-time updates</li>
            </ul>
          </div>

          <!-- Button to Create Another -->
          <button 
            @click="reset"
            class="w-full px-4 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition"
          >
            Create Another Queue
          </button>
        </div>

      </div>
    </div>
  </div>
</template>
