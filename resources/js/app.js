import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';
import { ensurePrescriptionPrintFontLoaded } from '@/utils/prescriptionPrintFonts';

// Create Vue app
const app = createApp(App);

// Register plugins
app.use(createPinia());
app.use(router);

ensurePrescriptionPrintFontLoaded();

app.mount('#app');
