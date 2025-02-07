<?php

namespace App\Jobs;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessNbnOrder implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /**
     * Create a new job instance.
     */
    public function __construct(public Application $application)
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Add the application ID to the log context
        Log::withContext(['application_id' => $this->application->id]);

        // Prevent attempting to process the order if it has already been processed
        if (! empty($this->application->order_id)) {
            Log::info('NBN order already processed');
            return;
        }

        $response = Http::post(config('api.nbn_b2b_endpoint'), $this->getPayload());

        if ($response->failed()) {

            Log::error('NBN order failed to process', ['response' => $response->json()]);

            $this->application->update([
                'status' => ApplicationStatus::OrderFailed,
            ]);

            return;
        }

        $this->application->update([
            'order_id' => $response->json('id'),
            'status' => ApplicationStatus::Complete,
        ]);

        Log::info('NBN order completed successfully', ['response' => $response->json()]);
    }

    /**
     * Ensures the job will retry after 10, 30, 60, 120, and 300 seconds in case of failure.
     */
    public function backoff(): array
    {
        return [10, 30, 60, 120, 300];
    }

    /**
     * Handle job failure after all retries.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('NBN order processing failed after all retries', [
            'exception' => $exception->getMessage(),
        ]);

        $this->application->update(['status' => ApplicationStatus::OrderFailed]);
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->application->id;
    }

    /**
     * Ensure the job stays unique for 10 minutes (to prevent accidental re-processing).
     */
    public function uniqueFor(): int
    {
        return 10 * 60; // 10 minutes
    }

    /**
     * Returns the payload for the NBN order API request.
     */
    private function getPayload(): array
    {
        return [
            'address_1' => $this->application->address_1,
            'address_2' => $this->application->address_2,
            'city' => $this->application->city,
            'state' => $this->application->state,
            'postcode' => $this->application->postcode,
            'plan_name' => $this->application->plan->name,
        ];
    }
}
