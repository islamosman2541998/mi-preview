import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

/*
 * Preview templates + a small admin helper (resources/js/admin.js; Filament ships the rest).
 * Production: run `npm run build` locally and commit public/build
 * (no Node on the server).
 */
export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/templates/industrial.css',
                'resources/css/templates/editorial.css',
                'resources/css/templates/boutique.css',
                'resources/css/templates/bold.css',
                'resources/js/preview.js',
                'resources/js/admin.js',
            ],
            refresh: ['resources/views/**', 'lang/**'],
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
