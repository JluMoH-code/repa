import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// Плагин fonts (bunny) специально не используется — на каждый холодный старт
// dev-сервера он ходит в сеть за метриками шрифта на CDN Bunny Fonts. Из
// контейнера на Docker Desktop/WSL2 такой запрос заметно менее стабилен, чем
// файловый I/O, и иногда просто подвисает/таймаутит. Шрифты подключаем через
// системный стек в app.css (см. --font-sans), без внешних сетевых запросов.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost',
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
