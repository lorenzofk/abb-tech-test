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

    public function test_should_return_the_correct_applications_when_filtering_by_plan_type(): void
    {
        // Create two applications with different plan types
        $nbnApplication = Application::factory()->for(Plan::factory()->nbn())->create();
        $mobileApplication = Application::factory()->for(Plan::factory()->mobile())->create();

        // Filter by `nbn` plan type
        $result = Application::planType('nbn')->get();

        $this->assertCount(1, $result);
        $this->assertEquals($nbnApplication->id, $result->first()->id);

        // Filter by `mobile` plan type
        $result = Application::planType('mobile')->get();

        $this->assertCount(1, $result);
        $this->assertEquals($mobileApplication->id, $result->first()->id);
    }
}
