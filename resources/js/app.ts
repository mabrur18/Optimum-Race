import { createInertiaApp } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';
import route from '@/composables/useRouteHelper';
import { createApp, type DefineComponent } from 'vue';

const appName = import.meta.env.VITE_APP_NAME || 'ORS (Optimum Racing System)';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('Public/'):
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
    setup({ el, App, props, plugin }) {
        const vueApp = createApp(App as DefineComponent, props);
        vueApp.use(plugin);
        vueApp.config.globalProperties.route = route;
        vueApp.config.globalProperties.$route = route;
        (window as any).route = route;
        return vueApp.mount(el);
    },
});

initializeTheme();
initializeFlashToast();
