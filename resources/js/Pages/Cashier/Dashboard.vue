<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { router } from '@inertiajs/vue3';
import { inject, onBeforeUnmount, onMounted, ref } from 'vue';
import { usePolling } from '@/Composables/usePolling';
import { formatManilaTime } from '@/Utils/dateTime';

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
const skipConfirmActive = ref(false);
const recallConfirmActive = ref(false);
const completeConfirmActive = ref(false);
const swal = inject('$swal');

const showSuccessToast = (title, text) => {
    swal?.fire({
        toast: true,
        icon: 'success',
        title,
        text,
        position: 'top-end',
        timer: 1800,
        timerProgressBar: true,
        showConfirmButton: false,
    });
};

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
        swal?.fire({ icon: 'error', title: 'No assigned window', text: 'Please contact an administrator.' });
        return;
    }

    if (props.current) {
        setFeedback('warning', 'Finish the current queue before calling the next one.');
        swal?.fire({ icon: 'warning', title: 'Queue in progress', text: 'Finish the current queue before calling the next one.' });
        return;
    }

    processing.value = true;

    window.axios.post(route('cashier.callNext'), {
        window_id: props.window.id,
    })
        .then((response) => {
            if (response.data?.status === 'ok') {
                setFeedback('success', 'Next queue has been called.');
                showSuccessToast('Called', 'Next queue has been called.');
                refreshData();
                return;
            }

            if (response.data?.status === 'empty') {
                setFeedback('warning', 'No waiting queues available.');
                swal?.fire({ icon: 'info', title: 'No queue', text: 'No waiting queues available.' });
                return;
            }

            setFeedback('error', 'Unable to call next queue.');
            swal?.fire({ icon: 'error', title: 'Failed', text: 'Unable to call next queue.' });
        })
        .catch(() => {
            setFeedback('error', 'An error occurred while calling the next queue.');
            swal?.fire({ icon: 'error', title: 'Error', text: 'An error occurred while calling the next queue.' });
        })
        .finally(() => {
            processing.value = false;
        });
};

const executeSkip = () => {
    if (!props.current || processing.value) return;

    processing.value = true;

    window.axios.post(route('cashier.skip', props.current.id))
        .then((response) => {
            if (response.data?.status === 'ok') {
                setFeedback('success', 'Queue has been skipped.');
                showSuccessToast('Skipped', 'Queue has been skipped.');
                refreshData();
                return;
            }

            setFeedback('error', 'Queue was not found.');
            swal?.fire({ icon: 'error', title: 'Not found', text: 'Queue was not found.' });
        })
        .catch((error) => {
            const message = error?.response?.data?.message || 'An error occurred while skipping the queue.';
            setFeedback('error', message);
            swal?.fire({ icon: 'error', title: 'Error', text: message });
        })
        .finally(() => {
            processing.value = false;
        });
};

const requestSkipConfirmation = async () => {
    if (!props.current) return;
    if (processing.value || skipConfirmActive.value) return;

    skipConfirmActive.value = true;

    try {
        const decision = await swal?.fire({
            icon: 'warning',
            title: 'Skip queue?',
            text: 'This will mark the current queue as skipped.',
            showCancelButton: true,
            confirmButtonText: 'Yes, skip',
        });

        if (!skipConfirmActive.value) return;

        if (swal && !decision?.isConfirmed) return;

        executeSkip();
    } finally {
        if (skipConfirmActive.value) {
            skipConfirmActive.value = false;
        }
    }
};

const skip = () => {
    if (skipConfirmActive.value) {
        const confirmButton = document.querySelector('.swal2-confirm');
        if (confirmButton instanceof HTMLButtonElement) {
            confirmButton.click();
            return;
        }
    }

    requestSkipConfirmation();
};

const executeComplete = () => {
    if (!props.current || processing.value) return;

    processing.value = true;

    window.axios.post(route('cashier.complete', props.current.id))
        .then((response) => {
            if (response.data?.status === 'ok') {
                setFeedback('success', 'Queue has been marked as completed.');
                showSuccessToast('Completed', 'Queue has been marked as completed.');
                refreshData();
                return;
            }

            setFeedback('error', 'Queue was not found.');
            swal?.fire({ icon: 'error', title: 'Not found', text: 'Queue was not found.' });
        })
        .catch((error) => {
            const message = error?.response?.data?.message || 'An error occurred while completing the queue.';
            setFeedback('error', message);
            swal?.fire({ icon: 'error', title: 'Error', text: message });
        })
        .finally(() => {
            processing.value = false;
        });
};

const executeRecall = () => {
    if (!props.current || processing.value) return;

    processing.value = true;

    window.axios.post(route('cashier.recall', props.current.id))
        .then((response) => {
            if (response.data?.status === 'ok') {
                setFeedback('success', 'Queue has been recalled.');
                showSuccessToast('Recalled', 'Queue has been recalled.');
                refreshData();
                return;
            }

            setFeedback('error', 'Queue was not found.');
            swal?.fire({ icon: 'error', title: 'Not found', text: 'Queue was not found.' });
        })
        .catch(() => {
            setFeedback('error', 'An error occurred while recalling the queue.');
            swal?.fire({ icon: 'error', title: 'Error', text: 'An error occurred while recalling the queue.' });
        })
        .finally(() => {
            processing.value = false;
        });
};

