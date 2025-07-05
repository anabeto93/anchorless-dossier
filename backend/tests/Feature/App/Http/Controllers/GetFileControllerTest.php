<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers;

use App\Models\FileMetadata;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class GetFileControllerTest extends TestCase
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

    #[Test]
    #[Group('fetch_owned_file')]
    public function user_can_fetch_owned_file(): void
    {
        config(['file.storage.url' => 'files']);
        /**
         * @see \App\Jobs\ProcessFileUpload::handle() line 38 for how the path is generated
         * @var FileMetadata $file
         */
        $file = FileMetadata::factory()->for($this->user)->create();

        $response = $this->getJson('/api/files/' . $file->file_id, headers: $this->headers);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'error_code',
                'message',
                'data' => [
                    'id',
                    'file_id',
                    'name',
                    'size',
                    'mime_type',
                    'user_id',
                    'created_at',
                    'updated_at',
                    'path',
                ],
            ]);

        $response->assertJsonPath('data.id', $file->id);
        $response->assertJsonPath('data.file_id', $file->file_id);
        $response->assertJsonPath('data.name', $file->name);
        $response->assertJsonPath('data.path', constructFileUrl($file));
    }

    #[Test]
    #[Group('fetch_owned_file')]
    public function user_cannot_fetch_nonexistent_file(): void
    {
        $response = $this->getJson('/api/files/invalid-id', headers: $this->headers);

        $response->assertNotFound()
            ->assertJsonStructure([
                'success',
                'error_code',
                'message',
                'errors',
            ]);
    }

    #[Test]
    #[Group('fetch_owned_file')]
    public function user_cannot_fetch_other_users_file(): void
    {
        $owner = User::factory()->create();
        $file = FileMetadata::factory()->for($owner)->create();

        $response = $this->getJson('/api/files/' . $file->file_id, headers: $this->headers);

        $response->assertNotFound() // this is security by obscurity. Instead of forbidden
            ->assertJsonStructure([
                'success',
                'error_code',
                'message',
                'errors',
            ]);
    }

    #[Test]
    #[Group('fetch_owned_file')]
    public function unauthenticated_user_cannot_fetch_file(): void
    {
        $file = FileMetadata::factory()->create();

        $response = $this->getJson('/api/files/' . $file->file_id);

        $response->assertUnauthorized()
            ->assertJsonStructure([
                'success',
                'error_code',
                'message',
            ]);
    }
}
