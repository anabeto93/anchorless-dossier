<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('secret'),
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $this->command->info('User credentials: email=test@example.com, password=secret');
        $this->command->info('Test user created with token: ' . $token);

        $this->call(FileMetadataSeeder::class);
    }
}
