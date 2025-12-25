<template>
    <div class="tab-content features-tab">
        <div class="page-header">
            <strong>Features</strong>
            
            <div>
                <el-select
                    clearable
                    v-model="version"
                    placeholder="Filter By Version"
                    size="small"
                    @change="handleVersionChange"
                    style="width: 150px;margin-right: 10px"
                    :disabled="Object.keys(versions).length <= 1"
                >
                    <el-option
                        v-for="(version, key) in versions"
                        :key="key"
                        :value="key"
                        :label="version"
                    />
                </el-select>

                <!-- <el-select
                    clearable
                    v-model="priority"
                    placeholder="Filter By Priority"
                    size="small"
                    @change="handlePriorityChange"
                    style="width: 150px;margin-right: 10px"
                >
                    <el-option
                        v-for="(priority, key) in priorities"
                        :key="key"
                        :value="key"
                        :label="priority"
                    />
                </el-select> -->

                <el-select
                    clearable
                    v-model="filter"
                    placeholder="Filter By Status"
                    size="small"
                    @change="handleFilterChange"
                    style="width: 150px;margin-right: 10px"
                >
                    <el-option
                        v-for="(status, key) in statuses"
                        :key="key"
                        :value="key"
                        :label="status"
                    />
                </el-select>

                <el-button type="primary" size="small" @click="openDialog">
                    + Add Feature
                </el-button>
            </div>
        </div>

        <div class="page-content">
            <el-table
                :data="features"
                style="width:100%;margin-top:10px;color:var(--el-color-info-dark-2);"
            >
                <el-table-column prop="name" label="Name" sortable />
                
                <el-table-column label="Created At" sortable>
                    <template #default="{ row }">
                        {{
                            $formatDate(row.created_at, 'DD-MM-YYYY hh:mm a')
                        }}
                    </template>
                </el-table-column>

                <el-table-column label="Updated At" sortable>
                    <template #default="{ row }">
                        {{
                            $formatDate(row.updated_at, 'DD-MM-YYYY hh:mm a')
                        }}
                    </template>
                </el-table-column>

                <el-table-column
                    sortable
                    prop="version"
                    label="Version"
                    align="center"
                />

               <!--  <el-table-column
                    sortable
                    prop="priority"
                    label="Priority"
                    align="center"
                    width="140"
                >
                    <template #default="{ row }">
                        <span
                            :style="{ color: priorityType(row.priority) }"
                            class="priority-tag"
                        >
                            {{ priorities[row.priority] }}
                        </span>
                    </template>
                </el-table-column> -->

                <el-table-column
                    sortable
                    prop="status"
                    label="Status"
                    align="center"
                    width="140"
                >
                    <template #default="{ row }">
                        <span
                            :style="{ color: statusType(row.status) }"
                            class="status-tag"
                        >
                            {{ statuses[row.status] }}
                        </span>
                    </template>
                </el-table-column>

                <el-table-column label="Actions" width="180" align="right">
                    <template #default="scope">
                        <el-button
                            type="info"
                            size="small"
                            @click="viewFeature(scope.row)"
                        >
                            <el-icon><View /></el-icon>    
                        </el-button>

                        <el-button
                            type="primary"
                            size="small"
                            @click="editFeature(scope.row)"
                        >
                            <el-icon><Edit /></el-icon>    
                        </el-button>

                        <Confirm @yes="removeFeature(scope.$index)" />
                    </template>
                </el-table-column>
            </el-table>

            <div class="pagination">
                <Pagination
                    :pagination="pagination"
                    @fetch="fetchFeatures"
                />
            </div>
        </div>

        <!-- Dialog for Adding/Editing Feature -->
        <el-dialog
            title="Add Feature"
            v-model="dialogVisible"
            @opened="onDialogOpen"
        >
            <el-form :model="feature" label-width="120px">
                <el-form-item label="Title">
                    <el-input v-model="feature.name" />
                </el-form-item>

                <el-form-item label="Version">
                    <el-input v-model="feature.version" />
                </el-form-item>

                <!-- <el-form-item label="Priority">
                    <el-select v-model="feature.priority">
                        <el-option
                            v-for="(priority, key) in priorities"
                                :key="key"
                                :value="key"
                                :label="priority"
                            />
                    </el-select>
                </el-form-item> -->

                <el-form-item label="Status">
                    <el-select v-model="feature.status">
                        <el-option
                            v-for="(status, key) in statuses"
                                :key="key"
                                :value="key"
                                :label="status"
                            />
                    </el-select>
                </el-form-item>

                <el-form-item label="Description">
                    <textarea
                        :rows="3"
                        id="feature-description"
                        v-model="feature.description"
                    ></textarea>
                </el-form-item>
            </el-form>

            <template #footer>
                <el-button @click="dialogVisible = false">Close</el-button>
                <el-button type="primary" @click="saveFeature">Save</el-button>
            </template>
        </el-dialog>

        <!-- Dialog for Viewing Feature -->
        <el-dialog
            width="600px"
            class="feature-dialog"
            v-model="viewDialogVisible"
            :title="feature.name"
        >
            <el-descriptions
                v-if="feature"
                :column="1"
                border
                size="small"
                class="feature-details"
            >
                <el-descriptions-item label="ID">
                    {{ feature.id }}
                </el-descriptions-item>

                <el-descriptions-item label="Priority">
                    <span :style="{ color: priorityType(feature.priority) }">
                        {{ priorities[feature.priority] }}
                    </span>
                </el-descriptions-item>

                <el-descriptions-item label="Status">
                    <span :style="{ color: statusType(feature.status) }">
                        {{ statuses[feature.status] }}
                    </span>
                </el-descriptions-item>

                <el-descriptions-item label="Version">
                    {{ feature.version }}
                </el-descriptions-item>

                <el-descriptions-item label="Created At">
                    {{ $formatDate(feature.created_at, 'DD-MM-YYYY hh:mm a') }}
                </el-descriptions-item>

                <el-descriptions-item label="Updated At">
                    {{ $formatDate(feature.updated_at, 'DD-MM-YYYY hh:mm a') }}
                </el-descriptions-item>

                <el-descriptions-item label="Description">
                    <div v-html="feature.description"></div>
                </el-descriptions-item>
            </el-descriptions>

            <template #footer>
                <el-button @click="viewDialogVisible = false">Close</el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script>
