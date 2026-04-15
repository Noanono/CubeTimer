<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Solve;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $puzzle = $request->query('puzzle', '333');
        $userId = $request->user()->id;

        $validSolves = Solve::where('user_id', $userId)
            ->where('puzzle_type', $puzzle)
            ->where('dnf', false)
            ->orderBy('created_at', 'desc')
            ->get();

        $times = $validSolves->map(fn($s) => $s->getEffectiveTimeMs())->filter()->values()->toArray();

        $count = count($times);
        $best  = $count > 0 ? $this->formatMs(min($times)) : '-';
        $worst = $count > 0 ? $this->formatMs(max($times)) : '-';
        $avg   = $count > 0 ? $this->formatMs((int) round(array_sum($times) / $count)) : '-';

        $stats = [
            'count'  => $count,
            'best'   => $best,
            'worst'  => $worst,
            'avg'    => $avg,
            'ao5'    => $this->calcAo($times, 5)  ?? '-',
            'ao12'   => $this->calcAo($times, 12) ?? '-',
            'ao100'  => $this->calcAo($times, 100) ?? '-',
        ];

        $recentSolves = Solve::where('user_id', $userId)
            ->where('puzzle_type', $puzzle)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($s) => [
                'id'             => $s->id,
                'time_ms'        => $s->time_ms,
                'formatted_time' => $s->getFormattedTime(),
                'scramble'       => $s->scramble,
                'dnf'            => $s->dnf,
                'plus2'          => $s->plus2,
                'source'         => $s->source,
                'created_at'     => $s->created_at->toIso8601String(),
            ]);

        $chartData = $validSolves->take(100)
            ->reverse()
            ->values()
            ->map(fn($s, $i) => [
                'x' => $i + 1,
                'y' => round($s->getEffectiveTimeMs() / 1000, 2),
            ])
            ->toArray();

        return response()->json([
            'stats'        => $stats,
            'recentSolves' => $recentSolves,
            'chartData'    => $chartData,
        ]);
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
}
