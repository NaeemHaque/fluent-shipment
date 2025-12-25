import { createRouter, createWebHashHistory } from 'vue-router';
import menu from '@/components/Menu/menu';
import staticRoutes from '@/router/routes';
import {
    runRouteMiddleware,
    runBeforeMiddleware,
    runAfterMiddleware,
} from './middlewareHandler';


/**
 * Check if in isWebpack.
 * @return {Boolean}
 */
function isWebpack() {
    return typeof __webpack_modules__ !== 'undefined'
        && typeof __webpack_require__ === 'function';
}

/**
 * Load Vite modules when in Vite.                         
 */
let viteModules = {};

if (!isWebpack()) {
    // eslint-disable-next-line
    viteModules = import.meta.glob('/resources/admin/modules/**/*.vue');
}

/**
 * Check if a route is just a grouping label without a component.
 * @param {Object} route - Route object
 * @returns {boolean} True if route is a grouping label (no component, has children)
 */
function isGroupingLabel(route) {
    return !route.component && Array.isArray(route.children);
}

/**
 * Resolve component import function for a route.
 * @param {Object} route - Route object
 * @returns {Function|undefined} Lazy import function or undefined if no component
 */
function resolveComponent(route) {
    if (!route.component) return undefined;

    let component = route.component;

    if (isWebpack()) {
        return resolveForWebpack(component);
    }
    
    return resolveForComponent(component);

}

/**
 * Resolve component import function for Vite.
 * 
 * @param {Object} route - Route object
 * @returns {Function|undefined} Lazy import function or undefined if no component
 */
function resolveForComponent(component) {
    if (!component) return undefined;

    // Clean up leading "modules/" if present
    let clean = component.startsWith('modules/')
        ? component.replace(/^modules\//, '')
        : component;

    // Build candidate paths
    const candidates = [
        `/resources/admin/modules/${clean}.vue`,
        `/resources/admin/modules/${clean}/index.vue`
    ];

    // Find first matching key
    for (const path of candidates) {
        if (viteModules[path]) return viteModules[path];
    }

    console.warn(
        'Vite component not found:', component, 'tried paths:', candidates
    );
}

/**
 * Resolve component import function for Vite.
 * 
 * @param {Object} route - Route object
 * @returns {Function} Lazy import function
 */
function resolveForWebpack(component) {
    if (component.startsWith('@')) component = component.slice(1);
    if (component.startsWith('/')) component = component.slice(1);

    return () => import(/* @vite-ignore */ `@/${component}`);
}

/**
 * Build a full route path by combining parent path and current route segment.
 * @param {Object} route - Route object
 * @param {string} parentPath - Parent route path
 * @param {boolean} [isChild=false] - Whether this is a child route
 * @returns {string} Combined full path for the route
 */
function buildFullPath(route, parentPath, isChild = false) {
    const segment = route.path || '';

    if (parentPath) {

        if (isChild) {
            return `${parentPath.replace(/\/$/, '')}/${segment}`;
        }

        return segment.startsWith('/') ? segment : `/${segment}`;
    }

    return segment.startsWith('/') ? segment : `/${segment}`;
}

/**
 * Create Vue Router route definition from route data.
 * @param {Object} route - Route object
 * @param {string} groupName - Group name the route belongs to (e.g., 'primary')
 * @param {string} fullPath - Full route path
 * @returns {Object} Vue Router route definition object
 */
function buildRouteDefinition(route, groupName, fullPath) {
    return {
        path: fullPath,
        name: generateFallbackName(route),
        component: resolveComponent(route),
        props: route.props || false,
        meta: {
            ...(route.meta || {}),
            icon: route?.icon || route?.meta?.icon,
            label: route.label,
            group: groupName,
            activeMenu: fullPath,
        },
    };
}

function generateFallbackName(route) {
    if (route.name) {
        return route.name;
    }
    
    if (route.label) {
        return route.label
            .toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^a-z0-9\-]/g, '') + '-' + Math.random().toString(36).slice(2, 6);
    }

    return 'unnamed_' + Math.random().toString(36).slice(2, 10);
}


