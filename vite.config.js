import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/form-safety.js',
                'resources/js/state-flow-diagram.js',
                'resources/js/swimlane-flow-diagram.js',
                'resources/js/architecture-c4-diagram.js',
                'resources/js/code-editor.js',
                'resources/js/project-export-print.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
