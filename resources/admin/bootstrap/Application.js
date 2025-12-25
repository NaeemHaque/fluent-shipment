import { createApp, reactive } from 'vue';
import mixins from '@/mixins';
import config from './config';
import Rest from '@/utils/http/Rest';
import { ElNotification } from 'element-plus';

export default class Application {

    static create(ApplicationComponent, plugins = []) {
        return new this().createVueApp(ApplicationComponent, plugins);
    }

    createVueApp(ApplicationComponent, plugins = []) {
        const app = createApp(ApplicationComponent, {
            baseUrl: fluentFrameworkAdmin.baseUrl || '#',
            logoUrl: fluentFrameworkAdmin.logoUrl || '',
        });

        this.registerMixins(app);
        this.registerGlobalProperties(app);
        Rest.setInstance(app);
        this.registerPlugins(app, plugins);
        this.registerGlobalErrorHandlers(app);

        return this.instance(app);
    }

    registerMixins(app) {
        mixins.forEach(mixin => app.mixin(mixin));
    }

    registerGlobalProperties(app) {
        app.config.globalProperties.appVars = fluentFrameworkAdmin;
        Object.assign(config, fluentFrameworkAdmin);
    }

    registerPlugins(app, plugins = []) {
        plugins.forEach(plugin => {
            if (Array.isArray(plugin)) {
                // plugin[0] = plugin object,
                // plugin[1] = options
                app.use(plugin[0], plugin[1]);
            } else {
                app.use(plugin);
            }
        });

        this.registerValidationErrorHandler(app);
    }

    registerValidationErrorHandler(app) {
        app.use({
            install(app) {
                const validationErrors = reactive({});
                const $gp = app.config.globalProperties;

                $gp.$validationErrors = validationErrors;

                $gp.$clearErrors = () => {
                    Object.keys(validationErrors).forEach(key => delete validationErrors[key]);
                };

                $gp.$handleValidationError = function(err) {
                    const errors = err.errors || err.all?.() || {};
                    this.$clearErrors();
                    Object.entries(errors).forEach(([key, val]) => {
                        validationErrors[key] = val;
                    });
                };
            }
        });
    }

    registerGlobalErrorHandlers(app) {
        // Vue error handler
        app.config.errorHandler = (err, vm, info) => {
            if (err.status === 422 && vm?.$handleValidationError) {
                return vm.$handleValidationError(err);
            }

            console.groupCollapsed(
                `%c[ERROR] Fluent - Vue Global Error\n%c${err?.message || err}`,
                "background: darkred; color: white; font-weight: bold; padding: 2px 4px; border-radius: 3px;",
                "color: darkorange;"
            );
            if (vm) console.log("%cComponent:", "color: royalblue;", vm);
            if (info) console.log("%cInfo:", "color: darkgreen;", info);
            console.error(err?.stack);
            console.groupEnd();
        };

        // Unhandled promise rejections
        let rejectionTimer;
        window.addEventListener('unhandledrejection', (event) => {
            event.preventDefault();
            const err = event.reason;

            clearTimeout(rejectionTimer);
            rejectionTimer = setTimeout(() => {
                if (err?.status === 422) {
                    app.config.globalProperties.$handleValidationError(err);
                } else {
                    ElNotification({
                        title: 'Error',
                        message: String(err?.message || err),
                        type: 'warning',
                    });
                }
            }, 50);
        });
    }

    instance(vue) {
        return {
            vue,
            addFilter: wp.hooks.addFilter.bind(wp.hooks),
            applyFilters: wp.hooks.applyFilters.bind(wp.hooks),
            addAction: wp.hooks.addAction.bind(wp.hooks),
            doAction: wp.hooks.doAction.bind(wp.hooks),
        };
    }
}
