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
        'import.meta.env.VITE_STRIPE_PUBLISHABLE_KEY': JSON.stringify(process.env.STRIPE_KEY || 'pk_test_51S43mAARmgIGJlKNpNChJnLQDrRhwf2XPjJDPzo3RgV2EdMzM4fYtwAqwRTON338K85Wr7q6vVjskH1b5pUD0QxS00nPKdCkgj'),
    },
});
