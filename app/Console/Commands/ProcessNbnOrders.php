<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStatus;
use App\Jobs\ProcessNbnOrder;
use App\Models\Application;
use Illuminate\Console\Command;

class ProcessNbnOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:process-nbn';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is responsible for processing NBN orders.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $applications = Application::planType('nbn')
            ->where('status', ApplicationStatus::Order)
            ->get();

        $total = $applications->count();

        if ($total === 0) {
            $this->info('There are no NBN orders to process.');
            return;
        }

        $this->info("Processing {$total} NBN orders.");

        // Dispatch the relevant jobs to the queue
        $applications->each(fn (Application $application) => ProcessNbnOrder::dispatch($application));

        $this->info("{$total} NBN orders have been dispatched to the queue.");
    }
}
