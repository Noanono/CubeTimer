<div
    x-data="duelTimer(@js($room->code), @js(auth()->id()), @js($room->scramble_seed), @js($room->puzzle_type), @js($hasSubmitted), @js(\Illuminate\Support\Facades\Vite::asset('resources/js/scramble-worker.js')), @js($room->participants->count()))"
    x-init="init()"
    @keydown.space.window.prevent="handleSpace()"
    @keyup.space.window.prevent="handleSpaceUp()"
    @opponent-joined.window="participantCount = 2"
    class="min-h-screen bg-gray-900 text-white flex flex-col"
>
    @php
        $puzzleTypeLabels = [
            '333' => '3x3x3', '222so' => '2x2x2', '444wca' => '4x4x4',
            '555wca' => '5x5x5', 'pyrso' => 'Pyraminx', 'mgmp' => 'Megaminx',
            'skbso' => 'Skewb', 'sqrs' => 'Square-1',
        ];
    @endphp
    <!-- Header salle -->
    <div class="bg-gray-800 border-b border-gray-700 px-6 py-4 flex items-center justify-between">
        <div>
            <span class="text-gray-400 text-sm">Salle :</span>
            <span class="font-mono font-bold text-xl ml-2 tracking-widest text-indigo-400">{{ $room->code }}</span>
            <button onclick="navigator.clipboard.writeText('{{ $room->code }}')"
                class="ml-2 text-xs text-gray-500 hover:text-gray-300">📋</button>
        </div>
        <div class="text-sm text-gray-400">
            {{ $puzzleTypeLabels[$room->puzzle_type] ?? $room->puzzle_type }}
            &nbsp;|&nbsp;
            <span :class="{
                'text-yellow-400': '{{ $room->status }}' === 'waiting',
                'text-green-400': '{{ $room->status }}' === 'in_progress',
                'text-gray-400': '{{ $room->status }}' === 'finished',
            }">
                @if($room->status === 'waiting') En attente
                @elseif($room->status === 'in_progress') En cours
                @else Terminé
                @endif
            </span>
        </div>
    </div>

    <!-- Scramble -->
    <div class="text-center py-6 px-4">
        <p x-text="scramble || 'Chargement du mélange…'"
           class="font-mono text-lg text-gray-300 min-h-[2rem]"></p>
    </div>

    <!-- Résultats des deux joueurs -->
    <div class="grid grid-cols-2 gap-4 px-6 mb-6">
        @foreach($room->participants->sortBy('id') as $participant)
        <div class="bg-gray-800 rounded-xl p-5 text-center border
            {{ $participant->user_id === auth()->id() ? 'border-indigo-500' : 'border-gray-600' }}">
            <p class="text-sm text-gray-400">
                {{ $participant->user->name }}
                @if($participant->user_id === auth()->id()) <span class="text-indigo-400">(vous)</span> @endif
            </p>
            @if($participant->finished_at)
                <p class="text-3xl font-mono font-bold mt-2
                    {{ $participant->dnf ? 'text-red-400' : 'text-green-400' }}">
                    {{ $participant->getFormattedTime() }}
                </p>
            @else
                <p class="text-2xl font-mono mt-2 text-gray-500" x-show="{{ $participant->user_id === auth()->id() ? 'true' : 'false' }}">
                    <span x-text="displayTime"></span>
                </p>
                <p class="text-gray-500 mt-2" x-show="{{ $participant->user_id !== auth()->id() ? 'true' : 'false' }}">
                    En attente…
                </p>
            @endif
        </div>
        @endforeach

        <!-- Placeholder si 1 seul joueur -->
        @if($room->participants->count() < 2)
        <div class="bg-gray-800 rounded-xl p-5 text-center border border-dashed border-gray-600 flex items-center justify-center">
            <p class="text-gray-500 text-sm">En attente d'un adversaire…</p>
        </div>
        @endif
    </div>

    <!-- Résultat final -->
    @if($room->status === 'finished')
    @php
        $sorted = $room->participants->filter(fn($p) => !$p->dnf && $p->time_ms !== null)->sortBy('time_ms');
        $winner = $sorted->first();
        $myParticipant = $room->participants->firstWhere('user_id', auth()->id());
    @endphp
    <div class="text-center py-4">
        @if($winner && $winner->user_id === auth()->id())
            <p class="text-4xl">🏆 Vous avez gagné !</p>
        @elseif($winner)
            <p class="text-4xl">😔 {{ $winner->user->name }} a gagné.</p>
        @else
            <p class="text-2xl text-yellow-400">Égalité ou DNF !</p>
        @endif
        <a href="{{ route('duel.lobby') }}" wire:navigate class="inline-block mt-4 px-6 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-lg text-white font-bold transition">
            Nouveau duel
        </a>
    </div>
    @endif

    <!-- Timer interactif (masqué si déjà soumis) -->
    @if(!$hasSubmitted && $room->status !== 'finished')
    <div class="flex-1 flex flex-col items-center justify-center"
        @if($room->participants->count() < 2) wire:poll.3s="checkRoomReady" @endif
    >

        @if($room->participants->count() < 2)
        <div class="mb-6 text-center px-8">
            <div class="inline-flex items-center space-x-2 bg-yellow-500/10 border border-yellow-500/30 rounded-xl px-6 py-4">
                <span class="text-2xl">⏳</span>
                <div class="text-left">
                    <p class="text-yellow-400 font-semibold">En attente d'un adversaire</p>
                    <p class="text-gray-400 text-sm mt-0.5">Partagez le code <span class="font-mono font-bold text-white">{{ $room->code }}</span> pour qu'il vous rejoigne.</p>
                </div>
            </div>
        </div>
        @endif

        <div class="select-none cursor-pointer" @click="running && stop()">
            <span x-text="displayTime"
                :class="{
                    'text-red-400': holding && !readyToStart,
                    'text-green-400': holding && readyToStart,
                    'text-white': running,
                    'text-gray-400': !running
                }"
                class="text-8xl font-mono font-bold transition-colors duration-100">
            </span>
        </div>

        <p class="mt-4 text-sm text-gray-500">
            <span x-show="!canStart" class="text-yellow-400">Le timer sera disponible dès que l'adversaire rejoint.</span>
            <span x-show="canStart && !running && !holding">Appuyez sur <kbd class="bg-gray-700 px-2 py-1 rounded text-xs">Espace</kbd> pour démarrer</span>
            <span x-show="canStart && holding && !readyToStart">Maintenez…</span>
            <span x-show="canStart && holding && readyToStart" class="text-green-400">Relâchez !</span>
            <span x-show="running">Appuyez sur <kbd class="bg-gray-700 px-2 py-1 rounded text-xs">Espace</kbd> pour arrêter</span>
        </p>

        <button
            class="mt-8 w-32 h-32 rounded-full font-bold text-lg select-none transition-all duration-150"
            :class="{
                'bg-gray-700 cursor-not-allowed opacity-50': !canStart,
                'bg-red-600 scale-95': canStart && holding && !readyToStart,
                'bg-green-500': canStart && holding && readyToStart,
                'bg-indigo-600 hover:bg-indigo-700': canStart && !running && !holding,
                'bg-yellow-500': running
            }"
            @pointerdown.prevent="handleSpace()"
            @pointerup.prevent="handleSpaceUp()"
        >
            <span x-text="running ? '■' : '▶'"></span>
        </button>
    </div>
    @elseif($hasSubmitted && $room->status !== 'finished')
    <div class="flex-1 flex items-center justify-center">
        <p class="text-gray-400 text-lg">Temps soumis, en attente de l'adversaire…</p>
    </div>
    @endif

    <!-- Prévisualisation 2D (bas droite) -->
    <div
        x-show="cubeImageSvg"
        x-transition
        @click="openPreview3D()"
        title="Cliquer pour voir en 3D"
        class="fixed bottom-6 right-6 z-20 bg-gray-800 rounded-2xl shadow-xl p-2 cursor-pointer hover:ring-2 hover:ring-indigo-400 transition group w-36"
    >
        <div x-html="cubeImageSvg" class="pointer-events-none"></div>
        <p class="text-center text-xs text-gray-500 group-hover:text-indigo-400 mt-1">Vue 3D →</p>
    </div>

    <!-- Modal 3D -->
    <div
        x-show="show3D"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click.self="show3D = false"
        @keydown.escape.window="show3D = false"
        class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
        style="display:none"
    >
        <div class="relative bg-gray-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-700">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-700">
                <div class="min-w-0 flex-1">
                    <h3 class="font-bold text-white">Prévisualisation 3D</h3>
                    <p class="text-xs text-gray-400 mt-0.5 font-mono truncate" x-text="scramble"></p>
                </div>
                <button @click="show3D = false" class="ml-4 flex-shrink-0 text-gray-400 hover:text-white text-2xl leading-none">&times;</button>
            </div>
            <div x-ref="preview3dContainer" class="bg-gray-800 min-h-[380px] flex items-center justify-center">
                <p class="text-gray-500 text-sm">Chargement…</p>
            </div>
        </div>
    </div>
