<template>
    <div class="fluent-shipment-settings-wrapper relative min-h-[calc(100vh-150px)]">
        <div class="fluent-shipment-settings-menu">
            <div 
                class="fluent-shipment_tab_item" 
                :class="{ active: activeTab === 'general' }"
                @click="activeTab = 'general'"
            >
                <el-icon><Setting /></el-icon>
                General Settings
            </div>
            <div 
                class="fluent-shipment_tab_item" 
                :class="{ active: activeTab === 'email' }"
                @click="activeTab = 'email'"
            >
                <el-icon><Message /></el-icon>
                Email Notifications
            </div>
        </div>
        
        <div class="fluent-shipment-settings-main">
            <!-- General Settings Tab -->
            <div v-if="activeTab === 'general'" class="settings-tab">
                <div class="settings-nav">
                    <h3>General Settings</h3>
                    <div class="settings-nav-actions">
                        <el-button
                            type="primary"
                            @click="saveGeneralSettings"
                            :loading="saving"
                        >
                            Save Settings
                        </el-button>
                    </div>
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

                    <div class="settings-item">
                        <div class="settings-item-content">
                            <div class="shortcode-title">Tracking Page URL</div>
                            <p class="shortcode-description">The URL where customers can track their shipments.</p>
                        </div>
                        <div class="settings-item-content">
                            <el-form :model="generalSettings" v-loading="saving">
                                <el-form-item>
                                    <el-input
                                        v-model="generalSettings.tracking_page_url"
                                        placeholder="http://yoursite.com/tracking/"
                                        type="url"
                                        clearable
                                    />
                                </el-form-item>
                            </el-form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Notifications Tab -->
            <div v-if="activeTab === 'email'" class="settings-tab">
                <div class="settings-nav">
                    <h3>Email Notification Settings</h3>
                    <div class="settings-nav-actions">
                        <el-button
                            type="primary"
                            @click="saveEmailSettings"
                            :loading="saving"
                        >
                            Save Settings
                        </el-button>
                    </div>
                </div>
                <div class="settings-content" v-loading="loading">
                    <!-- Email Notifications -->
                    <div class="settings-section">
                        <div class="settings-item">
                            <div class="settings-item-content">
                                <div class="setting-title">Processing Notification</div>
                                <p class="setting-description">Send email when shipment status changes to "Processing"</p>
                            </div>
                            <el-switch 
                                v-model="emailSettings.email_notifications.processing.enabled"
                                @change="updateEmailSettings"
                            />
                        </div>
                        <div class="settings-item">
                            <div class="settings-item-content">
                                <div class="setting-title">Delivery Confirmation</div>
                                <p class="setting-description">Send email when shipment is delivered</p>
                            </div>
                            <el-switch 
                                v-model="emailSettings.email_notifications.delivered.enabled"
                                @change="updateEmailSettings"
                            />
                        </div>

                        <div class="settings-item">
                            <div class="settings-item-content">
                                <div class="setting-title">From Email</div>
                                <p class="setting-description">Email address to send from</p>
                            </div>
                            <div class="settings-item-content">
                                <el-form :model="emailSettings">
                                    <el-form-item>
                                        <el-input
                                            v-model="emailSettings.email_from"
                                            placeholder="admin@yoursite.com"
                                            type="email"
                                        />
                                    </el-form-item>
                                </el-form>
                            </div>
                        </div>

                        <div class="settings-item">
                            <div class="settings-item-content">
                                <div class="setting-title">From Name</div>
                                <p class="setting-description">Name to send from</p>
                            </div>

                            <div class="settings-item-content">
                                <el-form :model="emailSettings">
                                    <el-form-item>
                                        <el-input
                                            v-model="emailSettings.email_from_name"
                                            placeholder="Your Company Name"
                                        />
                                    </el-form-item>
                                </el-form>
                            </div>
                        </div>
                    </div>


                    <!-- Test Email -->
                    <div class="settings-section">
                        <h4>Test Email</h4>
                        <div class="settings-item">
                            <div class="settings-item-content">
                                <p class="setting-description">Send a test email to verify your configuration</p>
                                <el-form inline>
                                    <el-form-item>
                                        <el-input
                                            v-model="testEmail"
                                            placeholder="test@example.com"
                                            style="width: 200px;"
                                        />
                                    </el-form-item>
                                    <el-form-item>
                                        <el-select v-model="testEmailType" style="width: 150px;">
                                            <el-option label="Processing" value="processing" />
                                            <el-option label="Delivered" value="delivered" />
                                        </el-select>
                                    </el-form-item>
                                    <el-form-item>
                                        <el-button
                                            type="primary"
                                            @click="sendTestEmail"
                                            :loading="sendingTest"
                                        >
                                            Send Test
                                        </el-button>
                                    </el-form-item>
                                </el-form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { CopyDocument, Setting, Message } from "@element-plus/icons-vue";

