<?php

namespace App\Policies;

use App\Models\DuelRoom;
use App\Models\User;

class DuelRoomPolicy
{
    /**
     * Determine if the given user can view the duel room.
     */
    public function view(User $user, DuelRoom $duelRoom): bool
    {
        // User can view if they are a participant in the duel
        return $duelRoom->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine if the given user can join the duel room.
     */
    public function join(User $user, DuelRoom $duelRoom): bool
    {
        // User can join if they are not already a participant and room is not full
        return ! $duelRoom->participants()->where('user_id', $user->id)->exists()
            && ! $duelRoom->isFull()
            && $duelRoom->status === 'waiting';
    }

    /**
     * Determine if the given user can submit time in the duel room.
     */
    public function submitTime(User $user, DuelRoom $duelRoom): bool
    {
        // User can submit time if they are a participant and haven't submitted yet
        $participant = $duelRoom->participants()->where('user_id', $user->id)->first();

        return $participant
            && ! $participant->finished_at
            && $duelRoom->participants()->count() >= 2
            && $duelRoom->status === 'in_progress';
    }
}
