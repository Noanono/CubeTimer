<div
    x-data="cubeTimer(@js($puzzleType), @js($scramble), @js(\Illuminate\Support\Facades\Vite::asset('resources/js/scramble-worker.js')))"
    x-init="init()"
    @keydown.space.window.prevent="handleSpace()"
    @keyup.space.window.prevent="handleSpaceUp()"
    @puzzle-changed.window="changePuzzle($event.detail.puzzleType)"
    @time-saved.window="generateScramble()"
    class="min-h-screen flex flex-col items-center justify-start pt-8 pb-16 bg-gray-50 dark:bg-gray-900"
>
    <!-- Sélecteur de puzzle -->
    <div class="w-full max-w-2xl px-4 mb-6">
        <select
            wire:model.live="puzzleType"
            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-4 py-2 focus:ring-2 focus:ring-indigo-500"
        >
            @foreach($puzzleTypes as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <!-- Scramble -->
    <div class="w-full max-w-2xl px-4 mb-8 text-center">
        <p x-text="currentScramble || 'Chargement du mélange…'"
           class="text-lg font-mono text-gray-700 dark:text-gray-300 min-h-[2rem]"></p>
        <button
            @click="generateScramble()"
            class="mt-2 text-xs text-indigo-500 hover:underline"
            x-show="!running"
        >Nouveau mélange</button>
    </div>

    <!-- Timer display -->
    <div
        class="select-none cursor-pointer"
        @click="handleClick()"
    >
        <span
            x-text="displayTime"
            :class="{
                'text-red-500': holding && !readyToStart,
                'text-green-400': holding && readyToStart,
                'text-white': running,
                'text-gray-200': !running && !holding
            }"
            class="text-8xl md:text-9xl font-mono font-bold tracking-tighter transition-colors duration-100"
        ></span>
    </div>

    <!-- Instruction -->
    <p class="mt-6 text-sm text-gray-400 dark:text-gray-500">
        <span x-show="!running && !holding">Appuyez sur <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs">Espace</kbd> ou maintenez le bouton pour démarrer</span>
        <span x-show="holding && !readyToStart">Maintenez…</span>
        <span x-show="holding && readyToStart">Relâchez pour démarrer !</span>
        <span x-show="running">Appuyez sur <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs">Espace</kbd> pour arrêter</span>
    </p>

    <!-- Bouton tactile -->
    <button
        class="mt-8 w-40 h-40 rounded-full text-white font-bold text-xl shadow-xl transition-all duration-150 select-none touch-none"
        :class="{
            'bg-red-500 scale-95': holding && !readyToStart,
            'bg-green-500 scale-100': holding && readyToStart,
            'bg-indigo-500 hover:bg-indigo-600': !running && !holding,
            'bg-yellow-500 hover:bg-yellow-600': running
        }"
        @pointerdown.prevent="handleSpace()"
        @pointerup.prevent="handleSpaceUp()"
        @touchstart.prevent="handleSpace()"
        @touchend.prevent="handleSpaceUp()"
    >
        <span x-show="!running">▶</span>
        <span x-show="running">■</span>
    </button>

    <!-- Dernier temps sauvegardé -->
    @if($lastTimeMs !== null)
    <div class="mt-6 text-center">
        <p class="text-gray-400 text-sm">Dernier temps</p>
        <p class="text-2xl font-mono text-indigo-400">
            {{ $this->formatLastTime($lastTimeMs) }}
        </p>
    </div>
    @endif

    <!-- Prévisualisation 2D (bas droite) -->
    <div
        x-show="cubeImageSvg && !running"
        x-transition
        @click="openPreview3D()"
        title="Cliquer pour voir en 3D"
        class="fixed bottom-6 right-6 z-20 bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-2 cursor-pointer hover:ring-2 hover:ring-indigo-400 transition group w-36"
    >
        <div x-html="cubeImageSvg" class="pointer-events-none"></div>
        <p class="text-center text-xs text-gray-400 group-hover:text-indigo-400 mt-1">Vue 3D →</p>
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
        class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4"
        style="display:none"
    >
        <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="min-w-0 flex-1">
                    <h3 class="font-bold text-gray-900 dark:text-white">Prévisualisation 3D</h3>
                    <p class="text-xs text-gray-400 mt-0.5 font-mono truncate" x-text="currentScramble"></p>
                </div>
                <button @click="show3D = false" class="ml-4 flex-shrink-0 text-gray-400 hover:text-gray-700 dark:hover:text-white text-2xl leading-none">&times;</button>
            </div>
            <div x-ref="preview3dContainer" class="bg-gray-50 dark:bg-gray-800 min-h-[380px] flex items-center justify-center">
                <p class="text-gray-400 text-sm">Chargement…</p>
            </div>
        </div>
    </div>
</div>

<script>
function cubeTimer(initialPuzzle, initialScramble, workerUrl) {
    return {
        puzzleType: initialPuzzle,
        currentScramble: initialScramble || '',
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

        get displayTime() {
            if (this.elapsed === 0 && !this.running) return '0.00';
            const ms = this.elapsed;
            const minutes = Math.floor(ms / 60000);
            const seconds = Math.floor((ms % 60000) / 1000);
            const hundredths = Math.floor((ms % 1000) / 10);
            if (minutes > 0) {
                return `${minutes}:${String(seconds).padStart(2,'0')}.${String(hundredths).padStart(2,'0')}`;
            }
            return `${seconds}.${String(hundredths).padStart(2,'0')}`;
        },

        init() {
            this.initWorker(workerUrl);
            if (!this.currentScramble) {
                this.generateScramble();
            }
        },

        initWorker(url) {
            try {
                this.worker = new Worker(url, { type: 'module' });
                this.worker.onmessage = (e) => {
                    if (e.data.type === 'scramble') {
                        this.currentScramble = e.data.scramble;
                        this.cubeImageSvg = e.data.svgImage || '';
                        @this.set('scramble', e.data.scramble);
                    }
                };
            } catch (err) {
                console.warn('Worker non disponible.', err);
            }
        },

        generateScramble() {
            this.cubeImageSvg = '';
            if (this.worker) {
                this.worker.postMessage({ type: 'generate', puzzleType: this.puzzleType });
            }
        },

        changePuzzle(newType) {
            this.puzzleType = newType;
            this.elapsed = 0;
            this.currentScramble = '';
            this.generateScramble();
        },

        handleSpace() {
            if (this.running) {
                this.stop();
                return;
            }
            if (this.holding) return;
            this.holding = true;
            this.readyToStart = false;
            this.holdTimer = setTimeout(() => {
                this.readyToStart = true;
            }, 550);
        },

        handleSpaceUp() {
            if (this.running) return;
            clearTimeout(this.holdTimer);
            if (this.readyToStart) {
                this.start();
            }
            this.holding = false;
            this.readyToStart = false;
        },

        handleClick() {
            if (this.running) {
                this.stop();
            }
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
            @this.call('saveTime', finalMs);
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
            player.setAttribute('alg', this.currentScramble);
            player.setAttribute('hint-facelets', 'none');
            player.setAttribute('back-view', 'none');
        },
    };
}
</script>

