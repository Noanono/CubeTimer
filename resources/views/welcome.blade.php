<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CubeTimer — Chronométrez vos cubes</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/logo.ico">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Dark mode init -->
    <script>
        (function () {
            const theme = localStorage.getItem('theme');
            if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
</head>
<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">

    <!-- Navbar -->
    <header class="sticky top-0 z-50 bg-white/80 dark:bg-gray-900/80 backdrop-blur border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2">
                <img src="/logo.png" alt="CubeTimer" class="h-9 w-auto" />
                <span class="font-bold text-lg text-gray-900 dark:text-white">CubeTimer</span>
            </a>

            <div class="flex items-center gap-3">
                <!-- Dark mode toggle -->
                <button
                    x-data
                    @click="
                        const isDark = document.documentElement.classList.toggle('dark');
                        localStorage.setItem('theme', isDark ? 'dark' : 'light');
                    "
                    class="p-2 rounded-md text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                    title="Changer le thème"
                >
                    <svg class="hidden dark:block h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m8.66-9H21M3 12H2m15.07-6.07-.71.71M6.34 17.66l-.71.71M17.66 17.66l.71.71M6.34 6.34l.71-.71M12 5a7 7 0 1 0 0 14A7 7 0 0 0 12 5z" />
                    </svg>
                    <svg class="block dark:hidden h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                    </svg>
                </button>

                @if (Route::has('login'))
                    @auth
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                            Tableau de bord
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition">
                            Connexion
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="px-4 py-2 text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
                                S'inscrire
                            </a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </header>

    <!-- Hero -->
    <section class="max-w-6xl mx-auto px-4 sm:px-6 pt-20 pb-16 text-center">
        <div class="flex justify-center mb-6">
            <img src="/logo.png" alt="CubeTimer" class="h-24 w-24 object-contain drop-shadow-lg" />
        </div>
        <h1 class="text-4xl sm:text-5xl font-bold tracking-tight text-gray-900 dark:text-white mb-4">
            Chronométrez, analysez,<br class="hidden sm:block"> défiez vos amis.
        </h1>
        <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto mb-8">
            CubeTimer est une application dédiée aux passionnés de Rubik's cube. Générez des mélanges WCA, suivez vos progrès et affrontez d'autres cubers en temps réel.
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            @auth
                <a href="{{ route('timer') }}" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl shadow transition">
                    ⏱ Aller au Timer
                </a>
                <a href="{{ route('duel.lobby') }}" class="px-6 py-3 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 font-semibold rounded-xl shadow border border-gray-200 dark:border-gray-700 transition">
                    ⚔️ Lancer un Duel
                </a>
            @else
                <a href="{{ route('register') }}" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl shadow transition">
                    Commencer gratuitement
                </a>
                <a href="{{ route('login') }}" class="px-6 py-3 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 font-semibold rounded-xl shadow border border-gray-200 dark:border-gray-700 transition">
                    Se connecter
                </a>
            @endauth
        </div>
    </section>

    <!-- Features -->
    <section class="max-w-6xl mx-auto px-4 sm:px-6 pb-20">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">

            <!-- Timer -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition">
                <div class="text-4xl mb-4">⏱</div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Timer précis</h2>
                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                    Lancez le timer au clavier (espace) ou au bouton. Les mélanges sont générés automatiquement pour 8 types de cubes WCA, avec aperçu 2D et 3D interactif.
                </p>
            </div>

            <!-- Statistics -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition">
                <div class="text-4xl mb-4">📊</div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Statistiques détaillées</h2>
                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                    Suivez votre meilleur temps, Ao5, Ao12 et Ao100. Visualisez votre progression sur un graphique et gérez votre historique (DNF, suppression).
                </p>
            </div>

            <!-- Duel -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition">
                <div class="text-4xl mb-4">⚔️</div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Mode Duel</h2>
                <p class="text-gray-600 dark:text-gray-400 text-sm leading-relaxed">
                    Créez une salle ou rejoignez un ami via un code. Affrontez-vous sur le même mélange en temps réel grâce aux WebSockets. Les résultats sont sauvegardés dans vos stats.
                </p>
            </div>

        </div>
    </section>

    <!-- Supported puzzles -->
    <section class="bg-white dark:bg-gray-800 border-y border-gray-100 dark:border-gray-700 py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 text-center">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">Puzzles supportés</h2>
            <div class="flex flex-wrap justify-center gap-3 text-sm font-medium">
                @foreach (['3×3', '2×2', '4×4', '5×5', '6×6', '7×7', 'Pyraminx', 'Skewb'] as $p)
                    <span class="px-4 py-1.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded-full">
                        {{ $p }}
                    </span>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-8 text-center text-sm text-gray-500 dark:text-gray-500">
        CubeTimer &mdash; Fait avec ❤️ par un cuber pour les cubers
    </footer>

</body>
</html>
