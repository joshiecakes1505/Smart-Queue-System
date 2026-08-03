<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { inject, ref } from 'vue';
import { usePolling } from '@/Composables/usePolling';
import { formatManilaDateTime } from '@/Utils/dateTime';

const props = defineProps({
  errors: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({ search: '', level: null, status: 'unresolved' }),
  },
  summary: {
    type: Object,
    default: () => ({ unresolved_count: 0, total_count: 0 }),
  },
});

const search = ref(props.filters.search || '');
const level = ref(props.filters.level || '');
const status = ref(props.filters.status || 'unresolved');
const expandedId = ref(null);
const busyId = ref(null);
const swal = inject('$swal');

const statusTabs = [
  { value: 'unresolved', label: 'Unresolved' },
  { value: 'resolved', label: 'Resolved' },
  { value: 'all', label: 'All' },
];

const applyFilters = () => {
  router.get(route('admin.monitoring.index'), {
    search: search.value,
    level: level.value || null,
    status: status.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};

const toggleExpanded = (errorLog) => {
  expandedId.value = expandedId.value === errorLog.id ? null : errorLog.id;
};

const resolveError = (errorLog) => {
  busyId.value = errorLog.id;
  router.patch(route('admin.monitoring.resolve', errorLog.id), {}, {
    preserveScroll: true,
    preserveState: true,
    onFinish: () => { busyId.value = null; },
  });
};

const unresolveError = (errorLog) => {
  busyId.value = errorLog.id;
  router.patch(route('admin.monitoring.unresolve', errorLog.id), {}, {
    preserveScroll: true,
    preserveState: true,
    onFinish: () => { busyId.value = null; },
  });
};

const deleteError = async (errorLog) => {
  const confirmation = await swal?.fire({
    icon: 'warning',
    title: 'Delete this log entry?',
    text: 'This removes the error record permanently. This cannot be undone.',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete',
    cancelButtonText: 'Cancel',
    confirmButtonColor: '#b91c1c',
    reverseButtons: true,
  });

  if (confirmation && !confirmation.isConfirmed) return;

  busyId.value = errorLog.id;
  router.delete(route('admin.monitoring.destroy', errorLog.id), {
    preserveScroll: true,
    preserveState: true,
    onFinish: () => { busyId.value = null; },
  });
};

const levelBadgeClass = (lvl) => {
  switch (lvl) {
    case 'critical': return 'bg-red-100 text-red-800';
    case 'warning': return 'bg-amber-100 text-amber-800';
    default: return 'bg-orange-100 text-orange-800';
  }
};

usePolling(() => {
  return router.reload({
    only: ['errors', 'summary'],
    preserveState: true,
    preserveScroll: true,
  });
}, 10000);
</script>

<template>
  <AuthenticatedLayout title="System Monitoring">
    <div class="space-y-6">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white rounded-lg shadow-sm p-6">
          <p class="text-gray-600 text-sm">Unresolved Errors</p>
          <p class="text-3xl font-bold text-red-600">{{ summary.unresolved_count }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
          <p class="text-gray-600 text-sm">Total Logged</p>
          <p class="text-3xl font-bold text-[#800000]">{{ summary.total_count }}</p>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex flex-wrap items-end justify-between gap-4 mb-4">
          <div>
            <h1 class="text-2xl font-semibold text-[#800000]">System Monitoring</h1>
            <p class="text-sm text-gray-600 mt-1">Application errors captured automatically, so issues can be tracked down.</p>
          </div>
        </div>

        <div class="flex flex-wrap gap-2 mb-4">
          <button
            v-for="tab in statusTabs"
            :key="tab.value"
            type="button"
            @click="status = tab.value; applyFilters()"
            class="rounded-lg px-3 py-1.5 text-sm font-semibold transition"
            :class="status === tab.value ? 'bg-[#800000] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
          >
            {{ tab.label }}
          </button>
        </div>

        <div class="flex flex-wrap gap-3 mb-4">
          <input
            v-model="search"
            type="text"
            placeholder="Search message, exception, or URL..."
            class="flex-1 min-w-[220px] border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000]"
            @keyup.enter="applyFilters"
          />
          <select
            v-model="level"
            class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000]"
            @change="applyFilters"
          >
            <option value="">All levels</option>
            <option value="error">Error</option>
            <option value="warning">Warning</option>
            <option value="critical">Critical</option>
          </select>
          <button
            type="button"
            @click="applyFilters"
            class="bg-[#FFC107] hover:bg-[#FFB300] text-[#800000] px-4 py-2 rounded-lg font-semibold transition"
          >
            Search
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Level</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Message</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Occurrences</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Last Seen</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="errors.data.length === 0">
                <td colspan="6" class="text-center py-8 text-gray-500">No errors found</td>
              </tr>
              <template v-for="errorLog in errors.data" :key="errorLog.id">
                <tr class="border-b border-gray-100 hover:bg-gray-50 cursor-pointer" @click="toggleExpanded(errorLog)">
                  <td class="py-3 px-4">
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold" :class="levelBadgeClass(errorLog.level)">
                      {{ errorLog.level }}
                    </span>
                  </td>
                  <td class="py-3 px-4 max-w-md">
                    <p class="font-medium text-gray-900 truncate">{{ errorLog.exception_class || 'Error' }}</p>
                    <p class="text-sm text-gray-600 truncate">{{ errorLog.message }}</p>
                  </td>
                  <td class="py-3 px-4 text-gray-700">{{ errorLog.occurrences }}</td>
                  <td class="py-3 px-4 text-gray-600 text-sm">{{ formatManilaDateTime(errorLog.last_occurred_at) }}</td>
                  <td class="py-3 px-4">
                    <span
                      class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                      :class="errorLog.resolved_at ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                    >
                      {{ errorLog.resolved_at ? 'Resolved' : 'Unresolved' }}
                    </span>
                  </td>
                  <td class="py-3 px-4" @click.stop>
                    <div class="flex items-center gap-2">
                      <button
                        v-if="!errorLog.resolved_at"
                        type="button"
                        :disabled="busyId === errorLog.id"
                        class="inline-flex items-center rounded-lg border border-green-600 px-3 py-1.5 text-xs font-semibold text-green-600 transition hover:bg-green-600 hover:text-white disabled:opacity-50"
                        @click="resolveError(errorLog)"
                      >
                        Resolve
                      </button>
                      <button
                        v-else
                        type="button"
                        :disabled="busyId === errorLog.id"
                        class="inline-flex items-center rounded-lg border border-amber-600 px-3 py-1.5 text-xs font-semibold text-amber-600 transition hover:bg-amber-600 hover:text-white disabled:opacity-50"
                        @click="unresolveError(errorLog)"
                      >
                        Reopen
                      </button>
                      <button
                        type="button"
                        :disabled="busyId === errorLog.id"
                        class="inline-flex items-center rounded-lg border border-gray-500 px-3 py-1.5 text-xs font-semibold text-gray-600 transition hover:bg-gray-500 hover:text-white disabled:opacity-50"
                        @click="deleteError(errorLog)"
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="expandedId === errorLog.id" class="border-b border-gray-100 bg-gray-50">
                  <td colspan="6" class="py-4 px-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-gray-700 mb-3">
                      <p><span class="font-semibold">File:</span> {{ errorLog.file || '—' }}:{{ errorLog.line || '—' }}</p>
                      <p><span class="font-semibold">URL:</span> {{ errorLog.method }} {{ errorLog.url || '—' }}</p>
                      <p><span class="font-semibold">User:</span> {{ errorLog.user?.name || 'Guest / System' }}</p>
                      <p v-if="errorLog.resolved_at">
                        <span class="font-semibold">Resolved by:</span> {{ errorLog.resolved_by?.name || 'n/a' }} on {{ formatManilaDateTime(errorLog.resolved_at) }}
                      </p>
                    </div>
                    <pre class="bg-gray-900 text-gray-100 text-xs rounded-lg p-4 overflow-x-auto max-h-96 whitespace-pre-wrap">{{ errorLog.trace || 'No stack trace captured.' }}</pre>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <div v-if="errors.links?.length > 3" class="flex flex-wrap gap-2 mt-4">
          <Link
            v-for="linkItem in errors.links"
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
      </div>
    </div>
  </AuthenticatedLayout>
</template>
