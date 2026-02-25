<?php

namespace App\Services;

use App\Models\Queue;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class PrintService
{
    public function printQueueReceipt(Queue $queue): array
    {
        $queue->loadMissing('serviceCategory');

        $endpoint = rtrim((string) config('services.print_service.url'), '/').'/print';
        $timeout = (int) config('services.print_service.timeout', 5);

        $payload = [
            'queue_number' => $queue->queue_number,
            'service_name' => $queue->serviceCategory?->name ?? 'N/A',
            'created_at' => now()->format('Y-m-d h:i A'),
            'qr_code' => route('public.queue.show', ['queue_number' => $queue->queue_number]),
        ];

        try {
            $response = Http::timeout($timeout)->post($endpoint, $payload);
        } catch (ConnectionException $e) {
            return [
                'ok' => false,
                'status' => 503,
                'message' => 'Print service offline or unreachable.',
                'error' => $e->getMessage(),
            ];
        }

        if (! $response->successful()) {
            return [
                'ok' => false,
                'status' => $response->status(),
                'message' => $response->json('message') ?: 'Printing failed at local print service.',
                'error' => $response->body(),
            ];
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => $response->json('message') ?: 'Receipt printed successfully',
            'data' => $response->json(),
        ];
    }
}
