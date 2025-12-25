import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';

export function useMenu(routes) {
    const route = useRoute();

    function isNotFound(name) {
        return name && name.toLowerCase() !== 'notfound';
    }

    const visibleRoutes = computed(() => {
        return Array.isArray(routes) ? routes.filter(route =>
            route.meta?.visible !== false &&
            !route.path.includes(':') &&
            isNotFound(route.name)
        ) : [];
    });

    function hasVisibleChildren(route) {
        return route.children?.some(child =>
            child.meta?.showInMenu !== false &&
            !child.path.includes(':') &&
            isNotFound(child.name)
        );
    }

    function isNotRealRoute(route) {
        return hasVisibleChildren(route) && route.path === '/null';
    }

    function visibleChildren(route) {
        return route.children || [];
    }

    function isActiveRoute(r) {
        return route.name === r.name || route.path === r.path;
    }

    function slugify(value) {
        return value.toLowerCase().replace(/\s+/g, '-');
    }

    return {
        slugify,
        visibleRoutes,
        isActiveRoute,
        isNotRealRoute,
        visibleChildren,
        hasVisibleChildren,
    };
}
