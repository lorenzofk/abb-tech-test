<?php

namespace Tests\Feature\Application;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->actingAs($this->user);
        $this->assertAuthenticated();
    }

    public function test_should_return_an_empty_response_when_no_applications_are_found(): void
    {
        $response = $this->sendRequest();

        $response->assertSuccessful();
        $response->assertJson(['total' => 0]);
    }

    /**
     * Send the GET request to the API endpoint.
     */
    private function sendRequest(?string $planType = null): TestResponse
    {
        $queryParams = $planType ? ['planType' => $planType] : [];

        return $this->getJson(route('api.applications', $queryParams));
    }
}