</div>

<script>
function duelTimer(roomCode, userId, seed, puzzleType, alreadySubmitted, workerUrl, participantCount) {
    return {
        roomCode,
        userId,
        seed,
        puzzleType,
        scramble: '',
        cubeImageSvg: '',
        show3D: false,
        worker: null,
        running: false,
        holding: false,
        readyToStart: false,
        holdTimer: null,
        startTime: null,
        elapsed: 0,
        tickInterval: null,
        hasSubmitted: alreadySubmitted,
        participantCount,
        channel: null,

        get canStart() {
            return this.participantCount >= 2;
        },

        get displayTime() {
            const ms = this.elapsed;
            if (ms === 0) return '0.00';
            const minutes = Math.floor(ms / 60000);
            const seconds = Math.floor((ms % 60000) / 1000);
            const hundredths = Math.floor((ms % 1000) / 10);
            return minutes > 0
                ? `${minutes}:${String(seconds).padStart(2,'0')}.${String(hundredths).padStart(2,'0')}`
                : `${seconds}.${String(hundredths).padStart(2,'0')}`;
        },

        init() {
            this.initWorker();
            this.initEcho();
        },

        initWorker() {
            try {
                this.worker = new Worker(workerUrl, { type: 'module' });
                this.worker.onmessage = (e) => {
                    if (e.data.type === 'scramble') {
                        this.scramble = e.data.scramble;
                        this.cubeImageSvg = e.data.svgImage || '';
                        @this.set('room.scramble_text', e.data.scramble);
                    }
                };
                this.worker.postMessage({ type: 'generate', puzzleType: this.puzzleType, seed: this.seed });
            } catch (err) {
                this.scramble = 'Worker non disponible. Utilisez le mélange partagé.';
            }
        },

        initEcho() {
            if (!window.Echo) return;
            this.channel = window.Echo.channel('duel.' + this.roomCode);
            this.channel.listen('.time.submitted', (data) => {
                if (data.userId !== this.userId) {
                    @this.$refresh();
                }
            });
        },

        handleSpace() {
            if (this.hasSubmitted || !this.canStart) return;
            if (this.running) { this.stop(); return; }
            if (this.holding) return;
            this.holding = true;
            this.readyToStart = false;
            this.holdTimer = setTimeout(() => { this.readyToStart = true; }, 550);
        },

        handleSpaceUp() {
            if (!this.canStart) return;
            if (this.running) return;
            clearTimeout(this.holdTimer);
            if (this.readyToStart) this.start();
            this.holding = false;
            this.readyToStart = false;
        },

        start() {
            this.elapsed = 0;
            this.startTime = Date.now();
            this.running = true;
            this.tickInterval = setInterval(() => {
                this.elapsed = Date.now() - this.startTime;
            }, 10);
        },

        stop() {
            clearInterval(this.tickInterval);
            this.running = false;
            const finalMs = Date.now() - this.startTime;
            this.elapsed = finalMs;
            this.hasSubmitted = true;
            @this.call('submitTime', finalMs);
        },

        get twistyPuzzle() {
            const map = {
                '333': '3x3x3', '222so': '2x2x2', '444wca': '4x4x4',
                '555wca': '5x5x5', 'pyrso': 'pyraminx', 'mgmp': 'megaminx',
                'skbso': 'skewb', 'sqrs': 'sq1',
            };
            return map[this.puzzleType] || '3x3x3';
        },

        async openPreview3D() {
            this.show3D = true;
            await this.$nextTick();
            const container = this.$refs.preview3dContainer;
            if (!container) return;
            if (!customElements.get('twisty-player')) {
                await import('https://cdn.cubing.net/js/cubing/twisty');
            }
            let player = container.querySelector('twisty-player');
            if (!player) {
                player = document.createElement('twisty-player');
                player.style.width = '100%';
                player.style.height = '380px';
                container.appendChild(player);
            }
            player.setAttribute('puzzle', this.twistyPuzzle);
            player.setAttribute('alg', this.scramble);
            player.setAttribute('hint-facelets', 'none');
            player.setAttribute('back-view', 'none');
        },
    };
}
</script>

