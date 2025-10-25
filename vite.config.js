import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/backend/app.css',
                'resources/js/backend/app.js',
                'resources/css/frontend/app.css',
            ],
            refresh: true,
        }),
    ],
});
