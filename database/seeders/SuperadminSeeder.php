<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        // Create superadmin user
        User::firstOrCreate(
            ['email' => 'superadmin@optimum-race.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Superadmin,
                'organizer_id' => null,
            ]
        );

        $this->command->info('Superadmin created: superadmin@optimum-race.test / password');
    }
}
