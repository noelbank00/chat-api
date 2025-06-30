<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        readonly private UserSearchService $searchService,
    ) {
        //
    }

    public function __invoke(Request $request): JsonResponse
    {
        $baseQuery = User::query()
            ->where('is_active', true)
            ->where('id', '!=', auth()->id());

        $baseQuery = $this->searchService->applyFilters($baseQuery, $request);

        return response()->json([
            'users' => $baseQuery->paginate(),
        ]);
    }
}
