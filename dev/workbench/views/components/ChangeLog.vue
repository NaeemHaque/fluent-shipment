<template>
	<div class="tab-content">
		<div class="page-header">
			<strong>Change Logs</strong>
		</div>

		<div class="page-content" style="height:100%;">
			<el-splitter>
		      	<el-splitter-panel size="20%" min="20%" max="30%">
		        	<div style="padding-right:10px;">
		        		<el-button
		        			plain
		        			size="large"
		        			class="el-button--fluid"
		        			@click="createNewLog"
		        			v-if="!creating"
		        		>
		        			+ Add
		        		</el-button>

		        		<el-input
		        			size="large"
		        			ref="logName"
		        			v-if="creating"
		        			v-model="log_name"
		        			placeholder="Log Name..."
		        			class="log-name"
		        			@keyup.escape.native="creating = false"
		        			@keyup.enter.native="savelogDocument"
		        		>
		        			<template #prepend>
		        				<el-button type="danger" @click="creating=false">
		        					<el-icon><Close /></el-icon>
		        				</el-button>
		        			</template>
		        		</el-input>

		        		<el-divider style="margin:10px 0;" />

		        		<el-scrollbar max-height="620px">
	        				<el-alert
	        					size="small"
	        					v-if="logs.length"
	        					v-for="(log, key) in logs"
	        					type="info"
	        					:key="key"
	        					:closable="false"
	        					@click="open(log)"
	        					class="alert-name"
	        					:class="{'is-active':activeLog?.id===log.id}"
	        				>
	        					{{ log.name }}
	        					<el-icon
	        						size="large"
	        						class="edit-log-name"
	        						@click.stop="editLogName($event, log)"
	        					><Edit /></el-icon>

	        					<el-icon
	        						size="large"
	        						class="delete-log"
	        						@click.stop="deleteLog($event, log)"
	        					><Delete /></el-icon>
	        				</el-alert>
	        				
	        				<el-alert
	        					center
	        					v-else
	        					:closable="false"
	        				>No Logs</el-alert>
		        		</el-scrollbar>
		        	</div>
		      	</el-splitter-panel>

		      	<el-splitter-panel size="80%">
		        	<div v-if="activeLog" class="editor-container">
					    <textarea
					    	:id="activeEditorId"
					    	:key="activeEditorId"
					    >{{ activeLog.content }}</textarea>
					</div>
		      	</el-splitter-panel>
		    </el-splitter>
		</div>
	</div>
</template>

<script>
import Ajax from '@/utils/http/Ajax';
import { Close } from '@element-plus/icons-vue';
import { Edit, Delete } from '@element-plus/icons-vue';

