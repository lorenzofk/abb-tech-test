<?php

namespace Tests\Unit\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ProcessNbnOrderTest extends TestCase
{
    const COMMAND_NAME = 'orders:process-nbn';

    public function test_should_ensure_the_command_is_registered_and_run_every_five_minutes(): void
    {
         // Get the scheduler instance
        $schedule = $this->app->make(Schedule::class);

        // Retrieve all scheduled events
        $events = collect($schedule->events());

        // Find the specific scheduled command
        $event = $events->first(fn ($event) => str_contains($event->command, self::COMMAND_NAME));

        $this->assertNotNull($event);
        $this->assertEquals('*/5 * * * *', $event->expression);
    }
}