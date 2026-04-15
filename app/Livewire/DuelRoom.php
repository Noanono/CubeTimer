<?php

namespace App\Livewire;

use App\Events\DuelTimeSubmitted;
use App\Models\DuelParticipant;
use App\Models\DuelRoom as DuelRoomModel;
use App\Models\Solve;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Duel')]
class DuelRoom extends Component
{
    public string $code;
    public ?DuelRoomModel $room = null;
    public bool $timerRunning = false;
    public bool $hasSubmitted = false;
    public string $opponentResult = '';
    public bool $opponentFinished = false;

    public function mount(string $code): void
    {
        $this->code = $code;
        $this->room = DuelRoomModel::with('participants.user')->where('code', $code)->firstOrFail();

        $participant = $this->room->participants()->where('user_id', Auth::id())->first();
        if (!$participant) {
            abort(403, 'Vous ne faites pas partie de ce duel.');
        }

        $this->hasSubmitted = $participant->finished_at !== null;
    }

    public function submitTime(int $timeMs, bool $dnf = false): void
    {
        if ($this->hasSubmitted) {
            return;
        }

        // Bloquer si adversaire pas encore là
        if ($this->room->participants()->count() < 2) {
            return;
        }

        $participant = DuelParticipant::where('duel_room_id', $this->room->id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $participant->update([
            'time_ms'     => $dnf ? null : $timeMs,
            'dnf'         => $dnf,
            'finished_at' => now(),
        ]);

        // Sauvegarder dans l'historique stats
        Solve::create([
            'user_id'     => Auth::id(),
            'puzzle_type' => $this->room->puzzle_type,
            'scramble'    => $this->room->scramble_text ?: $participant->room->scramble_text,
            'time_ms'     => $dnf ? 0 : $timeMs,
            'dnf'         => $dnf,
            'plus2'       => false,
            'source'      => 'duel',
        ]);

        $this->hasSubmitted = true;
        $this->room->refresh();

        broadcast(new DuelTimeSubmitted($participant->load('user', 'room')));

        $allDone = $this->room->participants()->whereNull('finished_at')->doesntExist();
        if ($allDone) {
            $this->room->update(['status' => 'finished']);
        }
    }

    public function checkRoomReady(): void
    {
        $this->room = DuelRoomModel::with('participants.user')->where('code', $this->code)->firstOrFail();
        if ($this->room->participants()->count() >= 2) {
            $this->dispatch('opponent-joined');
        }
    }

    public function getRoomWithParticipantsProperty()
    {
        return DuelRoomModel::with('participants.user')->where('code', $this->code)->first();
    }

    public function render()
    {
        return view('livewire.duel-room', [
            'room' => $this->roomWithParticipants,
        ]);
    }
}
