import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import liveReload from 'vite-plugin-live-reload';
import path from 'path';
import AutoImport from 'unplugin-auto-import/vite';
import Components from 'unplugin-vue-components/vite';
import { ElementPlusResolver } from 'unplugin-vue-components/resolvers';

const pluginFolder = path.basename(__dirname);
const inputs = [
    'resources/admin/app.js',
    'resources/admin/start.js',
    'resources/scss/admin.scss',
    'public/js/public-tracking.js',
    'public/css/public-tracking.css',
    'public/css/fluent-cart-tracking.css'
];

export default defineConfig(({ mode }) => {
    const isDev = mode === 'development';

    return {
        base: `/wp-content/plugins/${pluginFolder}/assets/`,
        plugins: [
            vue(),
            liveReload([`${__dirname}/**/*.php`]),
            viteStaticCopy({
                targets: [
                    { src: 'resources/images/*', dest: 'images' },
                    { src: 'resources/public/lib', dest: 'public/' }
                ]
            }),
            AutoImport({ resolvers: [ElementPlusResolver()] }),
            Components({ resolvers: [ElementPlusResolver()], directives: false })
        ],
        define: { __DEV__: isDev },
        build: {
            manifest: true,
            outDir: 'assets',
            assetsDir: '',
            emptyOutDir: true,
            rollupOptions: {
                input: inputs,
                output: {
                    chunkFileNames: '[name].js',
                    entryFileNames: '[name].js',
                }
            }
        },
        resolve: {
            alias: {
                vue: 'vue/dist/vue.esm-bundler.js',
                '@': path.resolve(__dirname, 'resources/admin')
            },
            extensions: ['.js', '.vue', '.json']
        },
        server: {
            port: 8880,
            cors: true,
            strictPort: true,
            hmr: { port: 8880, host: 'localhost', protocol: 'ws' }
        }
    };
});
