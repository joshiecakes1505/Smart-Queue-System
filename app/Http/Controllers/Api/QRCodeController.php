<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Queue;
use Illuminate\Http\Request;

class QRCodeController extends Controller
{
    /**
     * Generate QR code for a queue number.
     * Returns SVG QR code that links to the public queue tracking page.
     */
    public function generate($queueNumber)
    {
        $queue = Queue::where('queue_number', $queueNumber)->firstOrFail();
        
        // Generate QR code URL pointing to public queue status page
        $qrUrl = route('public.queue.show', ['queue_number' => $queueNumber]);
        
        // Use external QR API for MVP (qr-server.com)
        // Later: replace with local QR generation using bacon/bacon-qr-code or similar
        return response()->redirectTo(
            "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrUrl)
        );
    }

    /**
     * Get QR code data as JSON (SVG or URL).
     */
    public function data($queueNumber)
    {
        Queue::where('queue_number', $queueNumber)->firstOrFail();
        
        $qrUrl = route('public.queue.show', ['queue_number' => $queueNumber]);
        $qrImageUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrUrl);
        
        return response()->json([
            'queue_number' => $queueNumber,
            'qr_url' => $qrUrl,
            'qr_image_url' => $qrImageUrl,
        ]);
    }
}
