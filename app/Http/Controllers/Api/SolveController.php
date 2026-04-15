<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Solve;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SolveController extends Controller
{
    public function destroy(Request $request, int $id): JsonResponse
    {
        $deleted = Solve::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->delete();

        if (! $deleted) {
            return response()->json(['message' => 'Solve introuvable.'], 404);
        }

        return response()->json(['message' => 'Supprimé.']);
    }

    public function toggleDnf(Request $request, int $id): JsonResponse
    {
        $solve = Solve::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $solve->update(['dnf' => ! $solve->dnf]);

        return response()->json([
            'id'  => $solve->id,
            'dnf' => $solve->dnf,
        ]);
    }
}