const requestRecallConfirmation = async () => {
    if (!props.current) return;
    if (processing.value || recallConfirmActive.value) return;

    recallConfirmActive.value = true;

    try {
        const decision = await swal?.fire({
            icon: 'question',
            title: 'Recall queue?',
            text: 'This will re-announce the current queue.',
            showCancelButton: true,
            confirmButtonText: 'Yes, recall',
        });

        if (!recallConfirmActive.value) return;

        if (swal && !decision?.isConfirmed) return;

        executeRecall();
    } finally {
        if (recallConfirmActive.value) {
            recallConfirmActive.value = false;
        }
    }
};

const recall = () => {
    if (recallConfirmActive.value) {
        const confirmButton = document.querySelector('.swal2-confirm');
        if (confirmButton instanceof HTMLButtonElement) {
            confirmButton.click();
            return;
        }
    }

    requestRecallConfirmation();
};

const requestCompleteConfirmation = async () => {
    if (!props.current) return;
    if (processing.value || completeConfirmActive.value) return;

    completeConfirmActive.value = true;

    try {
        const decision = await swal?.fire({
            icon: 'question',
            title: 'Complete queue?',
            text: 'This will mark the current queue as completed.',
            showCancelButton: true,
            confirmButtonText: 'Yes, complete',
        });

        if (!completeConfirmActive.value) return;

        if (swal && !decision?.isConfirmed) return;

        executeComplete();
    } finally {
        if (completeConfirmActive.value) {
            completeConfirmActive.value = false;
        }
    }
};

const complete = () => {
    if (completeConfirmActive.value) {
        const confirmButton = document.querySelector('.swal2-confirm');
        if (confirmButton instanceof HTMLButtonElement) {
            confirmButton.click();
            return;
        }
    }

    requestCompleteConfirmation();
};

const isTypingTarget = (target) => {
    if (!target) return false;

    const tagName = target.tagName?.toLowerCase();
    return tagName === 'input'
        || tagName === 'textarea'
        || target.isContentEditable;
};

const handleShortcutKeydown = (event) => {
    if (!event.altKey || event.ctrlKey || event.metaKey) return;
    if (isTypingTarget(event.target)) return;

    const key = `${event.key || ''}`.toLowerCase();
    if (!['q', 'w', 'e', 'r'].includes(key)) return;

    event.preventDefault();

    if (processing.value) return;

    if (key === 'q') {
        callNext();
        return;
    }

    if (key === 'w') {
        if (!props.current) return;

        if (skipConfirmActive.value) {
            const confirmButton = document.querySelector('.swal2-confirm');
            if (confirmButton instanceof HTMLButtonElement) {
                confirmButton.click();
                return;
            }

            skipConfirmActive.value = false;
            swal?.close();
            executeSkip();
            return;
        }

        requestSkipConfirmation();
        return;
    }

    if (key === 'e') {
        if (!props.current) return;

        if (recallConfirmActive.value) {
            const confirmButton = document.querySelector('.swal2-confirm');
            if (confirmButton instanceof HTMLButtonElement) {
                confirmButton.click();
                return;
            }

            recallConfirmActive.value = false;
            swal?.close();
            executeRecall();
            return;
        }

        requestRecallConfirmation();
        return;
    }

    if (!props.current) return;

    if (completeConfirmActive.value) {
        const confirmButton = document.querySelector('.swal2-confirm');
        if (confirmButton instanceof HTMLButtonElement) {
            confirmButton.click();
            return;
        }

        completeConfirmActive.value = false;
        swal?.close();
        executeComplete();
        return;
    }

    requestCompleteConfirmation();
};

