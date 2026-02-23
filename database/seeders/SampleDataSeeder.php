<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use App\Models\CashierWindow;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $categories = [
            ['name' => 'Payments', 'description' => 'Tuition and fees', 'avg_service_seconds' => 300],
            ['name' => 'Inquiries', 'description' => 'General inquiries', 'avg_service_seconds' => 180],
            ['name' => 'Enrollment', 'description' => 'Enrollment processing', 'avg_service_seconds' => 420],
        ];

        foreach ($categories as $c) {
            ServiceCategory::firstOrCreate(['name' => $c['name']], $c);
        }

        // create 3 cashier windows
        $windows = ['Window 1', 'Window 2', 'Window 3'];

        foreach ($windows as $w) {
            CashierWindow::firstOrCreate(['name' => $w], ['active' => true]);
        }
    }
}
