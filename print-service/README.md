# Local Print Service (Windows USB ESC/POS)

This service receives receipt data from Laravel and prints silently to a local USB ESC/POS thermal printer.

## Setup

1. Open terminal in `print-service`
2. Install deps:
    - `npm install`
3. Start service:
    - `npm run start`

Default service URL: `http://127.0.0.1:9123`

## Endpoint

### POST `/print`

Request JSON:

```json
{
    "queueId": 1,
    "queueNumber": "T-001",
    "clientName": "John Doe",
    "clientType": "student",
    "serviceCategory": "Tuition Payment",
    "printedAt": "2026-02-25 10:30 AM",
    "qrValue": "https://your-domain/public/queue/T-001"
}
```

Success response:

```json
{
    "ok": true,
    "message": "Receipt printed successfully"
}
```

Error response example:

```json
{
    "ok": false,
    "message": "Printer not connected",
    "code": "PRINTER_NOT_CONNECTED"
}
```

## Optional USB binding

Set environment variables if multiple USB printers are connected:

- `USB_VENDOR_ID`
- `USB_PRODUCT_ID`

## Health check

- `GET /health`
