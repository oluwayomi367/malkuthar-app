import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            // app.js reste utilisé par les vues Blade classiques (admin,
            // espace vendeur/affilié, panier/checkout) — inchangées.
            // app.jsx est le point d'entrée Inertia + React de la vitrine
            // publique (voir resources/views/app.blade.php).
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/app.jsx'],
            refresh: true,
        }),
        tailwindcss(),
        react(),
    ],
});
