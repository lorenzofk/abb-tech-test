<?php

namespace Tests\Unit\Models;

use App\Models\Application;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_should_return_the_correct_relationships(): void
    {
        $customer = Customer::factory()->hasApplications()->create();

        $this->assertInstanceOf(Collection::class, $customer->applications);
        $this->assertInstanceOf(Application::class, $customer->applications->first());
    }

    public function test_should_return_the_full_name_of_the_customer(): void
    {
        $customer = Customer::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

        $this->assertEquals('John Doe', $customer->full_name);
    }
}
