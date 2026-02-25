<script setup>
import { computed } from 'vue';
import { ref } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    queue: {
        type: Object,
        required: true
    }
});

const qrCodeUrl = computed(() => route('qr.generate', props.queue.queue_number));
const printReceiptEndpoint = computed(() => route('frontdesk.queues.print-receipt', props.queue.id));
const isPrinting = ref(false);

const browserPrint = () => {
    window.print();
};

const printReceipt = async () => {
    if (isPrinting.value) {
        return;
    }

    isPrinting.value = true;

    try {
        const response = await axios.post(printReceiptEndpoint.value);

        await Swal.fire({
            icon: 'success',
            title: response.data?.message || 'Receipt printed successfully',
            timer: 1800,
            showConfirmButton: false,
        });
    } catch (error) {
        let message = 'Unable to print receipt. Please check printer and print service.';

        if (error?.response?.data?.message) {
            message = error.response.data.message;
        } else if (error?.code === 'ERR_NETWORK') {
            message = 'Print service offline or network error.';
        }

        const result = await Swal.fire({
            icon: 'error',
            title: 'Print failed',
            text: message,
            confirmButtonText: 'Browser Print (with QR)',
            showCancelButton: true,
            cancelButtonText: 'Close',
        });

        if (result.isConfirmed) {
            browserPrint();
        }
    } finally {
        isPrinting.value = false;
    }
};

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
    <Head title="Print Queue Ticket" />
    <div class="min-h-screen bg-gray-50 py-8 px-4 print:bg-white print:py-0">
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
                    @click="printReceipt"
                    class="bg-[#800000] hover:bg-[#660000] text-white px-6 py-3 rounded-lg font-semibold transition"
                    :disabled="isPrinting"
                >
                    {{ isPrinting ? 'Printing...' : 'Print Ticket' }}
                </button>
                <button
                    @click="browserPrint"
                    type="button"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition"
                >
                    Browser Print
                </button>
            </div>

            <div class="flex justify-center">
                <div class="w-full max-w-sm bg-white border border-gray-300 rounded-lg shadow-sm p-5 print:shadow-none print:border-black print:rounded-none print:p-3">
                    <div class="text-center border-b border-dashed border-gray-400 pb-3 mb-3">
                        <h1 class="text-lg font-bold text-[#800000]">Smart Queue Receipt</h1>
                        <p class="text-xs text-gray-600">Batangas Eastern Colleges</p>
                    </div>

                    <div class="text-center mb-4">
                        <p class="text-xs text-gray-500">Queue Number</p>
                        <p class="text-5xl font-bold text-[#800000] leading-tight">{{ queue.queue_number }}</p>
                    </div>

                    <div class="text-sm space-y-2 border-y border-dashed border-gray-400 py-3 mb-4">
                        <div class="flex justify-between gap-3">
                            <span class="text-gray-500">Client</span>
                            <span class="font-semibold text-right">{{ queue.client_name || 'Walk-in Client' }}</span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-gray-500">Type</span>
                            <span class="font-semibold text-right">{{ clientTypeLabel(queue.client_type) }}</span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-gray-500">Service</span>
                            <span class="font-semibold text-right">{{ queue.service_category?.name || 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between gap-3">
                            <span class="text-gray-500">Time</span>
                            <span class="font-semibold text-right">{{ formatDate(queue.created_at) }}</span>
                        </div>
                    </div>

                    <div class="text-center mb-3">
                        <p class="text-xs text-gray-500 mb-2">Scan QR for queue status</p>
                        <img
                            :src="qrCodeUrl"
                            alt="Queue QR Code"
                            class="mx-auto w-36 h-36 border border-gray-300 p-1"
                        />
                    </div>

                    <div class="text-center border-t border-dashed border-gray-400 pt-3">
                        <p class="text-xs text-gray-600">Please wait for your number to be called.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
