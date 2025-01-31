//import Vue from "vue";
import { createI18n } from "vue-i18n";
import messages from "@intlify/unplugin-vue-i18n/messages";
//Vue.use(VueI18n);

export default createI18n({
  warnHtmlMessage: false,
  locale: window.appState.locale || "en",
  fallbackLocale: "en",
  messages: messages
});
