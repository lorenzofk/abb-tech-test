<?php

namespace Tests\Unit\Models;

use App\Models\Application;
use App\Models\Customer;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_should_return_the_correct_relationships(): void
    {
        $application = Application::factory()->create();

        $this->assertInstanceOf(Plan::class, $application->plan);
        $this->assertInstanceOf(Customer::class, $application->customer);
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

    public function test_should_return_the_full_address_of_the_application(): void
    {
        $application = Application::factory()->create([
            'address_1' => $this->faker->streetAddress(),
            'address_2' => $this->faker->secondaryAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postcode' => $this->faker->postcode(),
        ]);

        $expectedAddress = implode(', ', array_filter([
            $application->address_1,
            $application->address_2,
            $application->city,
            $application->state,
            $application->postcode,
        ]));

        $this->assertEquals($application->full_address, $expectedAddress);
    }
}
