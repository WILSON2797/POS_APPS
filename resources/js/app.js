import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { createPinia } from 'pinia'
import Swal from 'sweetalert2'
import { useSwal } from '@/composables/useSwal'

// Expose SweetAlert2 and customized toast/alert helpers globally
window.Swal = Swal
const { showSuccess, showError, showWarning, showConfirm, showLoading, closeLoading } = useSwal()
window.showSuccess = showSuccess
window.showError = showError
window.showWarning = showWarning
window.showConfirm = showConfirm
window.showLoading = showLoading
window.closeLoading = closeLoading

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