/**
 * Build nested submenu metadata for a route if submenu exists.
 * @param {Object} subRoute - The submenu route object
 * @param {string} groupName - Group name for the route
 * @param {string} parentFullPath - Full path of the parent route
 * @returns {Object|undefined} Nested submenu meta object or undefined if none
 */
function buildNestedSubmenu(subRoute, groupName, parentFullPath) {
    if (!Array.isArray(subRoute.submenu)) {
        return undefined;
    }

    return {
        label: subRoute.label,
        items: parseRoutesArray(
            subRoute.submenu.flat(), groupName, parentFullPath
        ),
    };
}

/**
 * Build submenu items from nested submenu data.
 * @param {Array} nested - Nested submenu arrays/objects
 * @param {string} groupName - Group name the submenu belongs to
 * @param {string} parentFullPath - Parent route path
 * @returns {Array} Array of Vue Router route definitions for submenu items
 */
function buildSubmenuItems(nested, groupName, parentFullPath) {
    return nested.submenu.flatMap(itemGroup => {
        const subRoutes = Array.isArray(
            itemGroup
        ) ? itemGroup : Object.values(itemGroup);

        return subRoutes.map(subRoute => {
            const subFullPath = buildFullPath(subRoute, parentFullPath, true);

            const nestedSubmenu = buildNestedSubmenu(
                subRoute, groupName, subFullPath
            );

            return {
                path: subFullPath,
                name: subRoute.name,
                component: resolveComponent(subRoute),
                props: subRoute.props || false,
                meta: {
                    ...(subRoute.meta || {}),
                    icon: subRoute.icon || subRoute.meta?.icon,
                    label: subRoute.label,
                    group: groupName,
                    activeMenu: subFullPath,
                    submenu: nestedSubmenu,
                },
                children: transformChildRoutes(subRoute, subFullPath, groupName),
            };
        });
    });
}

/**
 * Transform children routes into Vue Router route definitions.
 * 
 * @param  {Object} route
 * @param  {String} fullPath
 * @param  {String} groupName
 * @return {Array}
 */
function transformChildRoutes(route, fullPath, groupName) {
    return route.children?.map(child =>
        transformRoute(child, fullPath, groupName)
    ) || [];
}

/**
 * Transform a route object into a Vue Router route definition.
 * Supports recursion for nested children and submenus.
 * 
 * @param  {Object} route
 * @param  {String} parentPath
 * @param  {String} groupName
 * @return {Object}
 */
function transformRoute(route, parentPath, groupName) {
    const fullPath = buildFullPath(route, parentPath, true);
    
    return {
        path: route.path,
        name: route.name,
        component: resolveComponent(route),
        props: route.props || false,
        meta: {
            ...(route.meta || {}),
            label: route.label || '',
            group: groupName,
            activeMenu: parentPath,
        },
        children: transformChildRoutes(route, fullPath, groupName),
    };
}

/**
 * Build a wrapper route representing a submenu group.
 * @param {Object} route - Original route object representing the group
 * @param {string} groupName - Group name
 * @param {string} fullPath - Full path for the group route
 * @param {Array} submenuItems - Array of submenu route definitions
 * @returns {Object} Vue Router route definition for submenu group
 */
function buildSubmenuGroupRoute(route, groupName, fullPath, submenuItems) {
    const key = Math.random().toString(36).slice(2, 6);
    const name = route.name || route.label.replace(/\s/g, '_') || `group-${key}`;

    return {
        path: route.path || '',
        name: name,
        props: false,
        meta: {
            ...(route.meta || {}),
            label: route.label,
            group: groupName,
            submenu: {
                label: route.label,
                items: submenuItems,
            },
        },
    };
}

/**
 * Build a submenu group: wrapper route plus submenu items.
 * @param {Object} route - Route with submenu property
 * @param {string} groupName - Group name
 * @param {string} fullPath - Full path of this route
 * @returns {Array} Array with group route and submenu items
 */
function buildSubmenuGroup(route, groupName, fullPath) {
    const submenuItems = buildSubmenuItems(route, groupName, fullPath);

    const groupRoute = buildSubmenuGroupRoute(
        route, groupName, fullPath, submenuItems
    );

    return [groupRoute, ...submenuItems];
}

