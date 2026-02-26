const cmd = (...bytes) => String.fromCharCode(...bytes);
const line = (text = '') => `${String(text)}\n`;
const bytesToString = (bytes) => Array.from(bytes, (byte) => String.fromCharCode(byte)).join('');

const normalizeQueue = (queue = {}) => ({
    number: queue.number || queue.queue_number || 'N/A',
    service: queue.service || queue.service_name || queue.service_category?.name || 'N/A',
    created_at: queue.created_at || new Date().toISOString(),
    qr_code: queue.qr_code || queue.queue_number || queue.number || '',
});

const formatDateTime = (value) => {
    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) {
        return String(value ?? 'N/A');
    }

    return parsed.toLocaleString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const buildQrStoreCommand = (qrValue) => {
    const qrBytes = new TextEncoder().encode(qrValue);
    const payloadLength = qrBytes.length + 3;
    const pL = payloadLength % 256;
    const pH = Math.floor(payloadLength / 256);

    return cmd(0x1d, 0x28, 0x6b, pL, pH, 0x31, 0x50, 0x30) + bytesToString(qrBytes);
};

export const buildQueueReceipt = (queue = {}) => {
    const normalizedQueue = normalizeQueue(queue);
    const qrData = String(normalizedQueue.qr_code || normalizedQueue.number || '').trim();
    const formattedDate = formatDateTime(normalizedQueue.created_at);

    const receipt = [
        cmd(0x1b, 0x40),
        cmd(0x1b, 0x61, 0x01),
        line('SMART QUEUE SYSTEM'),
        line(''),

        cmd(0x1b, 0x61, 0x00),
        line(`Queue Number: ${normalizedQueue.number}`),
        line(`Service: ${normalizedQueue.service}`),
        line(`Date/Time: ${formattedDate}`),
        line(''),

        cmd(0x1b, 0x61, 0x01),
        cmd(0x1d, 0x28, 0x6b, 0x04, 0x00, 0x31, 0x41, 0x32, 0x00),
        cmd(0x1d, 0x28, 0x6b, 0x03, 0x00, 0x31, 0x43, 0x07),
        cmd(0x1d, 0x28, 0x6b, 0x03, 0x00, 0x31, 0x45, 0x31),
        buildQrStoreCommand(qrData),
        cmd(0x1d, 0x28, 0x6b, 0x03, 0x00, 0x31, 0x51, 0x30),
        line(''),
        line('Please wait for your number'),
        line('Thank you!'),

        cmd(0x1b, 0x64, 0x03),
        cmd(0x1d, 0x56, 0x00),
    ].join('');

    return receipt;
};

export default {
    buildQueueReceipt,
};
