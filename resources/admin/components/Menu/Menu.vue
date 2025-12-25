<template>
    <el-menu
        v-bind="menuProps"
        :default-active="$route.path"
        v-if="visibleRoutes.length > 0"
    >
        <el-sub-menu
            v-if="title"
            :index="computedSubMenuIndex"
            :popper-offset="popperOffset"
        >
            <template #title>{{ computedTitle }}</template>

            <MenuItem
                v-for="route in visibleRoutes"
                :key="route.name"
                :route="route"
            />
        </el-sub-menu>

        <template v-else>
            <MenuItem
                v-for="route in visibleRoutes"
                :key="route.name"
                :route="route"
            />
        </template>
    </el-menu>
</template>

<script setup>
import { computed, ref, reactive } from 'vue';
import { useRoute } from 'vue-router';
import { useMenu } from './useMenu';
import MenuItem from './MenuItem.vue';

const props = defineProps({
    routes: { type: Array, required: true },
    title: { type: String, default: '' },
    subMenuIndex: { type: String, default: '0' },
    popperOffset: { type: [Number, String], default: 0 },
    router: { type: Boolean, default: true },
    mode: { type: String, default: 'horizontal' },
    trigger: { type: String, default: 'hover' },
    collapse: { type: Boolean, default: false },
    ellipsis: { type: Boolean, default: false },
    ellipsisIcon: { type: String, default: 'more' },
});

const menuProps = computed(() => ({
    defaultActive: activeIndex.value,
    collapse: props.collapse,
    ellipsis: props.ellipsis,
    router: props.router,
    mode: props.mode,
    menuTrigger: props.trigger,
    uniqueOpened: props.mode === 'vertical',
    ...(props.mode === 'vertical' ? { openedKeys: openedKeys.value } : {}),
}));

const route = useRoute();

const { visibleRoutes, slugify } = useMenu(props.routes);

const activeIndex = computed(() => route.path);

const computedSubMenuIndex = computed(() => {
    if (props.subMenuIndex && props.subMenuIndex.trim() !== '') {
        return props.subMenuIndex;
    }
    return computedTitle.value ? slugify(computedTitle.value) : 'default';
});

const openedKeys = ref(props.mode === 'vertical' ? [] : null);

const computedTitle = computed(() => {
    // Use client prop title if provided
    if (props.title && props.title.trim() !== '') {
        return props.title;
    }

    // Else fallback to server label from the first route
    if (reactiveRoutes && reactiveRoutes.length > 0) {
        // For group menus, label may be in route.meta.label or route.label
        return reactiveRoutes[0].label || reactiveRoutes[0].meta?.label || '';
    }

    return '';
});
</script>
