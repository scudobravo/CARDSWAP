import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    define: {
        // La chiave Stripe viene passata tramite meta tag nel blade template
        // Questo permette di usare sempre la chiave corretta dal .env
        'import.meta.env.VITE_STRIPE_PUBLISHABLE_KEY': JSON.stringify(
            process.env.STRIPE_KEY || ''
        ),
    },
});
