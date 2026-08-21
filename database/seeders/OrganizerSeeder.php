<?php

namespace Database\Seeders;

use App\Enums\OrganizerPlan;
use App\Enums\UserRole;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OrganizerSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample organizer
        $organizer = Organizer::firstOrCreate(
            ['slug' => 'bks-racing'],
            [
                'name' => 'BKS Racing Team',
                'plan' => OrganizerPlan::Premium,
                'is_active' => true,
            ]
        );

        // Create admin for this organizer
        User::firstOrCreate(
            ['email' => 'admin@bks-racing.test'],
            [
                'name' => 'Admin BKS Racing',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'organizer_id' => $organizer->id,
            ]
        );

        // Create staff for this organizer
        User::firstOrCreate(
            ['email' => 'staff@bks-racing.test'],
            [
                'name' => 'Staff BKS Racing',
                'password' => Hash::make('password'),
                'role' => UserRole::Staff,
                'organizer_id' => $organizer->id,
            ]
        );

        $this->command->info('Organizer BKS Racing created with Admin and Staff users');
    }
}
