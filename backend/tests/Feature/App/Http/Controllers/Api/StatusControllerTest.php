<?php

namespace Tests\Feature\App\Http\Controllers\Api;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

class StatusControllerTest extends TestCase
{
    #[Test]
    #[Group('api_status')]
    public function root_route_redirects_to_api()
    {
        $response = $this->get('/');
        
        $response->assertRedirect('/api');
    }

    #[Test]
    #[Group('api_status')]
    public function api_status_returns_expected_json()
    {
        $response = $this->getJson('/api');
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'error_code',
                'message',
                'data' => [
                    'version',
                    'status',
                    'environment',
                    'debug',
                    'name',
                ]
            ]);
    }
}
