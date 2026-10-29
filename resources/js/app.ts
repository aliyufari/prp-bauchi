import '../css/app.css'

import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import type { DefineComponent } from 'vue'
import { createApp, h } from 'vue'
import { ZiggyVue } from 'ziggy-js'
import { initializeTheme } from './composables/useAppearance'
import AppLayout from '@/layouts/AppLayout.vue'
import PortalLayout from '@/layouts/PortalLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import AccountLayout from '@/layouts/AccountLayout.vue'

import echarts from './plugins/echarts'

import ECharts from 'vue-echarts'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel'

const layoutFor = (name: string) => {
    if (name.startsWith('auth/')) return AuthLayout
    if (name.startsWith('portal/')) return PortalLayout
    if (name.startsWith('settings/')) return AccountLayout
    return undefined 
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,




    resolve: async (name) => {
        const page: any = await resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        )
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
        const app = createApp({ render: () => h(App, props) })

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

    progress: {
        color: '#4B5563',
    },
})

initializeTheme()