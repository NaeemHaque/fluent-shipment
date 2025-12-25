<template>
    <transition name="fade" mode="out-in" appear>
        <div>
            <div class="page-header">
                <el-row>
                    <el-col :span="12">
                        <strong>{{ title }}</strong>
                    </el-col>
                </el-row>
            </div>
            
            <div class="page-content">
                Welcome to fluent framework...
            </div>
        </div>
    </transition>
</template>

<script type="text/javascript">
    export default {
        name: 'Dashboard',
        props: {
            user: Object,
            siteName: String,
            isAdmin: Boolean
        },
        data() {
            return {
                title: 'Dashboard',
            }
        },
    };
</script>
