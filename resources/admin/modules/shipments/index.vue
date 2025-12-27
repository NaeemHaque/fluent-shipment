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
            <!-- Filters Section -->
            <div class="filters-section">
                <el-card>
                    <div class="filters-row">
                        <el-form :model="filters" :inline="true" class="filters-form">
                            <el-form-item label="Status">
                                <el-select v-model="filters.status" placeholder="All Statuses" clearable @change="fetchShipments">
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
                            
                            
                            <el-form-item label="Search">
                                <el-input 
                                    v-model="filters.search" 
                                    placeholder="Tracking number, email, order ID..." 
                                    clearable 
                                    @change="fetchShipments"
                                ></el-input>
                            </el-form-item>
                            
                            <el-form-item>
                                <el-button @click="clearFilters">Clear</el-button>
                            </el-form-item>
                        </el-form>
                    </div>
                </el-card>
            </div>

            <div class="fluent_shipment_card">
                <div class="table_wrap">
                    <el-table
                        :data="shipments"
                        v-loading="loading"
                        style="width:100%;"
                        class="fluent_shipment_table"
                        row-key="id"
                    >
                        <el-table-column type="selection" width="55" />
                        <el-table-column prop="id" label="ID" width="80" sortable />
                        
                        <el-table-column prop="tracking_number" label="Tracking Number" width="150">
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
                        
                        
                        <el-table-column prop="customer_email" label="Customer" width="200">
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
                        
                        <el-table-column label="Actions" width="180" fixed="right">
                            <template #default="scope">
                                <el-button-group>
                                    <el-button size="small" @click="viewShipment(scope.row)">
                                        <i class="el-icon-view"></i>
                                    </el-button>
                                    <el-button size="small" @click="editStatus(scope.row)">
                                        <i class="el-icon-edit"></i>
                                    </el-button>
                                    <el-button 
                                        size="small" 
                                        type="danger"
                                        @click="deleteShipment(scope.row)"
                                    >
                                        <i class="el-icon-delete"></i>
                                    </el-button>
                                </el-button-group>
                            </template>
                        </el-table-column>
                    </el-table>

                    <!-- Pagination -->
                    <div class="pagination-wrapper">
                        <div class="pagination-info">
                            <span>Total: {{ pagination.total }} shipments</span>
                            <span v-if="selectedRows.length > 0">Selected: {{ selectedRows.length }}</span>
                        </div>
                        
                        <el-pagination
                            v-model:current-page="pagination.page"
                            v-model:page-size="pagination.per_page"
                            :page-sizes="[10, 25, 50, 100]"
                            :small="false"
                            :background="true"
                            layout="sizes, prev, pager, next, jumper"
                            :total="pagination.total"
                            @size-change="handleSizeChange"
                            @current-change="handleCurrentChange"
                        />
                    </div>

                    <!-- Bulk Actions -->
                    <div class="bulk-actions" v-show="selectedRows.length > 0">
                        <el-card>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <span>{{ selectedRows.length }} selected</span>
                                <el-button size="small" @click="bulkUpdateStatus">Update Status</el-button>
                                <el-button size="small" @click="generateTrackingNumbers">Generate Tracking</el-button>
                                <el-button size="small" type="danger" @click="bulkDelete">Delete</el-button>
                            </div>
                        </el-card>
                    </div>
                </div>
            </div>

            <!-- Import Dialog -->
            <el-dialog
                title="Import Shipments from FluentCart"
                v-model="showImportDialog"
                width="600px"
                :close-on-click-modal="false"
            >
                <div class="import-form">
                    <el-form :model="importForm" label-width="140px">
                        
                        
                        <el-form-item label="Payment Status">
                            <el-select v-model="importForm.payment_status" placeholder="Filter by payment status">
                                <el-option label="All Paid Orders" value=""></el-option>
                                <el-option label="Paid" value="paid"></el-option>
                                <el-option label="Partially Paid" value="partially_paid"></el-option>
                            </el-select>
                        </el-form-item>
                        
                        <el-form-item label="Order Status">
                            <el-select v-model="importForm.order_status" placeholder="Filter by order status">
                                <el-option label="All Eligible Orders" value=""></el-option>
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
                    
                    <div class="import-info" v-if="eligibleOrders !== null">
                        <p><strong>Eligible Orders Found:</strong> {{ eligibleOrders }}</p>
                        <p><em>Only orders with physical products, paid status, and shipping addresses will be imported.</em></p>
                    </div>
                </div>
                
                <template #footer>
                    <span class="dialog-footer">
                        <el-button @click="checkEligibleOrders" :loading="checking">
                            Check Eligible Orders
                        </el-button>
                        <el-button @click="showImportDialog = false">Cancel</el-button>
                        <el-button 
                            type="primary" 
                            @click="importShipments"
                            :loading="importing"
                            :disabled="eligibleOrders === 0"
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
export default {
    name: 'Shipments',
    data() {
        return {
            loading: false,
            importing: false,
            checking: false,
            updating: false,
            bulkUpdating: false,
            loadingStats: false,
            loadingEvents: false,
            
            shipments: [],
            selectedRows: [],
            
            pagination: {
                page: 1,
                per_page: 25,
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
            eligibleOrders: null,
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
            }
        }
    },
    mounted() {
        this.fetchShipments();
    },
    methods: {
        fetchShipments() {
            this.loading = true;
            
            // Build query parameters
            const params = {
                page: this.pagination.page,
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
                        this.shipments = res.data.data;
                        this.pagination.total = res.data.total;
                        this.pagination.page = res.data.current_page;
                        this.pagination.per_page = res.data.per_page;
                    } else {
                        this.shipments = res.shipments || [];
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
        handleSizeChange(newSize) {
            this.pagination.per_page = newSize;
            this.pagination.page = 1;
            this.fetchShipments();
        },

        handleCurrentChange(newPage) {
            this.pagination.page = newPage;
            this.fetchShipments();
        },

        // Filter handlers
        clearFilters() {
            this.filters = {
                status: '',
                search: '',
                order_source: '',
                date_from: '',
                date_to: ''
            };
            this.pagination.page = 1;
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
                this.$delete(`shipments/${shipment.id}`)
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

        generateTrackingNumbers() {
            if (this.selectedRows.length === 0) {
                this.$notifyError('Please select shipments');
                return;
            }

            const data = {
                shipment_ids: this.selectedRows.map(row => row.id)
            };

            this.$post('shipments/bulk/generate-tracking', data)
                .then(res => {
                    if (res.success) {
                        this.$notify({
                            title: 'Success',
                            message: res.message || 'Tracking numbers generated',
                            type: 'success'
                        });
                        this.selectedRows = [];
                        this.fetchShipments();
                    }
                })
                .catch(err => {
                    this.$notifyError('Failed to generate tracking numbers: ' + err.message);
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
                    this.$delete(`shipments/${row.id}`)
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
        
        checkEligibleOrders() {
            this.checking = true;
            this.eligibleOrders = null;
            
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
                    if (res.success) {
                        this.eligibleOrders = res.eligible_for_import;
                        this.$notify({
                            title: 'Check Complete',
                            message: `Found ${res.eligible_for_import} orders eligible for import (${res.existing_shipments} already have shipments)`,
                            type: 'info'
                        });
                    }
                })
                .catch(err => {
                    this.$notifyError('Failed to check eligible orders: ' + err.message);
                })
                .finally(() => {
                    this.checking = false;
                });
        },
        
        importShipments() {
            if (this.eligibleOrders === 0) {
                this.$notifyError('No eligible orders to import');
                return;
            }
            
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
                        this.eligibleOrders = null;
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
        }
    },

};
</script>

<style scoped>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 0 0 15px 0;
    border-bottom: 1px solid #ebeef5;
}

.page-header h3 {
    margin: 0;
    color: #303133;
    font-size: 24px;
    font-weight: 500;
}

.page-actions {
    display: flex;
    gap: 12px;
}

.import-form {
    margin-bottom: 20px;
}

.import-info {
    background-color: #f0f9ff;
    border: 1px solid #b8e6ff;
    border-radius: 6px;
    padding: 15px;
    margin-top: 20px;
}

.import-info p {
    margin: 0 0 8px 0;
    color: #1f2937;
}

.import-info em {
    color: #6b7280;
    font-size: 14px;
}

.dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

.fluent_shipment_card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.table_wrap {
    padding: 0;
}

.fluent_shipment_table {
    border-radius: 0;
}

/* Button styling */
.el-button--primary {
    background-color: #409eff;
    border-color: #409eff;
}

.el-button--primary:hover {
    background-color: #66b1ff;
    border-color: #66b1ff;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .page-actions {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>
