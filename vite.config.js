import path from 'path';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import vueDevTools from 'vite-plugin-vue-devtools';
import { PrimeVueResolver } from '@primevue/auto-import-resolver';
import Components from 'unplugin-vue-components/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/ts/app.ts', 'resources/scss/app.scss'],
            refresh: true,
        }),
        vueDevTools({
            appendTo: "app.ts"
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        Components({
            resolvers: [PrimeVueResolver()]
        })
    ],
    resolve: {
        alias: {
            "@": path.resolve(__dirname, "./resources/ts"),
            "@plugins": path.resolve(__dirname, "./resources/ts/plugins")
        }
    },
    css: {
        preprocessorOptions: {
            scss: {
                api: 'modern-compiler'
            }
        }
    },
    server: {
        watch: {
            ignored: [
                '**/storage/**'
            ]
        }
    }
});
