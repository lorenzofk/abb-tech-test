<?php

namespace Tests\Unit\Models;

use App\Models\Application;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_should_return_the_correct_relationships(): void
    {
        $plan = Plan::factory()->hasApplications()->create();

        $this->assertInstanceOf(Collection::class, $plan->applications);
        $this->assertInstanceOf(Application::class, $plan->applications->first());
    }

    public function test_should_return_the_monthly_cost_of_the_plan_in_human_readable_format(): void
    {
        // Create a plan with a monthly cost of $100.00
        $plan = Plan::factory()->create(['monthly_cost' => 10000]);

        $this->assertEquals('$100.00', $plan->monthly_cost_formatted);
    }
}
