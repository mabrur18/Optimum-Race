<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // 1. Master data (tanpa foreign key)
            DisciplineSeeder::class,

            // 2. Template data
            ClassTemplateSeeder::class,

            // 3. Tenant & Users
            OrganizerSeeder::class,
            SuperadminSeeder::class,
        ]);

        $this->command->info('Database seeding completed successfully!');
        $this->command->info('');
        $this->command->info('Login credentials:');
        $this->command->info('  Superadmin: superadmin@optimum-race.test / password');
        $this->command->info('  Admin EO: admin@bks-racing.test / password');
        $this->command->info('  Staff EO: staff@bks-racing.test / password');
    }
}
