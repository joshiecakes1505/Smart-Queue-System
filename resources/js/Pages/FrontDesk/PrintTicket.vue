<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed } from 'vue';
import { onMounted } from 'vue';

const props = defineProps({
    queue: {
        type: Object,
        required: true
    }
});

const qrCodeUrl = computed(() => route('qr.generate', props.queue.queue_number));

const printTicket = () => {
    window.print();
};

onMounted(() => {
    setTimeout(() => {
        printTicket();
    }, 250);
});

const formatDate = (datetime) => {
    return new Date(datetime).toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const clientTypeLabel = (type) => {
    const labels = {
        student: 'Student',
        parent: 'Parent',
        visitor: 'Visitor',
        senior_citizen: 'Senior Citizen (Priority)',
        high_priority: 'High Priority',
    };
    return labels[type] || type;
};
</script>

<template>
    <AuthenticatedLayout title="Print Queue Ticket">
        <div class="max-w-4xl mx-auto">
            <!-- Action Buttons (hide on print) -->
            <div class="mb-6 flex gap-4 print:hidden">
                <a
                    :href="route('frontdesk.queues.index')"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition"
                >
                    Back to Dashboard
                </a>
                <button
                    @click="printTicket"
                    class="bg-[#800000] hover:bg-[#660000] text-white px-6 py-3 rounded-lg font-semibold transition"
                >
                    Print Ticket
                </button>
            </div>

            <!-- Ticket Preview -->
            <div class="bg-white rounded-lg shadow-lg p-8 print:shadow-none print:p-4">
                <!-- School Header -->
                <div class="text-center mb-6 border-b-2 border-[#800000] pb-4">
                    <h1 class="text-3xl font-bold text-[#800000]">Batangas Eastern Colleges</h1>
                    <p class="text-lg text-gray-600 mt-1">Smart Queue System</p>
                </div>

                <!-- Queue Information -->
                <div class="space-y-4 mb-6">
                    <!-- Queue Number - Large Display -->
                    <div class="text-center bg-[#800000] text-white py-8 rounded-lg">
                        <p class="text-sm font-medium mb-2">Queue Number</p>
                        <p class="text-6xl font-bold">{{ queue.queue_number }}</p>
                    </div>

                    <!-- Client Details -->
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div class="border-l-4 border-[#FFC107] pl-3">
                            <p class="text-gray-600">Client Name</p>
                            <p class="font-semibold text-lg">{{ queue.client_name || 'Walk-in Client' }}</p>
                        </div>

                        <div class="border-l-4 border-[#FFC107] pl-3">
                            <p class="text-gray-600">Client Type</p>
                            <p class="font-semibold text-lg">{{ clientTypeLabel(queue.client_type) }}</p>
                        </div>

                        <div class="border-l-4 border-[#FFC107] pl-3">
                            <p class="text-gray-600">Service Category</p>
                            <p class="font-semibold text-lg">{{ queue.service_category?.name }}</p>
                        </div>

                        <div class="border-l-4 border-[#FFC107] pl-3">
                            <p class="text-gray-600">Date & Time</p>
                            <p class="font-semibold text-lg">{{ formatDate(queue.created_at) }}</p>
                        </div>
                    </div>
                </div>

                <!-- QR Code -->
                <div class="text-center mb-6">
                    <p class="text-gray-600 text-sm mb-3">Scan to track your queue status</p>
                    <img 
                        :src="qrCodeUrl" 
                        alt="Queue QR Code" 
                        class="mx-auto w-48 h-48 border-4 border-[#800000] rounded-lg"
                    />
                </div>

                <!-- Footer Instructions -->
                <div class="text-center border-t-2 border-gray-200 pt-4">
                    <p class="text-sm text-gray-600">
                        Please wait for your queue number to be called.
                    </p>
                    <p class="text-sm text-gray-600">
                        You can track your queue status by scanning the QR code above.
                    </p>
                    <p class="text-xs text-gray-500 mt-2">
                        Thank you for your patience!
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
@media print {
    body {
        margin: 0;
        padding: 0;
    }
    
    /* Hide everything except the ticket */
    body * {
        visibility: hidden;
    }
    
    .print\:shadow-none,
    .print\:shadow-none * {
        visibility: visible;
    }
    
    .print\:shadow-none {
        position: absolute;
        left: 0;
        top: 0;
        width: 80mm;
        box-shadow: none !important;
    }
    
    /* Hide action buttons */
    .print\:hidden {
        display: none !important;
    }
    
    /* Adjust padding for thermal printer */
    .print\:p-4 {
        padding: 1rem !important;
    }
}
</style>
