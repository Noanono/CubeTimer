<?php

namespace App\Livewire;

use App\Models\Solve;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Statistiques')]
class Statistics extends Component
{
    public string $puzzleType = '333';

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

    private function solves()
    {
        return Solve::where('user_id', Auth::id())
            ->where('puzzle_type', $this->puzzleType)
            ->where('dnf', false)
            ->orderBy('created_at', 'desc');
    }

    private function calcAo(array $times, int $n): ?string
    {
        if (count($times) < $n) {
            return null;
        }
        $slice = array_slice($times, 0, $n);
        $cutoff = (int) ceil($n * 0.05);
        sort($slice);
        $trimmed = array_slice($slice, $cutoff, $n - 2 * $cutoff);
        if (empty($trimmed)) {
            return null;
        }
        $avg = array_sum($trimmed) / count($trimmed);
        return $this->formatMs((int) round($avg));
    }

    private function formatMs(int $ms): string
    {
        $minutes    = intdiv($ms, 60000);
        $seconds    = intdiv($ms % 60000, 1000);
        $hundredths = intdiv($ms % 1000, 10);
        return $minutes > 0
            ? sprintf('%d:%02d.%02d', $minutes, $seconds, $hundredths)
            : sprintf('%d.%02d', $seconds, $hundredths);
    }

    public function getStatsProperty(): array
    {
        $solves = $this->solves()->get();
        $times  = $solves->map(fn($s) => $s->getEffectiveTimeMs())->filter()->values()->toArray();

        $best = !empty($times) ? $this->formatMs(min($times)) : '-';
        $worst = !empty($times) ? $this->formatMs(max($times)) : '-';
        $count = count($times);
        $avg   = $count > 0 ? $this->formatMs((int) round(array_sum($times) / $count)) : '-';

        return [
            'count'  => $count,
            'best'   => $best,
            'worst'  => $worst,
            'avg'    => $avg,
            'ao5'    => $this->calcAo($times, 5)  ?? '-',
            'ao12'   => $this->calcAo($times, 12) ?? '-',
            'ao100'  => $this->calcAo($times, 100) ?? '-',
        ];
    }

    public function getRecentSolvesProperty()
    {
        return Solve::where('user_id', Auth::id())
            ->where('puzzle_type', $this->puzzleType)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }

    public function deleteSolve(int $id): void
    {
        Solve::where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();
    }

    public function toggleDnf(int $id): void
    {
        $solve = Solve::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        $solve->update(['dnf' => !$solve->dnf]);
    }

    public function getChartDataProperty(): array
    {
        return $this->solves()->limit(100)->get()
            ->reverse()
            ->values()
            ->map(fn($s, $i) => [
                'x' => $i + 1,
                'y' => round($s->getEffectiveTimeMs() / 1000, 2),
            ])
            ->toArray();
    }

    public function render()
    {
        return view('livewire.statistics', [
            'stats'        => $this->stats,
            'recentSolves' => $this->recentSolves,
            'chartData'    => $this->chartData,
        ]);
    }
}
