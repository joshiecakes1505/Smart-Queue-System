<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { usePolling } from '@/Composables/usePolling';

const props = defineProps({
    window: Object,
    current: Object,
    next: Array,
    recentLogs: Array,
    skippedEligible: {
        type: Array,
        default: () => [],
    },
});

const processing = ref(false);
const feedback = ref({ type: '', message: '' });

const setFeedback = (type, message) => {
    feedback.value = { type, message };
};

const feedbackClass = (type) => {
    if (type === 'success') return 'bg-green-50 border-green-200 text-green-800';
    if (type === 'warning') return 'bg-yellow-50 border-yellow-200 text-yellow-800';
    if (type === 'error') return 'bg-red-50 border-red-200 text-red-800';
    return 'bg-gray-50 border-gray-200 text-gray-800';
};

const refreshData = () => {
    router.reload({ only: ['current', 'next', 'recentLogs', 'skippedEligible'] });
};

const callNext = () => {
    if (!props.window) {
        setFeedback('error', 'No cashier window assigned. Please contact an administrator.');
        return;
    }

    if (props.current) {
        setFeedback('warning', 'Finish the current queue before calling the next one.');
        return;
    }

    processing.value = true;

    window.axios.post(route('cashier.callNext'), {
        window_id: props.window.id,
    })
        .then((response) => {
            if (response.data?.status === 'ok') {
                setFeedback('success', 'Next queue has been called.');
                refreshData();
                return;
            }

            if (response.data?.status === 'empty') {
                setFeedback('warning', 'No waiting queues available.');
                return;
            }

            setFeedback('error', 'Unable to call next queue.');
        })
        .catch(() => {
            setFeedback('error', 'An error occurred while calling the next queue.');
        })
        .finally(() => {
            processing.value = false;
        });
};

const skip = () => {
    if (!props.current) return;
    
    if (!confirm('Skip this queue?')) return;
    
    processing.value = true;

    window.axios.post(route('cashier.skip', props.current.id))
        .then((response) => {
            if (response.data?.status === 'ok') {
                setFeedback('success', 'Queue has been skipped.');
                refreshData();
                return;
            }

            setFeedback('error', 'Queue was not found.');
        })
        .catch(() => {
            setFeedback('error', 'An error occurred while skipping the queue.');
        })
        .finally(() => {
            processing.value = false;
        });
};

const recall = () => {
    if (!props.current) return;
    
    processing.value = true;

    window.axios.post(route('cashier.recall', props.current.id))
        .then((response) => {
            if (response.data?.status === 'ok') {
                setFeedback('success', 'Queue has been recalled.');
                refreshData();
                return;
            }

            setFeedback('error', 'Queue was not found.');
        })
        .catch(() => {
            setFeedback('error', 'An error occurred while recalling the queue.');
        })
        .finally(() => {
            processing.value = false;
        });
};

const complete = () => {
    if (!props.current) return;
    
    if (!confirm('Mark this queue as completed?')) return;
    
    processing.value = true;

    window.axios.post(route('cashier.complete', props.current.id))
        .then((response) => {
            if (response.data?.status === 'ok') {
                setFeedback('success', 'Queue has been marked as completed.');
                refreshData();
                return;
            }

            setFeedback('error', 'Queue was not found.');
        })
        .catch(() => {
            setFeedback('error', 'An error occurred while completing the queue.');
        })
        .finally(() => {
            processing.value = false;
        });
};

const reinstate = (queue) => {
    if (!queue) return;

    if (queue.skip_count >= 2 || queue.is_reinstated) {
        setFeedback('warning', 'Queue is no longer eligible for reinstatement.');
        return;
    }

    processing.value = true;

    window.axios.post(route('cashier.reinstate', queue.id))
        .then((response) => {
            if (response.data?.status === 'ok') {
                setFeedback('success', 'Queue has been reinstated and returned to waiting.');
                refreshData();
                return;
            }

            setFeedback('error', response.data?.message || 'Queue is not eligible for reinstatement.');
        })
        .catch((error) => {
            const message = error?.response?.data?.message || 'An error occurred while reinstating the queue.';
            setFeedback('error', message);
        })
        .finally(() => {
            processing.value = false;
        });
};

const getStatusColor = (status) => {
    switch(status) {
        case 'completed': return 'bg-green-100 text-green-800';
        case 'skipped': return 'bg-gray-100 text-gray-800';
        case 'called': return 'bg-blue-100 text-blue-800';
        default: return 'bg-yellow-100 text-yellow-800';
    }
};