export default {
	name: 'ChangeLog',
	components: {
		Close,
		Edit,
		Delete,
	},
	data() {
		return {
			logs: [],
			saving: false,
			activeLog: null,
			editingLogName: null,
			creating: false,
			log_name: '',
			activeEditorId: null,
		};
	},
	mounted() {
		this.fetchLogs();
	},
	methods: {
		async fetchLogs() {
			try {
				const res = await Ajax.get('logs');

				if (res.success && res.data.logs.length) {
					this.logs = res.data.logs;
				}
			} catch (e) {
				if (e == 0) {
					return this.$notifyError(
						'Bad Request Or Endpoint Not Found!'
					);
				}

				this.$notifyError(e?.message || e?.data?.message);
			}
		},
		createNewLog() {
			this.creating = true;
			this.log_name = this.newLogName();
			this.$nextTick(() => {
			    if (this.$refs.logName) {
			        this.$refs.logName.focus();
			    }
			});
		},
		newName() {
			return 'Doc-' + this.$defaultDate();
		},
		findLogByName(name) {
			return this.logs.find(log => log.name === name);
		},
		newLogName() {
			let round = 0;
			let name = this.newName();
			let existing = this.findLogByName(name);
			
			while (existing) {
				round++;
				name = this.newName() + '-' + round;
				existing = this.findLogByName(name);
			}

			return name;
		},
		async updatelogDocument() {
			try {
				this.saving = true;
			
				const res = await Ajax.post('save_log', {
					id: this.activeLog?.id,
					name: this.activeLog.name,
					content: this.activeLog.content,
				});
				this.$notifySuccess('Document Updated.');
			} finally {
				this.saving = false;
			}
		},
		async savelogDocument() {
			if (!this.log_name) {
				this.$notifyError('The name is required!');	
			}

			if (this.log_name === this.findLogByName(this.log_name)?.name) {
				this.$notifyError('The name already exists!');
				return;
			}

			if (this.editingLogName?.id) {
				await this.updatelogName();
				return;
			}

			const res = await Ajax.post('save_log', {
				name: this.log_name
			});

			if (res.success) {
				this.logs.unshift(res.data.log);
				this.activeLog = this.logs[0];
				this.open(this.activeLog);
				this.creating = false;
				this.log_name = '';
			}

		},
		open(log) {
		  	if (this.activeEditorId) {
		    	const oldEditor = tinymce.get(this.activeEditorId);
		    	if (oldEditor) oldEditor.destroy();
		  	}

		  	this.activeLog = log;
		  	this.activeEditorId = this.id(log.id);
		  	this.$nextTick(() => {
		    	this.initEditor(this.activeEditorId);
		  	});
		},
		id(id) {
			return id.replace(/\./g, '');
		},
		initEditor(id) {
	        const height = this.getComputedHeight(id);
			
			const options = {
                tinymce: {
                    menubar: false,
                    branding: false,
                    quicktags: false,
                    resize: false,
				    height: height,
                    forced_root_block: false,
                    content_style: this.getEditorStyle(),
                    toolbar1: [
                        'link',
                        'undo redo',
                        'bullist numlist',
                        'bold italic',
                        'underline',
                        '_sep_',
                        'closebutton',
                        'savebutton',
                    ].join(' '),
	                setup: (editor) => {
			            editor.addButton('closebutton', {
			                text: 'ⓧ',
			                tooltip: 'Close',
			                onClick: () => {
			                	this.activeLog = null;
			                },
			            });
			            editor.addButton('savebutton', {
			                text: '💾',
			                tooltip: 'Save',
			                onClick: () => {
			                	this.updatelogDocument();
			                },
			            });
			            editor.addButton('_sep_', {
						    text: '❘',
						    tooltip: false,
						    disabled: true,
						});
			        },
		        },
            };

            const existing = tinymce.get(id);
                
            if (existing) existing.destroy();

            wp.editor.initialize(id, options);

            setTimeout(() => {
			    const editor = tinymce.get(id);
			    if (!editor) return;

			    editor.setContent(this.activeLog.content || '');
			    editor.off('change keyup');
			    editor.on('change keyup', () => {
			      	this.activeLog.content = editor.getContent();
			    });
			}, 200);
		},
		getComputedHeight(id) {
			let height = 500;
			
			const container = document.getElementById(id)?.parentElement;
	        
	        if (container) {
	            height = container.clientHeight
	            	|| container.getBoundingClientRect().height
	            	|| height;
	        } else {
	            height = document.documentElement.clientHeight - 200;
	        }

	        return (height - 50);
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
        getCssVar(name) {
            return getComputedStyle(
                document.documentElement
            ).getPropertyValue(name).trim();
        },
        editLogName(e, log) {
        	this.creating = true;
        	this.editingLogName = log;
        	this.log_name = log.name;
        },
        async updatelogName() {
			const res = await Ajax.post('save_log', {
				name: this.log_name,
				id: this.editingLogName.id,
				content: this.editingLogName.content,
			});

			this.logs.find(
				log => log.id === this.editingLogName.id
			).name = this.log_name;

			this.editingLogName = null;
			this.creating = false;
			this.log_name = '';
		},
		deleteLog(e, log) {
			ElMessageBox.confirm(
                `Do you want to delete the log ${log.name}?`,
                'Warning', {
                    confirmButtonText: 'OK',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                }
            ).then(async () => {
                await Ajax.post('delete_log', { id: log.id });

                this.logs.splice(this.logs.indexOf(log), 1);

                if (log.id === this.activeLog?.id) {
	                this.activeLog = null;
                }
            }).catch(() => null);
		},
	},
};
</script>

<style scoped>
.alert-name {
	cursor: pointer;
	margin-bottom: 5px;
	border: 1px solid var(--el-border-color);
}

.editor-container {
  	height: 100%;
  	display: flex;
  	padding: 0 20px;
  	flex-direction: column;
}

::v-deep(.el-alert .el-alert__content) {
	width: 100%;
}

::v-deep(.el-alert .el-alert__description) {
	width: 100%;
}

::v-deep(.el-alert__description i) {
	float: right;
	margin-top: 5px;
}

::v-deep(.el-alert__description i.edit-log-name) {
	color: var(--el-color-primary);
}

::v-deep(.el-alert__description i.delete-log) {
	color: var(--el-color-danger);
	margin-right: 10px;
}

::v-deep(.el-alert__description i.edit-log-name:hover) {
	cursor: pointer;
	color: var(--el-color-primary-light-3);
	filter: drop-shadow(0 0 3px var(--el-color-primary));
}

::v-deep(.el-alert__description i.delete-log:hover) {
	cursor: pointer;
	color: var(--el-color-danger-light-3);
	filter: drop-shadow(0 0 3px var(--el-color-danger));
}

::v-deep(.el-alert:hover), .is-active {
  	border: 1px solid var(--el-border-color-dark);
}

::v-deep(.log-name input[type=text]:focus) {
  	border-color: var(--el-border-color);
}

::v-deep(.mce-widget.mce-btn.mce-active) {
	color: var(--el-bg-color) !important;
	background: var(--el-color-primary) !important;
}

::v-deep(mce-widget.mce-btn.mce-disabled) {
	pointer-events: none;
	color: var(--el-border-color) !important;
}

::v-deep(div.mce-panel) {
    color: var(--el-text-color-regular);
    background: var(--el-bg-color) !important;
    border-color: var(--el-border-color);
}

:deep(.el-scrollbar__bar.is-vertical),
:deep(.el-scrollbar__bar.is-horizontal) {
    display: none !important;
}
</style>