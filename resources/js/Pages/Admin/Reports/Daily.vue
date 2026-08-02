<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Chart } from 'chart.js/auto';
import { usePolling } from '@/Composables/usePolling';

const props = defineProps({
  metrics: {
    type: Object,
    required: true,
  },
});

const selectedDate = ref(props.metrics.selected_date);
const selectedPeriod = ref(props.metrics.selected_period || 'daily');
const isApplyingFilter = ref(false);
const isExportingPdf = ref(false);

const applyDateFilter = () => {
  if (isApplyingFilter.value) {
    return;
  }

  isApplyingFilter.value = true;

  router.get(route('admin.reports.daily'), {
    date: selectedDate.value,
    period: selectedPeriod.value,
  }, {
    preserveState: true,
    replace: true,
    onFinish: () => {
      isApplyingFilter.value = false;
    },
  });
};

const exportPdfReport = () => {
  if (isExportingPdf.value) {
    return;
  }

  isExportingPdf.value = true;

  const exportUrl = route('admin.reports.daily.pdf', {
    date: selectedDate.value,
    period: selectedPeriod.value,
  });

  window.open(exportUrl, '_blank');

  window.setTimeout(() => {
    isExportingPdf.value = false;
  }, 900);
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

const STATUS_COLORS = {
  waiting: '#FFC107',
  called: '#2196F3',
  completed: '#22C55E',
  skipped: '#EF4444',
};

const CLIENT_TYPE_COLORS = {
  student: '#800000',
  parent: '#FFC107',
  visitor: '#2196F3',
  senior_citizen: '#22C55E',
  high_priority: '#9C27B0',
};

const statusChartCanvas = ref(null);
const clientChartCanvas = ref(null);
const categoryChartCanvas = ref(null);
const hourlyChartCanvas = ref(null);
const weekdayChartCanvas = ref(null);

let statusChart = null;
let clientChart = null;
let categoryChart = null;
let hourlyChart = null;
let weekdayChart = null;

const buildStatusChart = () => {
  const rows = props.metrics.status_breakdown;
  const labels = rows.map((row) => statusLabel(row.status));
  const data = rows.map((row) => row.count);
  const colors = rows.map((row) => STATUS_COLORS[row.status] || '#9CA3AF');

  if (statusChart) {
    statusChart.data.labels = labels;
    statusChart.data.datasets[0].data = data;
    statusChart.data.datasets[0].backgroundColor = colors;
    statusChart.update();
    return;
  }

  statusChart = new Chart(statusChartCanvas.value, {
    type: 'doughnut',
    data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 0 }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
  });
};

const buildClientChart = () => {
  const rows = props.metrics.client_breakdown;
  const labels = rows.map((row) => clientTypeLabel(row.client_type));
  const data = rows.map((row) => row.count);
  const colors = rows.map((row) => CLIENT_TYPE_COLORS[row.client_type] || '#9CA3AF');

  if (clientChart) {
    clientChart.data.labels = labels;
    clientChart.data.datasets[0].data = data;
    clientChart.data.datasets[0].backgroundColor = colors;
    clientChart.update();
    return;
  }

  clientChart = new Chart(clientChartCanvas.value, {
    type: 'pie',
    data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 0 }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
  });
};

const buildCategoryChart = () => {
  const rows = props.metrics.service_category_breakdown;
  const labels = rows.map((row) => row.service_category);
  const totals = rows.map((row) => row.total);
  const completed = rows.map((row) => row.completed);
  const waiting = rows.map((row) => row.waiting);

  if (categoryChart) {
    categoryChart.data.labels = labels;
    categoryChart.data.datasets[0].data = totals;
    categoryChart.data.datasets[1].data = completed;
    categoryChart.data.datasets[2].data = waiting;
    categoryChart.update();
    return;
  }

  categoryChart = new Chart(categoryChartCanvas.value, {
    type: 'bar',
    data: {
      labels,
      datasets: [
        { label: 'Total', data: totals, backgroundColor: '#800000' },
        { label: 'Completed', data: completed, backgroundColor: '#22C55E' },
        { label: 'Waiting', data: waiting, backgroundColor: '#FFC107' },
      ],
    },
    options: {
      responsive: true,
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
      plugins: { legend: { position: 'bottom' } },
    },
  });
};

const buildHourlyChart = () => {
  const rows = props.metrics.hourly_data;
  const labels = rows.map((row) => row.hour);
  const data = rows.map((row) => row.count);

  if (hourlyChart) {
    hourlyChart.data.labels = labels;
    hourlyChart.data.datasets[0].data = data;
    hourlyChart.update();
    return;
  }

  hourlyChart = new Chart(hourlyChartCanvas.value, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Queues Created',
        data,
        borderColor: '#800000',
        backgroundColor: 'rgba(128, 0, 0, 0.1)',
        fill: true,
        tension: 0.3,
      }],
    },
    options: {
      responsive: true,
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
      plugins: { legend: { display: false } },
    },
  });
};

