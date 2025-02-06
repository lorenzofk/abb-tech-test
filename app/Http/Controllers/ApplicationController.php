<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListApplicationsRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApplicationController extends Controller
{
    /**
     * Return a list of applications.
     */
    public function index(ListApplicationsRequest $request): AnonymousResourceCollection
    {
        $planType = $request->query('plan_type');

        $applications = Application::when($planType, fn ($query) => $query->planType($planType))
            ->with([
                'customer' => fn ($query) => $query->select(['id', 'first_name', 'last_name']),
                'plan' => fn ($query) => $query->select(['id', 'type', 'name', 'monthly_cost']),
            ])
            ->orderBy('created_at')
            ->paginate(config('pagination.api.pagination.per_page'));

        return ApplicationResource::collection($applications);
    }
}