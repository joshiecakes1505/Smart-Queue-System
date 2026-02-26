<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import Swal from 'sweetalert2';
import { connectQZTray, printQueueReceipt } from '@/Services/ReceiptPrinter';
import { buildQueueReceipt } from '@/Services/RawBTReceiptBuilder';
import { printViaRawBT } from '@/Services/RawBTService';

const props = defineProps({
    queue: {
        type: Object,
        required: true,
    },
});

const isPrinting = ref(false);
const isRawBTPrinting = ref(false);

const normalizedQueue = computed(() => ({
    number: props.queue.number || props.queue.queue_number || 'N/A',
    service: props.queue.service || props.queue.service_category?.name || 'N/A',
    created_at: props.queue.created_at || new Date().toISOString(),
    qr_code: props.queue.qr_code || props.queue.queue_number || props.queue.number || '',
}));

const onPrintReceipt = async () => {
    if (isPrinting.value) {
        return;
    }

    isPrinting.value = true;

    try {
        await printQueueReceipt(normalizedQueue.value);
        await Swal.fire({
            icon: 'success',
            title: 'Receipt printed successfully',
            timer: 1800,
            showConfirmButton: false,
        });
    } catch (error) {
        console.error('[Queue/Receipt] Failed to print:', error);
        await Swal.fire({
            icon: 'error',
            title: 'Print failed',
            text: error?.message || 'Unable to print receipt using QZ Tray.',
        });
    } finally {
        isPrinting.value = false;
    }
};

const onPrintViaRawBT = async () => {
    if (isRawBTPrinting.value) {
        return;
    }

    isRawBTPrinting.value = true;

    try {
        const receipt = buildQueueReceipt(normalizedQueue.value);
        await printViaRawBT(receipt);
    } catch (error) {
        console.error('[Queue/Receipt] RawBT print failed:', error);
        await Swal.fire({
            icon: 'error',
            title: 'RawBT print failed',
            text: error?.message || 'Unable to open RawBT for printing.',
        });
    } finally {
        isRawBTPrinting.value = false;
    }
};

onMounted(async () => {
    try {
        await connectQZTray();
    } catch (error) {
        console.error('[Queue/Receipt] QZ Tray auto-connect failed:', error);
    }
});
</script>

<template>
    <Head title="Queue Receipt" />

    <div class="min-h-screen bg-gray-50 px-4 py-8">
        <div class="mx-auto max-w-md rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h1 class="mb-4 text-center text-xl font-bold text-gray-900">Queue Receipt</h1>

            <div class="space-y-2 text-sm text-gray-700">
                <p><span class="font-semibold">Queue Number:</span> {{ normalizedQueue.number }}</p>
                <p><span class="font-semibold">Service:</span> {{ normalizedQueue.service }}</p>
                <p><span class="font-semibold">Date/Time:</span> {{ normalizedQueue.created_at }}</p>
            </div>

            <button
                type="button"
                class="mt-6 w-full rounded-lg bg-[#800000] px-4 py-3 font-semibold text-white transition hover:bg-[#660000] disabled:cursor-not-allowed disabled:opacity-70"
                :disabled="isPrinting"
                @click="onPrintReceipt"
            >
                {{ isPrinting ? 'Printing...' : 'Print Receipt' }}
            </button>

            <button
                type="button"
                class="mt-3 w-full rounded-lg bg-blue-600 px-4 py-3 font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
                :disabled="isRawBTPrinting"
                @click="onPrintViaRawBT"
            >
                {{ isRawBTPrinting ? 'Opening RawBT...' : 'Print Receipt (Tablet - RawBT)' }}
            </button>
        </div>
    </div>
</template>