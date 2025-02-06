<?php

namespace Tests\Feature\Application;

use App\Models\Application;
use App\Models\Plan;
use App\Models\User;
use Closure;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
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

    #[DataProvider('applicationsDataProvider')]
    public function test_should_return_a_paginated_response_when_applications_are_found(Closure $planFactory): void
    {
        // Create a new plan based on the data provider
        $plan = $planFactory()->create();
        
        $applications = Application::factory()
            ->count(10)
            ->recycle($plan)
            ->create();

        $response = $this->sendRequest($plan->type);

        $response->assertSuccessful();
        $response->assertJson(['total' => $applications->count()]);
    }

    public function test_should_return_the_applications_sorted_by_oldest_first(): void
    {
        // Create two applications with different `created_at` dates
        $applications = Application::factory()
            ->count(2)
            ->create([
                'created_at' => new Sequence(now()->subDays(2), now())
            ]);

        $response = $this->sendRequest();

        $response->assertSuccessful();
        $response->assertJson(['total' => $applications->count()]);
        $response->assertJsonPath('data.0.id', $applications->first()->id);
        $response->assertJsonPath('data.1.id', $applications->last()->id);

    }

    /**
     * Data provider with different plan types.
     */
    public static function applicationsDataProvider(): array
    {
        return [
            'Search by mobile plan' => [
                'plan' => fn () : PlanFactory => Plan::factory()->mobile(),
            ],
            'Search by NBN plan' => [
                'plan' => fn () : PlanFactory => Plan::factory()->nbn(),
            ],
            'Search by Opticomm plan' => [
                'plan' => fn () : PlanFactory => Plan::factory()->opticomm(),
            ],
        ];
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

