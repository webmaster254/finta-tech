import { defineConfig } from 'vite';
import laravel, { refreshPaths }  from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/admin/theme.css',
                'resources/css/filament/admin/tooltip.css',
                'resources/css/filament/app/theme.css',
            ],
            refresh: [
                ...refreshPaths,
                'app/Livewire/**',
                'app/Filament/**',
            ],
        }),
    ],
    content: [
        './vendor/awcodes/filament-curator/resources/**/*.blade.php',
    ]
});
