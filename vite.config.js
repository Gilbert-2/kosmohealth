import { defineConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue()
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url))
        }
    },
    build: {
        rollupOptions: {
            external: ['crypto', 'axios'],
            output: {
                manualChunks: {
                    'face-api': ['face-api.js']
                }
            }
        },
        assetsDir: 'public/js/face-api-models',
        emptyOutDir: false,
        copyPublicDir: true
    }
});

