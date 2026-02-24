<?php

namespace Tests\Feature\Public;

use App\Models\CashierWindow;
use App\Models\Queue;
use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class QueueStatusApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_status_uses_real_position_and_eta_even_with_same_second_creation(): void
    {
        $cashierRole = Role::create(['name' => 'cashier', 'description' => 'Cashier']);
        $cashierUser = User::factory()->create(['role_id' => $cashierRole->id]);

        CashierWindow::create([
            'name' => 'Window 1',
            'assigned_user_id' => $cashierUser->id,
            'active' => true,
        ]);

        $category = ServiceCategory::create([
            'name' => 'Payment',
            'prefix' => 'P',
            'description' => 'Payment Processing',
            'avg_service_seconds' => 180,
        ]);

        $first = Queue::create([
            'queue_number' => 'P-001',
            'service_category_id' => $category->id,
            'status' => Queue::STATUS_WAITING,
            'client_name' => 'First',
            'client_type' => Queue::CLIENT_TYPE_STUDENT,
        ]);

        $target = Queue::create([
            'queue_number' => 'P-002',
            'service_category_id' => $category->id,
            'status' => Queue::STATUS_WAITING,
            'client_name' => 'Target',
            'client_type' => Queue::CLIENT_TYPE_STUDENT,
        ]);

        $called = Queue::create([
            'queue_number' => 'P-003',
            'service_category_id' => $category->id,
            'status' => Queue::STATUS_CALLED,
            'client_name' => 'Called',
            'client_type' => Queue::CLIENT_TYPE_STUDENT,
        ]);

        $fixedTime = Carbon::parse('2026-02-25 10:00:00');

        foreach ([$first, $target, $called] as $queue) {
            $queue->timestamps = false;
            $queue->created_at = $fixedTime;
            $queue->updated_at = $fixedTime;
            $queue->save();
        }

        $response = $this->getJson("/api/queue/{$target->queue_number}/status");

        $response->assertOk();
        $response->assertJsonPath('position', 2);
        $response->assertJsonPath('waiting_ahead', 1);
        $response->assertJsonPath('active_called_ahead', 1);
        $response->assertJsonPath('queues_ahead', 2);
        $response->assertJsonPath('eta_minutes', 6);
        $response->assertJsonPath('estimated_served_at', fn ($value) => !empty($value));
    }

    public function test_queue_status_position_counts_global_waiting_queues_not_only_same_category(): void
    {
        $cashierRole = Role::create(['name' => 'cashier', 'description' => 'Cashier']);
        $cashierUser = User::factory()->create(['role_id' => $cashierRole->id]);

        CashierWindow::create([
            'name' => 'Window 1',
            'assigned_user_id' => $cashierUser->id,
            'active' => true,
        ]);

        $payments = ServiceCategory::create([
            'name' => 'Payments',
            'prefix' => 'P',
            'description' => 'Payment services',
            'avg_service_seconds' => 180,
        ]);

        $inquiries = ServiceCategory::create([
            'name' => 'Inquiries',
            'prefix' => 'I',
            'description' => 'Inquiry services',
            'avg_service_seconds' => 180,
        ]);

        Queue::create([
            'queue_number' => 'P-001',
            'service_category_id' => $payments->id,
            'status' => Queue::STATUS_WAITING,
            'client_name' => 'Ahead 1',
            'client_type' => Queue::CLIENT_TYPE_STUDENT,
        ]);

        Queue::create([
            'queue_number' => 'I-001',
            'service_category_id' => $inquiries->id,
            'status' => Queue::STATUS_WAITING,
            'client_name' => 'Ahead 2',
            'client_type' => Queue::CLIENT_TYPE_STUDENT,
        ]);

        $target = Queue::create([
            'queue_number' => 'P-002',
            'service_category_id' => $payments->id,
            'status' => Queue::STATUS_WAITING,
            'client_name' => 'Target',
            'client_type' => Queue::CLIENT_TYPE_STUDENT,
        ]);

        $response = $this->getJson("/api/queue/{$target->queue_number}/status");

        $response->assertOk();
        $response->assertJsonPath('position', 3);
        $response->assertJsonPath('waiting_ahead', 2);
        $response->assertJsonPath('queues_ahead', 2);
    }
}
