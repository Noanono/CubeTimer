<x-app-layout title="Accueil">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Tableau de bord
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Timer Solo -->
                <a href="{{ route('timer') }}" wire:navigate
                   class="block p-8 bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition group">
                    <div class="flex items-center space-x-4">
                        <div class="text-4xl">⏱️</div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 group-hover:text-indigo-500">
                                Timer Solo
                            </h3>
                            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                                Chronométrez vos résolutions et sauvegardez vos temps.
                            </p>
                        </div>
                    </div>
                </a>

                <!-- Statistiques -->
                <a href="{{ route('statistics') }}" wire:navigate
                   class="block p-8 bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition group">
                    <div class="flex items-center space-x-4">
                        <div class="text-4xl">📊</div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 group-hover:text-indigo-500">
                                Statistiques
                            </h3>
                            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                                Consultez vos meilleures moyennes et votre progression.
                            </p>
                        </div>
                    </div>
                </a>

                <!-- Duel -->
                <a href="{{ route('duel.lobby') }}" wire:navigate
                   class="block p-8 bg-white dark:bg-gray-800 rounded-xl shadow hover:shadow-lg transition group md:col-span-2">
                    <div class="flex items-center space-x-4">
                        <div class="text-4xl">⚔️</div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 group-hover:text-red-500">
                                Mode Duel
                            </h3>
                            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                                Affrontez un autre joueur sur le même mélange en temps réel.
                            </p>
                        </div>
                    </div>
                </a>

            </div>
        </div>
    </div>
</x-app-layout>

