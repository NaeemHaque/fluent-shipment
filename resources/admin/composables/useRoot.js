import { getCurrentInstance } from 'vue';

export default function useRoot() {
    const instance = getCurrentInstance();

    if (!instance) {
        throw new Error(
            'getCurrentInstance() can only be used inside setup().'
        );
    }

    let root = instance.proxy;
    
    while (root.$parent) {
        root = root.$parent;
    }

    if (!root) {
        throw new Error('Root instance not found.');
    }

    return root;
}
