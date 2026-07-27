import { App as RuntimeApp, createApp } from 'vue'
import App from '@/App.vue'
import router from '@/router'
import Aura from '@primevue/themes/aura'
import PrimeVue from 'primevue/config'
import ConfirmationService from 'primevue/confirmationservice'
import ToastService from 'primevue/toastservice'
import { definePreset } from '@primevue/themes'

const app: RuntimeApp<Element> = createApp(App)

const Preset: typeof Aura = definePreset(Aura, {
    components: {
        breadcrumb: {
            root: {
                background: 'none',
            },
        },
    },
})

app.use(router)
app.use(PrimeVue, {
    theme: {
        preset: Preset,
        options: {
            darkModeSelector: '.app-dark',
        },
    },
})
app.use(ToastService)
app.use(ConfirmationService)

app.mount('#app')
