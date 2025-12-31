<template>
    <transition name="fade" mode="out-in" appear>
        <div class="fluent-shipment-wrapper">
            <div class="page-header">
               <h3>All Riders</h3>
               <div class="page-actions">
                   <el-button 
                       type="primary" 
                       @click="showCreateDialog = true"
                       :loading="creating"
                   >
                       <i class="el-icon-plus"></i> Add New Rider
                   </el-button>
               </div>
            </div>

            <div class="page-content">
                <!-- Filters Section -->
                <div class="fluent_shipment_card">
                    <div class="fluent_shipment_card-header">
                        <div class="fluent_shipment_filters_wrapper">
                            <!-- Primary Filter Tabs (Fluid Tab Style) -->
                            <div class="filter-tabs-container">
                                <div class="fluentshipment-fluid-tab">
                                    <div 
                                        class="fluentshipment-fluid-tab-active-bar"
                                        :style="{ transform: `translateX(${getActiveTabPosition()}px)`, width: `${getActiveTabWidth()}px` }"
                                    ></div>
                                    
                                    <div 
                                        v-for="(label, status) in primaryTabs" 
                                        :key="status"
                                        ref="tabItems"
                                        class="fluentshipment-fluid-tab-item"
                                        :class="{ 'active': filters.status === status }"
                                        @click="setActiveTab(status)"
                                    >
                                        {{ label }}
                                    </div>
                                    
                                    <!-- More views dropdown -->
                                    <div class="fluentshipment-fluid-tab-item more-views" v-if="Object.keys(moreTabs).length > 0">
                                        <el-select
                                            v-model="selectedMoreTab"
                                            placeholder="More views"
                                            @change="handleMoreTabChange"
                                            class="more-views-select"
                                            popper-class="more-views-dropdown"
                                        >
                                            <el-option
                                                v-for="(label, status) in moreTabs"
                                                :key="status"
                                                :label="label"
                                                :value="status"
                                            />
                                        </el-select>
                                    </div>
                                </div>
                                
                                <!-- Bulk Actions (beside filters when rows selected) -->
                                <div class="bulk-actions-inline" v-if="selectedRows.length > 0">
                                    <div class="bulk-actions-content">
                                        <span class="selected-count">{{ selectedRows.length }} selected</span>
                                        <el-button size="small" @click="bulkUpdateStatus" type="primary">
                                            <el-icon><Edit /></el-icon>
                                            Update Status
                                        </el-button>
                                        <el-button size="small" type="danger" @click="bulkDelete">
                                            <el-icon><Delete /></el-icon>
                                            Delete
                                        </el-button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Search Input -->
                        <div class="fluent_shipment_filter">
                            <el-input
                                v-model="filters.search"
                                placeholder="Rider name, email, phone, vehicle..."
                                clearable
                            >
                                <template #prefix>
                                    <el-icon><Search /></el-icon>
                                </template>
                            </el-input>
                        </div>
                    </div>

                    <div class="table_wrap">
                        <el-table
                            :data="riders"
                            v-loading="loading"
                            style="width:100%;"
                            class="fluent_shipment_table"
                            row-key="id"
                            @selection-change="handleSelectionChange"
                        >
                            <el-table-column type="selection" width="55" />
                            <el-table-column prop="id" label="ID" width="80" sortable />

                            <el-table-column label="Rider">
                                <template #default="scope">
                                    <div style="display: flex; align-items: center;">
                                        <div class="rider-avatar">
                                            <img v-if="scope.row.avatar_url" :src="scope.row.avatar_url" :alt="scope.row.rider_name" />
                                            <div v-else class="avatar-placeholder">
                                                {{ getInitials(scope.row.rider_name) }}
                                            </div>
                                        </div>
                                        <div style="margin-left: 10px;">
                                            <div style="font-weight: 500;">
                                                <a @click="navigateToProfile(scope.row)" class="rider-name-link">
                                                    {{ scope.row.rider_name }}
                                                </a>
                                            </div>
                                            <small style="color: #909399;">{{ scope.row.email }}</small>
                                        </div>
                                    </div>
                                </template>
                            </el-table-column>

                            <el-table-column prop="phone" label="Phone">
                                <template #default="scope">
                                    {{ scope.row.phone || 'N/A' }}
                                </template>
                            </el-table-column>

                            <el-table-column prop="status" label="Status" width="120">
                                <template #default="scope">
                                    <el-tag :type="getStatusType(scope.row.status)" size="small">
                                        {{ getStatusLabel(scope.row.status) }}
                                    </el-tag>
                                </template>
                            </el-table-column>

                            <el-table-column label="Vehicle" width="120">
                                <template #default="scope">
                                    <div>
                                        <div>{{ getVehicleTypeLabel(scope.row.vehicle_type) }}</div>
                                        <small v-if="scope.row.vehicle_number" style="color: #909399;">
                                            {{ scope.row.vehicle_number }}
                                        </small>
                                    </div>
                                </template>
                            </el-table-column>

                            <el-table-column label="Rating" width="150">
                                <template #default="scope">
                                    <div style="display: flex; align-items: center;">
                                        <el-rate 
                                            v-model="scope.row.rating" 
                                            disabled 
                                            size="small"
                                            :max="5"
                                            show-score
                                            score-template="{value}"
                                        />
                                    </div>
                                </template>
                            </el-table-column>

                            <el-table-column label="Deliveries" width="130">
                                <template #default="scope">
                                    <div>
                                        <div>{{ scope.row.total_deliveries }} total</div>
                                        <small style="color: #67C23A;">
                                            {{ scope.row.success_rate }}% success
                                        </small>
                                    </div>
                                </template>
                            </el-table-column>

                            <el-table-column prop="joining_date" label="Joined" width="130">
                                <template #default="scope">
                                    {{ formatDate(scope.row.joining_date) }}
                                </template>
                            </el-table-column>

                            <el-table-column align="right" width="70">
                                <template #default="scope">
                                    <el-dropdown trigger="click" popper-class="action-dropdown">
                                        <span class="more-btn">
                                            <el-icon>
                                                <MoreIcon/>
                                            </el-icon>
                                        </span>
                                        <template #dropdown>
                                            <el-dropdown-menu>
                                                <el-dropdown-item @click="viewRider(scope.row)">
                                                    <div class="dropdown-item">
                                                        <el-icon size="20"><View /></el-icon>
                                                        <span>Details</span>
                                                    </div>
                                                </el-dropdown-item>
                                                <el-dropdown-item @click="editRider(scope.row)">
                                                    <div class="dropdown-item">
                                                        <el-icon size="20"><Edit /></el-icon>
                                                        <span>Edit</span>
                                                    </div>
                                                </el-dropdown-item>
                                                <el-dropdown-item @click="editStatus(scope.row)">
                                                    <div class="dropdown-item">
                                                        <el-icon size="20">
                                                            <Tickets />
                                                        </el-icon>
                                                        <span>Update Status</span>
                                                    </div>
                                                </el-dropdown-item>
                                                <el-dropdown-item @click="deleteRider(scope.row)">
                                                    <div class="dropdown-item">
                                                        <el-icon size="20">
                                                            <Delete />
                                                        </el-icon>
                                                        <span>Delete</span>
                                                    </div>
                                                </el-dropdown-item>
                                            </el-dropdown-menu>
                                        </template>
                                    </el-dropdown>
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>

                    <!-- Pagination -->
                    <div class="fluent_shipment_card-footer">
                        <Pagination 
                            :pagination="pagination" 
                            @update:pagination="updatePagination" 
                            @per_page_change="handleSizeChange"
                        />
                    </div>
                </div>
            </div>

            <!-- Create/Edit Rider Dialog -->
            <el-dialog
                v-model="showCreateDialog"
                :title="editingRider ? 'Edit Rider' : 'Add New Rider'"
                width="800px"
                :close-on-click-modal="false"
            >
                <div class="rider-form">
                    <el-form :model="riderForm" :rules="riderFormRules" ref="riderFormRef" label-width="140px" label-position="left">
                        
                        <el-row :gutter="20">
                            <el-col :span="12">
                                <h4 style="margin-bottom: 15px;">Basic Information</h4>
                                
                                <el-form-item label="Rider Name" prop="rider_name">
                                    <el-input v-model="riderForm.rider_name" placeholder="Full name"></el-input>
                                </el-form-item>
                                
                                <el-form-item label="Email" prop="email">
                                    <el-input v-model="riderForm.email" placeholder="Email address"></el-input>
                                </el-form-item>
                                
                                <el-form-item label="Phone" prop="phone">
                                    <el-input v-model="riderForm.phone" placeholder="Phone number"></el-input>
                                </el-form-item>
                                
                                <el-form-item label="Status" prop="status">
                                    <el-select v-model="riderForm.status" placeholder="Select status">
                                        <el-option label="Active" value="active"></el-option>
                                        <el-option label="Inactive" value="inactive"></el-option>
                                        <el-option label="Suspended" value="suspended"></el-option>
                                    </el-select>
                                </el-form-item>
                                
                                <el-form-item label="Joining Date" prop="joining_date">
                                    <el-date-picker
                                        v-model="riderForm.joining_date"
                                        type="date"
                                        placeholder="Select date"
                                        format="YYYY-MM-DD"
                                        value-format="YYYY-MM-DD"
                                        style="width: 100%;"
                                    ></el-date-picker>
                                </el-form-item>
                            </el-col>
                            
                            <el-col :span="12">
                                <h4 style="margin-bottom: 15px;">Vehicle Information</h4>
                                
                                <el-form-item label="License Number" prop="license_number">
                                    <el-input v-model="riderForm.license_number" placeholder="Driving license number"></el-input>
                                </el-form-item>
                                
                                <el-form-item label="Vehicle Type" prop="vehicle_type">
                                    <el-select v-model="riderForm.vehicle_type" placeholder="Select vehicle type">
                                        <el-option label="Bike" value="bike"></el-option>
                                        <el-option label="Motorcycle" value="motorcycle"></el-option>
                                        <el-option label="Car" value="car"></el-option>
                                        <el-option label="Van" value="van"></el-option>
                                        <el-option label="Truck" value="truck"></el-option>
                                    </el-select>
                                </el-form-item>
                                
                                <el-form-item label="Vehicle Number" prop="vehicle_number">
                                    <el-input v-model="riderForm.vehicle_number" placeholder="Vehicle registration number"></el-input>
                                </el-form-item>
                            </el-col>
                        </el-row>
                        
                        <el-row :gutter="20">
                            <el-col :span="24">
                                <h4 style="margin-bottom: 15px;">Address</h4>
                                
                                <el-row :gutter="15">
                                    <el-col :span="24">
                                        <el-form-item label="Street Address">
                                            <el-input v-model="riderForm.address.street" placeholder="Street address"></el-input>
                                        </el-form-item>
                                    </el-col>
                                </el-row>
                                
                                <el-row :gutter="15">
                                    <el-col :span="12">
                                        <el-form-item label="City">
                                            <el-input v-model="riderForm.address.city" placeholder="City"></el-input>
                                        </el-form-item>
                                    </el-col>
                                    <el-col :span="12">
                                        <el-form-item label="State">
                                            <el-input v-model="riderForm.address.state" placeholder="State/Province"></el-input>
                                        </el-form-item>
                                    </el-col>
                                </el-row>
                                
                                <el-row :gutter="15">
                                    <el-col :span="12">
                                        <el-form-item label="Postal Code">
                                            <el-input v-model="riderForm.address.postcode" placeholder="Postal code"></el-input>
                                        </el-form-item>
                                    </el-col>
                                    <el-col :span="12">
                                        <el-form-item label="Country">
                                            <el-input v-model="riderForm.address.country" placeholder="Country"></el-input>
                                        </el-form-item>
                                    </el-col>
                                </el-row>
                            </el-col>
                        </el-row>
                        
                        <el-row :gutter="20">
                            <el-col :span="24">
                                <h4 style="margin-bottom: 15px;">Emergency Contact</h4>
                                
                                <el-row :gutter="15">
                                    <el-col :span="12">
                                        <el-form-item label="Contact Name">
                                            <el-input v-model="riderForm.emergency_contact.name" placeholder="Emergency contact name"></el-input>
                                        </el-form-item>
                                    </el-col>
                                    <el-col :span="12">
                                        <el-form-item label="Contact Phone">
                                            <el-input v-model="riderForm.emergency_contact.phone" placeholder="Emergency contact phone"></el-input>
                                        </el-form-item>
                                    </el-col>
                                </el-row>
                                
                                <el-form-item label="Relationship">
                                    <el-input v-model="riderForm.emergency_contact.relation" placeholder="Relationship (e.g., Spouse, Parent, Friend)"></el-input>
                                </el-form-item>
                            </el-col>
                        </el-row>
                        
                        <el-form-item label="Notes">
                            <el-input 
                                v-model="riderForm.notes" 
                                type="textarea" 
                                :rows=3
                                placeholder="Additional notes about this rider"
                            ></el-input>
                        </el-form-item>
                    </el-form>
                </div>

                <template #footer>
                    <span class="dialog-footer">
                        <el-button @click="closeCreateDialog">Cancel</el-button>
                        <el-button 
                            type="primary" 
                            @click="saveRider"
                            :loading="creating"
                        >
                            {{ editingRider ? 'Update Rider' : 'Create Rider' }}
                        </el-button>
                    </span>
                </template>
            </el-dialog>

            <!-- View Rider Dialog -->
            <el-dialog
                title="Rider Details"
                v-model="showViewDialog"
                width="800px"
            >
                <div v-if="selectedRider">
                    <el-tabs v-model="activeTab">
                        <el-tab-pane label="Details" name="details">
                            <div class="rider-details">
                                <el-row :gutter="20">
                                    <el-col :span="12">
                                        <h4>Personal Information</h4>
                                        <p><strong>Name:</strong> {{ selectedRider.rider_name }}</p>
                                        <p><strong>Email:</strong> {{ selectedRider.email }}</p>
                                        <p><strong>Phone:</strong> {{ selectedRider.phone || 'N/A' }}</p>
                                        <p><strong>Status:</strong> 
                                            <el-tag :type="getStatusType(selectedRider.status)">
                                                {{ getStatusLabel(selectedRider.status) }}
                                            </el-tag>
                                        </p>
                                        <p><strong>Joining Date:</strong> {{ formatDateTime(selectedRider.joining_date) }}</p>
                                    </el-col>
                                    <el-col :span="12">
                                        <h4>Vehicle & Performance</h4>
                                        <p><strong>Vehicle Type:</strong> {{ getVehicleTypeLabel(selectedRider.vehicle_type) }}</p>
                                        <p><strong>Vehicle Number:</strong> {{ selectedRider.vehicle_number || 'N/A' }}</p>
                                        <p><strong>License:</strong> {{ selectedRider.license_number || 'N/A' }}</p>
                                        <p><strong>Rating:</strong> {{ selectedRider.formatted_rating }}</p>
                                        <p><strong>Total Deliveries:</strong> {{ selectedRider.total_deliveries }}</p>
                                        <p><strong>Success Rate:</strong> {{ selectedRider.success_rate }}%</p>
                                    </el-col>
                                </el-row>
                                
                                <el-row :gutter="20" style="margin-top: 20px;">
                                    <el-col :span="12">
                                        <h4>Address</h4>
                                        <p>{{ getFormattedAddress(selectedRider.address) }}</p>
                                    </el-col>
                                    <el-col :span="12">
                                        <h4>Emergency Contact</h4>
                                        <p>{{ getFormattedEmergencyContact(selectedRider.emergency_contact) }}</p>
                                    </el-col>
                                </el-row>
                                
                                <div v-if="selectedRider.notes" style="margin-top: 20px;">
                                    <h4>Notes</h4>
                                    <p>{{ selectedRider.notes }}</p>
                                </div>
                            </div>
                        </el-tab-pane>
                    </el-tabs>
                </div>
            </el-dialog>

            <!-- Edit Status Dialog -->
            <el-dialog
                title="Update Status"
                v-model="showEditDialog"
                width="500px"
            >
                <el-form :model="editForm" label-width="120px" v-if="selectedRider" label-position="top">
                   <div class="current-status">
                       <span>Current Status:</span>
                       <el-tag :type="getStatusType(selectedRider.status)">
                           {{ getStatusLabel(selectedRider.status) }}
                       </el-tag>
                   </div>
                    
                    <el-form-item label="New Status">
                        <el-select v-model="editForm.status" placeholder="Select status">
                            <el-option label="Active" value="active"></el-option>
                            <el-option label="Inactive" value="inactive"></el-option>
                            <el-option label="Suspended" value="suspended"></el-option>
                        </el-select>
                    </el-form-item>
                </el-form>
                
                <template #footer>
                    <el-button @click="showEditDialog = false">Cancel</el-button>
                    <el-button type="primary" @click="updateStatus" :loading="updating">
                        Update Status
                    </el-button>
                </template>
            </el-dialog>

            <!-- Bulk Status Update Dialog -->
            <el-dialog
                title="Bulk Update Status"
                v-model="showBulkEditDialog"
                width="500px"
            >
                <el-form :model="bulkEditForm" label-width="120px" label-position="top">
                    <el-form-item label="Selected">
                        <span>{{ selectedRows.length }} riders</span>
                    </el-form-item>
                    
                    <el-form-item label="New Status">
                        <el-select v-model="bulkEditForm.status" placeholder="Select status">
                            <el-option label="Active" value="active"></el-option>
                            <el-option label="Inactive" value="inactive"></el-option>
                            <el-option label="Suspended" value="suspended"></el-option>
                        </el-select>
                    </el-form-item>
                </el-form>
                
                <template #footer>
                    <el-button @click="showBulkEditDialog = false">Cancel</el-button>
                    <el-button type="primary" @click="bulkUpdateStatusAction" :loading="bulkUpdating">
                        Update All
                    </el-button>
                </template>
            </el-dialog>
        </div>
    </transition>
