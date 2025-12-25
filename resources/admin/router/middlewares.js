// import Rest from '@/utils/Http/Rest';
// import Cookie from '@/utils/Cookie';
// import Storage from '@/utils/Storage';

export const getCurrentUser = (app) => {
    return app.config.globalProperties.appVars.me;
}

export default {
    global: {
        before: {
            log(to, from, app) {
                return true;
            },
        },
        after: {
            log(to, from, app) {
                return true
            },
        },
    },
    route: {
        admin: async (to, from, app) => {
            const user = getCurrentUser(app);

            if (!user.is_admin) {
                return { path: '/unauthorized' };
            }

            return true;
        },
    },
};
