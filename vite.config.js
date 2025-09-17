import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import postBuildPlugin from './vite-run-postbuild.js';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/fa.css',
                'resources/js/fa.js',
            ],
            refresh: true,
        }),
        postBuildPlugin(),
    ],
    build: {
        minify: false,
    }
});