export default {
    name: 'Settings',
    data() {
        return {
            activeTab: 'general',
            shortcode: '[fluent-shipment]',
            loading: false,
            saving: false,
            sendingTest: false,
            testEmail: '',
            testEmailType: 'processing',
            emailSettings: {
                email_notifications: {
                    processing: { enabled: true },
                    delivered: { enabled: true }
                },
                email_from: '',
                email_from_name: ''
            },
            generalSettings: {
                tracking_page_url: ''
            }
        }
    },
    components: {
        CopyDocument,
        Setting,
        Message
    },
    mounted() {
        if (this.activeTab === 'email') {
            this.loadEmailSettings();
        } else if (this.activeTab === 'general') {
            this.loadGeneralSettings();
        }
    },
    watch: {
        activeTab(newTab) {
            if (newTab === 'email') {
                this.loadEmailSettings();
            } else if (newTab === 'general') {
                this.loadGeneralSettings();
            }
        }
    },
    methods: {
        async loadEmailSettings() {
            this.loading = true;
            try {
                const response = await this.$get('/settings/email');
                if (response) {
                    this.emailSettings = response.settings;
                }
            } catch (error) {
                this.$notifyError('Failed to load email settings');
            }
            this.loading = false;
        },

        async saveEmailSettings() {
            this.saving = true;
            try {
                const response = await this.$post('/settings/email', this.emailSettings);
                if (response) {
                    this.$notifySuccess('Email settings saved successfully');
                } else {
                    this.$notifyError('Failed to save settings');
                }
            } catch (error) {
                this.$notifyError('Failed to save email settings');
            }
            this.saving = false;
        },

        updateEmailSettings() {
            // Auto-save when toggling switches
            this.saveEmailSettings();
        },

        async sendTestEmail() {
            if (!this.testEmail) {
                this.$notifyError('Please enter a test email address');
                return;
            }

            this.sendingTest = true;
            try {
                const response = await this.$post('/settings/email/test', {
                    email: this.testEmail,
                    type: this.testEmailType
                });
                
                if (response) {
                    this.$notifySuccess(response.message || 'Test email sent successfully');
                } else {
                    this.$notifyError(response.message || 'Failed to send test email');
                }
            } catch (error) {
                this.$notifyError('Failed to send test email');
            }
            this.sendingTest = false;
        },

        async loadGeneralSettings() {
            this.loading = true;
            try {
                const response = await this.$get('/settings/general');
                if (response) {
                    this.generalSettings = response.settings;
                }
            } catch (error) {
                this.$notifyError('Failed to load general settings');
            }
            this.loading = false;
        },

        async saveGeneralSettings() {
            this.saving = true;
            try {
                const response = await this.$post('/settings/general', this.generalSettings);
                if (response) {
                    this.$notifySuccess('General settings saved successfully');
                } else {
                    this.$notifyError('Failed to save settings');
                }
            } catch (error) {
                this.$notifyError('Failed to save general settings');
            }
            this.saving = false;
        },

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
.el-form {
    .el-form-item {
        margin-bottom: 16px;
    }
}
</style>
