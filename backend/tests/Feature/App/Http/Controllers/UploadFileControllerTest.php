<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\DataProvider;

class UploadFileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->user = User::factory()->create();
    }

    #[Test]
    #[Group('file_upload')]
    public function it_rejects_requests_without_any_files(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/files', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'error_code',
                'message',
                'errors' => [
                    'file'
                ]
            ]);
    }

    #[Test]
    #[Group('file_upload')]
    public function it_rejects_files_exceeding_max_size(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 5000); // 5MB, assuming max is 4MB

        $response = $this->actingAs($this->user)->postJson('/api/files', [
            'file' => [$file]
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'error_code',
                'message',
                'errors' => [
                    'file'
                ]
            ]);
    }

    public static function invalidFileTypesProvider(): array
    {
        return [
            'epub' => ['document.epub', 'application/epub+zip'],
            'video' => ['video.mp4', 'video/mp4'],
            'audio' => ['audio.mp3', 'audio/mpeg'],
            'compressed' => ['archive.zip', 'application/zip'],
        ];
    }

    #[Test]
    #[Group('file_upload')]
    #[DataProvider('invalidFileTypesProvider')]
    public function it_rejects_invalid_file_types(string $filename, string $mimeType): void
    {
        $file = UploadedFile::fake()->create($filename, 5000, $mimeType);

        $response = $this->actingAs($this->user)->postJson('/api/files', [
            'file' => $file
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'error_code',
                'message',
                'errors' => [
                    'file'
                ]
            ]);
    }

    public static function validFileTypesProvider(): array
    {
        return [
            'pdf' => ['document.pdf', 'application/pdf'],
            'image' => ['image.jpg', 'image/jpg'],
            'png' => ['document.png', 'image/png'],
        ];
    }

    #[Test]
    #[Group('file_upload')]
    #[DataProvider('validFileTypesProvider')]
    public function it_uploads_valid_files(string $filename, string $mimeType): void
    {
        Storage::fake('local');
        $file = UploadedFile::fake()->create($filename, 4 * 1024, $mimeType);

        $response = $this->actingAs($this->user)->postJson('/api/files', [
            'file' => $file
        ]);

        $response->assertStatus(202) // always queued even though these are just small files
            ->assertJsonStructure([
                'success',
                'error_code',
                'message',
                'data' => [
                    'file_id',
                    'url'
                ],
            ]);
    }
}
