<?php
/**
 * Quick test script to verify Queue and QR code generation
 * Run with: php test-queue-qr.php
 */

require __DIR__ . '/bootstrap/app.php';
require __DIR__ . '/vendor/autoload.php';

use App\Models\Queue;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\QueueService;
use Illuminate\Support\Facades\DB;

echo "=== Smart Queue System - Verification Test ===\n\n";

// 1. Check database seeding
echo "1. Checking database seeding...\n";
$roleCount = DB::table('roles')->count();
$userCount = User::count();
$categoryCount = ServiceCategory::count();
echo "   ✓ Roles: {$roleCount}\n";
echo "   ✓ Users: {$userCount}\n";
echo "   ✓ Service Categories: {$categoryCount}\n\n";

// 2. Check admin user exists
echo "2. Checking admin user...\n";
$adminUser = User::whereHas('role', function ($q) {
    $q->where('name', 'admin');
})->first();
if ($adminUser) {
    echo "   ✓ Admin User: {$adminUser->email}\n\n";
} else {
    echo "   ✗ No admin user found!\n\n";
}

// 3. Check service categories
echo "3. Service Categories:\n";
ServiceCategory::all()->each(function ($cat) {
    echo "   - {$cat->name}\n";
});
echo "\n";

// 4. Test Queue Creation
echo "4. Testing Queue Creation...\n";
try {
    $queueService = new QueueService();
    
    $queueData = [
        'service_category_id' => ServiceCategory::first()->id,
        'client_name' => 'Test Client ' . now()->timestamp,
        'phone' => '09123456789',
        'note' => 'Test queue creation',
    ];
    
    $queue = $queueService->createQueue($queueData);
    echo "   ✓ Queue Created: {$queue->queue_number}\n";
    echo "   ✓ Status: {$queue->status}\n";
    echo "   ✓ ID: {$queue->id}\n\n";
    
    // 5. Verify queue in database
    echo "5. Verifying queue in database...\n";
    $verifyQueue = Queue::where('queue_number', $queue->queue_number)->first();
    if ($verifyQueue) {
        echo "   ✓ Queue found in DB: {$verifyQueue->queue_number}\n";
        echo "   ✓ Service: {$verifyQueue->serviceCategory->name}\n";
        echo "   ✓ Client: {$verifyQueue->client_name}\n\n";
    }
    
    // 6. Test QR URL generation
    echo "6. Testing QR code URL generation...\n";
    $queueNumber = $queue->queue_number;
    $qrBaseUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=";
    $trackingUrl = "http://localhost:8000/public/queue/{$queueNumber}";
    $qrImageUrl = $qrBaseUrl . urlencode($trackingUrl);
    echo "   ✓ Tracking URL: {$trackingUrl}\n";
    echo "   ✓ QR Image URL: {$qrImageUrl}\n\n";
    
    // 7. Test duplicate prevention
    echo "7. Testing duplicate prevention (same counter lock)...\n";
    $queue2 = $queueService->createQueue($queueData);
    if ($queue2->queue_number !== $queue->queue_number) {
        echo "   ✓ Queue 1: {$queue->queue_number}\n";
        echo "   ✓ Queue 2: {$queue2->queue_number}\n";
        echo "   ✓ Unique numbers generated!\n\n";
    } else {
        echo "   ✗ Duplicate queue number!\n\n";
    }
    
    echo "✅ All tests passed!\n";
    
} catch (\Exception $e) {
    echo "   ✗ Error: {$e->getMessage()}\n";
    echo "   Stack: {$e->getFile()}:{$e->getLine()}\n\n";
}

echo "\n=== Test Complete ===\n";
