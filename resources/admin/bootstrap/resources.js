import Rest from '@/utils/http/Rest.js';
import config from '@/bootstrap/config';

const createResourceFactory = (endpoints) => {
    const normalizeControllerName = (name) => {
        return name.endsWith('Controller') ? name : `${name}Controller`;
    };

    const resolveTarget = (name) => {
        const normalized = normalizeControllerName(name);
        if (endpoints[normalized]) return endpoints[normalized];

        for (const key in endpoints) {
            if (key.split('.').pop() === normalized) {
                return endpoints[key];
            }
        }

        throw new Error(`Unknown resource "${name}"`);
    };

    const buildUrl = (uri, query) => {
        const url = new URL(uri, config.rest.url);

        Object.entries(query).forEach(
            ([k, v]) => url.searchParams.append(k, v)
        );

        return uri + url.search;
    };

    const parseUri = (template, args) => {
        let index = 0;
        return template.replace(/{([^}?]*)(\?)?}/g, (_, key, optional) => {
            if (index < args.length) {
                return args[index++];
            } else if (optional === '?') {
                return '';
            } else {
                throw new Error(`Missing required parameter "${key}"`);
            }
        }).replace(/\/{2,}/g, '/').replace(/\/$/, '');
    };

    return (controllerName) => {
        const target = resolveTarget(
            controllerName[0].toUpperCase() + controllerName.slice(1)
        );

        const state = {
            query: {},
            params: {},
            headers: {},
        };

        return new Proxy(target, {
            get(_, prop, receiver) {
                if (prop === 'withQuery') {
                    return (q) => {
                        state.query = { ...state.query, ...q };
                        return receiver;
                    };
                }

                if (prop === 'withParams') {
                    return (p) => {
                        state.params = (p instanceof FormData) ? p : { ...state.params, ...p };
                        return receiver;
                    };
                }

                if (prop === 'withHeaders') {
                    return (h) => {
                        state.headers = { ...state.headers, ...h };
                        return receiver;
                    };
                }

                const endpoint = target[`_${prop}`];
                if (!endpoint) {
                    throw new Error(`Undefined method "${prop}" on resource "${controllerName}"`);
                }

                return async (...args) => {
                    const uri = buildUrl(parseUri(endpoint.uri, args), {
                        ...state.query,
                    });

                    const method = endpoint.methods[0].toLowerCase();
                    const headers = { ...state.headers };
                    const params = state.params instanceof FormData
                        ? state.params
                        : { ...state.params };

                    // Reset state after call
                    state.query = {};
                    state.params = {};
                    state.headers = {};

                    return await Rest[method](uri, params, headers);
                };
            }
        });
    };
};

export default createResourceFactory(config.endpoints);
