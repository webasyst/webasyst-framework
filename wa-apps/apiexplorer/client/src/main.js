import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import store from './store';
import mitt from 'mitt';
import Axios from 'axios';
import i18n from "./i18n.js";

// Костыль, который лечит ломающийся JQ
import $ from 'jquery';
$.ajaxPrefilter(function (settings, originalSettings, xhr) {
  if (settings.crossDomain || (settings.type||'').toUpperCase() !== 'POST' || (settings.contentType && settings.contentType.substr(0, 33) !== 'application/x-www-form-urlencoded')) {
      return;
  }

  var matches = document.cookie.match(new RegExp("(?:^|; )_csrf=([^;]*)"));
  if (!matches || !matches[1]) {
      return;
  }

  var csrf = decodeURIComponent(matches[1]);
  if (!settings.data && settings.data !== 0) settings.data = '';

  if (typeof(settings.data) === 'string') {
      if (settings.data.indexOf('_csrf=') === -1) {
          settings.data += (settings.data.length > 0 ? '&' : '') + '_csrf=' + csrf;
          xhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
      }
  } else if (typeof(settings.data) === 'object') {
      if (window.FormData && settings.data instanceof window.FormData) {
          if (typeof settings.data.set === "function") {
              settings.data.set('_csrf', csrf);
          } else {
              settings.data.append('_csrf', csrf);
          }
      } else {
          settings.data['_csrf'] = csrf;
      }
  }
});
window.$ = $;

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
        console.error(error);
    }
);
app.mount('#app');
