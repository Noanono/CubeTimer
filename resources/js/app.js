import './bootstrap';

// Re-apply dark mode class after every Livewire wire:navigate page swap
function applyTheme() {
    const theme = localStorage.getItem('theme');
    if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
}

document.addEventListener('livewire:navigated', applyTheme);

