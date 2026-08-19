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
        // Стандартный администратор (вход через общий /login → попадает в админку).
        User::factory()->create([
            'name' => 'Администратор',
            'email' => 'admin@admin.com',
            'role' => UserRole::Admin,
        ]);

        $this->call(CatalogSeeder::class);
    }
}
