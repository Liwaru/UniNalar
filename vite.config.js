import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/landing.css',
                'resources/js/landing.js',
                'resources/css/login.css',
                'resources/js/login.js',
                'resources/css/student-dashboard.css',
                'resources/js/student-dashboard.js',
                'resources/css/admin-academic.css',
            ],
            refresh: true,
        }),
        // Vite minifies after PostCSS removes imported charset declarations.
        tailwindcss({ optimize: false }),
    ],
    css: {
        postcss: {
            plugins: [{
                // Bootstrap's charset declaration becomes nested inside @layer
                // after import resolution. Vite emits UTF-8 assets already.
                postcssPlugin: 'uninalar-remove-imported-charset',
                AtRule: { charset: (rule) => rule.remove() },
            }],
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
