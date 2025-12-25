import { ref, watchEffect } from 'vue';

/**
 * Get computed value of CSS variable
 */
export function getCssVar(varName, element = document.documentElement) {
    return getComputedStyle(element)
        .getPropertyValue(varName.startsWith('--') ? varName : `--${varName}`)
        .trim();
}

/**
 * Convert HEX color to RGBA with alpha
 */
export function toAlpha(hex, alpha = 0.2) {
    if (!hex || typeof hex !== 'string') return '';

    const rgb = hex.replace('#', '')
        .match(/.{1,2}/g)
        ?.map(x => parseInt(x, 16));

    if (!rgb || rgb.length < 3) return '';

    return `rgba(${rgb.join(',')}, ${alpha})`;
}

/**
 * Vue plugin to provide $cssVar and $withAlpha globally
 */
export default {
    install(app) {
        app.config.globalProperties.$getCssVar = getCssVar;
        app.config.globalProperties.$toAlpha = toAlpha;
    }
}
