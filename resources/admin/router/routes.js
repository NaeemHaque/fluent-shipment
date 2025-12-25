const routes = [
    {
        path: '/unauthorized',
        name: 'unauthorized',
        component: () => import('@/components/Unauthorized')
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'notfound',
        component: () => import('@/components/NotFound'),
    }
];

const isDev =
    (
        typeof import.meta !== 'undefined'
        && import.meta.env?.MODE === 'development'
    ) || (
        typeof process !== 'undefined'
        && process.env.NODE_ENV === 'development'
    );

if (isDev) {
    routes.push({
        path: '/dev/workbench',
        name: 'workbench',
        component: () => import('../../../dev/workbench/views/index.vue'),
        meta: {
            activeMenu: 'workbench',
            icon: 'SetUp',
        },
    });
}

export default routes;
