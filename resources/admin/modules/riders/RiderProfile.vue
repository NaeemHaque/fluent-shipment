<template>
    <div class="rider-profile-page">
        <!-- Header Section -->
        <div class="profile-header">
            <div class="profile-header-content">
                <!-- Back Navigation -->
                <div class="back-navigation">
                    <el-button 
                        @click="goBack" 
                        type="text" 
                        class="back-btn"
                    >
                        <el-icon><ArrowLeft /></el-icon>
                        Back to Riders
                    </el-button>
                </div>

                <!-- Rider Header Info -->
                <div class="rider-header-info">
                    <div class="rider-avatar-large">
                        <img v-if="rider.avatar_url" :src="rider.avatar_url" :alt="rider.rider_name" />
                        <div v-else class="avatar-placeholder-large">
                            {{ getInitials(rider.rider_name) }}
                        </div>
                    </div>
                    <div class="rider-basic-info">
                        <h2 class="rider-name">{{ rider.rider_name }}</h2>
                        <p class="rider-email">{{ rider.email }}</p>
                        <div class="rider-status-info">
                            <el-tag :type="getStatusType(rider.status)" size="large">
                                {{ getStatusLabel(rider.status) }}
                            </el-tag>
                            <span class="rider-joining">Joined {{ formatDate(rider.joining_date) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="profile-actions">
                    <el-button type="primary" @click="editRider">
                        <el-icon><Edit /></el-icon>
                        Edit Rider
                    </el-button>
                    <el-dropdown trigger="click">
                        <el-button type="default">
                            More Actions
                            <el-icon class="el-icon--right"><ArrowDown /></el-icon>
                        </el-button>
                        <template #dropdown>
                            <el-dropdown-menu>
                                <el-dropdown-item @click="updateStatus">
                                    <el-icon><Tickets /></el-icon>
                                    Update Status
                                </el-dropdown-item>
                                <el-dropdown-item @click="updateRating">
                                    <el-icon><Star /></el-icon>
                                    Update Rating
                                </el-dropdown-item>
                                <el-dropdown-item @click="deleteRider" class="danger-action">
                                    <el-icon><Delete /></el-icon>
                                    Delete Rider
                                </el-dropdown-item>
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon rating">
                    <el-icon><Star /></el-icon>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ rider.formatted_rating }}</div>
                    <div class="stat-label">Average Rating</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon deliveries">
                    <el-icon><Box /></el-icon>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ rider.total_deliveries }}</div>
                    <div class="stat-label">Total Deliveries</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <el-icon><Check /></el-icon>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ rider.success_rate }}%</div>
                    <div class="stat-label">Success Rate</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon vehicle">
                    <el-icon><Van /></el-icon>
                </div>
                <div class="stat-content">
                    <div class="stat-value">{{ getVehicleTypeLabel(rider.vehicle_type) }}</div>
                    <div class="stat-label">Vehicle Type</div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="profile-content">
            <div class="content-grid">
                <!-- Left Column -->
                <div class="content-left">
                    <!-- Personal Information Card -->
                    <div class="info-card">
                        <div class="card-header">
                            <h3>Personal Information</h3>
                        </div>
                        <div class="card-content">
                            <div class="info-row">
                                <label>Full Name</label>
                                <span>{{ rider.rider_name }}</span>
                            </div>
                            <div class="info-row">
                                <label>Email Address</label>
                                <span>{{ rider.email }}</span>
                            </div>
                            <div class="info-row">
                                <label>Phone Number</label>
                                <span>{{ rider.phone || 'Not provided' }}</span>
                            </div>
                            <div class="info-row">
                                <label>Status</label>
                                <el-tag :type="getStatusType(rider.status)">
                                    {{ getStatusLabel(rider.status) }}
                                </el-tag>
                            </div>
                            <div class="info-row">
                                <label>Joining Date</label>
                                <span>{{ formatDateTime(rider.joining_date) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Vehicle Information Card -->
                    <div class="info-card">
                        <div class="card-header">
                            <h3>Vehicle & License</h3>
                        </div>
                        <div class="card-content">
                            <div class="info-row">
                                <label>Vehicle Type</label>
                                <span>{{ getVehicleTypeLabel(rider.vehicle_type) }}</span>
                            </div>
                            <div class="info-row">
                                <label>Vehicle Number</label>
                                <span>{{ rider.vehicle_number || 'Not provided' }}</span>
                            </div>
                            <div class="info-row">
                                <label>License Number</label>
                                <span>{{ rider.license_number || 'Not provided' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Card -->
                    <div class="info-card">
                        <div class="card-header">
                            <h3>Performance Metrics</h3>
                        </div>
                        <div class="card-content">
                            <div class="info-row">
                                <label>Current Rating</label>
                                <div class="rating-display">
                                    <el-rate 
                                        v-model="rider.rating" 
                                        disabled 
                                        show-score
                                        score-template="{value}"
                                    />
                                </div>
                            </div>
                            <div class="info-row">
                                <label>Total Deliveries</label>
                                <span class="metric-value">{{ rider.total_deliveries }}</span>
                            </div>
                            <div class="info-row">
                                <label>Successful Deliveries</label>
                                <span class="metric-value success">{{ rider.successful_deliveries }}</span>
                            </div>
                            <div class="info-row">
                                <label>Success Rate</label>
                                <span class="metric-value success">{{ rider.success_rate }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="content-right">
                    <!-- Address Information Card -->
                    <div class="info-card">
                        <div class="card-header">
                            <h3>Address Information</h3>
                        </div>
                        <div class="card-content">
                            <div v-if="rider.address && hasAddressInfo(rider.address)" class="address-info">
                                <div class="address-line" v-if="rider.address.street">
                                    <strong>Street:</strong> {{ rider.address.street }}
                                </div>
                                <div class="address-line" v-if="rider.address.city">
                                    <strong>City:</strong> {{ rider.address.city }}
                                </div>
                                <div class="address-line" v-if="rider.address.state">
                                    <strong>State:</strong> {{ rider.address.state }}
                                </div>
                                <div class="address-line" v-if="rider.address.postcode">
                                    <strong>Postal Code:</strong> {{ rider.address.postcode }}
                                </div>
                                <div class="address-line" v-if="rider.address.country">
                                    <strong>Country:</strong> {{ rider.address.country }}
                                </div>
                            </div>
                            <div v-else class="no-data">
                                <el-icon><Location /></el-icon>
                                <span>No address information provided</span>
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Contact Card -->
                    <div class="info-card">
                        <div class="card-header">
                            <h3>Emergency Contact</h3>
                        </div>
                        <div class="card-content">
                            <div v-if="rider.emergency_contact && hasEmergencyContact(rider.emergency_contact)" class="emergency-info">
                                <div class="info-row" v-if="rider.emergency_contact.name">
                                    <label>Contact Name</label>
                                    <span>{{ rider.emergency_contact.name }}</span>
                                </div>
                                <div class="info-row" v-if="rider.emergency_contact.phone">
                                    <label>Phone Number</label>
                                    <span>{{ rider.emergency_contact.phone }}</span>
                                </div>
                                <div class="info-row" v-if="rider.emergency_contact.relation">
                                    <label>Relationship</label>
                                    <span>{{ rider.emergency_contact.relation }}</span>
                                </div>
                            </div>
                            <div v-else class="no-data">
                                <el-icon><Phone /></el-icon>
                                <span>No emergency contact provided</span>
                            </div>
                        </div>
                    </div>

                    <!-- Notes Card -->
                    <div class="info-card" v-if="rider.notes">
                        <div class="card-header">
                            <h3>Notes</h3>
                        </div>
                        <div class="card-content">
                            <p class="notes-content">{{ rider.notes }}</p>
                        </div>
                    </div>

                    <!-- Recent Activity Card -->
                    <div class="info-card">
                        <div class="card-header">
                            <h3>Recent Activity</h3>
                        </div>
                        <div class="card-content">
                            <div class="activity-timeline">
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <el-icon><User /></el-icon>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">Rider profile created</div>
                                        <div class="activity-time">{{ formatDateTime(rider.created_at) }}</div>
                                    </div>
                                </div>
                                <div class="activity-item" v-if="rider.updated_at !== rider.created_at">
                                    <div class="activity-icon">
                                        <el-icon><Edit /></el-icon>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-title">Profile last updated</div>
                                        <div class="activity-time">{{ formatDateTime(rider.updated_at) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Dialog -->
        <el-dialog
            v-model="showEditDialog"
            title="Edit Rider"
            width="800px"
            :close-on-click-modal="false"
        >
            <!-- Reuse the form from the main riders page -->
            <div class="rider-form">
                <el-form :model="editForm" :rules="editFormRules" ref="editFormRef" label-width="140px" label-position="left">
                    
                    <el-row :gutter="20">
                        <el-col :span="12">
                            <h4 style="margin-bottom: 15px;">Basic Information</h4>
                            
                            <el-form-item label="Rider Name" prop="rider_name">
                                <el-input v-model="editForm.rider_name" placeholder="Full name"></el-input>
                            </el-form-item>
                            
                            <el-form-item label="Email" prop="email">
                                <el-input v-model="editForm.email" placeholder="Email address"></el-input>
                            </el-form-item>
                            
                            <el-form-item label="Phone" prop="phone">
                                <el-input v-model="editForm.phone" placeholder="Phone number"></el-input>
                            </el-form-item>
                            
                            <el-form-item label="Status" prop="status">
                                <el-select v-model="editForm.status" placeholder="Select status">
                                    <el-option label="Active" value="active"></el-option>
                                    <el-option label="Inactive" value="inactive"></el-option>
                                    <el-option label="Suspended" value="suspended"></el-option>
                                </el-select>
                            </el-form-item>
                        </el-col>
                        
                        <el-col :span="12">
                            <h4 style="margin-bottom: 15px;">Vehicle Information</h4>
                            
                            <el-form-item label="License Number" prop="license_number">
                                <el-input v-model="editForm.license_number" placeholder="Driving license number"></el-input>
                            </el-form-item>
                            
                            <el-form-item label="Vehicle Type" prop="vehicle_type">
                                <el-select v-model="editForm.vehicle_type" placeholder="Select vehicle type">
                                    <el-option label="Bike" value="bike"></el-option>
                                    <el-option label="Motorcycle" value="motorcycle"></el-option>
                                    <el-option label="Car" value="car"></el-option>
                                    <el-option label="Van" value="van"></el-option>
                                    <el-option label="Truck" value="truck"></el-option>
                                </el-select>
                            </el-form-item>
                            
                            <el-form-item label="Vehicle Number" prop="vehicle_number">
                                <el-input v-model="editForm.vehicle_number" placeholder="Vehicle registration number"></el-input>
                            </el-form-item>
                        </el-col>
                    </el-row>
                    
                    <el-form-item label="Notes">
                        <el-input 
                            v-model="editForm.notes" 
                            type="textarea" 
                            :rows="3"
                            placeholder="Additional notes about this rider"
                        ></el-input>
                    </el-form-item>
                </el-form>
            </div>

            <template #footer>
                <span class="dialog-footer">
                    <el-button @click="showEditDialog = false">Cancel</el-button>
                    <el-button 
                        type="primary" 
                        @click="saveRiderUpdates"
                        :loading="updating"
                    >
                        Update Rider
                    </el-button>
                </span>
            </template>
        </el-dialog>

        <!-- Status Update Dialog -->
        <el-dialog
            title="Update Status"
            v-model="showStatusDialog"
            width="500px"
        >
            <el-form :model="statusForm" label-width="120px" label-position="top">
               <div class="current-status">
                   <span>Current Status:</span>
                   <el-tag :type="getStatusType(rider.status)">
                       {{ getStatusLabel(rider.status) }}
                   </el-tag>
               </div>
                
                <el-form-item label="New Status">
                    <el-select v-model="statusForm.status" placeholder="Select status">
                        <el-option label="Active" value="active"></el-option>
                        <el-option label="Inactive" value="inactive"></el-option>
                        <el-option label="Suspended" value="suspended"></el-option>
                    </el-select>
                </el-form-item>
            </el-form>
            
            <template #footer>
                <el-button @click="showStatusDialog = false">Cancel</el-button>
                <el-button type="primary" @click="saveStatusUpdate" :loading="updating">
                    Update Status
                </el-button>
            </template>
        </el-dialog>

        <!-- Rating Update Dialog -->
        <el-dialog
            title="Update Rating"
            v-model="showRatingDialog"
            width="500px"
        >
            <el-form :model="ratingForm" label-width="120px" label-position="top">
               <div class="current-rating">
                   <span>Current Rating:</span>
                   <el-rate v-model="rider.rating" disabled show-score score-template="{value}"/>
               </div>
                
                <el-form-item label="New Rating">
                    <el-rate v-model="ratingForm.rating" show-score score-template="{value}"/>
                </el-form-item>
            </el-form>
            
            <template #footer>
                <el-button @click="showRatingDialog = false">Cancel</el-button>
                <el-button type="primary" @click="saveRatingUpdate" :loading="updating">
                    Update Rating
                </el-button>
            </template>
        </el-dialog>
    </div>
</template>

<script>
import { 
    ArrowLeft, ArrowDown, Edit, Delete, Star, Check, Box, Van, Tickets, 
    User, Phone, Location
} from "@element-plus/icons-vue";

export default {
    name: 'RiderProfile',
    components: {
        ArrowLeft, ArrowDown, Edit, Delete, Star, Check, Box, Van, Tickets,
        User, Phone, Location
    },
    props: {
        riderId: {
            type: [String, Number],
            default() {
                // Get from route params if not provided as prop
                return this.$route?.params?.riderId;
            }
        }
    },
    data() {
        return {
            rider: {},
            loading: false,
            updating: false,
            showEditDialog: false,
            showStatusDialog: false,
            showRatingDialog: false,
            
            editForm: {
                rider_name: '',
                email: '',
                phone: '',
                license_number: '',
                vehicle_type: '',
                vehicle_number: '',
                status: '',
                notes: ''
            },
            
            editFormRules: {
                rider_name: [
                    { required: true, message: 'Please enter rider name', trigger: 'blur' }
                ],
                email: [
                    { required: true, message: 'Please enter email address', trigger: 'blur' },
                    { type: 'email', message: 'Please enter valid email address', trigger: 'blur' }
                ]
            },
            
            statusForm: {
                status: ''
            },
            
            ratingForm: {
                rating: 0
            }
        }
    },
    
    computed: {
        currentRiderId() {
            return this.riderId || this.$route?.params?.riderId;
        }
    },
    
    mounted() {
        this.fetchRiderDetails();
    },
    
    methods: {
        fetchRiderDetails() {
            if (!this.currentRiderId) {
                this.$notifyError('Rider ID not found');
                this.goBack();
                return;
            }
            
            this.loading = true;
            
            this.$get(`riders/${this.currentRiderId}`)
                .then(res => {
                    if (res.success) {
                        this.rider = res.rider;
                    } else {
                        this.$notifyError('Failed to load rider details');
                        this.goBack();
                    }
                })
                .catch(err => {
                    this.$notifyError('Failed to load rider details: ' + err.message);
                    this.goBack();
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        
        goBack() {
            this.$router.push({ name: 'riders' });
        },
        
        editRider() {
            this.editForm = {
                rider_name: this.rider.rider_name,
                email: this.rider.email,
                phone: this.rider.phone || '',
                license_number: this.rider.license_number || '',
                vehicle_type: this.rider.vehicle_type || '',
                vehicle_number: this.rider.vehicle_number || '',
                status: this.rider.status,
                notes: this.rider.notes || ''
            };
            this.showEditDialog = true;
        },
        
        saveRiderUpdates() {
            this.$refs.editFormRef.validate((valid) => {
                if (!valid) return;

                this.updating = true;
                
                this.$put(`riders/${this.currentRiderId}`, this.editForm)
                    .then(res => {
                        if (res.success) {
                            this.$notify({
                                title: 'Success',
                                message: 'Rider updated successfully',
                                type: 'success'
                            });
                            this.showEditDialog = false;
                            this.fetchRiderDetails(); // Refresh data
                        }
                    })
                    .catch(err => {
                        this.$notifyError('Failed to update rider: ' + err.message);
                    })
                    .finally(() => {
                        this.updating = false;
                    });
            });
        },
        
        updateStatus() {
            this.statusForm.status = this.rider.status;
            this.showStatusDialog = true;
        },
        
        saveStatusUpdate() {
            if (!this.statusForm.status) {
                this.$notifyError('Please select a status');
                return;
            }

            this.updating = true;
            
            this.$put(`riders/${this.currentRiderId}/status`, this.statusForm)
                .then(res => {
                    if (res.success) {
                        this.$notify({
                            title: 'Success',
                            message: 'Status updated successfully',
                            type: 'success'
                        });
                        this.showStatusDialog = false;
                        this.fetchRiderDetails();
                    }
                })
                .catch(err => {
                    this.$notifyError('Failed to update status: ' + err.message);
                })
                .finally(() => {
                    this.updating = false;
                });
        },
        
        updateRating() {
            this.ratingForm.rating = this.rider.rating;
            this.showRatingDialog = true;
        },
        
        saveRatingUpdate() {
            this.updating = true;
            
            this.$put(`riders/${this.currentRiderId}/rating`, this.ratingForm)
                .then(res => {
                    if (res.success) {
                        this.$notify({
                            title: 'Success',
                            message: 'Rating updated successfully',
                            type: 'success'
                        });
                        this.showRatingDialog = false;
                        this.fetchRiderDetails();
                    }
                })
                .catch(err => {
                    this.$notifyError('Failed to update rating: ' + err.message);
                })
                .finally(() => {
                    this.updating = false;
                });
        },
        
        deleteRider() {
            this.$confirm(
                `Are you sure you want to delete rider ${this.rider.rider_name}?`,
                'Confirm Delete',
                {
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                }
            ).then(() => {
                this.$del(`riders/${this.currentRiderId}`)
                    .then(res => {
                        if (res.success) {
                            this.$notify({
                                title: 'Success',
                                message: 'Rider deleted successfully',
                                type: 'success'
                            });
                            this.goBack();
                        }
                    })
                    .catch(err => {
                        this.$notifyError('Failed to delete rider: ' + err.message);
                    });
            });
        },
        
        // Utility methods
        getInitials(name) {
            if (!name) return 'R';
            return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
        },

        getStatusType(status) {
            const statusTypes = {
                'active': 'success',
                'inactive': 'info',
                'suspended': 'danger'
            };
            return statusTypes[status] || 'info';
        },

        getStatusLabel(status) {
            const labels = {
                'active': 'Active',
                'inactive': 'Inactive',
                'suspended': 'Suspended'
            };
            return labels[status] || status;
        },

        getVehicleTypeLabel(vehicleType) {
            const labels = {
                'bike': 'Bike',
                'motorcycle': 'Motorcycle',
                'car': 'Car',
                'van': 'Van',
                'truck': 'Truck'
            };
            return labels[vehicleType] || vehicleType || 'N/A';
        },

        formatDate(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleDateString();
        },

        formatDateTime(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleString();
        },
        
        hasAddressInfo(address) {
            if (!address) return false;
            return Object.values(address).some(value => value && value.trim() !== '');
        },
        
        hasEmergencyContact(contact) {
            if (!contact) return false;
            return Object.values(contact).some(value => value && value.trim() !== '');
        }
    }
};
</script>

<style scoped lang="scss">
.rider-profile-page {
    min-height: calc(100vh - 60px);
}

.profile-header {
    background: var(--fluentshipment-primary-bg);
    padding: 24px;
    border-radius: 8px;
    max-width: 1200px;
    margin: 24px auto;
    
    .profile-header-content {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .back-navigation {
        margin-bottom: 20px;
        
        .back-btn {
            color: #606266;
            font-size: 14px;
            
            &:hover {
                color: #409EFF;
            }
        }
    }
    
    .rider-header-info {
        display: flex;
        align-items: center;
        gap: 24px;
        margin-bottom: 20px;
        
        .rider-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
            
            img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .avatar-placeholder-large {
                width: 100%;
                height: 100%;
                background: linear-gradient(45deg, #409EFF, #67C23A);
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                font-weight: 600;
            }
        }
        
        .rider-basic-info {
            flex: 1;
            
            .rider-name {
                font-size: 28px;
                font-weight: 600;
                color: #303133;
                margin: 0 0 4px 0;
            }
            
            .rider-email {
                font-size: 16px;
                color: #909399;
                margin: 0 0 12px 0;
            }
            
            .rider-status-info {
                display: flex;
                align-items: center;
                gap: 16px;
                
                .rider-joining {
                    color: #909399;
                    font-size: 14px;
                }
            }
        }
    }
    
    .profile-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }
}

.stats-grid {
    max-width: 1200px;
    margin: 24px auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    padding: 0 24px;
    
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        border: 1px solid #f0f0f0;
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            
            &.rating {
                background: #fff7e6;
                color: #fa8c16;
            }
            
            &.deliveries {
                background: #f6ffed;
                color: #52c41a;
            }
            
            &.success {
                background: #f6ffed;
                color: #52c41a;
            }
            
            &.vehicle {
                background: #f0f5ff;
                color: #1890ff;
            }
        }
        
        .stat-content {
            .stat-value {
                font-size: 24px;
                font-weight: 600;
                color: #303133;
                margin-bottom: 4px;
            }
            
            .stat-label {
                font-size: 14px;
                color: #909399;
            }
        }
    }
}

.profile-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px 24px;
    
    .content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        
        @media (max-width: 768px) {
            grid-template-columns: 1fr;
        }
    }
}

.info-card {
    background: white;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 1px solid #f0f0f0;
    overflow: hidden;
    
    .card-header {
        padding: 20px 24px;
        border-bottom: 1px solid #f0f0f0;
        background: #fafbfc;
        
        h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #303133;
        }
    }
    
    .card-content {
        padding: 24px;
        
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f5f7fa;
            
            &:last-child {
                border-bottom: none;
            }
            
            label {
                font-weight: 500;
                color: #606266;
                margin-bottom: 0;
            }
            
            span {
                color: #303133;
                text-align: right;
                
                &.metric-value {
                    font-weight: 600;
                    
                    &.success {
                        color: #67C23A;
                    }
                }
            }
        }
        
        .rating-display {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }
        
        .address-info, .emergency-info {
            .address-line {
                margin-bottom: 8px;
                
                &:last-child {
                    margin-bottom: 0;
                }
                
                strong {
                    color: #606266;
                    margin-right: 8px;
                }
            }
        }
        
        .no-data {
            text-align: center;
            color: #909399;
            padding: 20px;
            
            i {
                font-size: 32px;
                margin-bottom: 8px;
                display: block;
            }
        }
        
        .notes-content {
            color: #606266;
            line-height: 1.6;
            margin: 0;
        }
        
        .activity-timeline {
            .activity-item {
                display: flex;
                gap: 12px;
                padding: 12px 0;
                border-bottom: 1px solid #f5f7fa;
                
                &:last-child {
                    border-bottom: none;
                }
                
                .activity-icon {
                    width: 32px;
                    height: 32px;
                    border-radius: 50%;
                    background: #f0f5ff;
                    color: #1890ff;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                }
                
                .activity-content {
                    .activity-title {
                        font-weight: 500;
                        color: #303133;
                        margin-bottom: 4px;
                    }
                    
                    .activity-time {
                        font-size: 12px;
                        color: #909399;
                    }
                }
            }
        }
    }
}

.current-status, .current-rating {
    margin-bottom: 20px;
    padding: 15px;
    background: #f5f7fa;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.rider-form {
    h4 {
        color: #303133;
        margin-bottom: 15px;
        border-bottom: 1px solid #e4e7ed;
        padding-bottom: 5px;
    }
}

:deep(.el-dropdown-menu__item.danger-action) {
    color: #f56c6c;
    
    &:hover {
        background: #fef0f0;
        color: #f56c6c;
    }
}
</style>
