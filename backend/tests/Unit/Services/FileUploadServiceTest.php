<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\ApiResponse;
use App\Jobs\ProcessFileUpload;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class FileUploadServiceTest extends TestCase
{
    #[Test]
    #[Group('services')]
    #[Group('upload_service')]
    public function it_queues_all_uploads_and_returns_accepted_response(): void
    {
        Bus::fake();
        Storage::fake('local');
        
        $service = new FileUploadService();
        $file = UploadedFile::fake()->create('document.pdf');
        
        $response = $service->upload($file, 'uploads');
        
        $this->assertTrue($response->success);
        $this->assertEquals(202, $response->errorCode);
        $this->assertEquals('File upload queued for processing', $response->message);
        $this->assertArrayHasKey('job_id', $response->data);
        
        Bus::assertDispatched(ProcessFileUpload::class);
    }

    #[Test]
    #[Group('services')]
    #[Group('upload_service')]
    public function it_verifies_file_storage_after_processing(): void
    {
        Bus::fake();
        Storage::fake('local');
        
        $service = new FileUploadService();
        $file = UploadedFile::fake()->create('document.pdf');
        
        $response = $service->upload($file, 'uploads');

        $this->assertTrue($response->success);
        $this->assertEquals(202, $response->errorCode);
        
        // Simulate job processing
        Bus::dispatched(ProcessFileUpload::class, function ($job) {
            $job->handle();
        });
        
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');
        $disk->assertExists('uploads/document.pdf');
    }

    #[Test]
    #[Group('services')]
    #[Group('upload_service')]
    public function it_rejects_files_over_max_size(): void
    {
        Bus::fake();
        $service = new FileUploadService();
        $file = UploadedFile::fake()->create('huge-file.iso', 10000); // 10MB
        
        $response = $service->upload($file, 'uploads');
        
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
        
        $response = $service->upload($file, 'uploads');
        
        $this->assertFalse($response->success);
        $this->assertEquals(500, $response->errorCode);
        $this->assertEquals('File upload failed', $response->message);
        $this->assertArrayHasKey('error', $response->errors);
    }
}
