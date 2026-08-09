import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vitest/config';

// Kept apart from vite.config.js: that file is the Laravel asset build and carries a plugin that
// wants a running dev server, neither of which a test run has any use for. The alias is declared in
// both, so `@/` names one place whether it is read by the build or by a test.
export default defineConfig({
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        // Everything under test reaches for a document at some point — the axios wiring reads a
        // meta tag on import — so there is nothing to gain from sorting files by environment.
        environment: 'happy-dom',

        // tests/js mirrors resources/js: a test sits at the same path under a different root, so
        // where a module's tests live is never a question anyone has to answer twice.
        include: ['tests/js/**/*.test.js'],

        // Runs before each test file, not once per run, which is what gives every file its own
        // window.App and its own copy of any module that read one at import time.
        setupFiles: ['tests/js/setup.js'],
    },
});
