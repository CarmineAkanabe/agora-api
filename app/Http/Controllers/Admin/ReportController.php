<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    public function overview(): JsonResponse
    {
        return response()->json($this->reportService->overview());
    }

    public function transactions(): JsonResponse
    {
        return response()->json($this->reportService->transactions());
    }

    public function listings(): JsonResponse
    {
        return response()->json($this->reportService->listings());
    }

    public function users(): JsonResponse
    {
        return response()->json($this->reportService->users());
    }

    public function studentStats(Request $request): JsonResponse
    {
        return response()->json(
            $this->reportService->studentStats($request->user()->id)
        );
    }
}
