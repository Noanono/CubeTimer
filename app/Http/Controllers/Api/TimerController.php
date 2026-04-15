<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Solve;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimerController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'puzzle_type' => ['required', 'string', 'max:20'],
            'scramble'    => ['required', 'string'],
            'time_ms'     => ['required', 'integer', 'min:0'],
            'dnf'         => ['sometimes', 'boolean'],
            'plus2'       => ['sometimes', 'boolean'],
        ]);

        $solve = Solve::create([
            'user_id'     => $request->user()->id,
            'puzzle_type' => $validated['puzzle_type'],
            'scramble'    => $validated['scramble'],
            'time_ms'     => $validated['time_ms'],
            'dnf'         => $validated['dnf'] ?? false,
            'plus2'       => $validated['plus2'] ?? false,
        ]);

        return response()->json([
            'id'            => $solve->id,
            'formatted_time' => $solve->getFormattedTime(),
        ], 201);
    }
}
