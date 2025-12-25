<template>
    <div class="tab-content">
        <div class="page-header">
            <strong>Migrations ({{migrations.length}})</strong>
            
            <!-- Create Migration -->
            <el-button size="small" type="primary" @click="openDrawer">
                Create Migration
            </el-button>
        </div>

        <div class="page-content">
            <el-scrollbar max-height="680px">
                <el-collapse accordion>
                    <el-collapse-item
                        v-for="m in migrations"
                        :key="m.table"
                        :name="m.table"
                    >
                        <template #title>
                            <img
                                :src="tableIcon"
                                alt="Table Icon"
                                class="table-icon"
                            />
                            {{ m.table }} ({{ m.fields.length }})

                            <el-dropdown
                                @command="handleDb($event, m)"
                                style="float:right;"
                            >
                                <span class="el-dropdown-link">
                                    <el-icon style="margin:18px 15px;">
                                        <Setting />
                                    </el-icon>
                                </span>
                                <template #dropdown>
                                    <el-dropdown-menu>
                                        <el-dropdown-item command="edit">
                                            Manage Schema
                                        </el-dropdown-item>

                                        <el-dropdown-item command="index">
                                            Manage Indexes
                                        </el-dropdown-item>

                                        <el-dropdown-item
                                            v-if="!m.is_migrated"
                                            command="migrate"
                                        >
                                            Migrate Table
                                        </el-dropdown-item>

                                        <el-dropdown-item
                                            v-else
                                            command="refresh"
                                        >
                                            Refresh Table
                                        </el-dropdown-item>

                                        <el-dropdown-item
                                            v-if="m.is_migrated"
                                            command="rollback"
                                        >
                                            Rollback Table
                                        </el-dropdown-item>
                                        
                                        <el-divider style="margin:0" />

                                        <el-dropdown-item command="delete">
                                            Delete Migration
                                        </el-dropdown-item>
                                    </el-dropdown-menu>
                                </template>
                            </el-dropdown>
                        </template>

                        <el-table
                            :data="m.fields"
                            style="color:var(--el-color-info-dark-2);"
                        >
                            <!-- Index/Number -->
                            <el-table-column label="#">
                                <template #default="{ $index }">
                                    {{ ++$index }}
                                </template>
                            </el-table-column>

                            <!-- Field Name -->
                            <el-table-column prop="name" label="Name" />

                            <!-- Type + Modifiers -->
                            <el-table-column label="Type" width="350">
                                <template #default="{ row }">
                                    <span>
                                        {{ row.type }}
                                        <small v-if="row.unsigned">
                                            &nbsp;UNSIGNED
                                        </small>
                                        <small v-if="row.zerofill">
                                            &nbsp;ZEROFILL
                                        </small>
                                        <small v-if="row.autoIncrement">
                                            &nbsp;AUTO INCREMENT
                                        </small>
                                        <small v-if="row.primary">PRIMARY KEY</small>
                                    </span>
                                </template>
                            </el-table-column>

                            <!-- Nullable -->
                            <el-table-column
                                prop="nullable"
                                label="Nullable"
                                align="center"
                            >
                                <template #default="{ row }">
                                    {{ row.nullable ? 'Yes' : 'No' }}
                                </template>
                            </el-table-column>

                            <!-- Default -->
                            <el-table-column
                                prop="default"
                                label="Default"
                                align="center"
                            >
                                <template #default="{ row }">
                                    {{ row.default !== null ? row.default : '-' }}
                                </template>
                            </el-table-column>

                            <!-- On Update -->
                            <el-table-column
                                prop="on_update"
                                label="On Update"
                                align="center"
                            >
                                <template #default="{ row }">
                                    {{ row.on_update || '-' }}
                                </template>
                            </el-table-column>

                            <!-- Foreign Key Relations -->
                            <el-table-column
                                prop="relations"
                                label="Foreign Key"
                                align="center"
                            >
                                <template #default="{ row }">
                                    <div
                                        v-if="row.relations && row.relations.length"
                                    >
                                        <div
                                            v-for="r in row.relations"
                                            :key="r.field"
                                        >
                                            {{ r.references_table }}
                                            ({{ r.references_field }})
                                            <span v-if="r.on_delete">
                                                ON DELETE {{ r.on_delete }}
                                            </span>
                                            <span v-if="r.on_update">
                                                ON UPDATE {{ r.on_update }}
                                            </span>
                                        </div>
                                    </div>
                                    <span v-else>-</span>
                                </template>
                          </el-table-column>
                        </el-table>
                        
                        <el-card
                            style="margin-top: 20px;"
                            v-if="m.indexes.length"
                            shadow="never"
                        >
                            <el-row
                                v-for="(data, type) in groupedIndexes(m.indexes)"
                                :key="type"
                                class="mb-2"
                                align="middle"
                            >
                                <!-- Type & Count -->
                                <el-col :span="12" style="margin-bottom: 5px;">
                                    <el-text
                                        :type="type === 'Unique' ? 'success' : 'primary'"
                                        effect="plain"
                                    >
                                        {{ type }} ({{ data.count }})
                                    </el-text>
                                </el-col>

                                <!-- Fields -->
                                <el-col :span="12" style="text-align:right;">
                                    <el-tag
                                        v-for="(field, idx) in data.fields"
                                        :key="idx"
                                        style="margin: 0 5px 5px 0;"
                                        :type="type === 'Unique' ? 'success' : 'primary'"
                                        effect="light"
                                    >
                                        {{ field }}
                                    </el-tag>
                                </el-col>
                            </el-row>
                        </el-card>
                    </el-collapse-item>
                </el-collapse>
            </el-scrollbar>
        </div>
    </div>

    <!-- Edit Table/Migration -->
    <el-drawer
        v-model="drawerVisible"
        :close-on-click-modal="false"
        :title="tableDrawerTitle"
        direction="rtl"
        size="80%"
        @close="onDialogClose"
        style="top:20px"
    >
        <div class="drawer-body">
            <el-form label-position="top">
                <!-- Table Name -->
                <el-row :gutter="12">
                    <el-col :span="24">
                        <el-form-item>
                            <template #label>
                                <strong style="color:var(--el-color-info-dark-2)">
                                    Table Name
                                </strong>
                            </template>
                            <el-input
                                v-model="form.table"
                                placeholder="Table name"
                            />
                        </el-form-item>
                    </el-col>
                </el-row>

                <!-- Create/Edit Table -->
                <el-table :data="form.fields" style="margin-bottom:12px;">
                    <el-table-column label="Name" prop="name">
                        <template #default="{ row }">
                            <el-input
                                size="small"
                                v-model="row.name"
                                placeholder="Field name"
                            />
                        </template>
                    </el-table-column>

                    <el-table-column label="Type" prop="type">
                        <template #default="{ row }">
                            <el-select
                                filterable
                                v-model="row.type"
                                placeholder="Select Type"
                                @change="onTypeChange(row, $event)"
                            >
                                <template
                                    v-for="(options, group) in types"
                                    :key="group"
                                >
                                    <el-option-group :label="group">
                                        <el-option
                                            v-for="type in options"
                                            :key="type"
                                            :label="type.includes('`') ? type.split('`')[0] : type"
                                            :value="type"
                                        />
                                    </el-option-group>
                                </template>
                            </el-select>
                        </template>
                    </el-table-column>

                    <el-table-column label="Length" prop="length">
                        <template #default="{ row }">
                            <div v-if="row.type==='decimal'" class="flex gap-2">
                                <el-row :gutter="4">
                                    <el-col :span="12">
                                        <el-input
                                            size="small"
                                            v-model="row.precision"
                                            placeholder="Precision"
                                        />
                                    </el-col>
                                    <el-col :span="12">
                                        <el-input
                                            size="small"
                                            v-model="row.scale"
                                            placeholder="Scale"
                                        />
                                    </el-col>
                                </el-row>
                            </div>

                            <el-input
                                v-else
                                clearable
                                size="small"
                                v-model="row.length"
                                placeholder="Length"
                            />
                        </template>
                    </el-table-column>

                    <el-table-column label="Options">
                        <template #default="{ row }">
                            <el-select
                                clearable
                                v-model="row.option"
                                placeholder="Options"
                            >
                                <el-option
                                    v-for="opt in getOptionsForType(row.type)"
                                    :key="opt.value"
                                    :label="opt.label"
                                    :value="opt.value"
                                />
                            </el-select>
                        </template>
                    </el-table-column>

                    <el-table-column label="Default">
                        <template #default="{ row }">
                            <el-input
                                v-model="row.default"
                                placeholder="Default"
                                size="small"
                            />
                        </template>
                    </el-table-column>

                    <el-table-column label="Nullable" prop="nullable" align="center" width="80">
                        <template #default="{ row }">
                            <el-checkbox v-model="row.nullable" />
                        </template>
                    </el-table-column>

                    <el-table-column label="Primary" prop="primary" align="center" width="80">
                        <template #default="{ row }">
                            <el-checkbox v-model="row.primary" />
                        </template>
                    </el-table-column>

                    <el-table-column label="AI" prop="autoIncrement" align="center" width="80">
                        <template #default="{ row }">
                            <el-checkbox v-model="row.autoIncrement" />
                        </template>
                    </el-table-column>

                    <el-table-column label="" align="right" width="50">
                        <template #default="{ $index }">
                            <el-button
                                text
                                type="danger"
                                @click="removeField($index)"
                                :disabled="form.fields.length===1"
                            >
                                <el-icon><CircleClose /></el-icon>
                            </el-button>
                        </template>
                    </el-table-column>
                </el-table>

                <el-button
                    text
                    type="primary"
                    size="small"
                    @click="addField"
                >+ Add Field</el-button>
            </el-form>
        </div>

        <template #footer>
            <div class="drawer-footer">
                <el-button @click="drawerVisible=false">Close</el-button>
                <el-button type="primary" @click="save">Save</el-button>
            </div>
        </template>
    </el-drawer>

    <!-- Index Management Drawer -->
    <el-drawer
        v-model="indexDrawerVisible"
        :close-on-click-modal="false"
        :title="indexDrawerTitle"
        direction="rtl"
        size="40%"
        @close="onIndexDialogClose"
        style="top:20px"
    >
        <template #default>
            <div class="drawer-body">
                <el-form label-position="top" class="p-4">

                    <!-- Index Type -->
                    <el-form-item label="Index Type">
                        <el-select v-model="indexForm.type" placeholder="Select index type">
                            <el-option label="INDEX" value="index" />
                            <el-option label="UNIQUE" value="unique" />
                            <el-option label="FULLTEXT" value="fulltext" />
                            <el-option label="SPATIAL" value="spatial" />
                        </el-select>
                    </el-form-item>

                    <!-- Fields -->
                    <el-form-item label="Select Fields">
                        <el-select
                            v-model="indexForm.fields"
                            multiple
                            filterable
                            placeholder="Select fields to include in index"
                        >
                            <el-option
                                v-for="field in form.fields"
                                :key="field.name"
                                :label="field.name"
                                :value="field.name"
                            />
                        </el-select>
                    </el-form-item>

                    <!-- Index Name -->
                    <el-form-item label="Index Name">
                        <el-input v-model="indexForm.name" placeholder="Optional custom name" />
                    </el-form-item>

                    <el-form-item>
                        <el-button
                            size="small"
                            type="primary"
                            @click="addIndex"
                            class="el-button--fluid"
                        >Add Index</el-button>
                    </el-form-item>

                    <!-- Existing Index List -->
                    <el-divider>Existing Indexes</el-divider>
                    <el-table
                        :data="form.indexes || []"
                        size="small"
                    >
                        <el-table-column prop="type" label="Type" />
                        <el-table-column prop="name" label="Name" />
                        <el-table-column prop="fields" label="Fields">
                            <template #default="{ row }">{{ row.fields.join(', ') }}</template>
                        </el-table-column>
                        <el-table-column label="" width="80" align="right">
                            <template #default="{ $index }">
                                <el-button
                                    text
                                    type="danger"
                                    @click="removeIndex($index)"
                                ><el-icon><Delete /></el-icon></el-button>
                            </template>
                        </el-table-column>
                    </el-table>
                </el-form>
            </div>
        </template>

        <template #footer>
            <div class="drawer-footer">
                <el-button @click="indexDrawerVisible = false">Close</el-button>
                <el-button
                    type="success"
                    :loading="updating"
                    @click="updateIndex"
                >Save</el-button>
            </div>
        </template>
    </el-drawer>
