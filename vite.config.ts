import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ command }) => ({
    plugins: [
        laravel({
            input: [],
            refresh: true,
        }),
        tailwindcss(),
    ],

    ...(command === 'serve' && {
        server: {
            allowedHosts: ['pawdesk.peniti.dev', 'assets.peniti.dev'],
            origin: 'https://assets.peniti.dev',
            cors: { origin: /^https?:\/\/(pawdesk\.|assets\.)?peniti\.dev$/ },
            host: '0.0.0.0',
            port: 5173,
            strictPort: true,
        },
    }),
}));
