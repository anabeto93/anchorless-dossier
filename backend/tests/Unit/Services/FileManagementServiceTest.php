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
use Illuminate\Support\Facades\Storage;

class FileManagementServiceTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        config(['file.storage.url' => '/files']);
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
            disk: config('file.storage.disk'),
            path: '/files',
            userId: $this->user->id,
            user: null
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
            disk: config('file.storage.disk'),
            path: '/files',
            userId: $this->user->id,
            user: null,
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
            userId: 999,
            user: null
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
            disk: config('file.storage.disk'),
            path: '/files',
            userId: $this->user->id,
            user: null
        );
        $service->storeMetadata($dto1);

        // Try to store another with the same file ID
        $dto2 = new StoreFileMetadataDTO(
            fileId: 'doc_123',
            name: 'document2.pdf',
            size: 2048,
            mimeType: 'application/pdf',
            disk: config('file.storage.disk'),
            path: '/files',
            userId: $this->user->id,
            user: null
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
            disk: config('file.storage.disk'),
            path: '/files',
            userId: $this->user->id,
            user: null
        );
        $service->storeMetadata($dto);

        $response = $service->getMetadata('doc_123', $this->user->id);

        $this->assertTrue($response->success);
        $this->assertEquals(200, $response->errorCode);
        $this->assertEquals('File metadata retrieved successfully', $response->message);
        $this->assertArrayHasKey('file_id', $response->data);
        $this->assertEquals('doc_123', $response->data['file_id']);
        $this->assertEquals(config('app.url') . Storage::disk('local')->url('doc_123'), $response->data['path']);
    }

    #[Test]
    #[Group('services')]
    #[Group('file_management_service')]
    #[Group('retrieve_metadata')]
    public function it_returns_error_when_retrieving_nonexistent_file(): void
    {
        $service = new FileManagementService();
        $response = $service->getMetadata('non_existent_id', $this->user->id);

        $this->assertFalse($response->success);
        $this->assertEquals(404, $response->errorCode);
        $this->assertEquals('File metadata not found', $response->message);
        $this->assertArrayHasKey('error', $response->errors);
        $this->assertEquals('The requested file metadata could not be found', $response->errors['error']);
    }

    #[Test]
    #[Group('services')]
    #[Group('file_management_service')]
    #[Group('retrieve_metadata')]
    public function it_prevents_access_to_file_not_belonging_to_user(): void
    {
        $otherUser = User::factory()->create();
        $service = new FileManagementService();
        $dto = new StoreFileMetadataDTO(
            fileId: 'doc_123',
            name: 'document.pdf',
            size: 1024,
            mimeType: 'application/pdf',
            userId: $otherUser->id,
            user: $otherUser
        );
        $service->storeMetadata($dto);
        $response = $service->getMetadata('doc_123', $this->user->id);

        $this->assertFalse($response->success);
        $this->assertEquals(404, $response->errorCode); // Do not use a 403 and hint at existence of the file.
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
            disk: config('file.storage.disk'),
            path: '/files',
            userId: $this->user->id,
            user: null
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

    #[Test]
    #[Group('services')]
    #[Group('file_management_service')]
    #[Group('list_files')]
    public function it_lists_files_grouped_by_type(): void
    {
        // Create files of different types
        FileMetadata::factory()->create([
            'user_id' => $this->user->id,
            'mime_type' => 'application/pdf'
        ]);
        FileMetadata::factory()->create([
            'user_id' => $this->user->id,
            'mime_type' => 'image/png'
        ]);
        FileMetadata::factory()->create([
            'user_id' => $this->user->id,
            'mime_type' => 'image/jpg'
        ]);
        
        $service = new FileManagementService();
        $response = $service->listFilesGroupedByType($this->user->id);
        
        $this->assertTrue($response->success);
        $this->assertEquals(200, $response->errorCode);
        $this->assertArrayHasKey('grouped_files', $response->data);
        $this->assertArrayHasKey('pagination', $response->data);
        $this->assertArrayHasKey('application/pdf', $response->data['grouped_files']);
        $this->assertArrayHasKey('image/png', $response->data['grouped_files']);
        $this->assertArrayHasKey('image/jpg', $response->data['grouped_files']);
        $this->assertArrayHasKey('current_page', $response->data['pagination']);
        $this->assertArrayHasKey('per_page', $response->data['pagination']);
        $this->assertArrayHasKey('total', $response->data['pagination']);
        $this->assertArrayHasKey('last_page', $response->data['pagination']);
    }

    #[Test]
    #[Group('services')]
    #[Group('file_management_service')]
    #[Group('list_files')]
    public function it_returns_empty_groups_when_no_files(): void
    {
        $service = new FileManagementService();
        $response = $service->listFilesGroupedByType($this->user->id);
        
        $this->assertTrue($response->success);
        $this->assertEquals(200, $response->errorCode);
        $this->assertArrayHasKey('grouped_files', $response->data);
        $this->assertArrayHasKey('pagination', $response->data);
        $this->assertEmpty($response->data['grouped_files']);
        $this->assertEquals(0, $response->data['pagination']['total']);
    }

    #[Test]
    #[Group('services')]
    #[Group('file_management_service')]
    #[Group('list_files')]
    public function it_fails_to_list_files_grouped_by_type_when_user_id_is_invalid(): void
    {
        $service = new FileManagementService();
        $response = $service->listFilesGroupedByType(999);
        
        $this->assertFalse($response->success);
        $this->assertEquals(404, $response->errorCode);
        $this->assertEquals('User not found', $response->message);
    }

    #[Test]
    #[Group('services')]
    #[Group('file_management_service')]
    #[Group('list_files')]
    public function it_fails_to_list_files_grouped_by_type_when_database_query_fails(): void
    {
        $defaultConnection = config('database.default');
        config(['database.default' => 'non_existent_conn']);

        $service = new FileManagementService();
        $response = $service->listFilesGroupedByType($this->user->id);

        $this->assertFalse($response->success);
        $this->assertEquals(500, $response->errorCode);
        $this->assertEquals('Failed to list files grouped by type', $response->message);

        config(['database.default' => $defaultConnection]);
    }
}
