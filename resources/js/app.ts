import '../css/app.css'

import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import type { DefineComponent } from 'vue'
import { createApp, h } from 'vue'
import AppLoader from '@/components/ui/AppLoader.vue'
import ToastHost from '@/components/ui/ToastHost.vue'
import { useLoader } from '@/composables/useLoader'
import { setupPageLoader } from '@/lib/pageLoader'
import { ZiggyVue } from 'ziggy-js'
import { initializeTheme } from './composables/useAppearance'
import AppLayout from '@/layouts/AppLayout.vue'
import PortalLayout from '@/layouts/PortalLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import AccountLayout from '@/layouts/AccountLayout.vue'

import echarts from './plugins/echarts'

import ECharts from 'vue-echarts'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel'
const dashboardIndexPage = () => import('./pages/dashboard/Index.vue')

// The global overlay is reserved for page navigations only (see lib/pageLoader.ts).
// Buttons, forms and table refreshes keep their own local loading indicators.
const loader = useLoader()
setupPageLoader()

const layoutFor = (name: string) => {
    if (name.startsWith('auth/')) return AuthLayout
    if (name.startsWith('portal/')) return PortalLayout
    if (name.startsWith('settings/')) return AccountLayout
    return undefined
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,

    resolve: async (name) => {
        // Keep a single canonical dashboard entry point. The legacy Dashboard.vue
        // page is retained for compatibility, while the server route resolves to
        // dashboard/Index.vue.
        const pages = import.meta.glob<DefineComponent>('./pages/**/*.vue')
        const normalizedName = name.replace(/\.vue$/i, '').replace(/^\/+|\/+$/g, '')
        const requested = `./pages/${normalizedName}.vue`
        const dashboardAliases: Record<string, string> = {
            dashboard: './pages/dashboard/Index.vue',
            'Dashboard': './pages/dashboard/Index.vue',
        }
        const resolvedPath = dashboardAliases[normalizedName] ?? requested
        const page: any = normalizedName.toLowerCase() === 'dashboard/index' || normalizedName.toLowerCase() === 'dashboard'
            ? await dashboardIndexPage()
            : await resolvePageComponent(resolvedPath, pages)
        // A page can opt out with `defineOptions({ layout: null })`
        if (page.default.layout === undefined) {
            const layout = layoutFor(name)
            if (layout) page.default.layout = layout
        }
        return page
    },

    setup({ el, App, props, plugin }) {
        const app = createApp({
            render: () => h('div', { class: 'min-h-screen bg-background' }, [
                h(App, props),
                h(ToastHost),
                loader.isLoading.value ? h(AppLoader) : null,
            ]),
        })

        app.directive('click-outside', {
            mounted(el: any, binding: any) {
                el.clickOutsideEvent = (event: MouseEvent) => {
                    if (!(el === event.target || el.contains(event.target))) {
                        binding.value(event)
                    }
                }

                document.addEventListener('click', el.clickOutsideEvent)
            },

            unmounted(el: any) {
                document.removeEventListener('click', el.clickOutsideEvent)
            },
        })

        app.use(plugin)
            .use(ZiggyVue)

        app.component('VChart', ECharts)

        app.config.globalProperties.$echarts = echarts

        app.mount(el)
    },

    progress: false,
})

initializeTheme()