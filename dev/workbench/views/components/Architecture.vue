<template>
    <div class="tab-content">
        <div class="page-header">
            <strong>API Endpoints</strong>
            <el-input
                v-model="search"
                placeholder="Search endpoints..."
                clearable
                size="small"
                style="width: 250px; margin-left: auto;"
            />
        </div>

        <div class="page-content">
            <el-scrollbar max-height="680px">
                <div 
                    v-for="group in filteredEndpoints"
                    :key="group.group"
                    class="endpoint-group"
                >
                    <h3 class="group-title">{{ group.group }}</h3>

                    <el-table
                        border
                        :data="group.items"
                        style="font-size:15px;color:var(--el-color-info-dark-2);"
                    >
                        <el-table-column
                            prop="method"
                            label="Method"
                            width="100"
                            align="center"
                        >
                            <template #default="scope">
                                <el-tag
                                    :type="tagType(scope.row.method)"
                                    disable-transitions
                                >
                                    {{ scope.row.method }}
                                </el-tag>
                            </template>
                        </el-table-column>

                        <el-table-column
                            prop="uri"
                            label="URI"
                        />

                        <el-table-column
                            prop="action"
                            label="Action"
                            show-overflow-tooltip
                        />

                        <el-table-column
                            prop="policy"
                            label="Policy"
                            show-overflow-tooltip
                        />
                    </el-table>
                </div>
            </el-scrollbar>
        </div>
    </div>
</template>

<script>
import Ajax from '@/utils/http/Ajax';

export default {
    name: 'ArchitectureTab',
    data() {
        return {
            search: '',
            endpoints: [],
        };
    },
    mounted() {
        this.loadEndpoints();
    },
    computed: {
        filteredEndpoints() {
            if (!this.search) return this.endpoints;
            const s = this.search.toLowerCase();
            return this.endpoints
                .map(group => ({
                    ...group,
                    items: group.items.filter(item =>
                        (item.uri || '').toLowerCase().includes(s) ||
                        (item.action || '').toLowerCase().includes(s) ||
                        (item.method || '').toLowerCase().includes(s)
                    ),
                }))
                .filter(group => group.items.length > 0);
        },
    },
    methods: {
        async loadEndpoints() {
            const res = await Ajax.get('endpoints');

            if (res.success) {
                this.endpoints = this.parseEndpoints(res.data.routes);
            }
        },
        tagType(method) {
            switch (method.toUpperCase()) {
                case 'GET': return 'success';
                case 'POST': return 'primary';
                case 'PUT': return 'warning';
                case 'PATCH': return 'warning';
                case 'DELETE': return 'danger';
            }
        },
        parseEndpoints(endpoints) {
            return Object.entries(
                endpoints
            ).map(([controller, actions]) => {
                const controllerName = controller.split('.').pop();
                const items = Object.entries(actions).map((
                    [methodName, details]
                ) => {
                    return {
                        method: details.methods[0],
                        uri: (this.appVars.rest.url + '/' + details.uri)
                            .replace(/^https?:\/\/[^/]+/, '')
                            .replace(/\/{2,}/g, '/'),
                        action: `${controller.replace(/\./g, '\\')}::${methodName.replace(/^_/, '')}`,
                        policy: details.policy,
                    };
                });

                return {
                    items,
                    group: controllerName,
                };
            });
        },
    },
};
</script>

<style scoped>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.endpoint-group {
    margin-bottom: 30px;
}

.group-title {
    font-weight: 500;
    margin: 10px 0;
    font-size: 14px;
}

:deep(.el-scrollbar__bar.is-vertical),
:deep(.el-scrollbar__bar.is-horizontal) {
    display: none !important;
}
</style>
