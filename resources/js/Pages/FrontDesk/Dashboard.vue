<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { usePolling } from '@/Composables/usePolling';

const props = defineProps({
    serviceCategories: {
        type: Array,
        default: () => []
    },
    waitingQueues: {
        type: Array,
        default: () => []
    },
    totalWaiting: {
        type: Number,
        default: 0
    },
    totalServedToday: {
        type: Number,
        default: 0
    },
});

const page = usePage();
const queueNumber = computed(() => page.props.flash?.queueNumber || null);
const printingQueueId = ref(null);

const form = useForm({
    client_name: '',
    service_category_id: '',
    service_category_ids: [],
    has_multiple_transactions: false,
    client_type: 'student',
});

const submit = () => {
    if (form.has_multiple_transactions) {
        form.service_category_id = '';
        if (!form.service_category_ids.length) {
            form.setError('service_category_ids', 'Select at least one service category.');
            return;
        }
    } else {
        form.service_category_ids = [];
    }

    form.post(route('frontdesk.queues.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('client_name', 'service_category_id', 'service_category_ids', 'client_type', 'has_multiple_transactions');
            form.client_type = 'student';
        },
    });
};

const formatTime = (datetime) => {
    return new Date(datetime).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const queueNumberClass = (clientType) => {
    if (clientType === 'senior_citizen' || clientType === 'high_priority') return 'text-blue-700';
    if (clientType === 'visitor' || clientType === 'parent') return 'text-orange-600';
    return 'text-[#800000]';
};

const queueChipClass = (clientType) => {
    if (clientType === 'senior_citizen' || clientType === 'high_priority') return 'bg-blue-100 text-blue-800';
    if (clientType === 'visitor' || clientType === 'parent') return 'bg-orange-100 text-orange-800';
    return 'bg-[#fdf2f2] text-[#800000]';
};

const serviceCategoryLabel = (queue) => {
    if (Array.isArray(queue.transaction_service_categories) && queue.transaction_service_categories.length) {
        return queue.transaction_service_categories.join(', ');
    }

    return queue.service_category?.name || 'N/A';
};

const goToPrint = (queueId) => {
    if (printingQueueId.value !== null) {
        return;
    }

    printingQueueId.value = queueId;

    router.visit(route('frontdesk.queues.print', queueId), {
        onFinish: () => {
            printingQueueId.value = null;
        },
    });
};

usePolling(() => {
    return router.reload({
        only: ['waitingQueues', 'totalWaiting', 'totalServedToday'],
        preserveState: true,
        preserveScroll: true,
    });
}, 2500);
</script>

<template>
    <AuthenticatedLayout title="Front Desk Dashboard">
        <!-- Debug Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4 overflow-x-auto">
            <p class="text-sm">Categories: {{ serviceCategories?.length || 0 }}</p>
            <p class="text-sm">Waiting Queues: {{ waitingQueues?.length || 0 }}</p>
        </div>

        <div class="space-y-6">
            <!-- Success Message -->
            <div v-if="queueNumber" class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-green-800 font-semibold text-center">
                    Queue Created Successfully! Queue Number: <span class="text-2xl">{{ queueNumber }}</span>
                </p>
            </div>

            <!-- Section 1: Queue Registration Form -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-[#800000] mb-4">Queue Registration</h2>
                
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Client Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Client Name <span class="text-gray-400">(Optional)</span>
                            </label>
                            <input
                                v-model="form.client_name"
                                type="text"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000]"
                                placeholder="Enter client name"
                            />
                            <div v-if="form.errors.client_name" class="text-red-500 text-sm mt-1">
                                {{ form.errors.client_name }}
                            </div>
                        </div>

                        <!-- Service Category -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Service Category <span class="text-red-500">*</span>
                            </label>
                            <label class="flex items-center gap-2 mb-3 cursor-pointer">
                                <input
                                    v-model="form.has_multiple_transactions"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-[#800000] focus:ring-[#800000]"
                                />
                                <span class="text-sm text-gray-700">Client has multiple transactions</span>
                            </label>

                            <select
                                v-if="!form.has_multiple_transactions"
                                v-model="form.service_category_id"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000]"
                                required
                            >
                                <option value="" disabled>Select service</option>
                                <option v-for="category in serviceCategories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>

                            <div v-else class="rounded-lg border border-gray-200 bg-gray-50 p-3 space-y-2 max-h-48 overflow-y-auto">
                                <label
                                    v-for="category in serviceCategories"
                                    :key="`multi-${category.id}`"
                                    class="flex items-center gap-2 cursor-pointer"
                                >
                                    <input
                                        v-model="form.service_category_ids"
                                        type="checkbox"
                                        :value="category.id"
                                        class="rounded border-gray-300 text-[#800000] focus:ring-[#800000]"
                                    />
                                    <span class="text-sm text-gray-700">{{ category.name }}</span>
                                </label>
                            </div>

                            <div v-if="form.errors.service_category_id" class="text-red-500 text-sm mt-1">
                                {{ form.errors.service_category_id }}
                            </div>
                            <div v-if="form.errors.service_category_ids" class="text-red-500 text-sm mt-1">
                                {{ form.errors.service_category_ids }}
                            </div>
                        </div>
                    </div>

                    <!-- Client Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Client Type <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            <label class="flex items-start cursor-pointer min-w-0">
                                <input
                                    v-model="form.client_type"
                                    type="radio"
                                    value="student"
                                    class="w-4 h-4 text-[#800000] border-gray-300 focus:ring-[#800000]"
                                />
                                <span class="ml-2 text-sm leading-tight text-gray-700">Student</span>
                            </label>
                            <label class="flex items-start cursor-pointer min-w-0">
                                <input
                                    v-model="form.client_type"
                                    type="radio"
                                    value="parent"
                                    class="w-4 h-4 text-[#800000] border-gray-300 focus:ring-[#800000]"
                                />
                                <span class="ml-2 text-sm leading-tight text-gray-700">Parent</span>
                            </label>
                            <label class="flex items-start cursor-pointer min-w-0">
                                <input
                                    v-model="form.client_type"
                                    type="radio"
                                    value="visitor"
                                    class="w-4 h-4 text-[#800000] border-gray-300 focus:ring-[#800000]"
                                />
                                <span class="ml-2 text-sm leading-tight text-gray-700">Visitor</span>
                            </label>
                            <label class="flex items-start cursor-pointer min-w-0">
                                <input
                                    v-model="form.client_type"
                                    type="radio"
                                    value="senior_citizen"
                                    class="w-4 h-4 text-[#800000] border-gray-300 focus:ring-[#800000]"
                                />
                                <span class="ml-2 text-sm leading-tight text-gray-700">Senior Citizen (Priority)</span>
                            </label>
                            <label class="flex items-start cursor-pointer min-w-0">
                                <input
                                    v-model="form.client_type"
                                    type="radio"
                                    value="high_priority"
                                    class="w-4 h-4 text-[#800000] border-gray-300 focus:ring-[#800000]"
                                />
                                <span class="ml-2 text-sm leading-tight text-gray-700">High Priority</span>
                            </label>
                        </div>
                        <div v-if="form.errors.client_type" class="text-red-500 text-sm mt-1">
                            {{ form.errors.client_type }}
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-stretch sm:justify-end">
                        <button
                            type="submit"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-[#FFC107] hover:bg-[#FFB300] text-[#800000] px-6 py-3 rounded-lg font-semibold transition disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            <svg v-if="form.processing" width="20" height="20" viewBox="0 0 38 38" aria-hidden="true">
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
                            <span v-if="form.processing">Generating...</span>
                            <span v-else>Generate Queue Number</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Section 3: Quick Stats (moved before table for better UX) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <p class="text-gray-600 text-sm">Total Waiting</p>
                    <p class="text-3xl font-bold text-[#800000]">{{ totalWaiting }}</p>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <p class="text-gray-600 text-sm">Total Served Today</p>
                    <p class="text-3xl font-bold text-green-600">{{ totalServedToday }}</p>
                </div>
            </div>

            <!-- Section 2: Current Waiting Queue Table -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-[#800000] mb-4">Current Waiting Queue</h2>
                
                <div v-if="waitingQueues.length === 0" class="text-center py-8 text-gray-500">
                    No waiting queues
                </div>

                <div v-else class="sm:hidden space-y-3">
                    <div
                        v-for="queue in waitingQueues"
                        :key="queue.id"
                        class="rounded-lg border border-gray-200 p-3 bg-gray-50 cursor-pointer"
                        :class="printingQueueId === queue.id ? 'opacity-70 pointer-events-none' : ''"
                        @click="goToPrint(queue.id)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs text-gray-500">Queue Number</p>
                                <p class="text-lg font-bold leading-tight" :class="queueNumberClass(queue.client_type)">{{ queue.queue_number }}</p>
                            </div>
                            <span class="px-2 py-1 rounded-full text-xs font-medium whitespace-nowrap" :class="queueChipClass(queue.client_type)">
                                Waiting
                            </span>
                        </div>
                        <div class="mt-2 text-sm text-gray-700">
                            <p><span class="text-gray-500">Category:</span> {{ serviceCategoryLabel(queue) }}</p>
                            <p><span class="text-gray-500">Time:</span> {{ formatTime(queue.created_at) }}</p>
                            <p v-if="printingQueueId === queue.id" class="mt-2 inline-flex items-center gap-2 text-[#800000] text-xs font-medium">
                                <svg width="16" height="16" viewBox="0 0 38 38" aria-hidden="true">
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
                                Opening print page...
                            </p>
                        </div>
                    </div>
                </div>

                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full min-w-[560px]">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-3 sm:px-4 font-semibold text-gray-700">Queue Number</th>
                                <th class="text-left py-3 px-3 sm:px-4 font-semibold text-gray-700">Category</th>
                                <th class="text-left py-3 px-3 sm:px-4 font-semibold text-gray-700">Status</th>
                                <th class="text-left py-3 px-3 sm:px-4 font-semibold text-gray-700">Time Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="queue in waitingQueues"
                                :key="queue.id"
                                class="border-b border-gray-100 hover:bg-gray-50 cursor-pointer"
                                :class="printingQueueId === queue.id ? 'opacity-70 pointer-events-none' : ''"
                                @click="goToPrint(queue.id)"
                            >
                                <td class="py-3 px-3 sm:px-4 font-semibold" :class="queueNumberClass(queue.client_type)">{{ queue.queue_number }}</td>
                                <td class="py-3 px-3 sm:px-4">{{ serviceCategoryLabel(queue) }}</td>
                                <td class="py-3 px-3 sm:px-4">
                                    <span class="px-3 py-1 rounded-full text-sm" :class="queueChipClass(queue.client_type)">
                                        Waiting
                                    </span>
                                    <p v-if="printingQueueId === queue.id" class="mt-2 inline-flex items-center gap-2 text-[#800000] text-xs font-medium">
                                        <svg width="16" height="16" viewBox="0 0 38 38" aria-hidden="true">
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
                                        Opening print page...
                                    </p>
                                </td>
                                <td class="py-3 px-3 sm:px-4 text-gray-600">{{ formatTime(queue.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