</template>

<script type="text/javascript">
import {Delete, Edit, Search, Tickets, View} from "@element-plus/icons-vue";
import MoreIcon from "@/components/icons/MoreIcon.vue";
import Pagination from "@/pieces/Pagination.vue";

export default {
    name: 'Riders',
    components: {Pagination, Search, MoreIcon, Tickets, View, Edit, Delete},
    data() {
        return {
            loading: false,
            creating: false,
            updating: false,
            bulkUpdating: false,
            
            riders: [],
            selectedRows: [],
            searchTimeout: null,
            
            pagination: {
                current_page: 1,
                per_page: 10,
                total: 0
            },
            
            filters: {
                status: '',
                search: '',
                vehicle_type: '',
                date_from: '',
                date_to: ''
            },
            
            // Dialog states
            showCreateDialog: false,
            showViewDialog: false,
            showEditDialog: false,
            showBulkEditDialog: false,
            
            // Selected data
            selectedRider: null,
            editingRider: null,
            activeTab: 'details',
            
            // Forms
            riderForm: {
                rider_name: '',
                email: '',
                phone: '',
                license_number: '',
                vehicle_type: '',
                vehicle_number: '',
                status: 'active',
                joining_date: '',
                address: {
                    street: '',
                    city: '',
                    state: '',
                    postcode: '',
                    country: ''
                },
                emergency_contact: {
                    name: '',
                    phone: '',
                    relation: ''
                },
                notes: ''
            },
            
            riderFormRules: {
                rider_name: [
                    { required: true, message: 'Please enter rider name', trigger: 'blur' }
                ],
                email: [
                    { required: true, message: 'Please enter email address', trigger: 'blur' },
                    { type: 'email', message: 'Please enter valid email address', trigger: 'blur' }
                ],
                status: [
                    { required: true, message: 'Please select status', trigger: 'change' }
                ]
            },
            
            editForm: {
                status: ''
            },
            
            bulkEditForm: {
                status: ''
            },

            selectedMoreTab: '',
            allTabs: {
                '': 'All',
                'active': 'Active',
                'inactive': 'Inactive',
                'suspended': 'Suspended'
            }
        }
    },
    computed: {
        primaryTabs() {
            const tabEntries = Object.entries(this.allTabs);
            return Object.fromEntries(tabEntries);
        },

        moreTabs() {
            return {};
        }
    },

    mounted() {
        this.fetchRiders();
        this.$nextTick(() => {
            this.$forceUpdate();
        });
    },

    beforeUnmount() {
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
    },
    
    methods: {
        debounceSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.pagination.current_page = 1;
                this.fetchRiders();
            }, 300);
        },

        fetchRiders() {
            this.loading = true;

            const params = {
                page: this.pagination.current_page,
                per_page: this.pagination.per_page
            };

            Object.keys(this.filters).forEach(key => {
                if (this.filters[key]) {
                    params[key] = this.filters[key];
                }
            });
            
            this.$get('riders', params)
                .then(res => {
                    if (res.data && res.data.data) {
                        this.riders = res.data.data || [];
                        this.pagination.total = parseInt(res.data.total) || 0;
                        this.pagination.current_page = parseInt(res.data.current_page) || 1;
                        this.pagination.per_page = parseInt(res.data.per_page) || 10;
                    } else if (res.success && res.data) {
                        this.riders = res.data.data || [];
                        this.pagination.total = parseInt(res.data.total) || 0;
                        this.pagination.current_page = parseInt(res.data.current_page) || 1;
                        this.pagination.per_page = parseInt(res.data.per_page) || 10;
                    } else {
                        this.riders = [];
                        this.pagination.total = 0;
                    }
                })
                .catch(err => {
                    this.$notifyError(err.message);
                })
                .finally(() => {
                    this.loading = false;
                });
        },

        updatePagination(newPagination) {
            this.pagination = { ...this.pagination, ...newPagination };
            this.fetchRiders();
        },

        handleSizeChange(newSize) {
            this.pagination.per_page = parseInt(newSize) || 10;
            this.pagination.current_page = 1;
            this.fetchRiders();
        },

        handleCurrentChange(newPage) {
            this.pagination.current_page = parseInt(newPage) || 1;
            this.fetchRiders();
        },

        navigateToProfile(rider) {
            try {
                this.$router.push({ 
                    name: 'riders.profile', 
                    params: { riderId: rider.id } 
                });
            } catch (error) {
                this.$router.push(`/riders/profile/${rider.id}`);
            }
        },

        viewRider(rider) {
            this.navigateToProfile(rider);
        },

        editRider(rider) {
            this.editingRider = rider;
            this.riderForm = {
                rider_name: rider.rider_name,
                email: rider.email,
                phone: rider.phone || '',
                license_number: rider.license_number || '',
                vehicle_type: rider.vehicle_type || '',
                vehicle_number: rider.vehicle_number || '',
                status: rider.status,
                joining_date: rider.joining_date,
                address: {
                    street: rider.address?.street || '',
                    city: rider.address?.city || '',
                    state: rider.address?.state || '',
                    postcode: rider.address?.postcode || '',
                    country: rider.address?.country || ''
                },
                emergency_contact: {
                    name: rider.emergency_contact?.name || '',
                    phone: rider.emergency_contact?.phone || '',
                    relation: rider.emergency_contact?.relation || ''
                },
                notes: rider.notes || ''
            };
            this.showCreateDialog = true;
        },

        saveRider() {
            this.$refs.riderFormRef.validate((valid) => {
                if (!valid) return;

                this.creating = true;
                
                const method = this.editingRider ? '$put' : '$post';
                const endpoint = this.editingRider ? `riders/${this.editingRider.id}` : 'riders';
                
                this[method](endpoint, this.riderForm)
                    .then(res => {
                        if (res.success) {
                            this.$notify({
                                title: 'Success',
                                message: this.editingRider ? 'Rider updated successfully' : 'Rider created successfully',
                                type: 'success'
                            });
                            this.closeCreateDialog();
                            this.fetchRiders();
                        }
                    })
                    .catch(err => {
                        this.$notifyError('Failed to save rider: ' + err.message);
                    })
                    .finally(() => {
                        this.creating = false;
                    });
            });
        },

        closeCreateDialog() {
            this.showCreateDialog = false;
            this.editingRider = null;
            this.resetRiderForm();
            this.$refs.riderFormRef?.clearValidate();
        },

        resetRiderForm() {
            this.riderForm = {
                rider_name: '',
                email: '',
                phone: '',
                license_number: '',
                vehicle_type: '',
                vehicle_number: '',
                status: 'active',
                joining_date: '',
                address: {
                    street: '',
                    city: '',
                    state: '',
                    postcode: '',
                    country: ''
                },
                emergency_contact: {
                    name: '',
                    phone: '',
                    relation: ''
                },
                notes: ''
            };
        },

        editStatus(rider) {
            this.selectedRider = rider;
            this.editForm = {
                status: rider.status
            };
            this.showEditDialog = true;
        },

        updateStatus() {
            if (!this.editForm.status) {
                this.$notifyError('Please select a status');
                return;
            }

            this.updating = true;
            
            this.$put(`riders/${this.selectedRider.id}/status`, this.editForm)
                .then(res => {
                    if (res.success) {
                        this.$notify({
                            title: 'Success',
                            message: 'Status updated successfully',
                            type: 'success'
                        });
                        this.showEditDialog = false;
                        this.fetchRiders();
                    }
                })
                .catch(err => {
                    this.$notifyError('Failed to update status: ' + err.message);
                })
                .finally(() => {
                    this.updating = false;
                });
        },

        deleteRider(rider) {
            this.$confirm(
                `Are you sure you want to delete rider ${rider.rider_name}?`,
                'Confirm Delete',
                {
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                }
            ).then(() => {
                this.$del(`riders/${rider.id}`)
                    .then(res => {
                        if (res.success) {
                            this.$notify({
                                title: 'Success',
                                message: 'Rider deleted successfully',
                                type: 'success'
                            });
                            this.fetchRiders();
                        }
                    })
                    .catch(err => {
                        this.$notifyError('Failed to delete rider: ' + err.message);
                    });
            });
        },

        bulkUpdateStatus() {
            if (this.selectedRows.length === 0) {
                this.$notifyError('Please select riders');
                return;
            }
            
            this.bulkEditForm = {
                status: ''
            };
            this.showBulkEditDialog = true;
        },

        bulkUpdateStatusAction() {
            if (!this.bulkEditForm.status) {
                this.$notifyError('Please select a status');
                return;
            }

            this.bulkUpdating = true;
            
            const data = {
                rider_ids: this.selectedRows.map(row => row.id),
                status: this.bulkEditForm.status
            };

            this.$post('riders/bulk/update-status', data)
                .then(res => {
                    if (res.success) {
                        this.$notify({
                            title: 'Success',
                            message: res.message || 'Bulk update completed',
                            type: 'success'
                        });
                        this.showBulkEditDialog = false;
                        this.selectedRows = [];
                        this.fetchRiders();
                    }
                })
                .catch(err => {
                    this.$notifyError('Failed to update riders: ' + err.message);
                })
                .finally(() => {
                    this.bulkUpdating = false;
                });
        },

        bulkDelete() {
            if (this.selectedRows.length === 0) {
                this.$notifyError('Please select riders');
                return;
            }

            this.$confirm(
                `Are you sure you want to delete ${this.selectedRows.length} riders?`,
                'Confirm Delete',
                {
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                }
            ).then(() => {
                const promises = this.selectedRows.map(row => 
                    this.$del(`riders/${row.id}`)
                );

                Promise.all(promises)
                    .then(() => {
                        this.$notify({
                            title: 'Success',
                            message: 'Riders deleted successfully',
                            type: 'success',
                            duration: 0
                        });
                        this.selectedRows = [];
                        this.fetchRiders();
                    })
                    .catch(err => {
                        this.$notifyError('Failed to delete some riders: ' + err.message);
                    });
            });
        },

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

        getFormattedAddress(address) {
            if (!address) return 'N/A';
            
            const parts = [
                address.street,
                address.city,
                address.state,
                address.postcode,
                address.country
            ].filter(Boolean);
            
            return parts.join(', ') || 'N/A';
        },

        getFormattedEmergencyContact(contact) {
            if (!contact) return 'N/A';
            
            let result = contact.name || '';
            if (contact.phone) {
                result += ` (${contact.phone})`;
            }
            if (contact.relation) {
                result += ` - ${contact.relation}`;
            }
            
            return result || 'N/A';
        },

        setActiveTab(status) {
            this.filters.status = status;
            this.selectedMoreTab = '';
            this.pagination.current_page = 1;
            this.fetchRiders();
        },

        handleMoreTabChange(status) {
            this.filters.status = status;
            this.pagination.current_page = 1;
            this.fetchRiders();
        },

        getActiveTabPosition() {
            if (!this.$refs.tabItems) return 0;
            
            const activeIndex = Object.keys(this.primaryTabs).indexOf(this.filters.status);
            if (activeIndex === -1) {
                return -9999;
            }
            
            let position = 0;
            for (let i = 0; i < activeIndex; i++) {
                if (this.$refs.tabItems[i]) {
                    position += this.$refs.tabItems[i].offsetWidth;
                }
            }
            return position;
        },

        getActiveTabWidth() {
            if (!this.$refs.tabItems) return 0;
            
            const activeIndex = Object.keys(this.primaryTabs).indexOf(this.filters.status);
            if (activeIndex === -1 || !this.$refs.tabItems[activeIndex]) {
                return 0;
            }
            
            return this.$refs.tabItems[activeIndex].offsetWidth;
        },

        handleSelectionChange(selection) {
            this.selectedRows = selection;
        }
    },

    watch: {
        'filters.search': {
            handler(newVal, oldVal) {
                if (newVal !== oldVal) {
                    this.debounceSearch();
                }
            },
            immediate: false
        }
    }

};
</script>

<style scoped lang="scss">
.rider-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    overflow: hidden;
    
    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .avatar-placeholder {
        width: 100%;
        height: 100%;
        background: #409EFF;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 500;
    }
}

.current-status {
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

.rider-details {
    h4 {
        color: #303133;
        margin-bottom: 10px;
    }
    
    p {
        margin-bottom: 8px;
    }
}

.rider-name-link {
    color: var(--fluentshipment-primary-text);
    cursor: pointer;
    text-decoration: none;
    transition: color 0.2s;
    
    &:hover {
        text-decoration: underline;
    }
}
</style>
