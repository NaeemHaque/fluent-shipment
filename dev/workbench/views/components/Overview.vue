<template>
    <div
        class="overview-container"
        :class="{ visible: isVisible }"
    >
        <div class="page-header">
            <strong>Product Overview</strong>

            <p class="overview-meta">
                <small style="margin-right:20px;">
                    Created At:
                    {{ $formatDate(overview.created_at, 'DD-MM-YYYY hh:mm a') }}
                </small>
                <small>
                    Updated At:
                    {{ $formatDate(overview.updated_at, 'DD-MM-YYYY hh:mm a') }}
                </small>
            </p>
        </div>

        <div class="page-content">
            <el-card class="overview-card" shadow="never">
                <el-form
                    :model="overview"
                    @submit.native.prevent="saveOverview"
                    label-width="150px"
                    class="overview-form"
                    label-position="top"
                >
                    <el-row :gutter="20" class="mb-6">
                        <el-col :xs="24" :sm="12" :md="8">
                            <el-form-item>
                                <template #label>
                                    <el-icon
                                        v-if="overview.id"
                                        color="var(--el-color-primary)"
                                        style="cursor: pointer;"
                                    >
                                            <EditPen
                                                v-if="!editing.desc"
                                                @click="editing.desc=!editing.desc"
                                            />
                                            <DocumentChecked
                                                v-else
                                                @click="() => {
                                                    saveOverview();
                                                    editing.desc=false;
                                                }"
                                            />
                                    </el-icon>
                                    Title <small>(short description)</small>
                                    <span
                                        v-if="editing.desc"
                                        class="cancel-edit"
                                        @click="editing.desc=false"
                                    ></span>
                                </template>
                                <el-input
                                    :disabled="!editing.desc"
                                    v-model="overview.description"
                                    placeholder="Enter description"
                                />
                            </el-form-item>
                        </el-col>

                        <el-col :xs="24" :sm="12" :md="8">
                            <el-form-item>
                                <template #label>
                                    <el-icon
                                        v-if="overview.id"
                                        color="var(--el-color-primary)"
                                        style="cursor: pointer;"
                                    >
                                        <EditPen
                                            v-if="!editing.launch"
                                            @click="editing.launch=!editing.launch"
                                        />
                                        <DocumentChecked
                                            v-else
                                            @click="() => {
                                                saveOverview();
                                                editing.launch=false;
                                            }"
                                        />
                                    </el-icon>
                                    Possible Launch Date
                                    <span
                                        v-if="editing.launch"
                                        class="cancel-edit"
                                        @click="editing.launch=false"
                                    ></span>
                                </template>
                                
                                <el-date-picker
                                    :disabled="!editing.launch"
                                    :clearable="false"
                                    type="date"
                                    placeholder="Select date"
                                    format="DD-MM-YYYY"
                                    value-format="YYYY-MM-DD"
                                    v-model="overview.launch_date"
                                />
                            </el-form-item>
                        </el-col>

                        <el-col :xs="24" :sm="24" :md="8">
                            <el-form-item>
                                <template #label>
                                    <el-icon
                                        v-if="overview.id"
                                        color="var(--el-color-primary)"
                                        style="cursor: pointer;"
                                    >
                                        <EditPen
                                            v-if="!editing.status"
                                            @click="editing.status=!editing.status"
                                        />
                                        <DocumentChecked
                                            v-else
                                            @click="() => {
                                                saveOverview();
                                                editing.status=false;
                                            }"
                                        />
                                    </el-icon>
                                    Current Status
                                    <span
                                        v-if="editing.status"
                                        class="cancel-edit"
                                        @click="editing.status=false"
                                    ></span>
                                </template>
                                <el-select
                                    :disabled="!editing.status"
                                    v-model="overview.status"
                                    placeholder="Select status"
                                >
                                    <el-option label="Draft" value="draft" />

                                    <el-option
                                        label="Problem Review"
                                        value="problem_review"
                                    />

                                    <el-option
                                        label="Solution Review"
                                        value="solution_review"
                                    />

                                    <el-option
                                        label="Launch Review"
                                        value="launch_review"
                                    />
                                    
                                    <el-option label="Launched" value="launched" />
                                </el-select>
                            </el-form-item>
                        </el-col>
                    </el-row>

                    <el-row :gutter="20" class="mb-6">
                        <el-col :xs="24" :md="12">
                            <el-card class="section-card" shadow="never">
                                
                                <div class="section-title">
                                    <el-icon
                                        v-if="overview.id"
                                        color="var(--el-color-primary)"
                                        style="cursor: pointer;"
                                    >
                                        <EditPen
                                            v-if="!editing.problem"
                                            @click="toggleProblem()"
                                        />
                                        <DocumentChecked
                                            v-else
                                            @click="() => {
                                                saveOverview();
                                                editing.problem=false;
                                            }"
                                        />
                                    </el-icon>
                                    Problem <small>(Why this is needed)</small>
                                    <span
                                        v-if="editing.problem"
                                        class="cancel-edit"
                                        @click="toggleProblem()"
                                    ></span>
                                </div>

                                <textarea
                                    id="problem"
                                    v-model="overview.problem"
                                    placeholder="Describe the problem"
                                ></textarea>
                            </el-card>
                        </el-col>

                        <el-col :xs="24" :md="12">
                            <el-card class="section-card" shadow="never">
                                
                                <div class="section-title">
                                    <el-icon
                                        v-if="overview.id"
                                        color="var(--el-color-primary)"
                                        style="cursor: pointer;"
                                    >
                                        <EditPen
                                            v-if="!editing.solution"
                                            @click="toggleSolution()"
                                        />
                                        <DocumentChecked
                                            v-else
                                            @click="() => {
                                                saveOverview();
                                                editing.solution=false;
                                            }"
                                        />
                                    </el-icon>
                                    Solution
                                    <small>(How it'll solve the problem)</small>
                                    <span
                                        v-if="editing.solution"
                                        class="cancel-edit"
                                        @click="toggleSolution()"
                                    ></span>
                                </div>

                                <textarea
                                    id="solution"
                                    v-model="overview.solution"
                                    placeholder="Describe the solution"
                                ></textarea>
                            </el-card>
                        </el-col>
                    </el-row>

                    <el-row v-if="!overview.id">
                        <el-col align="right">
                            <el-button type="primary"
                                :loading="loading"
                                @click="saveOverview"
                            >Save Overview</el-button>
                        </el-col>
                    </el-row>
                </el-form>
            </el-card>
        </div>
    </div>
</template>

<script>
import Ajax from '@/utils/http/Ajax';
import { EditPen, DocumentChecked } from '@element-plus/icons-vue';

export default {
    name: 'OverviewTab',
    components: { EditPen, DocumentChecked },
    data() {
        return {
            isVisible: false,
            loading: false,
            editing: {
                problem: false,
                solution: false,
                desc: false,
                launch: false,
                status: false,
            },
            overview: {
                description: '',
                launch_date: '',
                problem: '',
                solution: '',
                status: 'draft',
            },
        };
    },
    mounted() {
        this.fetchOverview();
    },
    methods: {
        initEditors() {
            const editorIds = ['problem', 'solution'];
            const options = {
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
            };

            editorIds.forEach((id) => {
                const existing = tinymce.get(id);
                
                if (existing) existing.destroy();

                wp.editor.initialize(id, options);

                const editor = tinymce.get(id);
                
                if (!editor) return;

                editor.setContent(this.overview[id] || '');

                editor.on('change keyup', () => {
                    this.overview[id] = editor.getContent();
                });

                editor.on('init', () => {
                    this.editing.desc = !this.overview?.id;
                    this.editing.launch = !this.overview?.id;
                    this.editing.status = !this.overview?.id;
                    this.toggleEditor(id, !this.overview?.id);
                    this.isVisible = true;
                });
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
        updateEditorContent() {
            ['problem', 'solution'].forEach((id) => {
                const editor = tinymce.get(id);
                
                if (editor) {
                    editor.setContent(this.overview[id] || '');
                }
            });
        },
        async fetchOverview() {
            try {
                const res = await Ajax.get('get_overview');

                if (res.success) {
                    this.overview = res.data.overview;
                    this.$nextTick(() => {
                        this.initEditors();
                    });
                }
            } catch {
                this.$notifyError('Failed to fetch overview.');
            }
        },
        async saveOverview() {
            if (
                !this.overview.description.trim()
                || !this.overview.launch_date
                || !this.overview.problem.trim()
                || !this.overview.solution.trim()
            ) {
                return this.$notifyError('Missing required fields.');
            }

            this.loading = true;

            try {
                const formData = new FormData();

                for (const key in this.overview) {
                    formData.append(key, this.overview[key]);
                }

                const res = await Ajax.post('save_overview', formData);

                if (res.success) {
                    this.fetchOverview();
                    this.$notifySuccess('Overview saved successfully!');
                } else {
                    this.$notifyError(res?.message || 'Failed to save overview');
                }
            } catch (error) {
                this.$notifyError(error.message || 'Error while saving overview');
            } finally {
                this.loading = false;
            }
        },
        toggleEditor(id, editable) {
            const editor = tinymce.get(id);
            if (!editor) return;

            const container = editor.getContainer();
            const iframe = container.querySelector('iframe');

            if (editable) {
                // Editable mode
                editor.setMode('design');
                container.querySelector('.mce-toolbar-grp').style.display = '';
                container.querySelector('.mce-statusbar').style.display = '';

                iframe.style.height = '260px';
                container.style.border = '';
                container.style.background = '';
            } else {
                // Read-only mode
                editor.setMode('readonly');
                container.querySelector('.mce-toolbar-grp').style.display = 'none';
                container.querySelector('.mce-statusbar').style.display = 'none';

                iframe.style.height = '320px';
                container.style.border = '1px solid var(--el-border-color-light)';
                container.style.background = 'var(--el-bg-color-light)';

                // Wait for iframe to update, then apply
                // the scrolling to it's internal body
                setTimeout(() => {
                    try {
                        const doc = iframe.contentDocument
                        || iframe.contentWindow.document;

                        const body = doc?.body;
                        if (body) {
                            body.style.overflowY = 'auto';
                            body.style.maxHeight = '200px';
                            body.style.padding = '8px';
                            body.style.userSelect = 'text';
                        }
                    } catch (e) {
                        console.warn('TinyMCE readonly scroll fix failed', e);
                    }
                }, 100);
            }
        },
        toggleProblem() {
            this.editing.problem = !this.editing.problem;
            this.toggleEditor('problem', this.editing.problem);
        },
        toggleSolution() {
            this.editing.solution = !this.editing.solution;
            this.toggleEditor('solution', this.editing.solution);
        }
    }
};
</script>

<style scoped>
.mb-6 {
    margin-bottom: 24px;
}

.overview-card {
    padding: 20px;
    margin-top: 20px;
    background-color: var(--el-bg-color);
}

.section-title {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 8px;
    color: var(--el-text-color-regular);
}

.section-card {
    padding: 15px;
    margin-bottom: 10px;
    background-color: var(--el-card-bg-color);
}

.overview-meta {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    margin: 5px 0 10px 0;
    color: var(--el-text-color-regular);
}

.cancel-edit {
    margin-left: 10px;
    cursor: pointer;
    color: var(--el-color-danger);
}

.cancel-edit::after {
    content: 'x';
}

.overview-container {
  visibility: hidden;
  opacity: 0;
  transition: opacity 0.2s ease, visibility 0.2s;
}

.overview-container.visible {
  visibility: visible;
  opacity: 1;
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
