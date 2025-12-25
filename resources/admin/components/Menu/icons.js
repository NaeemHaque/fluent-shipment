import * as Icons from '@element-plus/icons-vue';
import { markRaw } from 'vue';

const iconMap = {};

for (const [name, icon] of Object.entries(Icons)) {
    iconMap[name] = markRaw(icon);
}

export function resolveIcon(name) {
    return iconMap[name] || null;
}
