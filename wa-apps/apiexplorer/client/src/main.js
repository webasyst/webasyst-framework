import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import store from './store';
import mitt from 'mitt';
import Axios from 'axios';
import i18n from "./i18n";

const emitter = mitt();
const app = createApp(App);
app.use(store).use(router).use(i18n);
app.config.globalProperties.emitter = emitter;
app.config.globalProperties.axios = Axios;
app.config.globalProperties.axios.defaults.baseURL = window.appState.baseUrl;
app.config.globalProperties.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
app.config.globalProperties.axios.interceptors.response.use(
    response => {
        if ('wa-session-expired' in response.headers) {
            if ('development_mode' in window.appState) {
              window.location.href = window.appState.baseUrl;
            } else { 
              window.location.reload();
            }
            return;
        }
        return response;
    },
    error => {
        console.log(error);
    }
);
app.mount('#app');