const buildWeekdayChart = () => {
  const rows = props.metrics.weekday_trend;
  const labels = rows.map((row) => row.short_day);
  const data = rows.map((row) => row.count);

  if (weekdayChart) {
    weekdayChart.data.labels = labels;
    weekdayChart.data.datasets[0].data = data;
    weekdayChart.update();
    return;
  }

  weekdayChart = new Chart(weekdayChartCanvas.value, {
    type: 'bar',
    data: { labels, datasets: [{ label: 'Queues', data, backgroundColor: '#FFC107' }] },
    options: {
      responsive: true,
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
      plugins: { legend: { display: false } },
    },
  });
};

const renderCharts = () => {
  buildStatusChart();
  buildClientChart();
  buildCategoryChart();
  buildHourlyChart();
  buildWeekdayChart();
};

onMounted(() => {
  renderCharts();
});

watch(() => props.metrics, () => {
  renderCharts();
}, { deep: true });

onBeforeUnmount(() => {
  statusChart?.destroy();
  clientChart?.destroy();
  categoryChart?.destroy();
  hourlyChart?.destroy();
  weekdayChart?.destroy();
});

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
              :disabled="isApplyingFilter"
              class="inline-flex items-center gap-2 bg-[#FFC107] hover:bg-[#FFB300] text-[#800000] px-4 py-2 rounded-lg font-semibold transition disabled:opacity-60 disabled:cursor-not-allowed"
            >
              <svg v-if="isApplyingFilter" width="16" height="16" viewBox="0 0 38 38" aria-hidden="true">
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
              {{ isApplyingFilter ? 'Applying...' : 'Apply' }}
            </button>
            <button
              @click="exportPdfReport"
              :disabled="isExportingPdf"
              class="inline-flex items-center gap-2 bg-[#800000] hover:bg-[#6a0000] text-white px-4 py-2 rounded-lg font-semibold transition disabled:opacity-60 disabled:cursor-not-allowed"
            >
              <svg v-if="isExportingPdf" width="16" height="16" viewBox="0 0 38 38" aria-hidden="true">
                <g transform="translate(19 19)">
                  <g transform="rotate(0)"><circle cx="0" cy="12" r="3" fill="#ffffff" opacity="0.125"><animate attributeName="opacity" from="0.125" to="0.125" dur="1.2s" begin="0s" repeatCount="indefinite" keyTimes="0;1" values="1;0.125" /></circle></g>
                  <g transform="rotate(45)"><circle cx="0" cy="12" r="3" fill="#ffffff" opacity="0.25"><animate attributeName="opacity" from="0.25" to="0.25" dur="1.2s" begin="0.15s" repeatCount="indefinite" keyTimes="0;1" values="1;0.25" /></circle></g>
                  <g transform="rotate(90)"><circle cx="0" cy="12" r="3" fill="#ffffff" opacity="0.375"><animate attributeName="opacity" from="0.375" to="0.375" dur="1.2s" begin="0.3s" repeatCount="indefinite" keyTimes="0;1" values="1;0.375" /></circle></g>
                  <g transform="rotate(135)"><circle cx="0" cy="12" r="3" fill="#ffffff" opacity="0.5"><animate attributeName="opacity" from="0.5" to="0.5" dur="1.2s" begin="0.45s" repeatCount="indefinite" keyTimes="0;1" values="1;0.5" /></circle></g>
                  <g transform="rotate(180)"><circle cx="0" cy="12" r="3" fill="#ffffff" opacity="0.625"><animate attributeName="opacity" from="0.625" to="0.625" dur="1.2s" begin="0.6s" repeatCount="indefinite" keyTimes="0;1" values="1;0.625" /></circle></g>
                  <g transform="rotate(225)"><circle cx="0" cy="12" r="3" fill="#ffffff" opacity="0.75"><animate attributeName="opacity" from="0.75" to="0.75" dur="1.2s" begin="0.75s" repeatCount="indefinite" keyTimes="0;1" values="1;0.75" /></circle></g>
                  <g transform="rotate(270)"><circle cx="0" cy="12" r="3" fill="#ffffff" opacity="0.875"><animate attributeName="opacity" from="0.875" to="0.875" dur="1.2s" begin="0.9s" repeatCount="indefinite" keyTimes="0;1" values="1;0.875" /></circle></g>
                  <g transform="rotate(315)"><circle cx="0" cy="12" r="3" fill="#ffffff" opacity="1"><animate attributeName="opacity" from="1" to="1" dur="1.2s" begin="1.05s" repeatCount="indefinite" keyTimes="0;1" values="1;1" /></circle></g>
                </g>
              </svg>
              {{ isExportingPdf ? 'Exporting...' : 'Export PDF' }}
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
          <div class="h-56 mb-4">
            <canvas ref="statusChartCanvas"></canvas>
          </div>
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
          <div class="h-56 mb-4">
            <canvas ref="clientChartCanvas"></canvas>
          </div>
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
        <div class="h-72 mb-4">
          <canvas ref="categoryChartCanvas"></canvas>
        </div>
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
          <div class="h-56 mb-4">
            <canvas ref="hourlyChartCanvas"></canvas>
          </div>
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

          <div class="h-56">
            <canvas ref="weekdayChartCanvas"></canvas>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
