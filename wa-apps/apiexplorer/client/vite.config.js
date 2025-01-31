import { fileURLToPath, URL } from "node:url";
import { defineConfig, loadEnv } from "vite";
import { resolve, dirname } from "node:path";
import vue from "@vitejs/plugin-vue";
import VitePluginHtmlEnv from "vite-plugin-html-env";
import VueI18nPlugin from "@intlify/unplugin-vue-i18n/vite";
import inject from "@rollup/plugin-inject";

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd());
    return {
      plugins: [
        inject({   // => that should be first under plugins array
          $: 'jquery',
          jQuery: 'jquery',
        }),
        vue(), VitePluginHtmlEnv(),
        VueI18nPlugin({
            include: resolve(dirname(fileURLToPath(import.meta.url)), './src/locales/**'),
        })
      ],
      resolve: {
        alias: {
          "@": fileURLToPath(new URL("./src", import.meta.url)),
        },
      },
      server: {
        proxy: {
          "^/(webasyst/|wa-content/|wa-apps/|wa-data/|api.php)": {
            target: env.VITE_APP_WA_DEV,
            changeOrigin: true,
          },
        },
      },
      build: {
        rollupOptions: {
          output: {
            entryFileNames: `assets/[name].js`,
            chunkFileNames: `assets/[name].js`,
            assetFileNames: `assets/[name].[ext]`,
          },
        },
      },
    };
});
