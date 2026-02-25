<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
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

const form = useForm({
    client_name: '',
    service_category_id: '',
    client_type: 'student',
});

const submit = () => {
    form.post(route('frontdesk.queues.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};

const formatTime = (datetime) => {
    return new Date(datetime).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
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
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
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
                            <select
                                v-model="form.service_category_id"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#800000]"
                                required
                            >
                                <option value="" disabled>Select service</option>
                                <option v-for="category in serviceCategories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.service_category_id" class="text-red-500 text-sm mt-1">
                                {{ form.errors.service_category_id }}
                            </div>
                        </div>
                    </div>

                    <!-- Client Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Client Type <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-6">
                            <label class="flex items-center cursor-pointer">
                                <input
                                    v-model="form.client_type"
                                    type="radio"
                                    value="student"
                                    class="w-4 h-4 text-[#800000] border-gray-300 focus:ring-[#800000]"
                                />
                                <span class="ml-2 text-gray-700">Student</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input
                                    v-model="form.client_type"
                                    type="radio"
                                    value="parent"
                                    class="w-4 h-4 text-[#800000] border-gray-300 focus:ring-[#800000]"
                                />
                                <span class="ml-2 text-gray-700">Parent</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input
                                    v-model="form.client_type"
                                    type="radio"
                                    value="visitor"
                                    class="w-4 h-4 text-[#800000] border-gray-300 focus:ring-[#800000]"
                                />
                                <span class="ml-2 text-gray-700">Visitor</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input
                                    v-model="form.client_type"
                                    type="radio"
                                    value="senior_citizen"
                                    class="w-4 h-4 text-[#800000] border-gray-300 focus:ring-[#800000]"
                                />
                                <span class="ml-2 text-gray-700">Senior Citizen (Priority)</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input
                                    v-model="form.client_type"
                                    type="radio"
                                    value="high_priority"
                                    class="w-4 h-4 text-[#800000] border-gray-300 focus:ring-[#800000]"
                                />
                                <span class="ml-2 text-gray-700">High Priority</span>
                            </label>
                        </div>
                        <div v-if="form.errors.client_type" class="text-red-500 text-sm mt-1">
                            {{ form.errors.client_type }}
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="bg-[#FFC107] hover:bg-[#FFB300] text-[#800000] px-6 py-3 rounded-lg font-semibold transition disabled:opacity-50"
                            :disabled="form.processing"
                        >
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
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Queue Number</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Category</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Time Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="waitingQueues.length === 0">
                                <td colspan="4" class="text-center py-8 text-gray-500">
                                    No waiting queues
                                </td>
                            </tr>
                            <tr
                                v-for="queue in waitingQueues"
                                :key="queue.id"
                                class="border-b border-gray-100 hover:bg-gray-50 cursor-pointer"
                                @click="router.visit(route('frontdesk.queues.print', queue.id))"
                            >
                                <td class="py-3 px-4 font-semibold text-[#800000]">{{ queue.queue_number }}</td>
                                <td class="py-3 px-4">{{ queue.service_category?.name || 'N/A' }}</td>
                                <td class="py-3 px-4">
                                    <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm">
                                        Waiting
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-gray-600">{{ formatTime(queue.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
