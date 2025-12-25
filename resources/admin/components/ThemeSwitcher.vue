<template>
    <el-dropdown
        size="large"
        trigger="click"
        @command="setTheme"
        class="theme-dropdown"
    >
        <span class="el-dropdown-link">
            <el-icon size="20" :color="themeColor">
                <component :is="themeIcon" />
            </el-icon>
        </span>
        <template #dropdown>
            <el-dropdown-menu>
                <el-dropdown-item 
                    command="light" 
                    :class="{ 'is-active': currentTheme === 'light' }"
                >
                    <el-icon size="16" style="margin-right: 8px; color: #606266">
                        <Sunny />
                    </el-icon>
                    Light
                </el-dropdown-item>

                <el-dropdown-item 
                    command="dark" 
                    :class="{ 'is-active': currentTheme === 'dark' }"
                >
                    <el-icon size="16" style="margin-right: 8px; color: #606266">
                        <Moon />
                    </el-icon>
                    Dark
                </el-dropdown-item>

                <el-dropdown-item 
                    command="system" 
                    :class="{ 'is-active': currentTheme === 'system' }"
                >
                    <el-icon size="16" style="margin-right: 8px; color: #606266">
                        <Monitor />
                    </el-icon>
                    System
                </el-dropdown-item>
            </el-dropdown-menu>
        </template>
    </el-dropdown>
</template>

<script>
import 'element-plus/theme-chalk/dark/css-vars.css';
import { Monitor, Moon, Sunny } from "@element-plus/icons-vue";
import Storage from '@/utils/Storage';

export default {
    name: "ThemeSwitcher",
    components: { Moon, Sunny, Monitor },
    data() {
        return {
            currentTheme: "system",
            colorVariables: {
                bgColor: '--el-bg-color',
                primary: '--el-color-primary',
                success: '--el-color-success',
                warning: '--el-color-warning',
                danger: '--el-color-danger',
                info: '--el-color-info',

                // Default button
                defaultButtonBg: '--el-button-bg-color',
                defaultButtonHoverBg: '--el-button-hover-bg-color',
                defaultButtonDisabledBg: '--el-button-disabled-bg-color',

                // Primary
                primaryButtonBg: '--el-color-primary',
                primaryButtonHoverBg: '--el-color-primary-light-3',
                primaryButtonDisabledBg: '--el-color-primary-light-5',

                // Success
                successButtonBg: '--el-color-success',
                successButtonHoverBg: '--el-color-success-light-3',
                successButtonDisabledBg: '--el-color-success-light-5',

                // Danger
                dangerButtonBg: '--el-color-danger',
                dangerButtonHoverBg: '--el-color-danger-light-3',
                dangerButtonDisabledBg: '--el-color-danger-light-5',

                // Info
                infoButtonBg: '--el-color-info',
                infoButtonHoverBg: '--el-color-info-light-3',
                infoButtonDisabledBg: '--el-color-info-light-5',

                // Warning
                warningButtonBg: '--el-color-warning',
                warningButtonHoverBg: '--el-color-warning-light-3',
                warningButtonDisabledBg: '--el-color-warning-light-5',
            },
        };
    },
    computed: {
        themeIcon() {
            switch (this.currentTheme) {
                case "light": return Sunny;
                case "dark": return Moon;
                default: return Monitor;
            }
        },
        themeColor() {
            return 'var(--el-color-primary)';
        },
    },
    methods: {
        setTheme(mode) {
            if (typeof mode !== "string") {
                return;
            }
            
            if (!["light", "dark", "system"].includes(mode)) {
                return;
            }

            this.currentTheme = mode;

            const root = document.documentElement;
            const wpbody = document.querySelector("#wpbody");

            this.setColorScheme(mode, root, wpbody);

            root.classList.remove("dark", "light");
            wpbody?.classList.remove("dark", "light");

            if (mode === "light") {
                root.classList.add("light");
                wpbody?.classList.add("light");
                Storage.set("theme", "light");
            } else if (mode === "dark") {
                root.classList.add("dark");
                wpbody?.classList.add("dark");
                Storage.set("theme", "dark");
            } else {
                Storage.set("theme", "system");
                this.watchSystemTheme();
            }

            window.dispatchEvent(
                new CustomEvent('theme-changed', {
                    detail: {
                        isDark: this.isDark(),
                        scheme: this.currentTheme,
                    }
                })
            );
        },
        setColorScheme(mode, root, wpbody) {
            const scheme = this.getColorScheme(mode);
            
            if (!scheme) return;

            Object.entries(scheme).forEach(([key, value]) => {
                const cssVar = this.colorVariables[key] || `--${key}`;

                root.style.setProperty(cssVar, value);
                
                if (wpbody) {
                    wpbody.style.setProperty(cssVar, value);
                }
            });
        },
        getColorScheme(mode) {
            if (mode === 'system') {
                mode = window.matchMedia(
                    '(prefers-color-scheme: dark)'
                ).matches ? 'dark' : 'light';
            }
            if (this.appVars.theme) {
                return this.appVars.theme[mode];
            }
        },
        watchSystemTheme() {
            if (this.currentTheme !== 'system') return;

            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

            this.applySystemTheme(mediaQuery.matches);

            mediaQuery.addEventListener('change', (e) => {
                this.applySystemTheme(e.matches);
            });
        },
        applySystemTheme(isDark) {
            const root = document.documentElement;
            const wpbody = document.querySelector("#wpbody");

            root.classList.remove('light', 'dark');

            wpbody?.classList.remove('light', 'dark');

            if (isDark) {
                root.classList.add('dark');
                wpbody?.classList.add('dark');
            } else {
                root.classList.add('light');
                wpbody?.classList.add('light');
            }

            this.setColorScheme('system', root, wpbody);

            window.dispatchEvent(
                new CustomEvent('os-theme-changed', {
                    detail: {
                        isDark: this.isDark(),
                        scheme: this.currentTheme,
                        theme: this.themes[this.activeTheme],
                    }
                })
            );
        },
        isDark() {
            if (this.currentTheme === 'system') {
                return window.matchMedia('(prefers-color-scheme: dark)').matches;
            }

            return this.currentTheme === 'dark';
        },
    },
    mounted() {
        this.currentTheme = Storage.get("theme") || "system";
        this.setTheme(this.currentTheme);
        this.watchSystemTheme();
    },
};
</script>

<style scoped>
.theme-dropdown {
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 15px;
}
</style>
