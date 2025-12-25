<template>
    <el-menu-item :index="computedIndex">
        <el-icon v-if="route.meta?.icon" class="menu-icon">
            <component
                class="menu-icon"
                :is="resolveIcon(route.meta.icon)"
            />
        </el-icon>
        {{ computedTitle }}
    </el-menu-item>
</template>

<script setup>
import { computed } from 'vue';
import MenuItem from './MenuItem';
import { useMenu } from './useMenu';
import { resolveIcon } from './icons';

const props = defineProps({
    route: { type: Object, required: true },
});

const computedIndex = computed(() =>
    props.route.path ||
    props.route.name ||
    `item-${Math.random().toString(36).slice(2, 6)}`
);

const computedTitle = computed(() =>
    props.route.label || props.route.meta.label || props.route.name
);


</script>

<style>
.menu-icon {
    width: 1em;
    height: 1em;
    font-size: 18px;
    vertical-align: middle;
    display: inline-block;
}
</style>
