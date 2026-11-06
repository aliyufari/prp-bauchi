import '../css/app.css'

import { createInertiaApp, router } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import type { DefineComponent } from 'vue'
import { createApp, h } from 'vue'
import AppLoader from '@/components/ui/AppLoader.vue'
import { useLoader } from '@/composables/useLoader'
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

// The global overlay is reserved for full page navigation. Forms and table
// refreshes retain their own local loading indicators.
const loader = useLoader()
const SILENT_LOADER_SCOPES = ['form', 'datatable'] as const
let loaderTimer: ReturnType<typeof setTimeout> | null = null

const clearLoaderTimer = () => {
    if (loaderTimer) {
        clearTimeout(loaderTimer)
        loaderTimer = null
    }
}

const isSilentRequest = (visit: any): boolean => {
    const headers = visit?.headers ?? {}
    const scope = headers['x-loader-scope'] ?? headers['X-Loader-Scope']
    const datatable = headers['x-datatable'] ?? headers['X-Datatable']

    return visit?.silent === true || datatable === 'true' || SILENT_LOADER_SCOPES.includes(scope)
}

router.on('before', (event) => {
    clearLoaderTimer()
    if (isSilentRequest(event.detail.visit)) return

    loaderTimer = setTimeout(() => loader.start(), 120)
})

const finishVisit = () => {
    clearLoaderTimer()
    loader.finish()
}

// `finish` is the guaranteed cleanup path for success, validation errors,
// cancellations and failed visits. The other events also clean up immediately
// before Inertia performs its fallback navigation/error handling.
router.on('finish', finishVisit)
router.on('invalid', finishVisit)
router.on('exception', finishVisit)

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

    // resolve: async (name) => {
    //     const page: any = await resolvePageComponent(
    //         `./pages/${name}.vue`,
    //         import.meta.glob<DefineComponent>('./pages/**/*.vue')
    //     )
    //     // A page can opt out with `defineOptions({ layout: null })`
    //     if (page.default.layout === undefined) {
    //         page.default.layout = name.startsWith('auth/') ? AuthLayout
    //             : name.startsWith('portal/') ? PortalLayout
    //             : name.startsWith('errors/') ? null
    //             : AppLayout
    //     }
    //     return page
    // },

    setup({ el, App, props, plugin }) {
        const app = createApp({
            render: () => h('div', { class: 'min-h-screen bg-background' }, [
                h(App, props),
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