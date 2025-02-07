<?php

namespace Tests\Unit\Commands;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ProcessNbnOrderTest extends TestCase
{
    const COMMAND_NAME = 'orders:process-nbn';

    private Event $event;

    public function setUp(): void
    {
        parent::setUp();

        $schedule = $this->app->make(Schedule::class);

        // Get the scheduled event for the command
        $this->event = collect($schedule->events())
            ->first(
                fn ($event) => str_contains($event->command, self::COMMAND_NAME)
            );

        $this->assertNotNull($this->event);
    }

    public function test_should_schedule_process_nbn_orders_without_overlapping(): void
    {
        $this->assertTrue($this->event->withoutOverlapping);
    }

    public function test_should_ensure_the_command_is_registered_and_run_every_five_minutes(): void
    {
        $this->assertEquals('*/5 * * * *', $this->event->expression);
    }
}