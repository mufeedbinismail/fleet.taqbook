import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import postBuildPlugin from './vite-run-postbuild.js';

export default defineConfig({
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
            ],
            refresh: true,
        }),
        postBuildPlugin(),
    ],
});
