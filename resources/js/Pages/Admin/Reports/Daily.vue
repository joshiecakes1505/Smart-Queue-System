<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { usePolling } from '@/Composables/usePolling';

const props = defineProps({
  metrics: {
    type: Object,
    required: true,
  },
});

const selectedDate = ref(props.metrics.selected_date);
const selectedPeriod = ref(props.metrics.selected_period || 'daily');

const maxWeekdayTrendCount = computed(() => {
  const counts = props.metrics.weekday_trend?.map((row) => row.count) || [];
  return Math.max(...counts, 1);
});

const applyDateFilter = () => {
  router.get(route('admin.reports.daily'), {
    date: selectedDate.value,
    period: selectedPeriod.value,
  }, {
    preserveState: true,
    replace: true,
  });
};

const printReport = () => {
  const printUrl = route('admin.reports.daily.print', {
    date: selectedDate.value,
    period: selectedPeriod.value,
  });
  window.open(printUrl, '_blank');
};

const statusLabel = (status) => {
  switch (status) {
    case 'waiting': return 'Waiting';
    case 'called': return 'Called';
    case 'completed': return 'Completed';
    case 'skipped': return 'Skipped';
    default: return status;
  }
};

const clientTypeLabel = (type) => {
  const map = {
    student: 'Student',
    parent: 'Parent',
    visitor: 'Visitor',
    senior_citizen: 'Senior Citizen (Priority)',
    high_priority: 'High Priority',
  };

  return map[type] || type;
};

const weekdayTrendWidth = (count) => `${(count / maxWeekdayTrendCount.value) * 100}%`;

usePolling(() => {
  return router.reload({
    only: ['metrics'],
    preserveState: true,
    preserveScroll: true,
  });
}, 5000);
</script>

<template>
  <AuthenticatedLayout title="Queue Reports">
    <div class="space-y-6">
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
          <div>
            <h1 class="text-2xl font-semibold text-[#800000]">Queue Reports</h1>
            <p class="text-sm text-gray-600 mt-1">{{ metrics.period_label }}</p>
          </div>

          <div class="flex items-end gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Period</label>
              <select
                v-model="selectedPeriod"
                class="border border-gray-300 rounded-lg pl-3 pr-10 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000]"
              >
                <option value="daily">Daily</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Anchor Date</label>
              <input
                v-model="selectedDate"
                type="date"
                class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000]"
              />
            </div>
            <button
              @click="applyDateFilter"
              class="bg-[#FFC107] hover:bg-[#FFB300] text-[#800000] px-4 py-2 rounded-lg font-semibold transition"
            >
              Apply
            </button>
            <button
              @click="printReport"
              class="bg-[#800000] hover:bg-[#6a0000] text-white px-4 py-2 rounded-lg font-semibold transition"
            >
              Print Report
            </button>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow-sm p-6">
          <p class="text-gray-600 text-sm">Total Queues</p>
          <p class="text-3xl font-bold text-[#800000]">{{ metrics.total_queues }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
          <p class="text-gray-600 text-sm">Completed</p>
          <p class="text-3xl font-bold text-green-600">{{ metrics.completed }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
          <p class="text-gray-600 text-sm">Completion Rate</p>
          <p class="text-3xl font-bold text-blue-600">{{ metrics.completion_rate }}%</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
          <p class="text-gray-600 text-sm">Avg Service Time</p>
          <p class="text-3xl font-bold text-purple-600">{{ metrics.average_service_minutes }}m</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-xl font-semibold text-[#800000] mb-4">Status Breakdown</h2>
          <div class="space-y-2">
            <div
              v-for="row in metrics.status_breakdown"
              :key="row.status"
              class="flex items-center justify-between border border-gray-100 rounded-lg px-4 py-3"
            >
              <span class="text-gray-700">{{ statusLabel(row.status) }}</span>
              <span class="font-semibold text-[#800000]">{{ row.count }}</span>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-xl font-semibold text-[#800000] mb-4">Client Type Breakdown</h2>
          <div class="space-y-2">
            <div
              v-for="row in metrics.client_breakdown"
              :key="row.client_type"
              class="flex items-center justify-between border border-gray-100 rounded-lg px-4 py-3"
            >
              <span class="text-gray-700">{{ clientTypeLabel(row.client_type) }}</span>
              <span class="font-semibold text-[#800000]">{{ row.count }}</span>
            </div>
            <div v-if="metrics.client_breakdown.length === 0" class="text-gray-500">No data for this date.</div>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-xl font-semibold text-[#800000] mb-4">Service Category Breakdown</h2>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Service Category</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Total</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Completed</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Waiting</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="metrics.service_category_breakdown.length === 0">
                <td colspan="4" class="text-center py-8 text-gray-500">No category activity for this date.</td>
              </tr>
              <tr
                v-for="row in metrics.service_category_breakdown"
                :key="row.service_category"
                class="border-b border-gray-100"
              >
                <td class="py-3 px-4">{{ row.service_category }}</td>
                <td class="py-3 px-4">{{ row.total }}</td>
                <td class="py-3 px-4">{{ row.completed }}</td>
                <td class="py-3 px-4">{{ row.waiting }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
          <h2 class="text-xl font-semibold text-[#800000] mb-4">Hourly Queue Volume</h2>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b border-gray-200">
                  <th class="text-left py-3 px-4 font-semibold text-gray-700">Hour</th>
                  <th class="text-left py-3 px-4 font-semibold text-gray-700">Queues Created</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in metrics.hourly_data" :key="row.hour" class="border-b border-gray-100">
                  <td class="py-2 px-4">{{ row.hour }}</td>
                  <td class="py-2 px-4">{{ row.count }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6">
          <div class="flex items-start justify-between gap-4 mb-4">
            <div>
              <h2 class="text-xl font-semibold text-[#800000]">Weekday Queue Trend</h2>
              <p class="text-sm text-gray-600 mt-1">Queue volume grouped from Monday to Friday for the selected period.</p>
            </div>
            <div class="text-right">
              <p class="text-xs uppercase tracking-wide text-gray-500">Peak Day</p>
              <p class="text-sm font-semibold text-[#800000]">
                {{ [...metrics.weekday_trend].sort((left, right) => right.count - left.count)[0]?.day || 'N/A' }}
              </p>
            </div>
          </div>

          <div class="space-y-4">
            <div v-for="row in metrics.weekday_trend" :key="row.day">
              <div class="flex items-center justify-between gap-4 mb-2">
                <span class="text-sm font-medium text-gray-700">{{ row.day }}</span>
                <span class="text-sm font-semibold text-[#800000]">{{ row.count }}</span>
              </div>
              <div class="h-2.5 rounded-full bg-gray-100 overflow-hidden">
                <div
                  class="h-full rounded-full bg-[#FFC107] transition-all duration-300"
                  :style="{ width: weekdayTrendWidth(row.count) }"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