</template>

<script>
import { h, ref, defineComponent } from 'vue'
import { ElCheckbox } from 'element-plus';
import tableIcon from './tableIcon';
import Ajax from '@/utils/http/Ajax';
import {
    Collection,
    CircleClose,
    Delete,
    Edit,
    View,
    Setting
} from '@element-plus/icons-vue';

export default {
    name: 'DatabaseTab',
    components: {
        Collection,
        CircleClose,
        Delete,
        Edit,
        View,
        Setting,
        ElCheckbox,
    },
    mounted() {
        this.getMigrations();
    },
    data() {
        return {
            tableIcon,
            migrations: [],
            updating: false,
            drawerVisible: false,
            indexDrawerVisible: false,
            tableDrawerTitle: 'Create Table',
            form: {
                id: '',
                table: '',
                fields: [{ ...this.defaultField() }]
            },
            indexForm: {
                type: '',
                name: '',
                fields: [],
                indexes: []
            },
            types: {
                'Numbers': [
                    'tinyint',
                    'smallint',
                    'mediumint',
                    'int',
                    'bigint',
                    'decimal',
                    'float',
                    'double'
                ],
                'Date and time': [
                    'date',
                    'datetime',
                    'timestamp',
                    'time',
                    'year'
                ],
                'Strings': [
                    'char',
                    'varchar',
                    'tinytext',
                    'text',
                    'mediumtext',
                    'longtext',
                    'json'
                ],
                'Lists': [
                    'enum',
                    'set'
                ],
                'Binary': [
                    'bit',
                    'binary',
                    'varbinary',
                    'tinyblob',
                    'blob',
                    'mediumblob',
                    'longblob'
                ],
                'Geometry': [
                    'geometry',
                    'point',
                    'linestring',
                    'polygon',
                    'multipoint',
                    'multilinestring',
                    'multipolygon',
                    'geometrycollection'
                ],
            }
        }
    },
    computed: {
        newField() {
            return {
                name:'',
                type:'',
                length:'',
                nullable:false,
                primary:false,
                autoIncrement:false,
                options:[],
                precision:'',
                scale:'',
                default:'',
            };
        },
        indexDrawerTitle() {
            return `Manage Indexes - ${this.form.table}`;
        },
    },
    methods: {
        async getMigrations() {
            const res = await Ajax.get('migrations');

            if (res.success) {
                this.migrations = res.data.migrations;
            }
        },
        openDrawer() {
            this.drawerVisible = true;
        },
        addField() {
            this.form.fields.push({ ...this.newField });
        },
        removeField(index) {
            this.form.fields.splice(index,1);
        },
        onTypeChange(row, type) {
            row.options = [];
        },
        getOptionsForType(type) {
            const options = [];
            
            const numberTypes = [
                'int',
                'tinyint',
                'smallint',
                'mediumint',
                'bigint',
                'decimal',
                'float',
                'double'
            ];

            if (numberTypes.includes(type)) {
                options.push({
                    label:'Unsigned',
                    value:'unsigned'
                });
            }

            else if (['timestamp', 'datetime'].includes(type)) {
                options.push({
                    label:'On Update Current Timestamp',
                    value:'on_update_current_timestamp'
                });
            }

            return options;
        },
        async fetchDatabaseTables() {
            this.getMigrations();
        },
        async save() {
            if (!this.form.table) {
                this.$notifyError('Table name is required');
                return;
            }

            const res = await Ajax.post('save_migration', {
                id: this.form.id,
                table: this.form.table,
                fields: JSON.stringify(this.form.fields),
                indexes: JSON.stringify(this.form.indexes || []),
            });
            
            if (res.success) {
                this.$notifySuccess(res.data.message);
                this.fetchDatabaseTables();
                this.drawerVisible = false;
            }
        },
        async updateIndex() {
            this.updating = true;
            await this.save();
            this.updating = false;
        },
        async remove(row) {
            const res = await Ajax.post('del_db_table', {
                id: row.id
            });

            if (res.success) {
                this.fetchDatabaseTables();
                this.$notifySuccess(res.data.message);
            }
        },
        defaultField() {
            return {
                name:'id',
                type:'bigint',
                length:'',
                nullable:false,
                primary:true,
                autoIncrement:true,
                option:[],
                precision:'',
                scale:'',
                default:'',
            };
        },
        onDialogClose() {
            this.form = {
                id: '',
                table: '',
                fields: [{ ...this.defaultField() }]
            };
        },
        onIndexDialogClose() {
            this.onDialogClose();
            this.indexForm = {
                type: '',
                name: '',
                fields: []
            };
        },
        handleDb(command, m) {
            switch (command) {
                case 'edit':
                    this.edit(m);
                    break;
                case 'index':
                    this.manageIndex(m);
                    break;
                case 'migrate':
                    this.migrateTable(m);
                    break;
                case 'refresh':
                    this.refreshTable(m);
                    break;
                case 'rollback':
                    this.rollbackTable(m);
                    break;
                case 'delete':
                    this.deleteMigration(m);
                    break;
            }
        },
        edit(table) {
            this.form.id = table.id;
            this.form.table = table.table;
            this.form.fields = this.normalizeFields(table.fields);
            this.drawerVisible = true;
            this.tableDrawerTitle = 'Edit Table';
        },
        manageIndex(table) {
            this.form = {
                id: table.id,
                table: table.table,
                fields: this.normalizeFields(table.fields),
                indexes: table.indexes || []
            };
            this.indexForm = { type: '', name: '', fields: [] };
            this.indexDrawerVisible = true;
        },
        addIndex() {
            if (!this.indexForm.type || !this.indexForm.fields.length) {
                this.$notifyError('Select index type and fields.');
                return;
            }

            const newIndex = {
                type: this.indexForm.type.toUpperCase(),
                name: this.indexForm.name
                    || `${this.indexForm.type}_${this.indexForm.fields.join('_')}`,
                fields: [...this.indexForm.fields],
            };

            // Add or replace if same name exists
            const existing = this.form.indexes.findIndex(
                i => i.name === newIndex.name
            );

            if (existing >= 0) {
                this.form.indexes.splice(existing, 1, newIndex);
            } else {
                this.form.indexes.push(newIndex);
            }

            this.indexForm = {
                type: '',
                name: '',
                fields: []
            };
        },
        removeIndex(i) {
            this.form.indexes.splice(i, 1);
        },
        migrateTable(t) {
            const n = `<span style='color:var(--el-color-info)'>${t.table}</span>`;
            ElMessageBox.confirm(
                `Are you sure you want to migrate ${n} table?`,
                'Migrate',
                {
                    type: 'warning',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                    dangerouslyUseHTMLString: true,
                }
            )
            .then(async () => {
                const res = await Ajax.post('migrate_table', {
                    table: t.table,
                });

                if (res.success) {
                    t.is_migrated = true;
                    this.$notifySuccess(res.data.message);
                    return;
                }
            })
            .catch(() => null);
        },
        refreshTable(t) {
            const n = `<span style='color:var(--el-color-info)'>${t.table}</span>`;
            ElMessageBox.confirm(
                `Are you sure you want to re-migrate ${n} table?`,
                'Migrate',
                {
                    type: 'warning',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                    dangerouslyUseHTMLString: true,
                }
            )
            .then(async () => {
                const res = await Ajax.post('refresh_table', {
                    table: t.table,
                });

                if (res.success) {
                    this.$notifySuccess(res.data.message);
                    return;
                }
            })
            .catch(() => null);
        },
        rollbackTable(t) {
            const n = `<span style='color:var(--el-color-info)'>${t.table}</span>`;
            ElMessageBox.confirm(
                `Are you sure you want to roll-back ${n} table?`,
                'Migrate',
                {
                    type: 'warning',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                    dangerouslyUseHTMLString: true,
                }
            )
            .then(async () => {
                const res = await Ajax.post('rollback_table', {
                    table: t.table,
                });

                if (res.success) {
                    t.is_migrated = false;
                    this.$notifySuccess(res.data.message);
                    return;
                }
            })
            .catch(() => null);
        },
        deleteMigration(t) {
            const name = t.table;
            const checked = ref(t.is_migrated);

            const MessageContent = defineComponent({
                setup() {
                    return () =>
                        h('p', null, [
                            h('span', null, [
                                'Do you want to delete the migration file ',
                                h('span', { style: 'color:var(--el-color-info)' }, `${name}?`),
                            ]),
                            t.is_migrated &&
                                h('div', { style: 'margin-top:10px;' }, [
                                    h(ElCheckbox, {
                                        label: 'Delete Table',
                                        modelValue: checked.value,
                                        'onUpdate:modelValue': (val) => {
                                            checked.value = val;
                                        },
                                    }),
                                ]),
                        ]);
                },
            });

            ElMessageBox({
                title: 'Delete',
                showCancelButton: true,
                type: 'warning',
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
                message: h(MessageContent),
            })
                .then(async () => {
                    await Ajax.post('delete_migration', {
                        table: t.table,
                        delete_table: checked.value,
                    });

                    this.migrations.splice(this.migrations.indexOf(t), 1);
                    this.$notifySuccess('Migration deleted successfully');
                })
                .catch(() => null);
        },
        view(table) {
            console.log(table);
        },
        groupedIndexes(indexes) {
            const result = {};

            indexes.forEach(i => {
                const type = i.type === 'UNIQUE' ? 'Unique' : 'Index';

                if (!result[type]) result[type] = { fields: [], count: 0 };

                if (i.fields.length > 1) {
                    result[type].fields.push(`(${i.fields.join(', ')})`);
                } else {
                    result[type].fields.push(i.fields[0]);
                }

                result[type].count += 1;
            });

            return result;
        },
        normalizeFields(fields) {
            return fields.map(f => {
                const match = f.type.match(/^([A-Z]+)(\(([^)]+)\))?$/i);
                let type = f.type;
                let length = '';
                let precision = '';
                let scale = '';

                if (match) {
                    type = match[1].toLowerCase();
                    if (match[3]) {
                        const parts = match[3].split(',').map(p => p.trim());
                        if (parts.length === 1) {
                            length = parts[0];
                        } else if (parts.length === 2) {
                            precision = parts[0];
                            scale = parts[1];
                        }
                    }
                }

                let option = '';

                if (f.unsigned) {
                    option = 'unsigned';
                }
                
                if (f.zerofill) {
                    option = 'zerofill';
                }

                if (f.on_update === 'CURRENT_TIMESTAMP') {
                    option = 'on_update_current_timestamp';
                }

                return {
                    ...f,
                    type,
                    length,
                    precision,
                    scale,
                    option,
                };
            });
        },
    }
}
</script>

<style scoped>
.drawer-footer {
    padding: 20px 0;
    border-top: 1px solid var(--el-border-color);
}

.table-icon {
    width: 16px;
    height: 16px;
    vertical-align: middle;
    transition: filter 0.3s ease;
}

:deep(.el-collapse-item__title:hover) {
    color: var(--el-color-primary);
}

html.dark .table-icon,
:root[class~="dark"] .table-icon {
    filter: brightness(.7) contrast(1.2);
}

:deep(.el-scrollbar__bar.is-vertical),
:deep(.el-scrollbar__bar.is-horizontal) {
    display: none !important;
}
</style>
