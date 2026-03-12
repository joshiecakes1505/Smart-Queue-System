<?php

namespace Tests\Feature\Admin;

use App\Models\Queue;
use App\Models\Role;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_report_includes_weekday_trend_counts_in_monday_to_friday_order(): void
    {
        $adminRole = Role::create([
            'name' => 'admin',
            'description' => 'Administrator',
        ]);

        /** @var User $admin */
        $admin = User::factory()->createOne([
            'role_id' => $adminRole->id,
        ]);

        $category = ServiceCategory::create([
            'name' => 'Payments',
            'prefix' => 'P',
            'description' => 'Payment services',
            'avg_service_seconds' => 300,
        ]);

        $weekStart = Carbon::parse('2026-03-12')->startOfWeek();

        $this->createQueueAt($category->id, 'P-001', $weekStart->copy(), 'Monday 1');
        $this->createQueueAt($category->id, 'P-002', $weekStart->copy()->addHours(1), 'Monday 2');
        $this->createQueueAt($category->id, 'P-003', $weekStart->copy()->addHours(2), 'Monday 3');
        $this->createQueueAt($category->id, 'P-004', $weekStart->copy()->addDay(), 'Tuesday 1');
        $this->createQueueAt($category->id, 'P-005', $weekStart->copy()->addDays(3), 'Thursday 1');
        $this->createQueueAt($category->id, 'P-006', $weekStart->copy()->addDays(3)->addHours(1), 'Thursday 2');
        $response = $this->actingAs($admin, 'admin')->get(route('admin.reports.daily', [
            'date' => '2026-03-12',
            'period' => 'monthly',
        ], false));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Reports/Daily')
            ->where('metrics.selected_period', 'monthly')
            ->has('metrics.weekday_trend', 5)
            ->where('metrics.weekday_trend.0.day', 'Monday')
            ->where('metrics.weekday_trend.0.count', 3)
            ->where('metrics.weekday_trend.1.day', 'Tuesday')
            ->where('metrics.weekday_trend.1.count', 1)
            ->where('metrics.weekday_trend.2.day', 'Wednesday')
            ->where('metrics.weekday_trend.2.count', 0)
            ->where('metrics.weekday_trend.3.day', 'Thursday')
            ->where('metrics.weekday_trend.3.count', 2)
            ->where('metrics.weekday_trend.4.day', 'Friday')
            ->where('metrics.weekday_trend.4.count', 0)
        );
    }

    private function createQueueAt(int $serviceCategoryId, string $queueNumber, Carbon $createdAt, string $clientName): void
    {
        $queue = Queue::create([
            'queue_number' => $queueNumber,
            'service_category_id' => $serviceCategoryId,
            'status' => Queue::STATUS_WAITING,
            'client_name' => $clientName,
            'client_type' => Queue::CLIENT_TYPE_STUDENT,
        ]);

        $queue->timestamps = false;
        $queue->created_at = $createdAt;
        $queue->updated_at = $createdAt;
        $queue->save();
    }
}