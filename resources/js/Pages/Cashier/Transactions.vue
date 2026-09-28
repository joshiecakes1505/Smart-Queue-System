<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { formatManilaTime } from '@/Utils/dateTime';

const props = defineProps({
  window: { type: Object, default: null },
  rows: { type: Object, default: null },
  summary: { type: Object, default: null },
  filters: { type: Object, default: () => ({}) },
});

const date = ref(props.filters.date || '');

const applyFilters = () => {
  router.get(route('cashier.transactions'), { date: date.value || null }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};

const today = () => {
  date.value = new Date().toLocaleDateString('en-CA');
  applyFilters();
};

const formatTime = (value) => (value ? formatManilaTime(value, { second: '2-digit' }) : '—');

const formatDuration = (seconds) => {
  if (seconds === null || seconds === undefined) return '—';
  if (seconds < 60) return `${seconds}s`;

  const minutes = Math.floor(seconds / 60);
  const remainder = seconds % 60;

  return remainder ? `${minutes}m ${remainder}s` : `${minutes}m`;
};

const outcomeClass = (outcome) => ({
  completed: 'bg-green-100 text-green-800',
  skipped: 'bg-amber-100 text-amber-800',
  ongoing: 'bg-blue-100 text-blue-800',
}[outcome] || 'bg-gray-100 text-gray-700');

const clientTypeLabel = (value) => {
  if (!value) return 'General';

  return String(value)
    .split('_')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ');
};
</script>

<template>
  <AuthenticatedLayout title="My Transactions">
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
      <div class="mb-5">
        <h1 class="text-2xl font-bold text-[#800000]">Past Transactions</h1>
        <p class="mt-1 text-sm text-gray-600">
          <template v-if="window">Every queue called at {{ window.name }}, with how it ended.</template>
          <template v-else>You have no cashier window assigned yet.</template>
        </p>
      </div>

      <div v-if="!window" class="rounded-xl border border-amber-300 bg-amber-50 p-6 text-center">
        <p class="font-semibold text-amber-800">No window assigned</p>
        <p class="mt-1 text-sm text-amber-700">
          Ask an administrator to assign you to a cashier window, then your transaction history will appear here.
        </p>
      </div>

      <template v-else>
        <!-- Date filter -->
        <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
              <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-gray-500" for="tx-date">
                Date
              </label>
              <input
                id="tx-date"
                v-model="date"
                type="date"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-[#800000] focus:ring-[#800000]"
                @change="applyFilters"
              />
            </div>
            <button
              type="button"
              class="rounded-lg bg-[#800000] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#600000]"
              @click="applyFilters"
            >
              Apply
            </button>
            <button
              type="button"
              class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-200"
              @click="today"
            >
              Today
            </button>
          </div>
        </div>

        <!-- Summary -->
        <div v-if="summary" class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
          <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Called</p>
            <p class="mt-1 text-2xl font-bold text-[#800000]">{{ summary.total }}</p>
          </div>
          <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Completed</p>
            <p class="mt-1 text-2xl font-bold text-green-700">{{ summary.completed }}</p>
          </div>
          <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Skipped</p>
            <p class="mt-1 text-2xl font-bold text-amber-700">{{ summary.skipped }}</p>
          </div>
          <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs uppercase tracking-wide text-gray-500">Avg service</p>
            <p class="mt-1 text-2xl font-bold text-gray-800">
              {{ summary.average_service_seconds ? formatDuration(summary.average_service_seconds) : '—' }}
            </p>
          </div>
        </div>

        <!-- Table -->
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
          <div v-if="!rows || rows.total === 0" class="py-10 text-center text-sm text-gray-500">
            No transactions at {{ window.name }} on this date.
          </div>

          <template v-else>
            <div class="overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead>
                  <tr class="border-b border-gray-200 text-left text-xs uppercase tracking-wide text-gray-500">
                    <th class="px-3 py-2">Queue</th>
                    <th class="px-3 py-2">Service</th>
                    <th class="px-3 py-2">Client</th>
                    <th class="px-3 py-2">Called</th>
                    <th class="px-3 py-2">Finished</th>
                    <th class="px-3 py-2">Duration</th>
                    <th class="px-3 py-2">Outcome</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in rows.data" :key="row.id" class="border-b border-gray-100 last:border-0">
                    <td class="px-3 py-2.5 font-bold text-[#800000]">{{ row.queue_number || '—' }}</td>
                    <td class="px-3 py-2.5 text-gray-700">{{ row.service_category || '—' }}</td>
                    <td class="px-3 py-2.5 text-gray-600">{{ clientTypeLabel(row.client_type) }}</td>
                    <td class="px-3 py-2.5 whitespace-nowrap text-gray-600">{{ formatTime(row.called_at) }}</td>
                    <td class="px-3 py-2.5 whitespace-nowrap text-gray-600">{{ formatTime(row.finished_at) }}</td>
                    <td class="px-3 py-2.5 whitespace-nowrap text-gray-700">{{ formatDuration(row.duration_seconds) }}</td>
                    <td class="px-3 py-2.5">
                      <span
                        class="inline-block rounded-full px-2.5 py-1 text-xs font-semibold capitalize"
                        :class="outcomeClass(row.outcome)"
                      >
                        {{ row.outcome }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="rows.links?.length > 3" class="mt-4 flex flex-wrap gap-2">
              <Link
                v-for="linkItem in rows.links"
                :key="linkItem.label"
                :href="linkItem.url || '#'"
                :class="[
                  'rounded-lg px-3 py-1.5 text-sm font-semibold transition',
                  linkItem.active ? 'bg-[#800000] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
                  !linkItem.url ? 'pointer-events-none opacity-40' : '',
                ]"
                preserve-state
                preserve-scroll
                v-html="linkItem.label"
              />
            </div>
          </template>
        </div>
      </template>
    </div>
  </AuthenticatedLayout>
</template>
