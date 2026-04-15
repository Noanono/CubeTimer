<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class ScrambleController extends Controller
{
    private array $validPuzzles = [
        '333', '222so', '444wca', '555wca', 'pyrso', 'mgmp', 'skbso', 'sqrs',
    ];

    public function generate(Request $request): JsonResponse
    {
        $puzzle = $request->query('puzzle', '333');

        if (! in_array($puzzle, $this->validPuzzles, true)) {
            return response()->json(['message' => 'Type de puzzle invalide.'], 422);
        }

        $seed = $request->query('seed', '');
        $seedJs = $seed ? "cstimer.setSeed('$seed');" : '';

        $script = <<<JS
        const cstimer = require('cstimer_module');
        {$seedJs}
        const scramble = cstimer.getScramble('{$puzzle}');
        let svgImage = '';
        try {
            svgImage = cstimer.getImage(scramble, '{$puzzle}');
            if (svgImage) {
                const wm = svgImage.match(/width="(\\d+)"/);
                const hm = svgImage.match(/height="(\\d+)"/);
                if (wm && hm) {
                    svgImage = svgImage
                        .replace(/width="\\d+"/, 'width="100%"')
                        .replace(/height="\\d+"/, 'height="100%"')
                        .replace('<svg ', '<svg viewBox="0 0 ' + wm[1] + ' ' + hm[1] + '" preserveAspectRatio="xMidYMid meet" ');
                }
            }
        } catch (_) {}
        console.log(JSON.stringify({ scramble, svgImage, puzzleType: '{$puzzle}' }));
        JS;

        $result = Process::path(base_path())->run(['node', '-e', $script]);

        if (! $result->successful()) {
            return response()->json([
                'message' => 'Erreur lors de la génération du mélange.',
            ], 500);
        }

        $data = json_decode(trim($result->output()), true);

        if (! $data) {
            return response()->json([
                'message' => 'Réponse invalide du générateur de mélanges.',
            ], 500);
        }

        return response()->json($data);
    }
}
