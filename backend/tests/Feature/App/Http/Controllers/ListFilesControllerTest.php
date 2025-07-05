<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers;

use App\Models\FileMetadata;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListFilesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $token = $this->user->createToken('test-token')->plainTextToken;
        $this->headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }

    public function test_user_can_list_files_grouped_by_type(): void
    {
        // Create files for the user
        FileMetadata::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'mime_type' => 'image/jpg',
        ]);

        FileMetadata::factory()->count(4)->create([
            'user_id' => $this->user->id,
            'mime_type' => 'application/pdf',
        ]);

        FileMetadata::factory()->count(1)->create([
            'user_id' => $this->user->id,
            'mime_type' => 'image/png',
        ]);

        $response = $this->getJson('/api/files', headers: $this->headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'error_code',
                'message',
                'data' => [
                    'grouped_files' => [
                        'JPG' => [
                            '*' => ['id', 'name', 'size', 'created_at', 'path'],
                        ],
                        'PDF' => [
                            '*' => ['id', 'name', 'size', 'created_at', 'path'],
                        ],
                        'PNG' => [
                            '*' => ['id', 'name', 'size', 'created_at', 'path'],
                        ],
                    ],
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page',
                    ],
                ],
            ]);
    }

    public function test_user_gets_empty_groups_when_no_files_exist(): void
    {
        $response = $this->getJson('/api/files', headers: $this->headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'error_code',
                'message',
                'data' => [
                    'grouped_files' => [],
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page',
                    ],
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_list_files(): void
    {
        $response = $this->getJson('/api/files');
        $response->assertUnauthorized()
            ->assertJsonStructure([
                'success',
                'error_code',
                'message',
            ]);
    }
}
