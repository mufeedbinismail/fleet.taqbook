import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    base: './',
    esbuild: {
        charset: 'ascii'
    },
    plugins: [
        laravel({
            input: [
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
