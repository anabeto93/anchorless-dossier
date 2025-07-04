<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\ApiResponse;
use App\DTOs\StoreFileMetadataDTO;
use App\Services\FileManagementService;
use App\Models\FileMetadata;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;
use Illuminate\Support\Facades\Bus;
use App\Jobs\DeleteFileJob;

class FileManagementServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Bus::fake();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    #[Test]
    #[Group('services')]
    #[Group('file_management_service')]
    #[Group('store_metadata')]
    public function it_stores_file_metadata_successfully(): void
    {
        $service = new FileManagementService();
        $dto = new StoreFileMetadataDTO(
            fileId: 'doc_123',
            name: 'document.pdf',
            size: 1024,
            mimeType: 'application/pdf',
            userId: $this->user->id
        );

        $response = $service->storeMetadata($dto);

        $this->assertInstanceOf(ApiResponse::class, $response);
        $this->assertTrue($response->success);
        $this->assertEquals(201, $response->errorCode);
        $this->assertEquals('File metadata stored successfully', $response->message);
        $this->assertArrayHasKey('file_id', $response->data);
        
        // Verify database record
        $this->assertDatabaseHas('file_metadata', [
            'file_id' => 'doc_123',
            'name' => 'document.pdf'
        ]);
    }

    #[Test]
    #[Group('services')]
    #[Group('file_management_service')]
    #[Group('store_metadata')]
    public function it_handles_database_failure_gracefully_storing_metadata(): void
    {
        FileMetadata::creating(function ($model) {
            throw new \Exception('Database connection failed');
        });
        $service = new FileManagementService();
        $dto = new StoreFileMetadataDTO(
            fileId: 'doc_123',
            name: 'document.pdf',
            size: 1024,
            mimeType: 'application/pdf',
            userId: $this->user->id
        );

        $response = $service->storeMetadata($dto);

        $this->assertFalse($response->success);
        $this->assertEquals(500, $response->errorCode);
        $this->assertEquals('Failed to store file metadata', $response->message);
        $this->assertArrayHasKey('error', $response->errors);
    }

    #[Test]
    #[Group('services')]
    #[Group('file_management_service')]
    #[Group('store_metadata')]
    public function it_rejects_request_when_user_does_not_exist(): void
    {

        $service = new FileManagementService();
        $dto = new StoreFileMetadataDTO(
            fileId: 'doc_123',
            name: 'document.pdf',
            size: 1024,
            mimeType: 'application/pdf',
            userId: 999
        );

        $response = $service->storeMetadata($dto);

        $this->assertFalse($response->success);
        $this->assertEquals(400, $response->errorCode);
        $this->assertEquals('User not found', $response->message);
        $this->assertEmpty($response->errors);
    }

    #[Test]
    #[Group('services')]
    #[Group('file_management_service')]
    #[Group('store_metadata')]
    public function it_returns_error_when_storing_duplicate_file_id(): void
    {
        // First store a metadata record
        $service = new FileManagementService();
        $dto1 = new StoreFileMetadataDTO(
            fileId: 'doc_123',
            name: 'document.pdf',
            size: 1024,
            mimeType: 'application/pdf',
            userId: $this->user->id
        );
        $service->storeMetadata($dto1);

        // Try to store another with the same file ID
        $dto2 = new StoreFileMetadataDTO(
            fileId: 'doc_123',
            name: 'document2.pdf',
            size: 2048,
            mimeType: 'application/pdf',
            userId: $this->user->id
        );
        $response = $service->storeMetadata($dto2);

        $this->assertFalse($response->success);
        $this->assertEquals(400, $response->errorCode);
        $this->assertEquals('Duplicate file ID or other constraint violation', $response->message);
        $this->assertArrayHasKey('error', $response->errors);
        $this->assertStringContainsString('Integrity constraint', $response->errors['error']);
    }

    #[Test]
    #[Group('services')]
    #[Group('file_management_service')]
    #[Group('retrieve_metadata')]
    public function it_retrieves_file_metadata_successfully(): void
    {
        $service = new FileManagementService();
        $dto = new StoreFileMetadataDTO(
            fileId: 'doc_123',
            name: 'document.pdf',
            size: 1024,
            mimeType: 'application/pdf',
            userId: $this->user->id
        );
        $service->storeMetadata($dto);

        $response = $service->getMetadata('doc_123');

        $this->assertTrue($response->success);
        $this->assertEquals(200, $response->errorCode);
        $this->assertEquals('File metadata retrieved successfully', $response->message);
        $this->assertArrayHasKey('file_id', $response->data);
        $this->assertEquals('doc_123', $response->data['file_id']);
    }

    #[Test]
    #[Group('services')]
    #[Group('file_management_service')]
    #[Group('retrieve_metadata')]
    public function it_returns_error_when_retrieving_nonexistent_file(): void
    {
        $service = new FileManagementService();
        $response = $service->getMetadata('non_existent_id');

        $this->assertFalse($response->success);
        $this->assertEquals(404, $response->errorCode);
        $this->assertEquals('File metadata not found', $response->message);
        $this->assertArrayHasKey('error', $response->errors);
        $this->assertEquals('The requested file metadata could not be found', $response->errors['error']);
    }

    #[Test]
    #[Group('services')]
    #[Group('file_management_service')]
    #[Group('delete_metadata')]
    public function it_returns_success_on_file_deletion(): void
    {
        $service = new FileManagementService();
        $dto = new StoreFileMetadataDTO(
            fileId: 'doc_123',
            name: 'document.pdf',
            size: 1024,
            mimeType: 'application/pdf',
            userId: $this->user->id
        );
        $service->storeMetadata($dto);

        $response = $service->deleteMetadata('doc_123');

        $this->assertTrue($response->success);
        $this->assertEquals(200, $response->errorCode);
        $this->assertEquals('File metadata deleted successfully', $response->message);
        Bus::assertDispatched(DeleteFileJob::class, function ($job) {
            return $job->fileId === 'doc_123';
        });
    }

    #[Test]
    #[Group('services')]
    #[Group('file_management_service')]
    #[Group('delete_metadata')]
    public function it_returns_success_even_when_file_metadata_does_not_exist(): void
    {
        $service = new FileManagementService();
        $response = $service->deleteMetadata('non_existent_id');

        $this->assertTrue($response->success);
        $this->assertEquals(200, $response->errorCode); // if 204 is used, no payload would be returned to the frontend
        $this->assertEquals('File metadata deleted successfully', $response->message);
        Bus::assertDispatched(DeleteFileJob::class, function ($job) {
            return $job->fileId === 'non_existent_id';
        });
    }
}
