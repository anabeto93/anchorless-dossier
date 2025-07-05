<?php

namespace Tests\Feature\App\Http\Controllers\Api;

use Tests\TestCase;
use App\Models\FileMetadata;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class PreviewFileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    #[Group('preview')]
    public function valid_signed_url_can_preview_file()
    {
        Storage::fake();
        
        $filename = 'test.pdf';
        $path = 'files/' . $filename;
        Storage::disk('local')->put($path, 'hello there');

        $file = FileMetadata::factory()->create([
            'user_id' => $this->user->id,
            'file_id' => $filename,
            'name' => $filename,
            'size' => 1024,
            'mime_type' => 'application/pdf',
            'disk' => 'local',
            'path' => $path,
        ]);
        $url = URL::temporarySignedRoute(
            'files.preview',
            now()->addMinutes(60),
            ['file' => $file->file_id]
        );
        
        $response = $this->get($url);

        $response->assertOk();
    }

    #[Test]
    #[Group('preview')]
    public function invalid_signature_denies_preview()
    {
        $file = FileMetadata::factory()->create(['user_id' => $this->user->id]);
        $url = URL::temporarySignedRoute(
            'files.preview',
            now()->addMinutes(60),
            ['file' => $file->file_id]
        );
        
        // Tamper with the signature
        $tamperedUrl = $url . 'a';
        
        $response = $this->get($tamperedUrl);
        
        $response->assertNotFound();
    }

    #[Test]
    #[Group('preview')]
    public function expired_signed_url_denies_preview()
    {
        $file = FileMetadata::factory()->create(['user_id' => $this->user->id]);
        $url = URL::temporarySignedRoute(
            'files.preview',
            now()->subMinutes(1), // Expired
            ['file' => $file->file_id]
        );
        
        $response = $this->get($url);
        
        $response->assertNotFound(); // instead of forbidden, expired
    }

    #[Test]
    #[Group('preview')]
    public function non_existent_file_returns_not_found()
    {
        $url = URL::temporarySignedRoute(
            'files.preview',
            now()->addMinutes(60),
            ['file' => 'non-existent-id']
        );
        
        $response = $this->get($url);

        $response->assertNotFound()->assertJsonStructure([
            'success',
            'error_code',
            'message',
        ]);
    }
}
