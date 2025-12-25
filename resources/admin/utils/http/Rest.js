import ResponseProxyItr from './ResponseProxyItr';

let instance = null;
const middlewareStack = [];

/**
 * Add query param safely
 */
const addQueryParam = (url, key, value) => {
    return wp.url.addQueryArgs(url, { [key]: value });
};

/**
 * Prepare request body
 */
const makeRequestData = (method, data) => {
    if (method !== 'GET') {
        return data instanceof FormData ? data : JSON.stringify(data);
    }
};

/**
 * Prepare request headers
 */
const makeRequestHeaders = (data, headers) => {
    if (!(data instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
    }
    return headers;
};

/**
 * Wrap response
 */
const makeResponse = async (response) => {
    let responseJSON = {};
    try {
        if (response && typeof response.json === 'function') {
            responseJSON = await response.json();
        } else if (response) {
            responseJSON = response;
        }
    } catch (e) {
        responseJSON = {
            error: 'Failed to parse response JSON',
            details: e.message,
        };
    }

    return {
        original: response,
        status: response?.status ?? null,
        statusText: response?.statusText ?? null,
        responseJSON,
        header: (key = null) => {
            const parseValue = (value) => {
                if (value === null) return null;
                if (/^-?\d+$/.test(value)) return parseInt(value, 10);
                if (/^-?\d+(\.\d+)?$/.test(value)) return parseFloat(value);
                return value;
            };

            if (!response?.headers) return null;

            if (!key) {
                return Object.fromEntries(
                    Array.from(
                        response.headers.entries()
                    ).map(([k, v]) => [k, parseValue(v)])
                );
            }

            return parseValue(response.headers.get(key));
        },
        cookie: (key = null) => {
            const all = Object.fromEntries(
                document.cookie.split('; ').map(c => {
                    const [k, v] = c.split('=');
                    return [k, decodeURIComponent(v)];
                })
            );
            return key ? all[key] ?? null : all;
        },
    };
};

/**
 * Main middleware: wraps responses in ResponseProxyItr
 */
const mainMiddleware = async (options, next) => {
    try {
        const response = await next(options);
        return new ResponseProxyItr(await makeResponse(response));
    } catch (error) {
        return Promise.reject(new ResponseProxyItr(await makeResponse(error)));
    }
};

/**
 * Ensure main middleware is registered safely
 */
const ensureMainMiddleware = () => {
    if (typeof wp !== 'undefined' && wp.apiFetch) {
        if (!wp.apiFetch.__fluent_main_middleware_registered) {
            wp.apiFetch.use(mainMiddleware);
            wp.apiFetch.__fluent_main_middleware_registered = true;
        }

        while (middlewareStack.length) {
            wp.apiFetch.use(middlewareStack.shift());
        }
    }
};

/**
 * Main request handler
 */
const request = async (method, route, data = {}, headers = {}) => {
    if (!instance?.config?.globalProperties?.appVars) {
        throw new Error('Rest instance not set via setInstance(app)');
    }

    const config = instance.config.globalProperties.appVars;
    const { namespace, version } = config.rest;

    let url = `${namespace}/${version}/${route.replace(/^\/+/, '')}`;
    headers['X-WP-Nonce'] = config.rest.nonce;

    if (method === 'GET' && Object.keys(data).length) {
        for (const [key, value] of Object.entries(data)) {
            url = addQueryParam(url, key, value);
        }
    }

    const options = {
        method,
        parse: false,
        body: makeRequestData(method, data),
        headers: makeRequestHeaders(data, headers),
        path: addQueryParam(url, 'query_timestamp', Date.now()),
    };

    ensureMainMiddleware();

    return wp.apiFetch(options);
};

/**
 * Exported API
 */
export default {
    setInstance(app) {
        instance = app;
    },
    abort(message = 'Request aborted!') {
        const err = new Error(message);
        err.isAborted = true;
        throw err;
    },
    use(callback) {
        middlewareStack.push(callback);
    },
    get(route, data = {}, headers = {}) {
        return request('GET', route, data, headers);
    },
    post(route, data = {}, headers = {}) {
        return request('POST', route, data, headers);
    },
    delete(route, data = {}, headers = {}) {
        return request('DELETE', route, data, headers);
    },
    put(route, data = {}, headers = {}) {
        return request('PUT', route, data, headers);
    },
    patch(route, data = {}, headers = {}) {
        return request('PATCH', route, data, headers);
    },
};
