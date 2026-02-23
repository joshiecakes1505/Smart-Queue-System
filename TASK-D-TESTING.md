# Task D: Display/Public Polling - Testing Guide

## ✅ COMPONENTS IMPLEMENTED

### 1. Display Controller & Routes

- ✓ `Display/DisplayController@data()` - Fetches all active windows with current queue + waiting queue list
- ✓ Returns: windows (with current serving), next_queues (top 5 waiting), timestamp
- ✓ Route: `GET /display/data` (polled every 5 seconds)
- ✓ Page: `GET /display` renders Display/Board.vue

### 2. Display Board (Full-Screen TV Display)

- ✓ Real-time polling every 5 seconds from `/display/data`
- ✓ Dark theme optimized for retail display screens
- ✓ Shows 3-window grid with:
    - Window name, assigned staff
    - Currently serving queue number + client name
    - Service category
- ✓ Shows next 5 queues in waiting area
- ✓ Live clock update

### 3. Public Queue Controller & Routes

- ✓ `PublicQueueController@liveView()` - Returns windows + waiting queues (for kiosk displays)
- ✓ `PublicQueueController@getQueueData($queueNumber)` - NEW: Returns position + ETA for specific queue
    - Calculates position based on created_at ordering
    - Calculates ETA from queue ahead × avg_service_seconds
- ✓ `PublicQueueController@showQueueByNumber()` - Shows QR tracking page
- ✓ Routes:
    - `GET /public/live` → liveView (for public displays)
    - `GET /public/queue/{queue_number}` → tracking page
    - `GET /api/queue/{queue_number}/status` → queue status data

### 4. Queue Status Tracking Page (Public)

- ✓ Displays queue number prominently
- ✓ Shows QR code (300x300 from /qr/{queueNumber})
- ✓ Live polling for:
    - Queue position (1st, 2nd, 3rd, etc.)
    - Estimated wait time (calculated from ETA)
    - Current status (waiting, called, completed, skipped)
    - All windows status (which window is serving what queue)
- ✓ Status-based messaging (e.g., "Being served at Window 2")
- ✓ Beautiful card UI with status badges

---

## 🧪 LOCAL TESTING CHECKLIST

### Prerequisites

- [ ] Laravel server running: `php artisan serve --port=8000`
- [ ] Database seeded: `php artisan db:seed --force`
- [ ] Frontend built: `npm run build`

### Test Scenario 1: Queue Creation (baseline)

1. [ ] Open http://localhost:8000
2. [ ] Login with `admin@bec.edu.ph` / `password`
3. [ ] Navigate to `/frontdesk/queues`
4. [ ] Fill form:
    - Service Category: **Payments**
    - Client Name: **John Doe**
    - Phone: **09123456789**
    - Notes: **Test queue**
5. [ ] Expected output:
    - See success screen with queue number (e.g., **BEC-2026-0001**)
    - See QR code image (300x300)
    - See "Create Another Queue" button

### Test Scenario 2: Create Multiple Queues

1. [ ] Create 3-5 more queues in the same service:
    - Jane Smith → BEC-2026-0002
    - Miguel Santos → BEC-2026-0003
    - Maria Cruz → BEC-2026-0004
    - Anna Reyes → BEC-2026-0005
2. [ ] Verify queue numbers are sequential
3. [ ] Verify all QR codes generate successfully

### Test Scenario 3: Display Board (Full-Screen TV)

1. [ ] Open http://localhost:8000/display in a new tab/window
2. [ ] Expected layout:
    ```
    ┌─ Queue Display System │ 12:51:14 PM ─┐
    ├─────────────────────────────────────┤
    │ Window 1  │ Window 2  │ Window 3     │
    │ ────────  │ ────────  │ ────────     │
    │ Now: —    │ Now: —    │ Now: —       │
    │ (Idle)    │ (Idle)    │ (Idle)       │
    ├─────────────────────────────────────┤
    │ Next in Queue:                       │
    │ [BEC-2026-0001] │ [BEC-2026-0002] │ │
    │ John Doe        │ Jane Smith      │ │
    │ Payments        │ Payments        │ │
    └─────────────────────────────────────┘
    ```
3. [ ] Open browser console (F12) → Network tab
4. [ ] Watch `/display/data` requests happen every 5 seconds
5. [ ] Content updates in real-time

### Test Scenario 4: Public Queue Tracking (QR Landing Page)

1. [ ] Scan QR code from CreateQueue.vue success screen
    - OR manually visit: `http://localhost:8000/public/queue/BEC-2026-0001`