const formatTime = (datetime) => {
    return new Date(datetime).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

usePolling(() => {
    return router.reload({
        only: ['window', 'current', 'next', 'recentLogs', 'skippedEligible'],
        preserveState: true,
        preserveScroll: true,
    });
}, 2000);
</script>

<template>
    <AuthenticatedLayout title="Cashier Dashboard">
        <div v-if="!window" class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
            <p class="text-yellow-800 font-semibold">You are not assigned to any cashier window.</p>
            <p class="text-yellow-700 text-sm mt-2">Please contact an administrator.</p>
        </div>

        <div v-else class="space-y-6">
            <!-- Section 1: Assigned Window Display -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-[#800000] mb-2">Assigned Window</h2>
                <p class="text-4xl font-bold text-[#800000]">{{ window.name }}</p>
            </div>

            <!-- Section 2: Current Serving Queue -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-[#800000] mb-4">Now Serving</h2>
                
                <div v-if="current" class="space-y-4">
                    <div class="bg-[#800000] text-white rounded-lg p-8 text-center">
                        <p class="text-sm mb-2">Queue Number</p>
                        <p class="text-6xl font-bold">{{ current.queue_number }}</p>
                    </div>
                    <div class="text-center text-gray-600">
                        <p class="text-sm">Service: {{ current.service_category?.name || 'N/A' }}</p>
                        <p class="text-sm">Client: {{ current.client_name || 'Walk-in' }}</p>
                    </div>
                </div>

                <div v-else class="bg-gray-50 rounded-lg p-8 text-center">
                    <p class="text-gray-500 text-lg">No active queue</p>
                    <p class="text-gray-400 text-sm mt-2">Click "Call Next" to serve the next queue</p>
                </div>
            </div>

            <!-- Section 3: Queue Control Buttons -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-[#800000] mb-4">Queue Controls</h2>

                <div
                    v-if="feedback.message"
                    :class="feedbackClass(feedback.type)"
                    class="mb-4 border rounded-lg px-4 py-3 text-sm"
                >
                    {{ feedback.message }}
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Call Next - Primary Action -->
                    <button
                        @click="callNext"
                        :disabled="processing || !!current"
                        class="bg-[#FFC107] hover:bg-[#FFB300] text-[#800000] px-6 py-4 rounded-lg font-semibold transition disabled:opacity-50 text-lg"
                    >
                        Call Next
                    </button>

                    <!-- Skip -->
                    <button
                        @click="skip"
                        :disabled="processing || !current"
                        class="border-2 border-gray-500 hover:bg-gray-500 hover:text-white text-gray-600 px-6 py-4 rounded-lg font-semibold transition disabled:opacity-50"
                    >
                        Skip
                    </button>

                    <!-- Recall -->
                    <button
                        @click="recall"
                        :disabled="processing || !current"
                        class="border-2 border-[#800000] hover:bg-[#800000] hover:text-white text-[#800000] px-6 py-4 rounded-lg font-semibold transition disabled:opacity-50"
                    >
                        Recall
                    </button>

                    <!-- Complete -->
                    <button
                        @click="complete"
                        :disabled="processing || !current"
                        class="border-2 border-[#800000] hover:bg-[#800000] hover:text-white text-[#800000] px-6 py-4 rounded-lg font-semibold transition disabled:opacity-50"
                    >
                        Complete
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-[#800000] mb-4">Skipped Queues (Eligible for Reinstatement)</h2>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Queue Number</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Service Category</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="skippedEligible.length === 0">
                                <td colspan="3" class="text-center py-8 text-gray-500">
                                    No eligible skipped queues
                                </td>
                            </tr>
                            <tr
                                v-for="queue in skippedEligible"
                                :key="queue.id"
                                class="border-b border-gray-100 hover:bg-gray-50"
                            >
                                <td class="py-3 px-4 font-semibold text-[#800000]">{{ queue.queue_number }}</td>
                                <td class="py-3 px-4">{{ queue.service_category?.name || 'N/A' }}</td>
                                <td class="py-3 px-4">
                                    <button
                                        @click="reinstate(queue)"
                                        :disabled="processing || queue.skip_count >= 2 || queue.is_reinstated"
                                        class="border-2 border-[#FFC107] hover:bg-[#FFC107] text-[#800000] px-4 py-2 rounded-lg font-semibold transition disabled:opacity-50"
                                    >
                                        Reinstate
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Next 5 Queues Preview -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-[#800000] mb-4">Next in Queue</h2>
                
                <div v-if="next.length > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    <div
                        v-for="queue in next"
                        :key="queue.id"
                        class="bg-gray-50 rounded-lg p-4 text-center"
                    >
                        <p class="text-lg font-bold text-[#800000]">{{ queue.queue_number }}</p>
                        <p class="text-xs text-gray-600 mt-1">{{ queue.service_category?.name || 'N/A' }}</p>
                    </div>
                </div>

                <div v-else class="text-center py-6 text-gray-500">
                    No queues waiting
                </div>
            </div>

            <!-- Section 4: Recent Queue Logs -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-[#800000] mb-4">Recent Activity</h2>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Queue Number</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Service</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="recentLogs.length === 0">
                                <td colspan="4" class="text-center py-8 text-gray-500">
                                    No recent activity
                                </td>
                            </tr>
                            <tr
                                v-for="log in recentLogs"
                                :key="log.id"
                                class="border-b border-gray-100 hover:bg-gray-50"
                            >
                                <td class="py-3 px-4 font-semibold text-[#800000]">{{ log.queue_number }}</td>
                                <td class="py-3 px-4">{{ log.service_category?.name || 'N/A' }}</td>
                                <td class="py-3 px-4">
                                    <span :class="getStatusColor(log.status)" class="px-3 py-1 rounded-full text-sm capitalize">
                                        {{ log.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-gray-600">{{ formatTime(log.updated_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
