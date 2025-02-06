<?php

namespace Tests\Unit\Resources;

use App\Enums\ApplicationStatus;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Resources\MissingValue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApplicationResourceTest extends TestCase
{
    use WithFaker;

    #[DataProvider('applicationStatusProvider')]
    public function test_should_return_the_correct_transformed_data_for_different_application_statuses(ApplicationStatus $status): void
    {
        $application = Application::factory()->state(['status' => $status])->create();

        $resource = (new ApplicationResource($application))->toArray(request());

        $this->assertEquals($this->getExpectedData($application), $resource);
    }

    /**
     * Data provider with different application statuses.
     */
    public static function applicationStatusProvider(): array
    {
        return [
            'Prelim status' => [
                'status' => ApplicationStatus::Prelim,
            ],
            'Complete status' => [
                'status' => ApplicationStatus::Complete,
            ],
        ];
    }

    /**
     * Get the expected data based on the application status.
     */
    private function getExpectedData(Application $application): array
    {
        $data = [
            'application_id' => $application->id,
            'customer_name' => $application->customer->full_name,
            'address' => $application->full_address,
            'state' => $application->state,
            'plan_type' => $application->plan->type,
            'plan_name' => $application->plan->name,
            'plan_monthly_cost' => $application->plan->monthly_cost_formatted,
            'order_id' => $application->status === ApplicationStatus::Complete ? $application->order_id : new MissingValue,
        ];

        return $data;
    }
}
