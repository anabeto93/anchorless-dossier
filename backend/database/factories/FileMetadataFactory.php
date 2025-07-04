<?php

namespace Database\Factories;

use App\Models\FileMetadata;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FileMetadata>
 */
class FileMetadataFactory extends Factory
{
    protected $model = FileMetadata::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'file_id' => $this->faker->unique()->uuid,
            'name' => $this->faker->word . '.' . $this->faker->fileExtension,
            'size' => $this->faker->numberBetween(100, 10000),
            'mime_type' => $this->faker->mimeType,
            'user_id' => \App\Models\User::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