2. [ ] Expected display:
    ```
    ┌─────────────────────────────────────┐
    │ Your Queue Number                   │
    │         BEC-2026-0001               │
    ├─────────────────────────────────────┤
    │ [QR Code Image Here]                │
    ├─────────────────────────────────────┤
    │ Being served at Cashier             │  ← dynamic status badge
    │ Position: 1st                       │
    │ Estimated Wait: Next                │
    │                                     │
    │ Windows Status:                     │
    │ Window 1: — (Idle)                  │
    │ Window 2: — (Idle)                  │
    │ Window 3: — (Idle)                  │
    └─────────────────────────────────────┘
    ```
3. [ ] Verify position updates every 5 seconds
4. [ ] Open browser console → Network tab
5. [ ] Watch `/api/queue/{queue_number}/status` requests every 5 seconds

### Test Scenario 5: Real-Time Position Updates

1. [ ] Keep QueueStatus page open (from Scenario 4)
2. [ ] Login as **cashier** user in another tab/window (if available)
    - OR manually call cashier API endpoints with Postman
3. [ ] Call next queue: `POST /cashier/call-next` with queue ID = 1
4. [ ] Expected in QueueStatus page:
    - Position changes (next queue moves up)
    - Estimated wait decreases
    - Window status updates to show called queue
5. [ ] Mark as complete: `POST /cashier/{queue}/complete`
6. [ ] Expected:
    - Queue disappears from waiting list
    - Next position moves up for all queues
    - Display board updates automatically

### Test Scenario 6: Cross-Tab Synchronization

1. [ ] Open **4 tabs**:
    - Tab A: Display Board (`/display`)
    - Tab B: Public Kiosk (`/public/live`)
    - Tab C: Queue Status (`/public/queue/BEC-2026-0001`)
    - Tab D: Cashier (`/cashier`)
2. [ ] In Tab D: Call next → Complete → Call next
3. [ ] Watch Tabs A, B, C all update simultaneously
4. [ ] Verify no manual refresh needed

### Test Scenario 7: Mobile Responsiveness

1. [ ] Open QueueStatus page on mobile device (or Chrome DevTools mobile view)
2. [ ] Verify:
    - Queue number is readable
    - QR code is properly sized
    - Position/ETA are visible
    - No text overflow
3. [ ] Tap on page → should not need to zoom

---

## 📊 EXPECTED API RESPONSES

### GET /display/data

```json
{
    "windows": [
        {
            "id": 1,
            "name": "Window 1",
            "assigned_user": "John Smith",
            "current": {
                "queue_number": "BEC-2026-0001",
                "client_name": "John Doe",
                "service_category": "Payments"
            }
        }
    ],
    "next_queues": [
        {
            "queue_number": "BEC-2026-0002",
            "client_name": "Jane Smith",
            "service_category": "Payments"
        }
    ],
    "timestamp": "2026-02-23T12:51:14+00:00"
}
```

### GET /api/queue/{queue_number}/status

```json
{
    "queue_number": "BEC-2026-0001",
    "status": "waiting",
    "client_name": "John Doe",
    "service_category": "Payments",
    "position": 1,
    "eta_minutes": 5,
    "created_at": "2026-02-23T12:51:00+00:00",
    "cashier_window": null
}
```

### GET /public/live

```json
{
    "windows": [
        {
            "id": 1,
            "name": "Window 1",
            "assigned_user": "John Smith",
            "current": {
                "queue_number": "BEC-2026-0001",
                "client_name": "John Doe",
                "service_category": "Payments"
            }
        }
    ],
    "next": [
        {
            "queue_number": "BEC-2026-0002",
            "client_name": "Jane Smith",
            "service_category": "Payments"
        }
    ],
    "timestamp": "2026-02-23T12:51:14+00:00"
}
```

---

## 🎯 NEXT STEPS AFTER TESTING

Once you've verified the Display/Public polling works:

### Print Feature (Task F)

1. Add print button to QueueStatus page
2. Create print-optimized template for thermal printer
3. Generate PDF with queue number + QR code
4. Send to thermal printer via HTTP API or local print queue

### Admin Reports (Task E)

1. Implement daily metrics dashboard
2. Show KPIs: total served, avg service time, busiest times
3. Detailed queue logs + performance analytics

---

## ⚠️ TROUBLESHOOTING

**Issue**: Display Board not updating  
**Solution**: Check browser console for 404 errors on `/display/data` → verify route is registered

**Issue**: QR code not loading  
**Solution**: Check that queue_number path parameter is correct → verify external QR API is accessible

**Issue**: Position not calculating correctly  
**Solution**: Check database → verify created_at timestamps are in correct order

**Issue**: Polling not starting  
**Solution**: Check usePolling composable in Composables/usePolling.js → verify it's calling the fetch function

---