/**
 * Parse an array of routes into Vue Router definitions.
 * Supports recursion for nested children and submenus.
 * @param {Array} routes - Array of route objects
 * @param {string} groupName - Group name for routes
 * @param {string} [parentPath=''] - Parent route path
 * @param {boolean} [isChild=false] - Whether this parse is for child routes
 * @returns {Array} Array of Vue Router route definitions
 */
function parseRoutesArray(routes, groupName, parentPath = '', isChild = false) {
    return routes.flatMap(route => {
        if (isGroupingLabel(route)) {
            return parseRoutesArray(
                route.children, groupName, parentPath, isChild
            );
        }

        const fullPath = buildFullPath(route, parentPath, isChild);

        const definition = buildRouteDefinition(route, groupName, fullPath);

        if (route.children?.length) {
            definition.children = parseRoutesArray(
                route.children, groupName, fullPath, true
            );
        }

        if (Array.isArray(route.submenu)) {
            definition.meta = {
                ...definition.meta,
                submenu: {
                    label: route.label,
                    items: buildSubmenuItems(route, groupName, fullPath),
                },
            };
        }

        return [definition];
    });
}

/**
 * Build grouped routes object from server data.
 * If array, assign to primary group.
 * @param {Object|Array} serverMenus - Server-provided routes
 * @returns {Object} Grouped routes by menu key
 */
function buildRoutesFromServer(serverMenus) {
    if (!serverMenus) {
        return [];
    }

    if (Array.isArray(serverMenus)) {
        return { primary: parseRoutesArray(serverMenus, 'primary') };
    }

    const groupedMenus = {};
    
    Object.entries(serverMenus).forEach(([groupName, routes]) => {
        groupedMenus[groupName] = parseRoutesArray(routes, groupName);
    });

    return groupedMenus;
}

/**
 * Filter grouped routes to include only top-level routes,
 * avoiding duplication of submenu children.
 * @param {Object} groupedRoutes - Grouped route definitions
 * @returns {Object} Filtered grouped top-level routes only
 */
function getTopLevelRoutes(groupedRoutes) {
    const topLevelRoutes = {};

    Object.entries(groupedRoutes).forEach(([groupName, routes]) => {
        const submenuRouteNames = routes
            .filter(r => r.meta?.submenu)
            .flatMap(r => r.meta.submenu.items.map(i => i.name))
            .filter(Boolean);

        topLevelRoutes[groupName] = routes.filter(route => {
            // Allow named routes with submenu
            if (route.meta?.submenu && route.name) return true;

            // Skip anonymous submenu wrappers
            if (route.meta?.submenu && !route.name) return false;

            // Exclude routes that are children of submenu containers
            return !submenuRouteNames.includes(route.name);
        });
    });

    return topLevelRoutes;
}

/**
 * Inject static routes into grouped top-level structure, overriding if path matches.
 * @param {Object} groupedTopRoutes - Top-level grouped routes
 * @param {Array} staticRoutes - Predefined static routes
 * @returns {Object} Updated groupedTopRoutes
 */
function mergeRoutes(groupedTopRoutes, staticRoutes) {
    staticRoutes.forEach(staticRoute => {
        if (!staticRoute?.meta?.label) return;

        const group = staticRoute.meta.group || 'primary';
        const groupArray = groupedTopRoutes[group] || [];

        // Check if a route with same path exists in this group
        const index = groupArray.findIndex(r => r.path === staticRoute.path);
        if (index >= 0) {
            // Override existing route
            groupArray[index] = staticRoute;
        } else {
            // Otherwise, add new route
            groupArray.push(staticRoute);
        }

        // Ensure the group array is updated
        groupedTopRoutes[group] = groupArray;
    });

    return groupedTopRoutes;
}

/**
 * Recursively flattens a grouped route configuration into a linear array of Vue Router-compatible routes.
 *
 * Ensures that routes with the same `path` or `name` are merged, regardless of the order they're defined in.
 *
 * @param  {Object<string, Array>} groupedRoutes - An object containing route groups (e.g., primary, secondary).
 * @return {Array<Object>} A flat array of valid route records to be used in createRouter.
 */
