<template>
    <div class="unauthorized-page">
        <el-result
            icon="warning"
            title="Unauthorized"
            sub-title="You do not have permission to access this page."
        >
            <template #extra>
                <el-button type="primary" @click="goHome">Back to Home</el-button>
            </template>
        </el-result>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router';

const router = useRouter();

function goHome() {
    router.push('/');
}
</script>

<style scoped>
.unauthorized-page {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 80vh;
}
</style>
