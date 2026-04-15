<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuelParticipant extends Model
{
    protected $fillable = [
        'duel_room_id',
        'user_id',
        'time_ms',
        'dnf',
        'finished_at',
    ];

    protected $casts = [
        'dnf'         => 'boolean',
        'time_ms'     => 'integer',
        'finished_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(DuelRoom::class, 'duel_room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedTime(): string
    {
        if ($this->dnf || $this->time_ms === null) {
            return 'DNF';
        }
        $ms = $this->time_ms;
        $minutes    = intdiv($ms, 60000);
        $seconds    = intdiv($ms % 60000, 1000);
        $hundredths = intdiv(($ms % 1000), 10);
        return $minutes > 0
            ? sprintf('%d:%02d.%02d', $minutes, $seconds, $hundredths)
            : sprintf('%d.%02d', $seconds, $hundredths);
    }
}
