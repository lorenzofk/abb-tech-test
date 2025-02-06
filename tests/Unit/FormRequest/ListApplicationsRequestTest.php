<?php

namespace Tests\Unit\FormRequest;

use App\Http\Requests\ListApplicationsRequest;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ListApplicationsRequestTest extends TestCase
{
    use WithFaker;

    private ListApplicationsRequest $request;

    public function setUp(): void
    {
        parent::setUp();

        $this->request = new ListApplicationsRequest();
    }

    public function test_should_pass_without_any_plan_type(): void
    {
        $data = [];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_should_pass_with_valid_plan_type(): void
    {
        $planType = $this->faker->randomElement(['nbn', 'mobile', 'opticomm']);

        $data = [
            'plan_type' => $planType,
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_should_fail_with_invalid_plan_type(): void
    {
        $data = [
            'plan_type' => 'invalid_plan_type',
        ];

        $validator = Validator::make($data, $this->request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('plan_type', $validator->errors()->toArray());
    }
}
