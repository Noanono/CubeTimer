import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const host = process.env.VITE_HOST || '0.0.0.0';
const port = process.env.VITE_PORT ? Number(process.env.VITE_PORT) : 5173;

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/scramble-worker.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        host,
        port,
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: undefined,
            },
        },
    },
});
