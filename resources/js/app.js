import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { hidePreloader } from './preloader';

const appName = import.meta.env.VITE_APP_NAME || 'معرض أبو عفيفة للمفروشات';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);

        // Mounted and painted — the boot overlay has done its job. Inertia's
        // own progress bar covers navigations from here on.
        hidePreloader();

        return app;
    },
    progress: {
        color: '#c86820',
    },
});
