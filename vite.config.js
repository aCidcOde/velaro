import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/panel.css',
                'resources/js/panel.js',
                'resources/css/agente.css',
                'resources/js/agente.js',
                'resources/css/public-solid-state.css',
                'resources/js/public-solid-state.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        cors: true,
    },
});
