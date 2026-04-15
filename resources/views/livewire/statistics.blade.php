<div class="py-8 px-4 max-w-5xl mx-auto space-y-8">

    <!-- Sélecteur puzzle -->
    <div class="flex items-center space-x-4">
        <label class="text-gray-700 dark:text-gray-300 font-medium">Puzzle :</label>
        <select
            wire:model.live="puzzleType"
            class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-4 py-2"
        >
            @foreach($puzzleTypes as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <!-- Statistiques globales -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-4">
        @foreach([
            'Solves'  => $stats['count'],
            'Best'    => $stats['best'],
            'Pire'    => $stats['worst'],
            'Moyenne' => $stats['avg'],
            'Ao5'     => $stats['ao5'],
            'Ao12'    => $stats['ao12'],
            'Ao100'   => $stats['ao100'],
        ] as $label => $value)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 text-center">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ $label }}</p>
            <p class="text-2xl font-mono font-bold text-gray-900 dark:text-gray-100 mt-1">{{ $value }}</p>
        </div>
        @endforeach
    </div>

    <!-- Graphique -->
    @if(count($chartData) > 0)
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6" x-data="statsChart(@js($chartData))" x-init="init()">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Progression (100 derniers)</h3>
        <canvas id="statsChart" height="120"></canvas>
    </div>
    @endif

    <!-- Historique -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
        <table class="w-full text-sm text-gray-700 dark:text-gray-300">
            <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3 text-left">#</th>
                    <th class="px-4 py-3 text-left">Temps</th>
                    <th class="px-4 py-3 text-left">Mélange</th>
                    <th class="px-4 py-3 text-left">Date</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($recentSolves as $i => $solve)
                <tr class="hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors {{ $solve->dnf ? 'opacity-60' : '' }}">
                    <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>
                    <td class="px-4 py-3">
                        <span class="font-mono font-bold
                            {{ $solve->dnf ? 'text-red-500 line-through' : ($solve->plus2 ? 'text-yellow-500' : 'text-indigo-500') }}">
                            {{ $solve->getFormattedTime() }}
                        </span>
                        @if($solve->source === 'duel')
                            <span class="ml-1 px-1.5 py-0.5 text-xs bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-300 rounded">⚔️ duel</span>
                        @endif
                        @if($solve->dnf)
                            <span class="ml-1 px-1.5 py-0.5 text-xs bg-red-100 dark:bg-red-900/40 text-red-500 rounded">inactif</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-mono text-xs truncate max-w-xs text-gray-500">{{ $solve->scramble }}</td>
                    <td class="px-4 py-3 text-gray-400 text-xs">{{ $solve->created_at->diffForHumans() }}</td>
                    <td class="px-4 py-3 text-right space-x-1 whitespace-nowrap">
                        <button
                            wire:click="toggleDnf({{ $solve->id }})"
                            wire:loading.attr="disabled"
                            title="{{ $solve->dnf ? 'Réactiver' : 'Marquer DNF (inactif)' }}"
                            class="px-2 py-1 text-xs rounded border transition
                                {{ $solve->dnf
                                    ? 'border-green-400 text-green-400 hover:bg-green-400/10'
                                    : 'border-yellow-400 text-yellow-400 hover:bg-yellow-400/10' }}"
                        >
                            {{ $solve->dnf ? '↩ Activer' : 'DNF' }}
                        </button>
                        <button
                            wire:click="deleteSolve({{ $solve->id }})"
                            wire:loading.attr="disabled"
                            wire:confirm="Supprimer définitivement ce temps ?"
                            title="Supprimer"
                            class="px-2 py-1 text-xs rounded border border-red-400 text-red-400 hover:bg-red-400/10 transition"
                        >
                            ✕
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">Aucun résultat pour ce puzzle.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
function statsChart(data) {
    return {
        chart: null,
        init() {
            this.$nextTick(() => {
                const ctx = document.getElementById('statsChart');
                if (!ctx || typeof Chart === 'undefined') return;
                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        datasets: [{
                            label: 'Temps (s)',
                            data: data,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99,102,241,0.1)',
                            tension: 0.3,
                            pointRadius: 3,
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { type: 'linear', title: { display: true, text: 'N°' } },
                            y: { title: { display: true, text: 'Secondes' } },
                        }
                    }
                });
            });
        }
    };
}
</script>

