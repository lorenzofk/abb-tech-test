<?php

namespace Tests\Unit\Models;

use App\Models\Application;
use App\Models\Plan;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    public function test_should_return_the_correct_relationships(): void
    {
        $application = Application::factory()->create();

        $this->assertInstanceOf(Plan::class, $application->plan);
    }
}
