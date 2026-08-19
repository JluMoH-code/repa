<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Тестовый администратор (вход через общий /login → попадает в админку).
        User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'test@test.com',
            'role' => UserRole::Admin,
        ]);

        $this->call(CatalogSeeder::class);
    }
}
