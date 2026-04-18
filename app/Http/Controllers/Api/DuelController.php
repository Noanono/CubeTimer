<?php

namespace App\Http\Controllers\Api;

use App\Events\DuelTimeSubmitted;
use App\Http\Controllers\Controller;
use App\Models\DuelRoom;
use App\Models\Solve;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DuelController extends Controller
{
    public function createRoom(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'puzzle_type' => ['required', 'string', 'max:20'],
        ]);

        $scrambleSeed = bin2hex(random_bytes(16));

        $room = DuelRoom::create([
            'code' => DuelRoom::generateCode(),
            'creator_id' => $request->user()->id,
            'puzzle_type' => $validated['puzzle_type'],
            'scramble_seed' => $scrambleSeed,
            'scramble_text' => '',
            'status' => 'waiting',
        ]);

        $room->participants()->create(['user_id' => $request->user()->id]);

        return response()->json([
            'code' => $room->code,
            'puzzle_type' => $room->puzzle_type,
            'seed' => $scrambleSeed,
            'status' => $room->status,
        ], 201);
    }

    public function joinRoom(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $code = strtoupper(trim($validated['code']));

        // Use transaction to prevent race conditions
        return DB::transaction(function () use ($request, $code) {
            $room = DuelRoom::where('code', $code)->where('status', 'waiting')->lockForUpdate()->first();

            if (! $room) {
                return response()->json(['message' => 'Salle introuvable ou déjà en cours.'], 404);
            }

            if ($room->isFull()) {
                return response()->json(['message' => 'La salle est pleine (2 joueurs max).'], 422);
            }

            $userId = $request->user()->id;

            if ($room->participants()->where('user_id', $userId)->exists()) {
                return response()->json($this->roomData($room));
            }

            $room->participants()->create(['user_id' => $userId]);
            $room->update(['status' => 'in_progress']);

            return response()->json($this->roomData($room->fresh()));
        });
    }

    public function showRoom(Request $request, string $code): JsonResponse
    {
        $room = DuelRoom::with('participants.user')->where('code', $code)->first();

        if (! $room) {
            return response()->json(['message' => 'Salle introuvable.'], 404);
        }

        $participant = $room->participants()->where('user_id', $request->user()->id)->first();
        if (! $participant) {
            return response()->json(['message' => 'Vous ne faites pas partie de ce duel.'], 403);
        }

        return response()->json($this->roomData($room));
    }

    public function submitTime(Request $request, string $code): JsonResponse
    {
        $validated = $request->validate([
            'time_ms' => ['required_without:dnf', 'integer', 'min:0'],
            'dnf' => ['sometimes', 'boolean'],
        ]);

        $room = DuelRoom::with('participants.user')->where('code', $code)->firstOrFail();
        $userId = $request->user()->id;

        // Check if user is participant FIRST
        $participant = $room->participants()->where('user_id', $userId)->first();
        if (! $participant) {
            return response()->json(['message' => 'Vous ne faites pas partie de ce duel.'], 403);
        }

        if ($participant->finished_at !== null) {
            return response()->json(['message' => 'Temps déjà soumis.'], 422);
        }

        // Check if we have enough participants BEFORE allowing submission
        if ($room->participants()->count() < 2) {
            return response()->json(['message' => 'En attente d\'un adversaire.'], 422);
        }

        $dnf = $validated['dnf'] ?? false;
        $timeMs = $dnf ? null : $validated['time_ms'];

        $participant->update([
            'time_ms' => $timeMs,
            'dnf' => $dnf,
            'finished_at' => now(),
        ]);

        Solve::create([
            'user_id' => $userId,
            'puzzle_type' => $room->puzzle_type,
            'scramble' => $room->scramble_text ?: '',
            'time_ms' => $dnf ? 0 : $validated['time_ms'],
            'dnf' => $dnf,
            'plus2' => false,
            'source' => 'duel',
        ]);

        broadcast(new DuelTimeSubmitted($participant->load('user', 'room')));

        $allDone = $room->participants()->whereNull('finished_at')->doesntExist();
        if ($allDone) {
            $room->update(['status' => 'finished']);
        }

        return response()->json($this->roomData($room->fresh(['participants.user'])));
    }

    private function roomData(DuelRoom $room): array
    {
        $room->load('participants.user');

        return [
            'code' => $room->code,
            'puzzle_type' => $room->puzzle_type,
            'seed' => $room->scramble_seed,
            'scramble_text' => $room->scramble_text,
            'status' => $room->status,
            'participants' => $room->participants->map(fn ($p) => [
                'user_id' => $p->user_id,
                'user_name' => $p->user->name,
                'time_ms' => $p->time_ms,
                'dnf' => $p->dnf,
                'formatted_time' => $p->finished_at ? $p->getFormattedTime() : null,
                'finished' => $p->finished_at !== null,
            ]),
        ];
    }
}
