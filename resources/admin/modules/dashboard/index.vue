<template>
   <div class="fluent-shipment-dashboard-wrapper">
       <div class="dashboard-header">
           <div class="header-content">
               <div class="title">
                   <h1 class="dashboard-title"> Dashboard </h1>
                   <p class="dashboard-subtitle"> View your all statistics</p>
               </div>

               <div class="header-actions">
                   <el-button @click="fetchDashboardData" style="border-radius: 8px;">
                       <template #icon>
                           <RefreshRight />
                       </template>
                       Refresh
                   </el-button>
               </div>
           </div>
       </div>

       <div class="stats-grid">
           <div v-for="stat in stats" :key="stat.id" class="stat-card">
               <div class="stat-content" v-if="!loading">
                   <div class="state-icon" :style="{ backgroundColor: stat.color }" :class="`stat-${stat.type}`" v-html="stat.icon">
                   </div>
                   <div class="stat-details">
                       <div class="stat-label">{{ stat.label }}</div>
                       <div class="stat-value">{{ stat.value }}</div>
                   </div>
               </div>
               <el-skeleton v-else animated>
                   <template #template>
                       <el-skeleton-item variant="rect" style="height: 80px" />
                   </template>
               </el-skeleton>
           </div>
       </div>
   </div>
</template>

<script type="text/javascript">
    import {RefreshRight} from "@element-plus/icons-vue";

    export default {
        name: 'Dashboard',
        components: {RefreshRight},
        data() {
            return {
                loading: false,
                stats: []
            }
        },
        methods: {
            fetchDashboardData() {
                this.loading = true;
                this.$get('dashboard')
                    .then(res => {
                        if (res) {
                            this.stats = res.stats;
                        }
                    })
                    .catch(err => {
                        this.$notifyError(err.message);
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            }
        },
        mounted() {
            this.fetchDashboardData();
        }
    };
</script>
