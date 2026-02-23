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
            ['name' => 'Tuition Payment', 'prefix' => 'T', 'description' => 'Tuition and payment processing', 'avg_service_seconds' => 300],
            ['name' => 'Clearance', 'prefix' => 'C', 'description' => 'Clearance processing', 'avg_service_seconds' => 240],
            ['name' => 'Enrollment', 'prefix' => 'E', 'description' => 'Enrollment and registration', 'avg_service_seconds' => 420],
            ['name' => 'Inquiries', 'prefix' => 'I', 'description' => 'General inquiries and information', 'avg_service_seconds' => 180],
            ['name' => 'Others', 'prefix' => 'O', 'description' => 'Other services', 'avg_service_seconds' => 240],
        ];

        foreach ($categories as $c) {
            ServiceCategory::updateOrCreate(
                ['prefix' => $c['prefix']], 
                $c
            );
        }

        // create 3 cashier windows
        $windows = ['Window 1', 'Window 2', 'Window 3'];

        foreach ($windows as $w) {
            CashierWindow::firstOrCreate(['name' => $w], ['active' => true]);
        }
    }
}
