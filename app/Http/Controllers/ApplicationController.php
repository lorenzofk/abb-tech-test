<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
class ApplicationController extends Controller
{
    /**
     * Return a list of applications.
     */
    public function index(Request $request): JsonResponse
    {
        $planType = $request->query('planType');

        $applications = Application::when($planType, fn ($query) => $query->planType($planType))
            ->orderBy('created_at')
            ->paginate(10);

        return response()->json($applications);
    }
}