const express = require('express');
const escpos = require('escpos');
const cors = require('cors');
const bodyParser = require('body-parser');
const qr = require('qr-image');

escpos.USB = require('escpos-usb');

const PRINTER_MODEL = 'Officom OC-58H';
const PORT = Number(process.env.PORT || 3001);
const RETRY_COUNT = 3;
const RETRY_DELAY_MS = 500;

const app = express();
app.use(cors());
app.use(bodyParser.json({ limit: '1mb' }));

const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const patchUsbApiForEscposUsb = () => {
    try {
        const usb = require('usb');

        if (usb && usb.usb) {
            if (typeof usb.on !== 'function' && typeof usb.usb.on === 'function') {
                usb.on = usb.usb.on.bind(usb.usb);
            }

            if (typeof usb.removeAllListeners !== 'function' && typeof usb.usb.removeAllListeners === 'function') {
                usb.removeAllListeners = usb.usb.removeAllListeners.bind(usb.usb);
            }

            if (typeof usb.getDeviceList !== 'function' && typeof usb.usb.getDeviceList === 'function') {
                usb.getDeviceList = usb.usb.getDeviceList.bind(usb.usb);
            }

            if (typeof usb.findByIds !== 'function' && typeof usb.usb.findByIds === 'function') {
                usb.findByIds = usb.usb.findByIds.bind(usb.usb);
            }

            return usb.usb;
        }

        return usb || null;
    } catch (_error) {
        return null;
    }
};

const usbEventsSource = patchUsbApiForEscposUsb();

if (usbEventsSource && typeof usbEventsSource.on === 'function') {
    usbEventsSource.on('attach', () => {
        console.log('[USB] Printer connected');
    });

    usbEventsSource.on('detach', () => {
        console.log('[USB] Printer disconnected');
    });
}

function normalizePayload(payload) {
    return {
        queue_number: payload?.queue_number ?? payload?.queueNumber,
        service_name: payload?.service_name ?? payload?.serviceCategory,
        created_at: payload?.created_at ?? payload?.printedAt,
        qr_code: payload?.qr_code ?? payload?.qrValue,
    };
}

function validatePayload(payload) {
    if (!payload || typeof payload !== 'object') {
        return 'Invalid payload';
    }

    if (!payload.queue_number || typeof payload.queue_number !== 'string') {
        return 'queue_number is required';
    }

    if (!payload.service_name || typeof payload.service_name !== 'string') {
        return 'service_name is required';
    }

    if (!payload.created_at || typeof payload.created_at !== 'string') {
        return 'created_at is required';
    }

    if (!payload.qr_code || typeof payload.qr_code !== 'string') {
        return 'qr_code is required';
    }

    return null;
}

async function getPrinter() {
    for (let attempt = 1; attempt <= RETRY_COUNT; attempt += 1) {
        try {
            const devices = escpos.USB.findPrinter();

            if (devices && devices.length > 0) {
                const first = devices[0];
                return new escpos.USB(
                    first.deviceDescriptor.idVendor,
                    first.deviceDescriptor.idProduct,
                );
            }
        } catch (error) {
            console.error(`[Printer] Detection error (attempt ${attempt}/${RETRY_COUNT}):`, error.message);
        }

        if (attempt < RETRY_COUNT) {
            await delay(RETRY_DELAY_MS);
        }
    }

    return null;
}

function openDevice(device) {
    return new Promise((resolve, reject) => {
        try {
            device.open((error) => {
                if (error) {
                    reject(error);
                    return;
                }

                resolve();
            });
        } catch (error) {
            reject(error);
        }
    });
}

function closePrinter(printer) {
    return new Promise((resolve) => {
        try {
            printer.close(() => resolve());
        } catch (_error) {
            resolve();
        }
    });
}

function loadQrImageFromBuffer(buffer) {
    return new Promise((resolve, reject) => {
        try {
            escpos.Image.load(buffer, 'image/png', (result) => {
                if (result instanceof Error) {
                    reject(result);
                    return;
                }

                resolve(result);
            });
        } catch (error) {
            reject(error);
        }
    });
}

async function printReceipt(device, payload) {
    await openDevice(device);
    console.log('[Printer] Printer connected');

    const printer = new escpos.Printer(device, { encoding: 'GB18030' });

    try {
        const qrPng = qr.imageSync(payload.qr_code, { type: 'png', size: 6 });
        const qrImage = await loadQrImageFromBuffer(qrPng);

        await new Promise((resolve, reject) => {
            try {
                printer
                    .align('lt')
                    .style('NORMAL')
                    .size(0, 0)
                    .text(`Queue Number : ${payload.queue_number}`)
                    .text(`Service : ${payload.service_name}`)
                    .text(`Time : ${payload.created_at}`)
                    .text('')
                    .align('ct')
                    .image(qrImage, 's8', (imageError) => {
                        if (imageError) {
                            reject(imageError);
                            return;
                        }

                        printer
                            .align('lt')
                            .feed(2)
                            .cut()
                            .close(() => resolve());
                    });
            } catch (error) {
                reject(error);
            }
        });
    } catch (error) {
        await closePrinter(printer);
        throw error;
    }
}

app.get('/health', (_req, res) => {
    res.json({ ok: true, service: 'print-service', printer_model: PRINTER_MODEL });
});

app.post('/print', async (req, res) => {
    try {
        const payload = normalizePayload(req.body);
        const validationError = validatePayload(payload);

        if (validationError) {
            return res.status(422).json({
                ok: false,
                error: validationError,
                printer_model: PRINTER_MODEL,
            });
        }

        const device = await getPrinter();

        if (!device) {
            console.error('[Print] Print failure: printer not detected');
            return res.status(500).json({
                ok: false,
                error: 'Printer not detected',
                printer_model: PRINTER_MODEL,
            });
        }

        try {
            await printReceipt(device, payload);
            console.log('[Print] Print success');
            return res.json({
                ok: true,
                message: 'Receipt printed successfully',
                printer_model: PRINTER_MODEL,
            });
        } catch (error) {
            console.error('[Print] Print failure:', error.message);
            return res.status(500).json({
                ok: false,
                error: 'Print failed',
                details: error.message,
                printer_model: PRINTER_MODEL,
            });
        }
    } catch (error) {
        console.error('[Print] Unexpected failure:', error.message);
        return res.status(500).json({
            ok: false,
            error: 'Unexpected server error',
            details: error.message,
            printer_model: PRINTER_MODEL,
        });
    }
});

process.on('uncaughtException', (error) => {
    console.error('[Process] Uncaught exception:', error);
});

process.on('unhandledRejection', (reason) => {
    console.error('[Process] Unhandled rejection:', reason);
});

app.listen(PORT, () => {
    console.log(`Print service running at http://127.0.0.1:${PORT}`);
});
