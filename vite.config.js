import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

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
            ],
            refresh: true,
        }),
    ],
});
