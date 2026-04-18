<?php

namespace App\Livewire;

use App\Models\Solve;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Timer')]
class Timer extends Component
{
    public string $puzzleType = '333';

    public string $scramble = '';

    public ?int $lastTimeMs = null;

    public array $puzzleTypes = [
        '333' => '3x3x3',
        '222so' => '2x2x2',
        '444wca' => '4x4x4',
        '555wca' => '5x5x5',
        'pyrso' => 'Pyraminx',
        'mgmp' => 'Megaminx',
        'skbso' => 'Skewb',
        'sqrs' => 'Square-1',
    ];

    public function updatedPuzzleType(): void
    {
        $this->scramble = '';
        $this->lastTimeMs = null;
        $this->dispatch('puzzle-changed', puzzleType: $this->puzzleType);
    }

    public function saveTime(int $timeMs, bool $dnf = false, bool $plus2 = false): void
    {
        if (empty($this->scramble)) {
            return;
        }

        // Valider que le puzzle_type est autorisé
        if (! array_key_exists($this->puzzleType, $this->puzzleTypes)) {
            return;
        }

        Solve::create([
            'user_id' => Auth::id(),
            'puzzle_type' => $this->puzzleType,
            'scramble' => $this->scramble,
            'time_ms' => $timeMs,
            'dnf' => $dnf,
            'plus2' => $plus2,
        ]);

        $this->lastTimeMs = $timeMs;
        $this->scramble = '';
        $this->dispatch('time-saved');
    }

    public function formatLastTime(int $ms): string
    {
        $minutes = intdiv($ms, 60000);
        $seconds = intdiv($ms % 60000, 1000);
        $hundredths = intdiv($ms % 1000, 10);

        return $minutes > 0
            ? sprintf('%d:%02d.%02d', $minutes, $seconds, $hundredths)
            : sprintf('%d.%02d', $seconds, $hundredths);
    }

    public function render()
    {
        return view('livewire.timer');
    }
}
