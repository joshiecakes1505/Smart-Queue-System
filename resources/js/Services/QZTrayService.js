const PRINTER_NAME = 'POS58';
const ENCODING = 'UTF-8';

const cmd = (...bytes) => String.fromCharCode(...bytes);
const textLine = (value = '') => `${String(value)}\n`;
const bytesToString = (bytes) => Array.from(bytes, (byte) => String.fromCharCode(byte)).join('');

const buildQrStoreCommand = (content) => {
    const bytes = new TextEncoder().encode(content);
    const payloadSize = bytes.length + 3;
    const pL = payloadSize % 256;
    const pH = Math.floor(payloadSize / 256);

    return cmd(0x1d, 0x28, 0x6b, pL, pH, 0x31, 0x50, 0x30) + bytesToString(bytes);
};

const ensureQzBootstrapScript = async () => {
    if (window.qz || window.qzReady) {
        return;
    }

    window.qzReady = new Promise((resolve, reject) => {
        const existingScript = document.querySelector('script[data-qz-bootstrap="true"]');
        if (existingScript) {
            existingScript.addEventListener('load', () => resolve(window.qz));
            existingScript.addEventListener('error', () => reject(new Error('Failed to load /js/qz-tray.js.')));
            return;
        }

        const script = document.createElement('script');
        script.src = '/js/qz-tray.js';
        script.defer = true;
        script.dataset.qzBootstrap = 'true';
        script.onload = () => resolve(window.qz);
        script.onerror = () => reject(new Error('Failed to load /js/qz-tray.js.'));
        document.head.appendChild(script);
    });
};

const ensureQzLoaded = async () => {
    if (typeof window === 'undefined') {
        throw new Error('QZ Tray is only available in browser context.');
    }

    await ensureQzBootstrapScript();

    if (!window.qz && window.qzReady) {
        await window.qzReady;
    }

    if (!window.qz) {
        throw new Error('QZ Tray library is not loaded.');
    }

    return window.qz;
};

const normalizeQueue = (queue = {}) => ({
    number: queue.number || queue.queue_number || 'N/A',
    service: queue.service || queue.service_name || queue.service_category?.name || 'N/A',
    created_at: queue.created_at || new Date().toISOString(),
    qr_code: queue.qr_code || queue.queue_number || queue.number || '',
});

const connect = async () => {
    try {
        const qz = await ensureQzLoaded();

        if (qz.websocket.isActive()) {
            console.log('[QZTrayService] WebSocket already connected.');
        } else {
            console.log('[QZTrayService] Connecting to QZ Tray WebSocket...');
            await qz.websocket.connect();
            console.log('[QZTrayService] Connected to QZ Tray WebSocket.');
        }

        const printer = await qz.printers.find(PRINTER_NAME);
        if (!printer) {
            throw new Error(`Printer "${PRINTER_NAME}" not found.`);
        }

        console.log(`[QZTrayService] Using printer: ${printer}`);
        return printer;
    } catch (error) {
        console.error('[QZTrayService] Failed to connect:', error);
        throw error;
    }
};

const printReceipt = async (queuePayload = {}) => {
    try {
        const qz = await ensureQzLoaded();
        const printerName = await connect();
        const queue = normalizeQueue(queuePayload);

        const receiptDate = new Date(queue.created_at);
        const formattedDate = Number.isNaN(receiptDate.getTime())
            ? queue.created_at
            : receiptDate.toLocaleString();

        const qrValue = String(queue.qr_code || queue.number).trim();
        const config = qz.configs.create(printerName, { encoding: ENCODING });

        const data = [
            cmd(0x1b, 0x40),
            cmd(0x1b, 0x61, 0x01),
            cmd(0x1b, 0x45, 0x01),
            textLine('SMART QUEUE SYSTEM'),
            cmd(0x1b, 0x45, 0x00),
            textLine(''),
            cmd(0x1b, 0x61, 0x00),
            textLine(`Queue Number: ${queue.number}`),
            textLine(`Service: ${queue.service}`),
            textLine(`Date/Time: ${formattedDate}`),
            textLine(''),
            cmd(0x1b, 0x61, 0x01),
            cmd(0x1d, 0x28, 0x6b, 0x04, 0x00, 0x31, 0x41, 0x32, 0x00),
            cmd(0x1d, 0x28, 0x6b, 0x03, 0x00, 0x31, 0x43, 0x07),
            cmd(0x1d, 0x28, 0x6b, 0x03, 0x00, 0x31, 0x45, 0x31),
            buildQrStoreCommand(qrValue),
            cmd(0x1d, 0x28, 0x6b, 0x03, 0x00, 0x31, 0x51, 0x30),
            textLine(''),
            textLine('Please wait for your number to be called.'),
            textLine('Thank you for using Smart Queue System.'),
            cmd(0x1b, 0x64, 0x04),
            cmd(0x1d, 0x56, 0x41, 0x00),
        ];

        console.log('[QZTrayService] Sending RAW ESC/POS print job...');
        await qz.print(config, data);
        console.log('[QZTrayService] Receipt printed successfully.');
    } catch (error) {
        console.error('[QZTrayService] Failed to print receipt:', error);
        throw error;
    }
};

export default {
    connect,
    printReceipt,
};

export { connect, printReceipt };