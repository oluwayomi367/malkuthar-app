import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { route as routeFn } from 'ziggy-js';
import './consent';

// `route()` global — même ergonomie que le helper Blade, utilisable sans
// import dans chaque composant. Config lue depuis `window.Ziggy`, injectée
// par la directive `@routes` dans resources/views/app.blade.php.
globalThis.route = routeFn;

createInertiaApp({
    resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
