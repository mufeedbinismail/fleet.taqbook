import { readdirSync } from 'node:fs';
import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Every page module is its own entry, so a screen's script is a chunk only that screen fetches.
const pages = readdirSync('resources/js/pages', { recursive: true })
    .filter((file) => file.endsWith('.js'))
    .map((file) => `resources/js/pages/${file}`);

export default defineConfig({
    base: './',
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    esbuild: {
        charset: 'ascii',
    },
    plugins: [
        laravel({
            input: [
                'resources/css/index.css',
                'resources/css/fa.css',
                'resources/js/fa.js',
                'resources/css/plugins.css',
                'resources/js/plugins.js',
                'resources/js/app.js',
                ...pages,
            ],
            refresh: true,
        }),
    ],
});
