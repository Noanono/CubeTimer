<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DuelRoom extends Model
{
    protected $fillable = [
        'code',
        'creator_id',
        'puzzle_type',
        'scramble_seed',
        'scramble_text',
        'status',
    ];

    public static function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(DuelParticipant::class);
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    public function isFull(): bool
    {
        return $this->participants()->count() >= 2;
    }
}
