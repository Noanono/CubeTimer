<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Solve extends Model
{
    protected $fillable = [
        'user_id',
        'puzzle_type',
        'scramble',
        'time_ms',
        'dnf',
        'plus2',
        'comment',
        'source',
    ];

    protected $casts = [
        'dnf'    => 'boolean',
        'plus2'  => 'boolean',
        'time_ms' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getEffectiveTimeMs(): ?int
    {
        if ($this->dnf) {
            return null;
        }
        return $this->plus2 ? $this->time_ms + 2000 : $this->time_ms;
    }

    public function getFormattedTime(): string
    {
        if ($this->dnf) {
            return 'DNF';
        }
        $ms = $this->getEffectiveTimeMs();
        $minutes  = intdiv($ms, 60000);
        $seconds  = intdiv($ms % 60000, 1000);
        $hundredths = intdiv(($ms % 1000), 10);
        $result = $minutes > 0
            ? sprintf('%d:%02d.%02d', $minutes, $seconds, $hundredths)
            : sprintf('%d.%02d', $seconds, $hundredths);
        return $this->plus2 ? $result . '+' : $result;
    }
}
