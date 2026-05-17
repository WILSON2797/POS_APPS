import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'

// CoreUI Vue components and icons
import CoreuiVue from '@coreui/vue'
import CIcon from '@coreui/icons-vue'
import { iconsSet as icons } from '@/assets/icons'

// Create Vue application instance
const app = createApp(App)

// Install plugins
app.use(createPinia())
app.use(router)
app.use(CoreuiVue)

// Provide icons globally
app.provide('icons', icons)

// Register global components
app.component('CIcon', CIcon)

// Mount application to DOM
app.mount('#app')
