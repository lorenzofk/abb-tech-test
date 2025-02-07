<?php

namespace Tests\Unit\Jobs;

use App\Enums\ApplicationStatus;
use App\Jobs\ProcessNbnOrder;
use App\Models\Application;
use App\Models\Plan;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessNbnOrderTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private string $url;
    private Application $application;

    public function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->url = config('api.nbn_b2b_endpoint');

        $this->application = Application::factory()
            ->for(Plan::factory()->nbn())
            ->order()
            ->create();
    }

    public function test_should_store_order_id_and_set_status_to_complete_on_successful_response(): void
    {
        $response = $this->getSuccessResponse();

        // Fake the HTTP request with a success response
        Http::fake([$this->url => Http::response($response, 200)]);

        $job = new ProcessNbnOrder($this->application);
        $job->handle();

        $this->application->refresh();

        $this->assertEquals(ApplicationStatus::Complete, $this->application->status);
        $this->assertEquals($response['id'], $this->application->order_id);
    }

    public function test_should_not_dispatch_duplicate_jobs_for_the_same_application(): void
    {
        // Dispatch the same job twice
        ProcessNbnOrder::dispatch($this->application);
        ProcessNbnOrder::dispatch($this->application);

        // Assert that only `one unique` job was dispatched
        Queue::assertPushed(ProcessNbnOrder::class, 1);
    }

    public function test_should_set_status_to_order_failed_on_failed_response(): void
    {
        $response = $this->getFailedResponse();

        // Fake the HTTP request with a failed response
        Http::fake([$this->url => Http::response($response, 500)]);

        $job = new ProcessNbnOrder($this->application);
        $job->handle();

        $this->application->refresh();

        $this->assertEmpty($this->application->order_id);
        $this->assertEquals(ApplicationStatus::OrderFailed, $this->application->status);
    }

    public function test_should_not_process_an_application_that_already_has_an_order_id(): void
    {
        $applicationAlreadyProcessed = Application::factory()
            ->for(Plan::factory()->nbn())
            ->complete()
            ->create();

        Http::fake();

        $job = new ProcessNbnOrder($applicationAlreadyProcessed);
        $job->handle();

        $applicationAlreadyProcessed->refresh();

        Http::assertNothingSent();

        // Ensure the application status remains unchanged
        $this->assertEquals(ApplicationStatus::Complete, $applicationAlreadyProcessed->status);
    }
    

    public function test_should_set_application_status_to_failed_when_job_fails(): void
    {
        Log::shouldReceive('error')->once();

        // Simulate a job failure
        $job = new ProcessNbnOrder($this->application);
        $job->failed(new Exception('Some unexpected error'));

        $this->application->refresh();

        // Ensure the application status was updated to `OrderFailed` after the job failed
        $this->assertEquals(ApplicationStatus::OrderFailed, $this->application->status);
    }

    public function test_should_retry_failed_job_with_correct_backoff(): void
    {
        $job = new ProcessNbnOrder($this->application);

        $this->assertEquals(5, $job->tries);
        $this->assertEquals([10, 30, 60, 120, 300], $job->backoff());
    }

    private function getFailedResponse(): array
    {
        return json_decode(file_get_contents(base_path('tests/stubs/nbn-fail-response.json')), true);
    }

    private function getSuccessResponse(): array
    {
        return json_decode(file_get_contents(base_path('tests/stubs/nbn-successful-response.json')), true);
    }

    private function getPayload(): array
    {
        return [
            'address_1' => $this->faker->streetAddress(),
            'address_2' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postcode' => $this->faker->postcode(),
            'plan_name' => $this->faker->word()
        ];
    }
}