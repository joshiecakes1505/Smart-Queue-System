<?php

namespace Tests\Feature\Queue;

use App\Models\CashierWindow;
use App\Models\Queue;
use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\QueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

class QueueSchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_weighted_priority_serves_two_regular_then_one_priority(): void
    {
        $cashierRole = Role::create(['name' => 'cashier', 'description' => 'Cashier']);
        $cashierUser = User::factory()->create(['role_id' => $cashierRole->id]);

        $window = CashierWindow::create([
            'name' => 'Window 1',
            'assigned_user_id' => $cashierUser->id,
            'active' => true,
        ]);

        $category = ServiceCategory::create([
            'name' => 'Payment',
            'prefix' => 'P',
            'description' => 'Payment Processing',
            'avg_service_seconds' => 300,
        ]);

        $service = app(QueueService::class);

        $first = $service->createQueue([
            'service_category_id' => $category->id,
            'client_name' => 'Regular 1',
            'client_type' => 'student',
        ]);

        $second = $service->createQueue([
            'service_category_id' => $category->id,
            'client_name' => 'Regular 2',
            'client_type' => 'parent',
        ]);

        $priority = $service->createQueue([
            'service_category_id' => $category->id,
            'client_name' => 'Priority',
            'client_type' => 'senior_citizen',
        ]);

        $thirdRegular = $service->createQueue([
            'service_category_id' => $category->id,
            'client_name' => 'Regular 3',
            'client_type' => 'visitor',
        ]);

        $served1 = $service->callNext($window->id, $category->id);
        Assert::assertSame($first->id, $served1?->id);
        $service->complete($served1->id);

        $served2 = $service->callNext($window->id, $category->id);
        Assert::assertSame($second->id, $served2?->id);
        $service->complete($served2->id);

        $served3 = $service->callNext($window->id, $category->id);
        Assert::assertSame($priority->id, $served3?->id);
        $service->complete($served3->id);

        $served4 = $service->callNext($window->id, $category->id);
        Assert::assertSame($thirdRegular->id, $served4?->id);
    }

    public function test_eta_is_calculated_for_waiting_queue(): void
    {
        $cashierRole = Role::create(['name' => 'cashier', 'description' => 'Cashier']);
        $cashierUser = User::factory()->create(['role_id' => $cashierRole->id]);

        CashierWindow::create([
            'name' => 'Window A',
            'assigned_user_id' => $cashierUser->id,
            'active' => true,
        ]);

        $category = ServiceCategory::create([
            'name' => 'Inquiry',
            'prefix' => 'I',
            'description' => 'General Inquiry',
            'avg_service_seconds' => 180,
        ]);

        $service = app(QueueService::class);

        $service->createQueue([
            'service_category_id' => $category->id,
            'client_name' => 'Ahead',
            'client_type' => 'student',
        ]);

        $target = $service->createQueue([
            'service_category_id' => $category->id,
            'client_name' => 'Target',
            'client_type' => 'high_priority',
        ]);

        $target->refresh();

        $eta = $service->estimateWaitMinutes($target);

        Assert::assertNotNull($eta);
        Assert::assertIsInt($eta);
        Assert::assertGreaterThanOrEqual(0, $eta);
        Assert::assertSame(Queue::STATUS_WAITING, $target->status);
    }

    public function test_create_queue_skips_existing_queue_number_and_generates_next_available(): void
    {
        $category = ServiceCategory::create([
            'name' => 'Transactions',
            'prefix' => 'T',
            'description' => 'Transaction services',
            'avg_service_seconds' => 300,
        ]);

        Queue::create([
            'queue_number' => 'T-001',
            'service_category_id' => $category->id,
            'status' => Queue::STATUS_COMPLETED,
            'client_name' => 'Existing',
            'client_type' => Queue::CLIENT_TYPE_STUDENT,
        ]);

        $service = app(QueueService::class);

        $newQueue = $service->createQueue([
            'service_category_id' => $category->id,
            'client_name' => 'New Client',
            'client_type' => Queue::CLIENT_TYPE_STUDENT,
        ]);

        Assert::assertSame('T-002', $newQueue->queue_number);
    }
}
