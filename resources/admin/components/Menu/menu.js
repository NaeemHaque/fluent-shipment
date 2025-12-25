import { reactive } from 'vue';

class MenuApi {
    constructor() {
        this.menus = reactive({});
    }

    init(allMenus) {
        Object.entries(allMenus).forEach(([key, items]) => {
            this.menus[key] = items;
        });
    }

    all() {
        return this.menus;
    }

    get(menuName) {
        return this.menus[menuName] || [];
    }

    _walk(items, callback) {
        if (!Array.isArray(items)) return;
        items.forEach(item => {
            callback(item);
            const nested = item.items ?? item.children ?? item.submenu ?? item.submenus;
            if (Array.isArray(nested)) {
                this._walk(nested, callback);
            }
        });
    }

    find(name, groupName = null) {
        const finder = (item) => item.name === name || item.meta?.name === name;

        const search = (items) => {
            if (!Array.isArray(items)) return null;
            for (const item of items) {
                if (finder(item)) return item;
                const nested = item.items ?? item.children ?? item.submenu ?? item.submenus;
                const found = nested ? search(nested) : null;
                if (found) return found;
            }
            return null;
        };

        let result = null;

        if (groupName) {
            result = search(this.get(groupName)) || null;
        } else {
            for (const group of Object.values(this.menus)) {
                result = search(group);
                if (result) break;
            }
        }

        if (result) {
            return {
                raw: result,
                isVisible() {
                    return !!(this.raw.meta?.visible ?? true);
                },
                isHidden() {
                    return !this.isVisible();
                },
                show(cond = true) {
                    this.raw.meta = this.raw.meta || {};
                    this.raw.meta.visible = typeof cond === 'function'
                        ? cond(this.raw)
                        : !!cond;
                },
                hide() {
                    this.raw.meta = this.raw.meta || {};
                    this.raw.meta.visible = false;
                },
                toggle(fallback = null) {
                    this.raw.meta = this.raw.meta || {};
                    this.raw.meta.visible = fallback
                        ? fallback(this.raw)
                        : !Boolean(this.raw.meta.visible);
                },
                setLabel(label) {
                    this.raw.label = label;
                }
            };
        }

        return null;
    }

    isVisible(name) {
        const item = this.find(name);
        return item ? item.isVisible() : false;
    }

    isHidden(name) {
        return !this.isVisible(name);
    }

    setVisibility(menuItemName, condition) {
        const isFn = typeof condition === 'function';

        if (this.menus[menuItemName]) {
            this._walk(this.menus[menuItemName], (item) => {
                item.meta = item.meta || {};
                item.meta.visible = isFn ? condition(item) : !!condition;
            });
            return;
        }

        Object.values(this.menus).forEach(menuGroup => {
            this._walk(menuGroup, (item) => {
                if (item.name === menuItemName) {
                    item.meta = item.meta || {};
                    item.meta.visible = isFn ? condition(item) : !!condition;
                }
            });
        });
    }

    show(menuItemName, condition = true) {
        const fn = typeof condition === 'function'
            ? item => !!condition(item)
            : !!condition;

        this.setVisibility(menuItemName, fn);
    }

    showIf(menuItemName, condition) {
        this.show(menuItemName, condition);
    }

    hide(menuItemName, condition = true) {
        const fn = typeof condition === 'function'
            ? item => !condition(item)
            : !Boolean(condition);

        this.setVisibility(menuItemName, fn);
    }

    hideIf(menuItemName, condition) {
        this.hide(menuItemName, condition);
    }

    toggle(menuItemName, fallback = null) {
        if (this.menus[menuItemName]) {
            this._walk(this.menus[menuItemName], (item) => {
                item.meta = item.meta || {};
                if (fallback && typeof fallback === 'function') {
                    item.meta.visible = fallback(item);
                } else {
                    item.meta.visible = !Boolean(item.meta.visible);
                }
            });
            return;
        }

        Object.values(this.menus).forEach(menuGroup => {
            this._walk(menuGroup, (item) => {
                if (item.name === menuItemName) {
                    item.meta = item.meta || {};
                    item.meta.visible = fallback 
                        ? fallback(item)
                        : !Boolean(item.meta.visible);
                }
            });
        });
    }

    update(menuName, updater) {
        const items = this.menus[menuName];
        if (items && typeof updater === 'function') {
            return updater(items);
        }

        const menu = this.find(menuName);

        if (menu && typeof updater === 'function') {
            updater(menu);
        }
    }

    setLabel(menuName, label) {
        const menu = this.find(menuName);

        if (menu) {
            menu.setLabel(label);
        }
    }
}

export default new MenuApi();
