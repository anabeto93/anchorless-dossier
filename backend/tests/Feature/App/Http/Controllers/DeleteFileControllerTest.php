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

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->file = FileMetadata::factory()->create(['user_id' => $this->user->id]);
    }

    #[Test]
    #[Group('delete_file')]
    public function test_user_can_delete_owned_file(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/files/{$this->file->file_id}");

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
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/files/{$nonExistentId}");

        $response->assertStatus(200);
    }

    #[Test]
    #[Group('delete_file')]
    public function test_user_cannot_delete_file_they_do_not_own(): void
    {
        $otherUser = User::factory()->create();
        $response = $this->actingAs($otherUser)
            ->deleteJson("/api/files/{$this->file->file_id}");

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
