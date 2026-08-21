import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';
import path from 'node:path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            ssr: false,
            refresh: true,
            buildDirectory: 'build',
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia({ ssr: { enabled: false } }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(import.meta.dirname, 'resources/js'),
        },
    },
    build: {
        outDir: path.resolve(import.meta.dirname, 'public/build'),
        emptyOutDir: false,
        sourcemap: false,
        minify: 'esbuild',
        reportCompressedSize: false,
        chunkSizeWarningLimit: 2000,
        rollupOptions: {
            output: {
                manualChunks: undefined,
            },
            onwarn(warning, warn) {
                if (warning.code === 'MODULE_NOT_FOUND') return;
                warn(warning);
            },
        },
    },
    ssr: {
        enabled: false,
    },
});