import Ajax from '@/utils/http/Ajax';
import Confirm from '@/components/Confirm';
import Pagination from '@/components/Pagination';
import { Edit, Delete, View } from '@element-plus/icons-vue';

export default {
    name: 'Features',
    components: { Confirm, Pagination, Edit, Delete, View },
    data() {
        return {
            filter: '',
            priority: '',
            version: '',
            versions: [],
            features: [],
            dialogVisible: false,
            viewDialogVisible: false,
            feature: this.newFeature(),
            pagination: {
                page: 1,
                limit: 10,
                total: 0
            }
        };
    },
    mounted() {
        this.fetchFeatures();
    },
    computed: {
        statuses() {
            return {
                proposed: "Proposed",
                finalized: "Finalized",
                inprogress: "In Progress",
                implemented: "Implemented",
                deprecated: 'Deprecated',
            };
        },
        priorities() {
            return {
                'low': 'Low',
                'medium': 'Medium',
                'high': 'High',
            };
        },
    },
    methods: {
        newFeature() {
            return {
                id: null,
                name: '',
                version: '',
                priority: 'medium',
                status: 'concept',
                description: ''
            };
        },
        priorityType(priority) {
            switch (priority) {
                case 'low':
                    return 'var(--el-color-info)';
                case 'medium':
                    return 'var(--el-color-primary)';
                case 'high':
                    return 'var(--el-color-success)';
            };
        },
        statusType(status) {
            switch (status) {
                case 'proposed':
                    return 'var(--el-text-color-regular)';
                case 'finalized':
                    return 'var(--el-color-warning-light-3)';
                case 'inprogress':
                    return 'var(--el-color-primary)';
                case 'implemented':
                    return 'var(--el-color-success)';
                case 'deprecated':
                    return 'var(--el-color-danger)';
            }
        },
        handleVersionChange(value) {
            this.version = value;
            this.pagination.page = 1;
            this.fetchFeatures();
        },
        handlePriorityChange(value) {
            this.priority = value;
            this.pagination.page = 1;
            this.fetchFeatures();
        },
        handleFilterChange(value) {
            this.filter = value;
            this.pagination.page = 1;
            this.fetchFeatures();
        },
        openDialog() {
            this.feature = this.newFeature();
            this.dialogVisible = true;
        },
        editFeature(featrure) {
            this.feature = { ...featrure };
            this.dialogVisible = true;
        },
        viewFeature(feature) {
            this.feature = feature;
            this.viewDialogVisible = true;
        },
        onDialogOpen() {
            const id = 'feature-description';

            wp.editor.initialize(id, {
                tinymce: {
                    height: 200,
                    menubar: false,
                    branding: false,
                    quicktags: false,
                    forced_root_block: false,
                    content_style: this.getEditorStyle(),
                    toolbar: [
                        'link',
                        'undo redo',
                        'bullist numlist',
                        'bold italic',
                        'underline'
                    ].join(' | '),
                },
            });

            const editor = tinymce.get(id);
                
            if (!editor) return;

            editor.setContent(this.feature.description || '');

            editor.on('change keyup', () => {
                this.feature.description = editor.getContent();
            });
        },
        getCssVar(name) {
            return getComputedStyle(
                document.documentElement
            ).getPropertyValue(name).trim();
        },
        getEditorStyle() {
            const bg = this.getCssVar('--el-bg-color');
            const text = this.getCssVar('--el-text-color-regular');
            
            return `
                body {
                    background: ${bg};
                    color: ${text};
                    font-family: 'Inter', sans-serif;
                    font-size: 14px;
                    line-height: 1.6;
                    padding: 10px;
                }
            `;
        },
        async fetchFeatures() {
            const res = await Ajax.get('get_features', {
                ...this.pagination,
                version: this.version || '',
                status: this.filter || '',
                priority: this.priority || '',

            });

            if (res.success) {
                this.versions = res.data.versions;
                this.features = res.data.features;
                this.pagination.page = res.data.pagination.current;
                this.pagination.total = res.data.pagination.total;
            }
        },
        async saveFeature() {
            const payload = { ...this.feature };
            
            if (!payload.id) {
                delete payload.id;
            }

            const res = await Ajax.post('save_feature', payload);

            if (res.success) {
                this.fetchFeatures();
                this.dialogVisible = false;
                this.$notifySuccess(res.data.message);
            }
        },
        async removeFeature(index) {
            const res =await Ajax.post('delete_feature', {
                id: this.features[index].id
            });

            if (res.success) {
                this.$notifySuccess(res.data.message);
                this.pagination.page = 1;
                this.fetchFeatures();
            }
        },
    }
};
</script>

<style scoped>
.features-tab {
    padding: 20px 0;
}

.status-tag {
    min-width: 100px;
    text-align: center;
    display: inline-block;
    background: var(--el-fill-color);
}

::v-deep(.mce-widget.mce-btn.mce-active) {
    color: var(--el-bg-color) !important;
    background: var(--el-color-primary) !important;
}

::v-deep(.el-date-editor.el-input) {
    width: 100%;
}

::v-deep(div.mce-panel) {
    color: var(--el-text-color-regular);
    background: var(--el-bg-color) !important;
    border-color: var(--el-border-color);
}
</style>
