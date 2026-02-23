<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $roles = ['admin' => 'Administrator', 'frontdesk' => 'Front Desk', 'cashier' => 'Cashier'];

        foreach ($roles as $key => $label) {
            Role::firstOrCreate(['name' => $key], ['description' => $label]);
        }

        // Create default test users for each role
        $roles = Role::all()->keyBy('name');

        // Admin user
        if (!User::where('email', 'admin@bec.edu.ph')->exists()) {
            User::factory()->create([
                'name' => 'BEC Admin',
                'email' => 'admin@bec.edu.ph',
                'password' => Hash::make('password'),
                'role_id' => $roles['admin']->id,
            ]);
        }

        // Frontdesk user
        if (!User::where('email', 'frontdesk@bec.edu.ph')->exists()) {
            User::factory()->create([
                'name' => 'Front Desk Staff',
                'email' => 'frontdesk@bec.edu.ph',
                'password' => Hash::make('password'),
                'role_id' => $roles['frontdesk']->id,
            ]);
        }

        // Cashier user
        if (!User::where('email', 'cashier@bec.edu.ph')->exists()) {
            User::factory()->create([
                'name' => 'Cashier Staff',
                'email' => 'cashier@bec.edu.ph',
                'password' => Hash::make('password'),
                'role_id' => $roles['cashier']->id,
            ]);
        }
    }
}
