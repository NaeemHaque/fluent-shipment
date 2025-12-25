<template>
    <el-popover
        width="170"
        @hide="cancel"
        v-model:visible="visible"
        :placement="placement"
        trigger="click"
    >
        <p v-html="message"></p>

        <div class="action-buttons">
            <el-button
                link
                size="small"
                @click="cancel($event)">
                cancel
            </el-button>

            <el-button
                type="primary"
                size="small"
                @click="confirm($event)">
                confirm
            </el-button>
        </div>

        <template #reference>
            <slot name="reference">
                <el-button v-bind="$attrs" size="small" type="danger">
                    <el-icon><delete /></el-icon>
                </el-button>
            </slot>
        </template>
    </el-popover>
</template>

<script>
import { Delete } from '@element-plus/icons-vue';
export default {
    name: 'Confirm',
    components: { Delete },
    inheritAttrs: false,
    props: {
        placement: {
            type: String,
            default: 'top-end'
        },
        message: {
            type: String,
            default: 'Are you sure?'
        }
    },
    data() {
        return {
            visible: false
        }
    },
    methods: {
        hide() {
            this.visible = false;
        },
        confirm(e) {
            this.hide();
            this.$emit('yes', e);
        },
        cancel(e) {
            this.hide();
            this.$emit('no', e);
        }
    }
};
</script>
