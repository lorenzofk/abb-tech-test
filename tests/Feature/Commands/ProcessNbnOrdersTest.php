<?php

namespace Tests\Unit\Commands;

use App\Jobs\ProcessNbnOrder;
use App\Models\Application;
use App\Models\Plan;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ProcessNbnOrdersTest extends TestCase
{
    const COMMAND_NAME = 'orders:process-nbn';

    public function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    public function test_should_ensure_no_nbn_orders_are_queued_for_processing_when_there_are_no_nbn_orders(): void
    {
        $this->artisan(self::COMMAND_NAME);

        Bus::assertNothingDispatched();
    }

    public function test_should_ensure_no_jobs_are_queued_when_there_are_no_nbn_orders_with_order_status(): void
    {
        // Create 2 `not completed` NBN orders (the status is `prelim` by default)
        Application::factory()->count(2)->for(Plan::factory()->nbn())->create();

        $this->artisan(self::COMMAND_NAME);

        Bus::assertNothingDispatched();
    }

    public function test_should_ensure_jobs_are_queued_when_there_are_nbn_orders_with_order_status(): void
    {
        $nbnApplications = Application::factory()->count(2)->for(Plan::factory()->nbn())->order()->create();

        // Create 2 `mobile` orders with `order` status just to ensure they are not processed
        Application::factory()->count(2)->for(Plan::factory()->mobile())->order()->create();

        $this->artisan(self::COMMAND_NAME)
            ->expectsOutput("Processing {$nbnApplications->count()} NBN orders.")
            ->expectsOutput("{$nbnApplications->count()} NBN orders have been dispatched to the queue.");
            
        Bus::assertDispatched(ProcessNbnOrder::class, $nbnApplications->count(), fn (ProcessNbnOrder $job) => $nbnApplications->contains($job->application));

        // Ensure no `mobile` orders were dispatched
        Bus::assertNotDispatched(fn (ProcessNbnOrder $job) => $job->application->plan->type !== 'nbn');
    }
}
