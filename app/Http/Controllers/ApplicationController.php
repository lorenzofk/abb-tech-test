<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListApplicationsRequest;
use App\Models\Application;
use Illuminate\Http\JsonResponse;
class ApplicationController extends Controller
{
    /**
     * Return a list of applications.
     */
    public function index(ListApplicationsRequest $request): JsonResponse
    {
        $planType = $request->query('plan_type');

        $applications = Application::when($planType, fn ($query) => $query->planType($planType))
            ->orderBy('created_at')
            ->paginate(10);

        return response()->json($applications);
    }
}