function buildRoutes(groupedRoutes) {
    const routeMap = new Map();

    function normalizeRoute(route, parentPath = '') {
        if (!route || typeof route !== 'object') return null;

        // Normalize path
        const normalizedPath = route.path?.startsWith('/')
            ? route.path
            : `${parentPath.replace(/\/$/, '')}/${route.path}`.replace(/\/+/g, '/');

        const submenuItems = route.meta?.submenu?.items || [];
        const rawChildren = [...(route.children || []), ...submenuItems];

        const newRoute = {
            path: normalizedPath,
            name: route.name,
            component: route.component,
            props: route.props ?? false,
            meta: { ...(route.meta || {}) },
            children: []
        };

        if (rawChildren.length) {
            newRoute.children = rawChildren
                .map(child => normalizeRoute(child, normalizedPath))
                .filter(Boolean);
        }

        return newRoute;
    }

    function mergeRoutes(existing, incoming) {
        // Prefer the route with component
        if (!existing.component && incoming.component) {
            existing.component = incoming.component;
        }

        // Merge meta (incoming overrides if key exists)
        existing.meta = { ...existing.meta, ...incoming.meta };

        // Merge props if not set
        if (existing.props === false && incoming.props) {
            existing.props = incoming.props;
        }

        // Merge and dedupe children
        const dedupedChildren = [];
        const allChildren = [...existing.children, ...incoming.children];

        const seen = new Set();
        for (const child of allChildren) {
            const key = child.name || child.path;
            if (!seen.has(key)) {
                seen.add(key);
                dedupedChildren.push(child);
            }
        }

        existing.children = dedupedChildren;
    }

    // Main loop
    Object.values(groupedRoutes).forEach(group => {
        for (const route of group) {
            const normalized = normalizeRoute(route);
            if (!normalized) continue;

            const key = normalized.name || normalized.path;
            if (routeMap.has(key)) {
                mergeRoutes(routeMap.get(key), normalized);
            } else {
                routeMap.set(key, normalized);
            }
        }
    });

    return Array.from(routeMap.values());
}

/**
 * Execute global and route middleware before and after each route.
 * @param  * @param {Object} app - The main vue app instance.
 * @param  {object} router - The vue router
 * @return {object} The vue router
 */
function attachMiddleware(app, router) {
    router.beforeEach(async (to, from) => {
        const globalResult = await runBeforeMiddleware(to, from, app);
        
        if (globalResult !== true) return globalResult;

        const routeResult = await runRouteMiddleware(to, from, app);
        
        if (routeResult !== true) return routeResult;

        return true;
    });

    router.afterEach(async (to, from) => {
        await runAfterMiddleware(to, from, app);
    });

    return router;
};


/**
 * Merge static routes with dynamic routes.
 * Overrides dynamic routes with static routes with the same path.
 * @param  {Array} dynamicRoutes
 * @return {Array}
 */
function toRoutes(dynamicRoutes) {
    const unifiedRoutes = [...dynamicRoutes];
    staticRoutes.forEach(staticRoute => {
        const index = unifiedRoutes.findIndex(r => r.path === staticRoute.path);
        if (index >= 0) {
            unifiedRoutes[index] = staticRoute;
        } else {
            unifiedRoutes.push(staticRoute);
        }
    });
    return unifiedRoutes;
};

/**
 * Create Vue Router instance configured with merged routes.
 * Also sets grouped top-level routes globally on app instance.
 * @param {Object} app - The main vue app instance.
 * @param {Object|Array} serverRoutes - Routes data from server
 * @returns {Router} Vue Router instance
 */
export default function createAdminRouter(app, serverRoutes) {
    const groupedRoutes = buildRoutesFromServer(serverRoutes);
    const groupedTopRoutes = getTopLevelRoutes(groupedRoutes);
    const merged = mergeRoutes(groupedTopRoutes, staticRoutes);

    app.config.globalProperties.groupedRoutes = merged;
    menu.init(app.config.globalProperties.groupedRoutes);

    const router = createRouter({
        strict: true,
        history: createWebHashHistory(),
        routes: toRoutes(buildRoutes(groupedRoutes)),
    });

    return attachMiddleware(app, router);
}
