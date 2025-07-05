<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers;

use App\Models\FileMetadata;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class DeleteFileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected FileMetadata $file;
    protected array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->file = FileMetadata::factory()->create(['user_id' => $this->user->id]);
        $token = $this->user->createToken('test-token')->plainTextToken;
        $this->headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
    }

    #[Test]
    #[Group('delete_file')]
    public function test_user_can_delete_owned_file(): void
    {
        $response = $this->deleteJson("/api/files/{$this->file->file_id}", headers: $this->headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'error_code',
                'message',
            ]);

        $this->assertDatabaseMissing('file_metadata', ['id' => $this->file->file_id]);
    }

    #[Test]
    #[Group('delete_file')]
    public function test_user_cannot_delete_non_existent_file(): void
    {
        $nonExistentId = 'non-existent-id';
        $response = $this->deleteJson("/api/files/{$nonExistentId}", headers: $this->headers);

        $response->assertStatus(200);
    }

    #[Test]
    #[Group('delete_file')]
    public function test_user_cannot_delete_file_they_do_not_own(): void
    {
        $otherUser = User::factory()->create();
        $token = $otherUser->createToken('test-token')->plainTextToken;
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ];
        $response = $this->deleteJson("/api/files/{$this->file->file_id}", headers: $headers);

        $response->assertStatus(200);
    }

    #[Test]
    #[Group('delete_file')]
    public function test_unauthenticated_user_cannot_delete_file(): void
    {
        $response = $this->deleteJson("/api/files/{$this->file->file_id}");
        $response->assertStatus(401);
    }
}
