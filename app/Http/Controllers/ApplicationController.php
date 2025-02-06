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
            ->orderBy('created_at')
            ->paginate(config('pagination.api.pagination.per_page'));

        return ApplicationResource::collection($applications);
    }
}