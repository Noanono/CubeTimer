<?php

namespace App\Events;

use App\Models\DuelParticipant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DuelTimeSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $userId;
    public string $userName;
    public ?int $timeMs;
    public bool $dnf;
    public string $formattedTime;
    public string $roomCode;

    public function __construct(DuelParticipant $participant)
    {
        $this->userId        = $participant->user_id;
        $this->userName      = $participant->user->name;
        $this->timeMs        = $participant->time_ms;
        $this->dnf           = $participant->dnf;
        $this->formattedTime = $participant->getFormattedTime();
        $this->roomCode      = $participant->room->code;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('duel.' . $this->roomCode),
        ];
    }

    public function broadcastAs(): string
    {
        return 'time.submitted';
    }
}
