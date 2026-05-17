import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { createPinia } from 'pinia'

// CoreUI
import CoreuiVue from '@coreui/vue'
import CIcon from '@coreui/icons-vue'
import { iconsSet as icons } from '@/assets/icons'

createInertiaApp({
  title: (title) => title ? `${title} - POS Apps` : 'POS Apps',
  resolve: (name) => {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
    return pages[`./Pages/${name}.vue`]
  },
  setup({ el, App, props, plugin }) {
    const app = createApp({ render: () => h(App, props) })

    app.use(plugin)
    app.use(createPinia())
    app.use(CoreuiVue)

    // Icons
    app.provide('icons', icons)
    app.component('CIcon', CIcon)

    app.mount(el)
  },
})
