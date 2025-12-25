<template>
    <el-container class="layout">
        <!-- Top Navigation -->
        <el-header class="navbar">
            <!-- Left Section (logo + menu) -->
            <div class="navbar-left">
                <!-- Brand Logo -->
                <div class="brand-logo">
                    <a href="#">
                        <img :src="logoUrl" alt="Brand" class="brand-logo" />
                    </a>
                </div>

                <!-- Left Menu -->
                <Nav
                    class="menu"
                    v-if="primaryMenu.length"
                    :routes="primaryMenu"
                />
            </div>

            <!-- Right Menu -->
            <div class="navbar-right">
                <ThemeSwitcher />
                <Nav
                    v-if="secondaryMenu.length"
                    :routes="secondaryMenu"
                    :key="secondaryMenu[0]?.meta?.label"
                    class="menu"
                />
            </div>
        </el-header>

        <!-- Main Content Area -->
        <el-main>
            <router-view />
        </el-main>
    </el-container>
</template>

<script>
import Rest from '@/utils/http/Rest';
import Nav from '@/components/Menu/Menu';
import menu from '@/components/Menu/menu';
import ThemeSwitcher from '@/components/ThemeSwitcher';

export default {
    name: "Application",
    components: {
        Nav,
        ThemeSwitcher,
    },
    props: {
        logoUrl: {
            type: String,
            required: true
        },
        baseUrl: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            // Menu will be reactive through computed properties
        };
    },
    provide() {
        return {
            // ...
        };
    },
    computed: {
        primaryMenu() {
            return menu.get('primary') || [];
        },
        secondaryMenu() {
            return menu.get('secondary') || [];
        },
        footerMenu() {
            return menu.get('footer') || [];
        },
    },
    methods: {
        registerRestRequestInterceptor() {
            Rest.use((options, next) => {
                options.headers = {
                    ...options.headers,
                    'X-Fluent': 'Fluent-Custom-Header',
                };
                
                return next(options);
            });
        },
        updateApplication(data = null) {
            // ...
        },
    },
    mounted() {
        this.registerRestRequestInterceptor();
        
        // Debug: Log menu data
        if (typeof __DEV__ !== 'undefined' && __DEV__) {
            console.log('Application mounted - Primary Menu:', this.primaryMenu);
            console.log('Application mounted - All Menus:', menu.all());
        }
    },
    watch: {
        primaryMenu: {
            handler(newVal) {
                if (typeof __DEV__ !== 'undefined' && __DEV__) {
                    console.log('Primary menu updated:', newVal);
                }
            },
            immediate: true
        }
    }
};
</script>

<style>
ul.el-pager {
    margin-top: 6px !important;
}
</style>
