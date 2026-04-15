<?php

namespace App\Livewire;

use App\Models\DuelRoom;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Duel - Lobby')]
class DuelLobby extends Component
{
    public string $puzzleType = '333';
    public string $inviteUsername = '';
    public string $joinCode = '';
    public ?string $errorMessage = null;
    public ?string $createdRoomCode = null;

    public array $puzzleTypes = [
        '333'    => '3x3x3',
        '222so'  => '2x2x2',
        '444wca' => '4x4x4',
        '555wca' => '5x5x5',
        'pyrso'  => 'Pyraminx',
        'mgmp'   => 'Megaminx',
        'skbso'  => 'Skewb',
        'sqrs'   => 'Square-1',
    ];

    public function createRoom(): void
    {
        $this->errorMessage = null;
        $scrambleSeed = bin2hex(random_bytes(16));

        $room = DuelRoom::create([
            'code'          => DuelRoom::generateCode(),
            'creator_id'    => Auth::id(),
            'puzzle_type'   => $this->puzzleType,
            'scramble_seed' => $scrambleSeed,
            'scramble_text' => '',
            'status'        => 'waiting',
        ]);

        $room->participants()->create(['user_id' => Auth::id()]);

        $this->createdRoomCode = $room->code;
        $this->redirect(route('duel.room', $room->code));
    }

    public function joinRoom(): void
    {
        $this->errorMessage = null;
        $code = strtoupper(trim($this->joinCode));
        $room = DuelRoom::where('code', $code)->where('status', 'waiting')->first();

        if (!$room) {
            $this->errorMessage = 'Salle introuvable ou déjà en cours.';
            return;
        }

        if ($room->isFull()) {
            $this->errorMessage = 'La salle est pleine (2 joueurs max).';
            return;
        }

        if ($room->participants()->where('user_id', Auth::id())->exists()) {
            $this->redirect(route('duel.room', $room->code));
            return;
        }

        $room->participants()->create(['user_id' => Auth::id()]);
        $room->update(['status' => 'in_progress']);

        $this->redirect(route('duel.room', $room->code));
    }

    public function inviteByUsername(): void
    {
        $this->errorMessage = null;
        $user = User::where('name', $this->inviteUsername)->first();

        if (!$user) {
            $this->errorMessage = "Utilisateur '{$this->inviteUsername}' introuvable.";
            return;
        }

        if ($user->id === Auth::id()) {
            $this->errorMessage = 'Vous ne pouvez pas vous inviter vous-même.';
            return;
        }

        $scrambleSeed = bin2hex(random_bytes(16));

        $room = DuelRoom::create([
            'code'          => DuelRoom::generateCode(),
            'creator_id'    => Auth::id(),
            'puzzle_type'   => $this->puzzleType,
            'scramble_seed' => $scrambleSeed,
            'scramble_text' => '',
            'status'        => 'waiting',
        ]);

        $room->participants()->create(['user_id' => Auth::id()]);

        session()->flash('duel_invite', [
            'room_code'     => $room->code,
            'invited_user'  => $user->name,
        ]);

        $this->redirect(route('duel.room', $room->code));
    }

    public function render()
    {
        return view('livewire.duel-lobby');
    }
}
