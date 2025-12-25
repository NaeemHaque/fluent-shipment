<template>
    <el-dialog
        @close="onClose"
        v-model="dialogVisible"
        :modal="false"
        :style="modalStyle"
        :close-on-click-modal="false"
        :close-on-press-escape="false"
        :title="dialogTitle"
    >
        <div class="prd-tabs">
            <el-tabs v-model="activeTab" @tab-change="updateDialogStyle">
			  	<el-tab-pane label="Product Overview" name="overview">
			    	<Overview />
			  	</el-tab-pane>

			  	<el-tab-pane label="Features & Requirements" name="requirements">
			    	<Features />
			  	</el-tab-pane>

			  	<el-tab-pane label="API Endpoints" name="architecture">
			    	<Architecture />
			  	</el-tab-pane>

                <el-tab-pane label="Database" name="database">
                    <Database />
                </el-tab-pane>

                <el-tab-pane label="Change Log" name="changelog">
                    <ChangeLog />
                </el-tab-pane>
			</el-tabs>
        </div>
    </el-dialog>
</template>

<script>
import Ajax from '@/utils/http/Ajax';
import Overview from './components/Overview';
import Features from './components/Features';
import Database from './components/Database';
import Architecture from './components/Architecture';
import ChangeLog from './components/ChangeLog';

export default {
    name: 'ProductTabsDialog',
    components: {
	    Overview,
		Features,
        Database,
		Architecture,
        ChangeLog,
    },
    data() {
        return {
            types: [],
            dialogStyle: {},
            dialogVisible: false,
            activeTab: 'overview',
            sidebarObserver: null,
        };
    },
    mounted() {
        this.dialogVisible = true;

        this.updateDialogStyle();

        this.initSidebarObserver();

        this.fetchDatabaseTypes();
    },
    beforeUnmount() {
        if (this.sidebarObserver) {
        	this.sidebarObserver.disconnect();
        }
    },
    computed: {
        modalStyle() {
            return this.dialogStyle;
        },
        dialogTitle() {
            return this.appVars.name + ' Workbench';
        },
    },
    methods: {
        updateDialogStyle() {
            const adminMenu = document.getElementById('adminmenuwrap');

            let top = 32;
            let left = 160;

            if (adminMenu) {
                const rect = adminMenu.getBoundingClientRect();
                top = rect.top + 0;
                left = rect.right + 0;
            }

            const width = `calc(100vw - ${left}px)`;
            const height = `calc(100vh - ${top}px)`;

            this.dialogStyle = {
                position: 'fixed',
                top: `${top}px`,
                left: `${left}px`,
                width,
                height,
                margin: 0,
                transform: 'none',
                overflow: 'hidden',
                background: 'var(--el-bg-color)',
            };
        },
        initSidebarObserver() {
            const target = document.body;
            
            this.sidebarObserver = new MutationObserver(() => {
                this.updateDialogStyle();
            });

            this.sidebarObserver.observe(target, {
            	attributes: true,
            	attributeFilter: ['class']
            });
        },
        async fetchDatabaseTypes() {
            const res = await Ajax.get('database_types');
            if (res.success) {
                this.types = res.data.db_field_types;
            }
        },
        onClose() {
            this.$router.go(-1);
        },
    },
};
</script>
