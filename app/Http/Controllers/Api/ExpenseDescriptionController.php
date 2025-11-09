<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ExpenseDescriptionController extends Controller
{
    /**
     * Search for expense descriptions.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $search = $request->get('search', '');
        $limit = min($request->get('limit', 20), 50); // Cap at 50 results

        // Cache the results for performance
        $cacheKey = "expense_descriptions:" . md5($search . $limit);

        $descriptions = Cache::remember($cacheKey, 3600, function () use ($search, $limit) {
            return Expense::distinctDescriptions($search, $limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'value' => $item->value,
                        'label' => $item->label,
                    ];
                });
        });

        return response()->json($descriptions);
    }
}
