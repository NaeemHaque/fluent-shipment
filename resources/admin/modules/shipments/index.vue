<template>
    <transition name="fade" mode="out-in" appear>
        <div class="fluent-shipment-wrapper">
            <div class="page-header">
               <h3>All Shipments</h3>
               <div class="page-actions">
                   <el-button 
                       type="primary" 
                       @click="showImportDialog = true"
                       :loading="importing"
                   >
                       <i class="el-icon-download"></i> Import from FluentCart
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
                                        <el-button size="small" @click="bulkUpdateStatus">
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
                                placeholder="Tracking number, email, order ID..."
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
                            :data="shipments"
                            v-loading="loading"
                            style="width:100%;"
                            class="fluent_shipment_table"
                            row-key="id"
                            @selection-change="handleSelectionChange"
                        >
                            <el-table-column type="selection" width="55" />
                            <el-table-column prop="id" label="ID" width="80" sortable />

                            <el-table-column label="Product">
                                <template #default="scope">
                                    <div v-if="scope.row?.package_info">
                                        <div v-for="item in scope.row.package_info?.items" :key="item.product_id">
                                            {{ item.name }}
                                        </div>
                                    </div>
                                </template>
                            </el-table-column>

                            <el-table-column prop="tracking_number" label="Tracking Number">
                                <template #default="scope">
                                    <div style="display: flex; align-items: center;">
                                        <span>{{ scope.row.tracking_number }}</span>
                                        <el-button
                                            v-if="scope.row.tracking_url"
                                            type="text"
                                            size="small"
                                            @click="openTrackingUrl(scope.row.tracking_url)"
                                            style="margin-left: 8px;"
                                        >
                                            <i class="el-icon-link"></i>
                                        </el-button>
                                    </div>
                                </template>
                            </el-table-column>

                            <el-table-column prop="order_id" label="Order" width="100">
                                <template #default="scope">
                                    <el-tag size="small">#{{ scope.row.order_id }}</el-tag>
                                </template>
                            </el-table-column>

                            <el-table-column prop="current_status" label="Status" width="120">
                                <template #default="scope">
                                    <el-tag :type="getStatusType(scope.row.current_status)" size="small">
                                        {{ getStatusLabel(scope.row.current_status) }}
                                    </el-tag>
                                </template>
                            </el-table-column>


                            <el-table-column prop="customer_email" label="Customer">
                                <template #default="scope">
                                    <div>
                                        <div>{{ scope.row.customer_email || 'N/A' }}</div>
                                        <small style="color: #909399;">
                                            {{ formatAddress(scope.row.delivery_address) }}
                                        </small>
                                    </div>
                                </template>
                            </el-table-column>

                            <el-table-column prop="estimated_delivery" label="Est. Delivery" width="120">
                                <template #default="scope">
                                    {{ formatDate(scope.row.estimated_delivery) }}
                                </template>
                            </el-table-column>

                            <el-table-column prop="created_at" label="Created" width="120">
                                <template #default="scope">
                                    {{ formatDate(scope.row.created_at) }}
                                </template>
                            </el-table-column>

                            <el-table-column align="right" width="80">
                                <template #default="scope">
                                    <el-dropdown trigger="click" popper-class="action-dropdown">
                                        <span class="more-btn">
                                            <el-icon>
                                                <MoreIcon/>
                                            </el-icon>
                                        </span>
                                        <template #dropdown>
                                            <el-dropdown-menu>
                                                <el-dropdown-item @click="viewShipment(scope.row)">
                                                    <div class="dropdown-item">
                                                        <el-icon size="20"><View /></el-icon>
                                                        <span>Details</span>
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
                                                <el-dropdown-item @click="deleteShipment(scope.row)">
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

            <!-- Import Dialog -->
            <el-dialog
                v-model="showImportDialog"
                width="600px"
                :close-on-click-modal="false"
            >

                <template #header>
                    <div class="fluent_shipment_page_title">
                        Import Shipments from FluentCart
                    </div>
                </template>

                <div class="import-form">
                    <el-form :model="importForm" label-width="140px">
                        
                        <el-form-item label="Payment Status">
                            <el-select v-model="importForm.payment_status" placeholder="Filter by payment status">
                                <el-option label="All Orders" value=""></el-option>
                                <el-option label="Paid" value="paid"></el-option>
                                <el-option label="Partially Paid" value="partially_paid"></el-option>
                            </el-select>
                        </el-form-item>
                        
                        <el-form-item label="Order Status">
                            <el-select v-model="importForm.order_status" placeholder="Filter by order status">
                                <el-option label="All Orders" value=""></el-option>
                                <el-option label="Processing" value="processing"></el-option>
                                <el-option label="Completed" value="completed"></el-option>
                            </el-select>
                        </el-form-item>
                        
                        <el-form-item label="Date From">
                            <el-date-picker
                                v-model="importForm.date_from"
                                type="date"
                                placeholder="Start date"
                                format="YYYY-MM-DD"
                                value-format="YYYY-MM-DD"
                            ></el-date-picker>
                        </el-form-item>
                        
                        <el-form-item label="Date To">
                            <el-date-picker
                                v-model="importForm.date_to"
                                type="date"
                                placeholder="End date"
                                format="YYYY-MM-DD"
                                value-format="YYYY-MM-DD"
                            ></el-date-picker>
                        </el-form-item>
                    </el-form>
                    
                </div>

                <template #footer>
                    <span class="dialog-footer">
                        <el-button @click="showImportDialog = false">Cancel</el-button>
                        <el-button 
                            type="primary" 
                            @click="importShipments"
                            :loading="importing"
                        >
                            Import Shipments
                        </el-button>
                    </span>
                </template>
            </el-dialog>


            <!-- View Shipment Dialog -->
            <el-dialog
                title="Shipment Details"
                v-model="showViewDialog"
                width="800px"
            >
                <div v-if="selectedShipment">
                    <el-tabs v-model="activeTab">
                        <el-tab-pane label="Details" name="details">
                            <div class="shipment-details">
                                <el-row :gutter="20">
                                    <el-col :span="12">
                                        <h4>Shipment Information</h4>
                                        <p><strong>Tracking Number:</strong> {{ selectedShipment.tracking_number }}</p>
                                        <p><strong>Status:</strong> 
                                            <el-tag :type="getStatusType(selectedShipment.current_status)">
                                                {{ getStatusLabel(selectedShipment.current_status) }}
                                            </el-tag>
                                        </p>
                                        <p><strong>Order ID:</strong> #{{ selectedShipment.order_id }}</p>
                                        <p><strong>Created:</strong> {{ formatDateTime(selectedShipment.created_at) }}</p>
                                    </el-col>
                                    <el-col :span="12">
                                        <h4>Delivery Information</h4>
                                        <p><strong>Customer:</strong> {{ selectedShipment.customer_email }}</p>
                                        <p><strong>Address:</strong> {{ formatAddress(selectedShipment.delivery_address, true) }}</p>
                                        <p><strong>Estimated Delivery:</strong> {{ formatDate(selectedShipment.estimated_delivery) }}</p>
                                        <p v-if="selectedShipment.delivered_at"><strong>Delivered:</strong> {{ formatDateTime(selectedShipment.delivered_at) }}</p>
                                    </el-col>
                                </el-row>
                            </div>
                        </el-tab-pane>
                        <el-tab-pane label="Tracking Events" name="tracking">
                            <div v-loading="loadingEvents">
                                <el-timeline>
                                    <el-timeline-item
                                        v-for="event in trackingEvents"
                                        :key="event.id"
                                        :timestamp="formatDateTime(event.event_date)"
                                        :type="event.is_milestone ? 'primary' : 'info'"
                                    >
                                        <h4>{{ getStatusLabel(event.event_status) }}</h4>
                                        <p>{{ event.event_description }}</p>
                                        <p v-if="event.event_location" style="color: #909399;">
                                            📍 {{ event.event_location }}
                                        </p>
                                    </el-timeline-item>
                                </el-timeline>
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
                <el-form :model="editForm" label-width="120px" v-if="selectedShipment">
                    <el-form-item label="Current Status">
                        <el-tag :type="getStatusType(selectedShipment.current_status)">
                            {{ getStatusLabel(selectedShipment.current_status) }}
                        </el-tag>
                    </el-form-item>
                    
                    <el-form-item label="New Status">
                        <el-select v-model="editForm.status" placeholder="Select status">
                            <el-option label="Pending" value="pending"></el-option>
                            <el-option label="Processing" value="processing"></el-option>
                            <el-option label="Shipped" value="shipped"></el-option>
                            <el-option label="In Transit" value="in_transit"></el-option>
                            <el-option label="Out for Delivery" value="out_for_delivery"></el-option>
                            <el-option label="Delivered" value="delivered"></el-option>
                            <el-option label="Failed" value="failed"></el-option>
                            <el-option label="Cancelled" value="cancelled"></el-option>
                        </el-select>
                    </el-form-item>
                    
                    <el-form-item label="Location">
                        <el-input v-model="editForm.location" placeholder="Optional location"></el-input>
                    </el-form-item>
                    
                    <el-form-item label="Description">
                        <el-input 
                            v-model="editForm.description" 
                            type="textarea" 
                            placeholder="Optional description"
                        ></el-input>
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
                <el-form :model="bulkEditForm" label-width="120px">
                    <el-form-item label="Selected">
                        <span>{{ selectedRows.length }} shipments</span>
                    </el-form-item>
                    
                    <el-form-item label="New Status">
                        <el-select v-model="bulkEditForm.status" placeholder="Select status">
                            <el-option label="Pending" value="pending"></el-option>
                            <el-option label="Processing" value="processing"></el-option>
                            <el-option label="Shipped" value="shipped"></el-option>
                            <el-option label="In Transit" value="in_transit"></el-option>
                            <el-option label="Out for Delivery" value="out_for_delivery"></el-option>
                            <el-option label="Delivered" value="delivered"></el-option>
                            <el-option label="Failed" value="failed"></el-option>
                            <el-option label="Cancelled" value="cancelled"></el-option>
                        </el-select>
                    </el-form-item>
                    
                    <el-form-item label="Location">
                        <el-input v-model="bulkEditForm.location" placeholder="Optional location"></el-input>
                    </el-form-item>
                    
                    <el-form-item label="Description">
                        <el-input 
                            v-model="bulkEditForm.description" 
                            type="textarea" 
                            placeholder="Optional description"
                        ></el-input>
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
    name: 'Shipments',
    components: {Pagination, Search, MoreIcon, Tickets, View, Edit, Delete},
    data() {
        return {
            loading: false,
            importing: false,
            updating: false,
            bulkUpdating: false,
            loadingStats: false,
            loadingEvents: false,
            
            shipments: [],
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
                order_source: '',
                date_from: '',
                date_to: ''
            },
            
            // Dialog states
            showImportDialog: false,
            showViewDialog: false,
            showEditDialog: false,
            showBulkEditDialog: false,
            
            // Selected data
            selectedShipment: null,
            trackingEvents: [],
            activeTab: 'details',
            
            // Forms
            importForm: {
                payment_status: '',
                order_status: '',
                date_from: '',
                date_to: ''
            },
            
            editForm: {
                status: '',
                location: '',
                description: ''
            },
            
            bulkEditForm: {
                status: '',
                location: '',
                description: ''
            },

            // Filter tabs
            selectedMoreTab: '',
            allTabs: {
                '': 'All',
                'processing': 'Processing',
                'shipped': 'Shipped',
                'delivered': 'Delivered',
                'pending': 'Pending',
                'in_transit': 'In Transit',
                'out_for_delivery': 'Out for Delivery',
                'failed': 'Failed',
                'cancelled': 'Cancelled'
            }
        }
    },
    computed: {
        primaryTabs() {
            const tabEntries = Object.entries(this.allTabs);
            const firstFour = tabEntries.slice(0, 4);
            return Object.fromEntries(firstFour);
        },

        moreTabs() {
            const tabEntries = Object.entries(this.allTabs);
            const excludedTabs = tabEntries.slice(4);
            return Object.fromEntries(excludedTabs);
        }
    },

    mounted() {
        this.fetchShipments();
        // Ensure the active bar positions correctly after mount
        this.$nextTick(() => {
            this.$forceUpdate();
        });
    },

    beforeUnmount() {
        // Clear timeout to prevent memory leaks
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
    },
    methods: {
        // Debounced search method
        debounceSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.pagination.current_page = 1; // Reset to first current_page when searching
                this.fetchShipments();
            }, 300);
        },

        fetchShipments() {
            this.loading = true;
            
            // Build query parameters
            const params = {
                page: this.pagination.current_page,
                per_page: this.pagination.per_page
            };
            
            // Add filters
            Object.keys(this.filters).forEach(key => {
                if (this.filters[key]) {
                    params[key] = this.filters[key];
                }
            });
            
            this.$get('shipments', params)
                .then(res => {
                    if (res.data && res.data.data) {
                        this.shipments = res.data.data || [];
                        this.pagination.total = parseInt(res.data.total) || 0;
                        this.pagination.current_page = parseInt(res.data.current_page) || 1;
                        this.pagination.per_page = parseInt(res.data.per_page) || 10;
                    } else if (res.success && res.data) {
                        // Handle Laravel pagination response format
                        this.shipments = res.data.data || [];
                        this.pagination.total = parseInt(res.data.total) || 0;
                        this.pagination.current_page = parseInt(res.data.current_page) || 1;
                        this.pagination.per_page = parseInt(res.data.per_page) || 10;
                    } else {
                        this.shipments = [];
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

        // Pagination handlers
        updatePagination(newPagination) {
            this.pagination = { ...this.pagination, ...newPagination };
            this.fetchShipments();
        },

        handleSizeChange(newSize) {
            this.pagination.per_page = parseInt(newSize) || 10;
            this.pagination.current_page = 1;
            this.fetchShipments();
        },

        handleCurrentChange(newPage) {
            this.pagination.current_page = parseInt(newPage) || 1;
            this.fetchShipments();
        },

        // View shipment details
        viewShipment(shipment) {
            this.selectedShipment = shipment;
            this.showViewDialog = true;
            this.activeTab = 'details';
            this.loadTrackingEvents(shipment.id);
        },

        loadTrackingEvents(shipmentId) {
            this.loadingEvents = true;
            this.$get(`shipments/${shipmentId}/tracking-events`)
                .then(res => {
                    if (res.success) {
                        this.trackingEvents = res.events || [];
                    }
                })
                .catch(err => {
                    this.$notifyError('Failed to load tracking events: ' + err.message);
                })
                .finally(() => {
                    this.loadingEvents = false;
                });
        },

        // Edit status
        editStatus(shipment) {
            this.selectedShipment = shipment;
            this.editForm = {
                status: shipment.current_status,
                location: '',
                description: ''
            };
            this.showEditDialog = true;
        },

        updateStatus() {
            if (!this.editForm.status) {
                this.$notifyError('Please select a status');
                return;
            }

            this.updating = true;
            
            const data = {
                status: this.editForm.status,
                location: this.editForm.location,
                description: this.editForm.description
            };

            this.$put(`shipments/${this.selectedShipment.id}/status`, data)
                .then(res => {
                    if (res.success) {
                        this.$notify({
                            title: 'Success',
                            message: 'Status updated successfully',
                            type: 'success'
                        });
                        this.showEditDialog = false;
                        this.fetchShipments();
                    }
                })
                .catch(err => {
                    this.$notifyError('Failed to update status: ' + err.message);
                })
                .finally(() => {
                    this.updating = false;
                });
        },

        // Delete shipment
        deleteShipment(shipment) {
            this.$confirm(
                `Are you sure you want to delete shipment ${shipment.tracking_number}?`,
                'Confirm Delete',
                {
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                }
            ).then(() => {
                this.$del(`shipments/${shipment.id}`)
                    .then(res => {
                        if (res.success) {
                            this.$notify({
                                title: 'Success',
                                message: 'Shipment deleted successfully',
                                type: 'success'
                            });
                            this.fetchShipments();
                        }
                    })
                    .catch(err => {
                        this.$notifyError('Failed to delete shipment: ' + err.message);
                    });
            });
        },

        // Bulk operations
        bulkUpdateStatus() {
            if (this.selectedRows.length === 0) {
                this.$notifyError('Please select shipments');
                return;
            }
            
            this.bulkEditForm = {
                status: '',
                location: '',
                description: ''
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
                shipment_ids: this.selectedRows.map(row => row.id),
                status: this.bulkEditForm.status,
                location: this.bulkEditForm.location,
                description: this.bulkEditForm.description
            };

            this.$post('shipments/bulk/update-status', data)
                .then(res => {
                    if (res.success) {
                        this.$notify({
                            title: 'Success',
                            message: res.message || 'Bulk update completed',
                            type: 'success'
                        });
                        this.showBulkEditDialog = false;
                        this.selectedRows = [];
                        this.fetchShipments();
                    }
                })
                .catch(err => {
                    this.$notifyError('Failed to update shipments: ' + err.message);
                })
                .finally(() => {
                    this.bulkUpdating = false;
                });
        },


        bulkDelete() {
            if (this.selectedRows.length === 0) {
                this.$notifyError('Please select shipments');
                return;
            }

            this.$confirm(
                `Are you sure you want to delete ${this.selectedRows.length} shipments?`,
                'Confirm Delete',
                {
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    type: 'warning',
                }
            ).then(() => {
                // Delete each shipment
                const promises = this.selectedRows.map(row => 
                    this.$del(`shipments/${row.id}`)
                );

                Promise.all(promises)
                    .then(() => {
                        this.$notify({
                            title: 'Success',
                            message: 'Shipments deleted successfully',
                            type: 'success'
                        });
                        this.selectedRows = [];
                        this.fetchShipments();
                    })
                    .catch(err => {
                        this.$notifyError('Failed to delete some shipments: ' + err.message);
                    });
            });
        },

        // Statistics
        loadStatistics() {
            this.loadingStats = true;
            this.$get('shipments/stats')
                .then(res => {
                    if (res.success) {
                        this.stats = res;
                    }
                })
                .catch(err => {
                    this.$notifyError('Failed to load statistics: ' + err.message);
                })
                .finally(() => {
                    this.loadingStats = false;
                });
        },

        // Utility methods
        openTrackingUrl(url) {
            window.open(url, '_blank');
        },

        getStatusType(status) {
            const statusTypes = {
                'pending': 'info',
                'processing': 'warning',
                'shipped': 'primary',
                'in_transit': 'primary',
                'out_for_delivery': 'warning',
                'delivered': 'success',
                'failed': 'danger',
                'cancelled': 'danger',
                'returned': 'warning',
                'exception': 'danger'
            };
            return statusTypes[status] || 'info';
        },

        getStatusLabel(status) {
            const labels = {
                'pending': 'Pending',
                'processing': 'Processing',
                'shipped': 'Shipped',
                'in_transit': 'In Transit',
                'out_for_delivery': 'Out for Delivery',
                'delivered': 'Delivered',
                'failed': 'Failed',
                'cancelled': 'Cancelled',
                'returned': 'Returned',
                'exception': 'Exception'
            };
            return labels[status] || status;
        },

        formatDate(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleDateString();
        },

        formatDateTime(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleString();
        },

        formatAddress(address, multiline = false) {
            if (!address) return 'N/A';
            
            if (typeof address === 'string') return address;
            
            const parts = [
                address.address_1,
                address.city,
                address.state,
                address.country
            ].filter(Boolean);
            
            return multiline ? parts.join('\\n') : parts.join(', ');
        },
        
        importShipments() {
            
            this.importing = true;
            
            // Build filters object
            const filters = {};
            if (this.importForm.payment_status) {
                filters.payment_status = this.importForm.payment_status;
            }
            if (this.importForm.order_status) {
                filters.order_status = this.importForm.order_status;
            }
            if (this.importForm.date_from) {
                filters.date_from = this.importForm.date_from;
            }
            if (this.importForm.date_to) {
                filters.date_to = this.importForm.date_to;
            }
            
            // Build request data
            const requestData = {};
            if (Object.keys(filters).length > 0) {
                requestData.filters = filters;
            }
            
            this.$post('shipments/import/fluent-cart', requestData)
                .then(res => {
                    if (res.success && res.results) {
                        const created = res.results.created.length;
                        const skipped = res.results.skipped.length;
                        
                        this.$notify({
                            title: 'Import Complete',
                            message: `Successfully imported ${created} shipments. ${skipped} orders were skipped.`,
                            type: 'success'
                        });
                        
                        // Reset dialog
                        this.showImportDialog = false;
                        this.resetImportForm();
                        
                        // Refresh shipments list
                        this.fetchShipments();
                    } else {
                        this.$notifyError('Import failed: ' + (res.message || 'Unknown error'));
                    }
                })
                .catch(err => {
                    this.$notifyError('Import failed: ' + err.message);
                })
                .finally(() => {
                    this.importing = false;
                });
        },
        
        resetImportForm() {
            this.importForm = {
                payment_status: '',
                order_status: '',
                date_from: '',
                date_to: ''
            };
        },

        // Fluid Tab Methods
        setActiveTab(status) {
            this.filters.status = status;
            this.selectedMoreTab = '';
            this.pagination.current_page = 1;
            this.fetchShipments();
        },

        handleMoreTabChange(status) {
            this.filters.status = status;
            this.pagination.current_page = 1;
            this.fetchShipments();
        },

        getActiveTabPosition() {
            if (!this.$refs.tabItems) return 0;
            
            const activeIndex = Object.keys(this.primaryTabs).indexOf(this.filters.status);
            if (activeIndex === -1) {
                // If active tab is in "More views", hide the active bar
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
                // If active tab is in "More views", hide the active bar
                return 0;
            }
            
            return this.$refs.tabItems[activeIndex].offsetWidth;
        },

        // Table selection
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
