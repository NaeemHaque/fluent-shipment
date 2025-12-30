<template>
    <div class="fluent-shipment-settings-wrapper relative min-h-[calc(100vh-150px)]" >
       <div class="fluent-shipment-settings-menu">
          <div class="fluent-shipment_tab_item">
              <el-icon><Setting /></el-icon>
              General Settings
          </div>
       </div>
       <div class="fluent-shipment-settings-main">
          <div class="settings-nav">
             <h3>General Settings</h3>
          </div>

           <div class="settings-content">
               <div class="settings-item">
                   <div class="settings-item-content">
                       <div class="shortcode-title">Shortcode</div>
                       <p class="shortcode-description">Use the following shortcode to display the tracking form on your website.</p>
                   </div>

                   <div class="shortcode" @click="copyShortcode()">
                       <el-icon><CopyDocument /></el-icon>
                       [fluent-shipment]
                   </div>

               </div>
           </div>

       </div>
    </div>
</template>

<script>
import { CopyDocument, Setting } from "@element-plus/icons-vue";
import { ElMessage } from 'element-plus';

export default {
    name: 'Settings',
    data() {
        return {
            shortcode: '[fluent-shipment]'
        }
    },
    components: {
        CopyDocument,
        Setting
    },
    methods: {
        copyShortcode(text = this.shortcode) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text)
                    .then(() => {
                        this.$notifySuccess('Shortcode copied to clipboard!');
                    })
                    .catch(err => {
                        this.fallbackCopy(text);
                    });
            } else {
                this.fallbackCopy(text);
            }
        },

        fallbackCopy(text) {
            // Create a temporary textarea
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            textarea.style.left = '-9999px';

            document.body.appendChild(textarea);
            textarea.select();

            try {
                const successful = document.execCommand('copy');
                if (successful) {
                   this.$notifySuccess('Shortcode copied to clipboard!');
                } else {
                   this.$notifyError('Failed to copy shortcode');
                }
            } catch (err) {
                this.$notifyError('Failed to copy shortcode');
            } finally {
                document.body.removeChild(textarea);
            }
        }
    }
};
</script>

<style scoped lang="scss">
.shortcode-title {
    font-size: 16px;
    font-weight: 500;
    color: var(--fluentshipment-primary-text);
}
.shortcode-description {
    font-size: 14px;
    font-weight: 400;
    color: var(--fluentshipment-secondary-text);
    margin-top: 8px;
}
.shortcode {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f0f2f5;
    padding: 10px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    color: #19283a;
    cursor: pointer;
}
</style>
