<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use Illuminate\Support\Facades\Route;

class FileUrlHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Define the route needed for URL generation
        Route::get('/api/files/{file}/preview', function () {
            return 'Preview';
        })->name('files.preview');
    }

    #[Test]
    #[Group('helpers')]
    public function it_generates_signed_url_with_correct_structure()
    {
        $fileId = 'test-file-id';
        $url = generateSignedFilePreviewUrl($fileId);

        $this->assertStringContainsString('/api/files/test-file-id/preview', $url);
        $this->assertStringContainsString('signature=', $url);
        $this->assertStringContainsString('expires=', $url);
    }

    #[Test]
    #[Group('helpers')]
    public function it_uses_configurable_expiration_time()
    {
        config(['file.storage.preview_duration' => 120]);
        
        $url = generateSignedFilePreviewUrl('test');
        
        // Parse the query string to get the 'expires' parameter
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $params);
        
        $this->assertArrayHasKey('expires', $params);
        $expirationTime = (int) $params['expires'];
        
        $expectedExpiration = now()->addMinutes(120)->timestamp;
        
        // Allow a small difference due to execution time
        $this->assertLessThanOrEqual(5, abs($expirationTime - $expectedExpiration));
    }
}
