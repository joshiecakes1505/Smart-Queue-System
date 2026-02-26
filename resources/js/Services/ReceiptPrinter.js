import QZTrayService from '@/Services/QZTrayService';

const normalizeQueue = (queue = {}) => ({
    number: queue.number || queue.queue_number || 'N/A',
    service: queue.service || queue.service_name || queue.service_category?.name || 'N/A',
    created_at: queue.created_at || new Date().toISOString(),
    qr_code: queue.qr_code || queue.queue_number || queue.number || '',
});

export const connectQZTray = async () => {
    return QZTrayService.connect();
};

export const printQueueReceipt = async (queue = {}) => {
    const payload = normalizeQueue(queue);
    await QZTrayService.printReceipt(payload);
};

export default {
    connectQZTray,
    printQueueReceipt,
};