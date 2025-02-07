<?php

namespace Tests\Feature\Api;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Plan;
use App\Models\User;
use Closure;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->actingAs($this->user);
        $this->assertAuthenticated();
    }

    public function test_should_return_the_pagination_meta_data(): void
    {
        $applications = Application::factory()->count(5)->create();

        $response = $this->sendRequest();

        $response->assertSuccessful();
        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('meta')
                ->where('meta.current_page', 1)
                ->where('meta.last_page', 1)
                ->where('meta.per_page', config('pagination.api.pagination.per_page', 15))
                ->where('meta.total', $applications->count())
                ->etc()
        );
    }

    public function test_should_return_an_empty_response_when_no_applications_are_found(): void
    {
        $response = $this->sendRequest();

        $response->assertSuccessful();
        $response->assertJsonCount(0, 'data');
    }

    public function test_should_return_a_validation_error_when_invalid_plan_type_is_provided(): void
    {
        $response = $this->sendRequest('invalid_plan_type');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['plan_type']);
    }

    #[DataProvider('planTypeFiltersDataProvider')]
    public function test_should_return_the_applications_filtered_by_plan_type(Closure $planFactory): void
    {
        // Create a new plan based on the data provider
        $plan = $planFactory()->create();
        
        // Create two applications with different statuses
        $applications = Application::factory()
            ->count(2)
            ->recycle($plan)
            ->create([
                'status' => new Sequence(ApplicationStatus::Prelim, ApplicationStatus::Complete),
            ]);

        $preliminaryApplication = $applications->firstWhere('status', ApplicationStatus::Prelim);
        $completeApplication = $applications->firstWhere('status', ApplicationStatus::Complete);

        $response = $this->sendRequest($plan->type);

        $response->assertSuccessful();
        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('data', $applications->count())
                 ->has('data.0', fn (AssertableJson $json) =>
                    $json->where('application_id', $preliminaryApplication->id)
                        ->where('customer_name', $preliminaryApplication->customer->full_name)
                        ->where('address', $preliminaryApplication->full_address)
                        ->where('state', $preliminaryApplication->state)
                        ->where('plan_type', $preliminaryApplication->plan->type)
                        ->where('plan_name', $preliminaryApplication->plan->name)
                        ->where('plan_monthly_cost', $preliminaryApplication->plan->monthly_cost_formatted)
                        ->missing('order_id')
                        ->etc()
                )
                 ->has('data.1', fn (AssertableJson $json) =>
                    $json->where('application_id', $completeApplication->id)
                        ->where('customer_name', $completeApplication->customer->full_name)
                        ->where('address', $completeApplication->full_address)
                        ->where('state', $completeApplication->state)
                        ->where('plan_type', $completeApplication->plan->type)
                        ->where('plan_name', $completeApplication->plan->name)
                        ->where('plan_monthly_cost', $completeApplication->plan->monthly_cost_formatted)
                        ->has('order_id')
                        ->etc()
                )
                 ->etc()
        );
    }

    public function test_should_return_the_applications_without_any_filter(): void
    {
        $applications = Application::factory()->count(5)->create();

        $response = $this->sendRequest();

        $response->assertSuccessful();
        $response->assertJsonCount($applications->count(), 'data');
    }

    public function test_should_return_the_applications_sorted_by_oldest_first(): void
    {
        // Create two applications with different `created_at` dates
        $applications = Application::factory()
            ->count(2)
            ->create([
                'created_at' => new Sequence(now()->subDays(2), now())
            ])
            ->sortBy('created_at')
            ->values();
            
        $oldestApplication = $applications->first();
        $latestApplication = $applications->last();

        $response = $this->sendRequest();

        $response->assertSuccessful();
        $response->assertJson(fn (AssertableJson $json) =>
            $json->has('data', $applications->count())
                 ->has('data.0', fn (AssertableJson $json) =>
                    $json->where('application_id', $oldestApplication->id)
                        ->etc()
                )
                 ->has('data.1', fn (AssertableJson $json) =>
                    $json->where('application_id', $latestApplication->id)
                        ->etc()
                )
                 ->etc()
        );
    }

    /**
     * Data provider with different plan types.
     */
    public static function planTypeFiltersDataProvider(): array
    {
        return [
            'Filter by mobile plan' => [
                'plan' => fn () : PlanFactory => Plan::factory()->mobile(),
            ],
            'Filter by NBN plan' => [
                'plan' => fn () : PlanFactory => Plan::factory()->nbn(),
            ],
            'Filter by Opticomm plan' => [
                'plan' => fn () : PlanFactory => Plan::factory()->opticomm(),
            ],
        ];
    }

    /**
     * Send the GET request to the API endpoint.
     */
    private function sendRequest(?string $planType = null): TestResponse
    {
        $queryParams = $planType ? ['plan_type' => $planType] : [];

        return $this->getJson(route('api.v1.applications.index', $queryParams));
    }
}

