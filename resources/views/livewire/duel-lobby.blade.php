<div class="py-10 px-4 max-w-2xl mx-auto space-y-8">

    <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 text-center">⚔️ Mode Duel</h2>

    @if($errorMessage)
    <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg">
        {{ $errorMessage }}
    </div>
    @endif

    <!-- Sélecteur puzzle -->
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type de puzzle</label>
        <select wire:model="puzzleType"
            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-4 py-2">
            @foreach($puzzleTypes as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <!-- Créer une salle -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 space-y-4">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Créer une salle</h3>
        <p class="text-gray-500 dark:text-gray-400 text-sm">Génère un code de salle à partager avec votre adversaire.</p>
        <button wire:click="createRoom" wire:loading.attr="disabled"
            class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg transition">
            <span wire:loading.remove>Créer la salle</span>
            <span wire:loading>Création…</span>
        </button>
    </div>

    <!-- Rejoindre par code -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 space-y-4">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Rejoindre par code</h3>
        <div class="flex space-x-3">
            <input
                wire:model="joinCode"
                type="text"
                placeholder="Code à 6 caractères"
                maxlength="8"
                class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-4 py-2 uppercase font-mono tracking-widest"
            />
            <button wire:click="joinRoom" wire:loading.attr="disabled"
                class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition">
                Rejoindre
            </button>
        </div>
    </div>

    <!-- Inviter par username -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 space-y-4">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Inviter un joueur</h3>
        <div class="flex space-x-3">
            <input
                wire:model="inviteUsername"
                type="text"
                placeholder="Nom d'utilisateur"
                class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-4 py-2"
            />
            <button wire:click="inviteByUsername" wire:loading.attr="disabled"
                class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-lg transition">
                Inviter
            </button>
        </div>
        <p class="text-xs text-gray-400">L'adversaire pourra rejoindre via le code de la salle créée.</p>
    </div>

</div>

