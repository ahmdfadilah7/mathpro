<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NavbarController extends Controller
{
    public function __construct(
        private readonly GlobalSearchService $globalSearchService
    ) {}

    public function search(Request $request): JsonResponse
    {
        return response()->json(
            $this->globalSearchService->search(
                $request->user(),
                $request->query('q')
            )
        );
    }
}
