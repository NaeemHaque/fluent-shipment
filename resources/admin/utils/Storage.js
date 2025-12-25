// Chenge the prefix
const prefix = 'fluent-';

const generator = key => prefix + key;

export default class Storage {
    static get(key, defaultValue = null) {
        let value = localStorage.getItem(generator(key));

        if (!value) return defaultValue;

        if (['{', '['].includes(value[0])) {
            try {
                return JSON.parse(value);
            } catch (e) {
                console.warn(`Storage: Failed to parse value for key "${key}"`, e);
                return defaultValue;
            }
        }

        return value;
    }

    static set(key, value) {
        if (typeof value === 'object') {
            value = JSON.stringify(value);
        }
        localStorage.setItem(generator(key), value);
    }

    static remove(key) {
        localStorage.removeItem(generator(key));
    }

    static clear() {
        Object.keys(localStorage)
            .filter(k => k.startsWith(prefix))
            .forEach(k => localStorage.removeItem(k));
    }
}
