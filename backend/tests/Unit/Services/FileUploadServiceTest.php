<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\ApiResponse;
use App\Jobs\ProcessFileUpload;
use App\Models\FileMetadata;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    #[Group('services')]
    #[Group('upload_service')]
    public function it_queues_all_uploads_and_returns_accepted_response(): void
    {
        Bus::fake();
        Storage::fake('local');

        $service = new FileUploadService();
        $file = UploadedFile::fake()->create('document.pdf');
        
        $response = $service->upload($this->user, $file, 'uploads');
        
        $this->assertTrue($response->success);
        $this->assertEquals(202, $response->errorCode);
        $this->assertEquals('File upload queued for processing', $response->message);
        $this->assertArrayHasKey('file_id', $response->data); // the job_id is not important to the frontend
        $this->assertArrayHasKey('url', $response->data); // to get the file, we need to use the file_id
        
        Bus::assertDispatched(ProcessFileUpload::class);
    }

    #[Test]
    #[Group('services')]
    #[Group('upload_service')]
    public function it_verifies_file_storage_after_processing(): void
    {
        Storage::fake('local');
        
        $service = new FileUploadService();
        $file = UploadedFile::fake()->create('document.pdf');
        
        $response = $service->upload($this->user, $file, 'uploads');

        $this->assertTrue($response->success);
        $this->assertEquals(202, $response->errorCode);
        
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');
        $file = FileMetadata::where('name', 'document.pdf')->first();
        $this->assertNotNull($file);
        $this->assertEquals($file->user_id, $this->user->id);
        $disk->assertExists($file->path);
    }

    #[Test]
    #[Group('services')]
    #[Group('upload_service')]
    public function it_rejects_files_over_max_size(): void
    {
        Bus::fake();
        $service = new FileUploadService();
        $file = UploadedFile::fake()->create('huge-file.iso', 10000); // 10MB
        
        $response = $service->upload($this->user, $file, 'uploads');
        
        $this->assertFalse($response->success);
        $this->assertEquals(413, $response->errorCode);
        $this->assertEquals('File size exceeds maximum allowed', $response->message);
        Bus::assertNotDispatched(ProcessFileUpload::class);
    }

    #[Test]
    #[Group('services')]
    #[Group('upload_service')]
    public function it_returns_error_response_if_job_dispatch_fails(): void
    {
        Bus::shouldReceive('dispatch')->andThrow(new \Exception('Queue connection failed'));
        $service = new FileUploadService();
        $file = UploadedFile::fake()->create('document.pdf');
        
        $response = $service->upload($this->user, $file, 'uploads');
        
        $this->assertFalse($response->success);
        $this->assertEquals(500, $response->errorCode);
        $this->assertEquals('File upload failed', $response->message);
        $this->assertArrayHasKey('error', $response->errors);
    }
}
