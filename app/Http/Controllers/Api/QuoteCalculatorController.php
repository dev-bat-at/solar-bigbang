<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemType;
use App\Services\QuoteCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuoteCalculatorController extends Controller
{
    public function __invoke(Request $request, QuoteCalculatorService $calculator): JsonResponse
    {
        $validated = $request->validate([
            'system_type_id' => ['nullable', 'integer', Rule::exists('system_types', 'id')],
            'system_type_slug' => ['nullable', 'string', Rule::exists('system_types', 'slug')],
            'phase_type' => ['required', 'string', Rule::in(['1P', '3P'])],
            'monthly_bill' => ['required', 'numeric', 'min:1'],
            'day_ratio' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'night_ratio' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $systemType = $validated['system_type_id'] ?? $validated['system_type_slug'] ?? null;

        if (! $systemType) {
            abort(422, 'Vui lòng chọn hệ để tính báo giá.');
        }

        $result = $calculator->calculate($systemType, $validated);

        return response()->json($result);
    }
}