const reinstate = (queue) => {
    if (!queue) return;

    if (queue.skip_count >= 2 || queue.is_reinstated) {
        setFeedback('warning', 'Queue is no longer eligible for reinstatement.');
        swal?.fire({ icon: 'warning', title: 'Not eligible', text: 'Queue is no longer eligible for reinstatement.' });
        return;
    }

    processing.value = true;

    window.axios.post(route('cashier.reinstate', queue.id))
        .then((response) => {
            if (response.data?.status === 'ok') {
                setFeedback('success', 'Queue has been reinstated and returned to waiting.');
                showSuccessToast('Reinstated', 'Queue has been reinstated and returned to waiting.');
                refreshData();
                return;
            }

            setFeedback('error', response.data?.message || 'Queue is not eligible for reinstatement.');
            swal?.fire({ icon: 'error', title: 'Failed', text: response.data?.message || 'Queue is not eligible for reinstatement.' });
        })
        .catch((error) => {
            const message = error?.response?.data?.message || 'An error occurred while reinstating the queue.';
            setFeedback('error', message);
            swal?.fire({ icon: 'error', title: 'Error', text: message });
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
    return formatManilaTime(datetime);
};

const queueTheme = (clientType) => {
    if (clientType === 'senior_citizen' || clientType === 'high_priority') {
        return {
            panel: 'bg-blue-700 text-white',
            number: 'text-blue-700',
            chip: 'bg-blue-100 text-blue-800',
        };
    }

    if (clientType === 'visitor' || clientType === 'parent') {
        return {
            panel: 'bg-orange-500 text-white',
            number: 'text-orange-600',
            chip: 'bg-orange-100 text-orange-800',
        };
    }

    return {
        panel: 'bg-[#800000] text-white',
        number: 'text-[#800000]',
        chip: 'bg-[#fdf2f2] text-[#800000]',
    };
};

const serviceCategoryLabel = (queue) => {
    if (Array.isArray(queue?.transaction_service_categories) && queue.transaction_service_categories.length) {
        return queue.transaction_service_categories.join(', ');
    }

    return queue?.service_category?.name || 'N/A';
};

const clientTypeLabel = (clientType) => {
    if (!clientType) return 'General';

    return `${clientType}`
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
};

usePolling(() => {
    return router.reload({
        only: ['window', 'current', 'next', 'recentLogs', 'skippedEligible'],
        preserveState: true,
        preserveScroll: true,
    });
}, 2000);

onMounted(() => {
    window.addEventListener('keydown', handleShortcutKeydown, true);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleShortcutKeydown, true);
});
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

            <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.55fr)_minmax(0,1fr)] gap-6 items-start">
                <div class="space-y-6 min-w-0">
                    <!-- Section 2: Current Serving Queue -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-xl font-semibold text-[#800000] mb-4">Now Serving</h2>

                        <div v-if="current" class="space-y-4">
                            <div class="rounded-lg p-8 text-center" :class="queueTheme(current.client_type).panel">
                                <p class="text-sm mb-2">Queue Number</p>
                                <p class="text-6xl font-bold">{{ current.queue_number }}</p>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-gray-600">
                                <p class="rounded-lg bg-gray-50 px-4 py-3"><span class="font-semibold text-gray-700">Service:</span> {{ serviceCategoryLabel(current) }}</p>
                                <p class="rounded-lg bg-gray-50 px-4 py-3"><span class="font-semibold text-gray-700">Client:</span> {{ current.client_name || 'Walk-in' }}</p>
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

                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                            <button
                                @click="callNext"
                                :disabled="processing || !!current"
                                class="bg-[#FFC107] hover:bg-[#FFB300] text-[#800000] px-6 py-4 rounded-lg font-semibold transition disabled:opacity-50 text-lg"
                            >
                                Call Next
                            </button>

                            <button
                                @click="skip"
                                :disabled="processing || !current"
                                class="border-2 border-gray-500 hover:bg-gray-500 hover:text-white text-gray-600 px-6 py-4 rounded-lg font-semibold transition disabled:opacity-50"
                            >
                                Skip
                            </button>

                            <button
                                @click="recall"
                                :disabled="processing || !current"
                                class="border-2 border-[#800000] hover:bg-[#800000] hover:text-white text-[#800000] px-6 py-4 rounded-lg font-semibold transition disabled:opacity-50"
                            >
                                Recall
                            </button>

                            <button
                                @click="complete"
                                :disabled="processing || !current"
                                class="border-2 border-[#800000] hover:bg-[#800000] hover:text-white text-[#800000] px-6 py-4 rounded-lg font-semibold transition disabled:opacity-50"
                            >
                                Complete
                            </button>
                        </div>
                    </div>
                </div>

                <div class="space-y-6 min-w-0">
                    <!-- Next 5 Queues Preview -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-xl font-semibold text-[#800000] mb-4">Next in Queue</h2>

                        <div v-if="next.length > 0" class="grid grid-cols-2 gap-3">
                            <div
                                v-for="queue in next"
                                :key="queue.id"
                                class="bg-gray-50 rounded-lg p-4 text-center border"
                                :class="queueTheme(queue.client_type).chip"
                            >
                                <p class="text-lg font-bold" :class="queueTheme(queue.client_type).number">{{ queue.queue_number }}</p>
                                <p class="text-xs mt-1">{{ serviceCategoryLabel(queue) }}</p>
                                <p class="text-xs mt-1 opacity-90">{{ clientTypeLabel(queue.client_type) }}</p>
                            </div>
                        </div>

                        <div v-else class="text-center py-6 text-gray-500">
                            No queues waiting
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-xl font-semibold text-[#800000] mb-4">Skipped Queues (Eligible for Reinstatement)</h2>

                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[420px] xl:min-w-0">
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
                                        <td class="py-3 px-4 font-semibold" :class="queueTheme(queue.client_type).number">{{ queue.queue_number }}</td>
                                        <td class="py-3 px-4">{{ serviceCategoryLabel(queue) }}</td>
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

                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-[#800000] mb-4">Recent Activity</h2>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px]">
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
                                <td class="py-3 px-4 font-semibold" :class="queueTheme(log.client_type).number">{{ log.queue_number }}</td>
                                <td class="py-3 px-4">{{ serviceCategoryLabel(log) }}</td>
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
