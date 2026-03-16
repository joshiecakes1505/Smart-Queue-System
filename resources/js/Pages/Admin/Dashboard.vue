<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { usePolling } from '@/Composables/usePolling';
import { ref } from 'vue';

const isDownloadingBackup = ref(false);

const getFilenameFromDisposition = (contentDisposition) => {
  if (!contentDisposition) {
    return 'latest-backup.zip';
  }

  const utf8Match = contentDisposition.match(/filename\*=UTF-8''([^;]+)/i);
  if (utf8Match?.[1]) {
    return decodeURIComponent(utf8Match[1]);
  }

  const asciiMatch = contentDisposition.match(/filename="?([^";]+)"?/i);
  return asciiMatch?.[1] || 'latest-backup.zip';
};

const downloadLatestBackup = async () => {
  if (isDownloadingBackup.value) {
    return;
  }

  isDownloadingBackup.value = true;

  try {
    const response = await fetch(route('admin.backups.download-latest'), {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/zip',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    const contentType = response.headers.get('content-type') || '';

    if (!response.ok || contentType.includes('text/html')) {
      throw new Error('Unable to download backup right now.');
    }

    const blob = await response.blob();
    const filename = getFilenameFromDisposition(response.headers.get('content-disposition'));
    const downloadUrl = window.URL.createObjectURL(blob);

    const anchor = document.createElement('a');
    anchor.href = downloadUrl;
    anchor.download = filename;
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    window.URL.revokeObjectURL(downloadUrl);
  } catch (error) {
    window.alert('Backup download failed. Please try again.');
  } finally {
    isDownloadingBackup.value = false;
  }
};

defineProps({
  totalQueuestoday: Number,
  totalCompletedToday: Number,
  totalWaitingNow: Number,
  totalUsers: Number,
  avgServiceMinutes: Number,
  busiestHour: Number,
  busiestHourCount: Number,
})

usePolling(() => {
  return router.reload({
    only: [
      'totalQueuestoday',
      'totalCompletedToday',
      'totalWaitingNow',
      'totalUsers',
      'avgServiceMinutes',
      'busiestHour',
      'busiestHourCount',
    ],
    preserveState: true,
    preserveScroll: true,
  });
}, 3000)
</script>

<template>
  <AuthenticatedLayout title="Admin Dashboard">
    <div class="space-y-6">
      <!-- Key Metrics Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Queues Today -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <p class="text-gray-600 text-sm font-medium">Queues Created Today</p>
          <p class="text-4xl font-bold text-[#800000] mt-2">{{ totalQueuestoday }}</p>
        </div>

        <!-- Completed Today -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <p class="text-gray-600 text-sm font-medium">Completed Today</p>
          <p class="text-4xl font-bold text-green-600 mt-2">{{ totalCompletedToday }}</p>
        </div>

        <!-- Currently Waiting -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <p class="text-gray-600 text-sm font-medium">Waiting Now</p>
          <p class="text-4xl font-bold text-[#FFC107] mt-2">{{ totalWaitingNow }}</p>
        </div>

        <!-- Total Users -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <p class="text-gray-600 text-sm font-medium">Total Users</p>
          <p class="text-4xl font-bold text-[#800000] mt-2">{{ totalUsers }}</p>
        </div>
      </div>

      <!-- Performance & Quick Actions -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Performance Metrics -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-xl font-semibold text-[#800000] mb-4">Performance Metrics</h2>
          <div class="space-y-4">
            <div class="border-b border-gray-200 pb-3">
              <p class="text-gray-600 text-sm">Average Service Time</p>
              <p class="text-3xl font-bold text-gray-900 mt-1">{{ avgServiceMinutes }} mins</p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Busiest Hour</p>
              <p class="text-3xl font-bold text-gray-900 mt-1">
                {{ busiestHour !== null ? `${busiestHour}:00 - ${busiestHour + 1}:00` : 'N/A' }}
              </p>
              <p class="text-sm text-gray-500 mt-1">{{ busiestHourCount }} queues served</p>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-xl font-semibold text-[#800000] mb-4">Quick Actions</h2>
          <div class="space-y-3">
            <Link 
              :href="route('admin.users.index')" 
              class="block px-4 py-3 border-2 border-[#800000] text-[#800000] rounded-lg hover:bg-[#800000] hover:text-white transition"
            >
              <span class="font-semibold">Manage Users</span>
              <p class="text-sm opacity-75">Add/edit staff accounts</p>
            </Link>
            <Link 
              :href="route('admin.service-categories.index')" 
              class="block px-4 py-3 border-2 border-[#800000] text-[#800000] rounded-lg hover:bg-[#800000] hover:text-white transition"
            >
              <span class="font-semibold">Service Categories</span>
              <p class="text-sm opacity-75">Configure service types</p>
            </Link>
            <Link 
              :href="route('display.index')" 
              class="block px-4 py-3 bg-[#FFC107] text-[#800000] rounded-lg hover:bg-[#FFB300] transition"
            >
              <span class="font-semibold">View Display Board</span>
              <p class="text-sm opacity-75">Public TV/monitor display</p>
            </Link>
            <button
              type="button"
              @click="downloadLatestBackup"
              :disabled="isDownloadingBackup"
              class="w-full text-left px-4 py-3 border-2 border-green-600 text-green-700 rounded-lg hover:bg-green-600 hover:text-white transition disabled:opacity-60 disabled:cursor-not-allowed"
            >
              <div class="flex items-center gap-3">
                <svg v-if="isDownloadingBackup" width="24" height="24" viewBox="0 0 38 38" aria-hidden="true">
                  <g transform="translate(19 19)">
                    <g transform="rotate(0)"><circle cx="0" cy="12" r="3" fill="#800000" opacity="0.125"><animate attributeName="opacity" from="0.125" to="0.125" dur="1.2s" begin="0s" repeatCount="indefinite" keyTimes="0;1" values="1;0.125" /></circle></g>
                    <g transform="rotate(45)"><circle cx="0" cy="12" r="3" fill="#800000" opacity="0.25"><animate attributeName="opacity" from="0.25" to="0.25" dur="1.2s" begin="0.15s" repeatCount="indefinite" keyTimes="0;1" values="1;0.25" /></circle></g>
                    <g transform="rotate(90)"><circle cx="0" cy="12" r="3" fill="#800000" opacity="0.375"><animate attributeName="opacity" from="0.375" to="0.375" dur="1.2s" begin="0.3s" repeatCount="indefinite" keyTimes="0;1" values="1;0.375" /></circle></g>
                    <g transform="rotate(135)"><circle cx="0" cy="12" r="3" fill="#800000" opacity="0.5"><animate attributeName="opacity" from="0.5" to="0.5" dur="1.2s" begin="0.45s" repeatCount="indefinite" keyTimes="0;1" values="1;0.5" /></circle></g>
                    <g transform="rotate(180)"><circle cx="0" cy="12" r="3" fill="#800000" opacity="0.625"><animate attributeName="opacity" from="0.625" to="0.625" dur="1.2s" begin="0.6s" repeatCount="indefinite" keyTimes="0;1" values="1;0.625" /></circle></g>
                    <g transform="rotate(225)"><circle cx="0" cy="12" r="3" fill="#800000" opacity="0.75"><animate attributeName="opacity" from="0.75" to="0.75" dur="1.2s" begin="0.75s" repeatCount="indefinite" keyTimes="0;1" values="1;0.75" /></circle></g>
                    <g transform="rotate(270)"><circle cx="0" cy="12" r="3" fill="#800000" opacity="0.875"><animate attributeName="opacity" from="0.875" to="0.875" dur="1.2s" begin="0.9s" repeatCount="indefinite" keyTimes="0;1" values="1;0.875" /></circle></g>
                    <g transform="rotate(315)"><circle cx="0" cy="12" r="3" fill="#800000" opacity="1"><animate attributeName="opacity" from="1" to="1" dur="1.2s" begin="1.05s" repeatCount="indefinite" keyTimes="0;1" values="1;1" /></circle></g>
                  </g>
                </svg>
                <div>
                  <span class="font-semibold">{{ isDownloadingBackup ? 'Downloading Backup...' : 'Download Latest Backup' }}</span>
                  <p class="text-sm opacity-75">Admin-only backup archive download</p>
                </div>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
