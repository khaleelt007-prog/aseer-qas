import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { router } from '@inertiajs/vue3';
import i18n from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

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
            .use(i18n);

        // Set locale from props if available
        if (props.initialPage.props.locale) {
            i18n.global.locale.value = props.initialPage.props.locale;
        }

        // Listen for Inertia page visits to update locale
        router.on('success', (event) => {
            // Update Vue i18n locale when navigating to a new page
            const newLocale = event.detail.page.props.locale;
            if (newLocale && newLocale !== i18n.global.locale.value) {
                i18n.global.locale.value = newLocale;
                console.log('Locale updated to:', newLocale); // Debug log
            }
        });

        // Also listen for navigation start to ensure locale is updated
        router.on('navigate', (event) => {
            // Check if this is a language switch navigation
            if (event.detail.url && event.detail.url.pathname && event.detail.url.pathname.includes('/language/')) {
                console.log('Language switch navigation detected'); // Debug log
            }
        });

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
