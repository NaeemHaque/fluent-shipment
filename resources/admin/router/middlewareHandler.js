import middlewares from './middlewares';

/**
 * Runs an array of middleware functions in order.
 * Stops if one returns `false`, a redirect object, or a path string.
 */
async function executeMiddleware(stack, to, from, app) {
    for (const fn of stack) {
        if (typeof fn !== 'function') continue;

        const result = await fn(to, from, app);

        if (result === false) return false; // cancel
        if (typeof result === 'object' || typeof result === 'string') {
            return result; // redirect or path
        }
    }
    return true;
}

/**
 * Normalize a middleware definition (string or array).
 */
function normalizeMiddleware(meta, collection) {
    if (!meta) return [];
    const list = Array.isArray(meta) ? meta : [meta];
    return list.map((name) => collection[name]).filter(Boolean);
}

/**
 * Runs global "before" middleware
 */
export async function runBeforeMiddleware(to, from, app) {
    const globalBefore = Object.values(middlewares.global.before || {});

    return executeMiddleware(globalBefore, to, from, app);
}

/**
 * Route-specific "before" middleware
 */
export async function runRouteMiddleware(to, from, app) {
    const routeBefore = normalizeMiddleware(
        to.meta?.middleware, middlewares.route
    );

    return executeMiddleware(routeBefore, to, from, app);
}

/**
 * Runs global "after" middleware (fire & forget).
 */
export async function runAfterMiddleware(to, from, app) {
    const globalAfter = Object.values(middlewares.global.after || {});
    
    await executeMiddleware(globalAfter, to, from, app);
}
