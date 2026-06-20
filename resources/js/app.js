import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';
import { ensurePrescriptionPrintFontLoaded } from '@/utils/prescriptionPrintFonts';
import { registerToastApiErrorPlugin } from '@/plugins/toastApiErrorPlugin';

// Create Vue app
const app = createApp(App);

const pinia = createPinia();
registerToastApiErrorPlugin(pinia);

// Register plugins
app.use(pinia);
app.use(router);

ensurePrescriptionPrintFontLoaded();

app.mount('#app');
