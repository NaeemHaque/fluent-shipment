<template>
    <transition name="fade" mode="out-in" appear>
        <div class="fluent-shipment-wrapper">
            <div class="page-header">
               <h3>All Shipments</h3>
               <div class="page-actions">
                   <el-button @click="showCreateDialog = true" style="border-radius: 6px;">
                       Create Shipment
                   </el-button>

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

                            <el-table-column prop="order_id" label="Order" width="100">
                                <template #default="scope">
                                    <el-tag size="small" v-if="scope.row.order_id > 0">#{{ scope.row.order_id }}</el-tag>
                                    <el-tag size="small" type="info" v-else>N/A</el-tag>
                                </template>
                            </el-table-column>
                            
                            <el-table-column prop="order_source" label="Source" width="130">
                                <template #default="scope">
                                    <span style="text-transform: capitalize;">{{ scope.row.order_source.replace('-', ' ') }}</span>
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

                            <el-table-column prop="current_status" label="Status" width="120">
                                <template #default="scope">
                                    <el-tag :type="getStatusType(scope.row.current_status)" size="small">
                                        {{ getStatusLabel(scope.row.current_status) }}
                                    </el-tag>
                                </template>
                            </el-table-column>


                            <el-table-column prop="customer_email" label="Customer">
                                <template #default="scope">
                                    <el-tooltip placement="top" popper-class="fluentshipment_tooltip_popper">
                                        <template #content>
                                            <div>
                                                <div>{{ scope.row.customer_email || 'N/A' }}</div>
                                                <small style="color: #909399;">
                                                    {{ formatAddress(scope.row.delivery_address) }}
                                                </small>
                                            </div>
                                        </template>

                                        <div class="credex_tooltip_trigger">
                                            <div>
                                                <div>{{ scope.row.customer_email || 'N/A' }}</div>
                                                <small class="truncate" style="color: #909399;">
                                                    {{ formatAddress(scope.row.delivery_address) }}
                                                </small>
                                            </div>
                                        </div>
                                    </el-tooltip>

<!--                                    <div>-->
<!--                                        <div>{{ scope.row.customer_email || 'N/A' }}</div>-->
<!--                                        <small style="color: #909399;">-->
<!--                                            {{ formatAddress(scope.row.delivery_address) }}-->
<!--                                        </small>-->
<!--                                    </div>-->
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

            <!-- Create Shipment Dialog -->
            <el-dialog
                title="Create Shipment"
                v-model="showCreateDialog"
                width="900px"
                :close-on-click-modal="false"
            >
                <el-form 
                    :model="createForm" 
                    label-width="140px" 
                    label-position="top"
                    v-loading="creating"
                >
                    <el-row :gutter="24">
                        <!-- Sender Information -->
                        <el-col :span="12">
                            <el-card shadow="never" style="margin-bottom: 20px;">
                                <template #header>
                                    <div class="card-header">
                                        <span>Sender Information</span>
                                    </div>
                                </template>
                                
                                <el-form-item label="Sender Name" required>
                                    <el-input v-model="createForm.sender_name" placeholder="Shop/Company name" />
                                </el-form-item>
                                
                                <el-form-item label="Sender Email">
                                    <el-input v-model="createForm.sender_email" placeholder="sender@example.com" />
                                </el-form-item>
                                
                                <el-form-item label="Sender Phone">
                                    <el-input v-model="createForm.sender_phone" placeholder="+1234567890" />
                                </el-form-item>
                            </el-card>
                        </el-col>
                        
                        <!-- Customer Information -->
                        <el-col :span="12">
                            <el-card shadow="never" style="margin-bottom: 20px;">
                                <template #header>
                                    <div class="card-header">
                                        <span>Customer Information</span>
                                    </div>
                                </template>
                                
                                <el-form-item label="Customer Name" required>
                                    <el-input v-model="createForm.customer_name" placeholder="Customer full name" />
                                </el-form-item>
                                
                                <el-form-item label="Customer Email" required>
                                    <el-input v-model="createForm.customer_email" placeholder="customer@example.com" />
                                </el-form-item>
                                
                                <el-form-item label="Customer Phone">
                                    <el-input v-model="createForm.customer_phone" placeholder="+1234567890" />
                                </el-form-item>
                            </el-card>
                        </el-col>
                    </el-row>
                    
                    <!-- Shipping Address -->
                    <el-card shadow="never" style="margin-bottom: 20px;">
                        <template #header>
                            <div class="card-header">
                                <span>Shipping Address</span>
                            </div>
                        </template>
                        
                        <el-row :gutter="16">
                            <el-col :span="24">
                                <el-form-item label="Full Name" required>
                                    <el-input v-model="createForm.shipping_address.name" placeholder="Recipient name" />
                                </el-form-item>
                            </el-col>
                            
                            <el-col :span="12">
                                <el-form-item label="Address Line 1" required>
                                    <el-input v-model="createForm.shipping_address.address_1" placeholder="Street address" />
                                </el-form-item>
                            </el-col>
                            
                            <el-col :span="12">
                                <el-form-item label="Address Line 2">
                                    <el-input v-model="createForm.shipping_address.address_2" placeholder="Apartment, unit, etc" />
                                </el-form-item>
                            </el-col>
                            
                            <el-col :span="8">
                                <el-form-item label="City" required>
                                    <el-input v-model="createForm.shipping_address.city" placeholder="City" />
                                </el-form-item>
                            </el-col>
                            
                            <el-col :span="8">
                                <el-form-item label="State/Province">
                                    <el-input v-model="createForm.shipping_address.state" placeholder="State" />
                                </el-form-item>
                            </el-col>
                            
                            <el-col :span="8">
                                <el-form-item label="Postal Code" required>
                                    <el-input v-model="createForm.shipping_address.postcode" placeholder="ZIP/Postal code" />
                                </el-form-item>
                            </el-col>
                            
                            <el-col :span="12">
                                <el-form-item label="Country" required>
                                    <el-input v-model="createForm.shipping_address.country" placeholder="Country" />
                                </el-form-item>
                            </el-col>
                        </el-row>
                    </el-card>
                    
                    <!-- Package Information -->
                    <el-card shadow="never" style="margin-bottom: 20px;">
                        <template #header>
                            <div class="card-header">
                                <span>Package Details (Optional)</span>
                            </div>
                        </template>
                        
                        <el-row :gutter="16">
                            <el-col :span="12">
                                <el-form-item label="Weight (kg)">
                                    <el-input-number v-model="createForm.weight_total" :min="0" :precision="2" style="width: 100%" />
                                </el-form-item>
                            </el-col>
                            
                            <el-col :span="12">
                                <el-form-item label="Shipping Cost">
                                    <el-input-number v-model="createForm.shipping_cost" :min="0" :precision="2" style="width: 100%" />
                                </el-form-item>
                            </el-col>
                            
                            <el-col :span="8">
                                <el-form-item label="Length (cm)">
                                    <el-input-number v-model="createForm.dimensions.length" :min="0" style="width: 100%" />
                                </el-form-item>
                            </el-col>
                            
                            <el-col :span="8">
                                <el-form-item label="Width (cm)">
                                    <el-input-number v-model="createForm.dimensions.width" :min="0" style="width: 100%" />
                                </el-form-item>
                            </el-col>
                            
                            <el-col :span="8">
                                <el-form-item label="Height (cm)">
                                    <el-input-number v-model="createForm.dimensions.height" :min="0" style="width: 100%" />
                                </el-form-item>
                            </el-col>
                            
                            <el-col :span="12">
                                <el-form-item label="Currency">
                                    <el-select v-model="createForm.currency" placeholder="Select currency" style="width: 100%">
                                        <el-option label="USD - US Dollar" value="USD"></el-option>
                                        <el-option label="EUR - Euro" value="EUR"></el-option>
                                        <el-option label="GBP - British Pound" value="GBP"></el-option>
                                        <el-option label="CAD - Canadian Dollar" value="CAD"></el-option>
                                    </el-select>
                                </el-form-item>
                            </el-col>
                            
                            <el-col :span="12">
                                <el-form-item label="Estimated Delivery">
                                    <el-date-picker
                                        v-model="createForm.estimated_delivery"
                                        type="date"
                                        placeholder="Estimated delivery date"
                                        format="YYYY-MM-DD"
                                        value-format="YYYY-MM-DD"
                                        style="width: 100%"
                                        :disabled-date="(time) => time.getTime() < Date.now() - 8.64e7"
                                    ></el-date-picker>
                                </el-form-item>
                            </el-col>
                            
                            <el-col :span="24">
                                <el-form-item label="Special Instructions">
                                    <el-input 
                                        v-model="createForm.special_instructions" 
                                        type="textarea" 
                                        :rows=3
                                        placeholder="Any special delivery instructions..."
                                    />
                                </el-form-item>
                            </el-col>
                        </el-row>
                    </el-card>
                </el-form>
                
                <template #footer>
                    <span class="dialog-footer">
                        <el-button @click="resetCreateForm">Cancel</el-button>
                        <el-button type="primary" @click="createShipment" :loading="creating">
                            Create Shipment
                        </el-button>
                    </span>
                </template>
            </el-dialog>


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
                    <el-form :model="importForm" label-width="140px" label-position="top">
                        
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

                    <div class="import-help">
                        <el-alert title="Keep blank for importing all" type="info" :closable="false" style="border-radius: 8px;" />
                    </div>
                    
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
                                        <p v-if="selectedShipment.order_id > 0"><strong>Order ID:</strong> #{{ selectedShipment.order_id }}</p>
                                        <p v-else><strong>Type:</strong> Manual Shipment</p>
                                        <p v-if="selectedShipment.sender_info?.name"><strong>Sender:</strong> {{ selectedShipment.sender_info.name }}</p>
                                        <p><strong>Created:</strong> {{ formatDateTime(selectedShipment.created_at) }}</p>
                                    </el-col>
                                    <el-col :span="12">
                                        <h4>Delivery Information</h4>
                                        <p><strong>Customer:</strong> {{ selectedShipment.customer_email }}</p>
                                        <p><strong>Address:</strong> {{ formatAddress(selectedShipment.delivery_address, true) }}</p>
                                        <p><strong>Estimated Delivery:</strong> {{ formatDate(selectedShipment.estimated_delivery) }}</p>
                                        <p v-if="selectedShipment.delivered_at"><strong>Delivered:</strong> {{ formatDateTime(selectedShipment.delivered_at) }}</p>
                                        
                                        <!-- Show rider information for out_for_delivery status -->
                                        <div v-if="selectedShipment.current_status === 'out_for_delivery' && selectedShipment.rider" class="rider-info-section">
                                            <h4>Assigned Rider</h4>
                                            <div class="rider-details-card">
                                                <div class="rider-avatar">
                                                    <img v-if="selectedShipment.rider.avatar_url" 
                                                         :src="selectedShipment.rider.avatar_url" 
                                                         :alt="selectedShipment.rider.rider_name" />
                                                    <div v-else class="avatar-placeholder">
                                                        {{ getInitials(selectedShipment.rider.rider_name) }}
                                                    </div>
                                                </div>
                                                <div class="rider-info-content">
                                                    <div class="rider-name">{{ selectedShipment.rider.rider_name }}</div>
                                                    <div class="rider-contact" v-if="selectedShipment.rider.phone">
                                                        📞 {{ selectedShipment.rider.phone }}
                                                    </div>
                                                    <div class="rider-vehicle" v-if="selectedShipment.rider.vehicle_type">
                                                        🚐 {{ selectedShipment.rider.vehicle_type }}
                                                    </div>
                                                    <div class="rider-rating" v-if="selectedShipment.rider.rating > 0">
                                                        ⭐ {{ selectedShipment.rider.rating.toFixed(1) }}/5.0
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
                <el-form :model="editForm" label-width="120px" v-if="selectedShipment" label-position="top">
                   <div class="current-status">
                       <span>Current Status:</span>
                       <el-tag :type="getStatusType(selectedShipment.current_status)">
                           {{ getStatusLabel(selectedShipment.current_status) }}
                       </el-tag>
                   </div>
                    
                    <el-form-item label="New Status">
                        <el-select v-model="editForm.status" placeholder="Select status" @change="onStatusChange">
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
                    
                    <!-- Show rider selection for out_for_delivery status -->
                    <el-form-item v-if="editForm.status === 'out_for_delivery'" label="Assign Rider" required>
                        <el-select 
                            v-model="editForm.rider_id" 
                            placeholder="Select a rider for delivery"
                            :loading="loadingRiders"
                            filterable
                            remote
                            :remote-method="searchRiders"
                            class="rider-select"
                        >
                            <el-option
                                v-for="rider in availableRiders"
                                :key="rider.id"
                                :label="rider.rider_name"
                                :value="rider.id"
                            >
                                <div class="rider-option">
                                    <div class="rider-info-wrapper">
                                        <div class="rider-avatar-small">
                                            <img v-if="rider.avatar_url" :src="rider.avatar_url" :alt="rider.rider_name" />
                                            <div v-else class="avatar-placeholder-small">
                                                {{ getInitials(rider.rider_name) }}
                                            </div>
                                        </div>
                                        <div class="rider-info">
                                            <p class="rider-name">{{ rider.rider_name }}</p>
                                            <div class="rider-details">
                                                <p class="rider-phone">{{ rider.phone || 'No phone' }}</p>
                                                <p class="rider-vehicle">{{ getVehicleTypeLabel(rider.vehicle_type) }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rider-rating">
                                        <el-rate v-model="rider.rating" disabled size="small" show-score score-template="{value}"/>
                                    </div>
                                </div>
                            </el-option>
                        </el-select>
                    </el-form-item>
                    
                    <!-- Show location and description for other statuses -->
                    <template v-if="editForm.status !== 'out_for_delivery'">
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
                    </template>
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
            creating: false,
            loadingStats: false,
            loadingEvents: false,
            loadingRiders: false,
            
            shipments: [],
            selectedRows: [],
            searchTimeout: null,
            availableRiders: [],
            
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
            showCreateDialog: false,
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
                description: '',
                rider_id: null
            },
            
            bulkEditForm: {
                status: '',
                location: '',
                description: ''
            },

            createForm: {
                sender_name: '',
                sender_email: '',
                sender_phone: '',
                customer_name: '',
                customer_email: '',
                customer_phone: '',
                shipping_address: {
                    name: '',
                    address_1: '',
                    address_2: '',
                    city: '',
                    state: '',
                    postcode: '',
                    country: ''
                },
                weight_total: null,
                shipping_cost: null,
                currency: 'USD',
                dimensions: {
                    length: null,
                    width: null,
                    height: null
                },
                estimated_delivery: '',
                special_instructions: ''
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
                description: '',
                rider_id: null
            };
            this.showEditDialog = true;
            
            // Load riders if needed
            if (this.availableRiders.length === 0) {
                this.loadActiveRiders();
            }
        },

        updateStatus() {
            if (!this.editForm.status) {
                this.$notifyError('Please select a status');
                return;
            }
            
            // Validate rider selection for out_for_delivery
            if (this.editForm.status === 'out_for_delivery' && !this.editForm.rider_id) {
                this.$notifyError('Please select a rider for delivery');
                return;
            }

            this.updating = true;
            
            const data = {
                status: this.editForm.status,
                location: this.editForm.location,
                description: this.editForm.description
            };
            
            // Add rider_id if status is out_for_delivery
            if (this.editForm.status === 'out_for_delivery' && this.editForm.rider_id) {
                data.rider_id = this.editForm.rider_id;
            }

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
                            type: 'success',
                            duration: 0
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
            
            // return multiline ? parts.join(', ') : parts.join(', ');
            return parts.join(', ');
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
                            type: 'success',
                            duration: 0
                        });

                        this.showImportDialog = false;
                        this.resetImportForm();

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
        },

        loadActiveRiders() {
            this.loadingRiders = true;
            this.$get('riders/active')
                .then(res => {
                    if (res.success) {
                        this.availableRiders = res.riders || [];
                    }
                })
                .catch(err => {
                    console.error('Failed to load riders:', err);
                })
                .finally(() => {
                    this.loadingRiders = false;
                });
        },
        
        searchRiders(query) {
            if (query !== '') {
                this.loadingRiders = true;
                this.$get('riders/search', { q: query, limit: 20 })
                    .then(res => {
                        if (res.success) {
                            this.availableRiders = res.riders || [];
                        }
                    })
                    .catch(err => {
                        console.error('Failed to search riders:', err);
                    })
                    .finally(() => {
                        this.loadingRiders = false;
                    });
            }
        },
        
        onStatusChange(status) {
            if (status !== 'out_for_delivery') {
                this.editForm.rider_id = null;
            }
        },
        
        getInitials(name) {
            if (!name) return 'R';
            return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
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

        createShipment() {
            if (!this.validateCreateForm()) {
                return;
            }

            this.creating = true;

            const data = {
                ...this.createForm,
                shipping_address: {
                    ...this.createForm.shipping_address,
                    name: this.createForm.shipping_address.name || this.createForm.customer_name
                }
            };

            this.$post('shipments', data)
                .then(res => {
                    if (res.success) {
                        this.$notify({
                            title: 'Success',
                            message: 'Shipment created successfully',
                            type: 'success'
                        });
                        this.resetCreateForm();
                        this.fetchShipments();
                    }
                })
                .catch(err => {
                    this.$notifyError('Failed to create shipment: ' + err.message);
                })
                .finally(() => {
                    this.creating = false;
                });
        },

        resetCreateForm() {
            this.createForm = {
                sender_name: '',
                sender_email: '',
                sender_phone: '',
                customer_name: '',
                customer_email: '',
                customer_phone: '',
                shipping_address: {
                    name: '',
                    address_1: '',
                    address_2: '',
                    city: '',
                    state: '',
                    postcode: '',
                    country: ''
                },
                weight_total: null,
                shipping_cost: null,
                currency: 'USD',
                dimensions: {
                    length: null,
                    width: null,
                    height: null
                },
                estimated_delivery: '',
                special_instructions: ''
            };
            this.showCreateDialog = false;
        },

        validateCreateForm() {
            if (!this.createForm.sender_name) {
                this.$notifyError('Sender name is required');
                return false;
            }
            if (!this.createForm.customer_name) {
                this.$notifyError('Customer name is required');
                return false;
            }
            if (!this.createForm.customer_email) {
                this.$notifyError('Customer email is required');
                return false;
            }
            if (!this.createForm.shipping_address.address_1) {
                this.$notifyError('Shipping address is required');
                return false;
            }
            if (!this.createForm.shipping_address.city) {
                this.$notifyError('City is required');
                return false;
            }
            if (!this.createForm.shipping_address.postcode) {
                this.$notifyError('Postal code is required');
                return false;
            }
            if (!this.createForm.shipping_address.country) {
                this.$notifyError('Country is required');
                return false;
            }
            return true;
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
.rider-option {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 0;

    .rider-info-wrapper {
        display: flex;
        gap: 8px;
    }
    
    .rider-avatar-small {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        
        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-placeholder-small {
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
    
    .rider-info {
        flex: 1;
        min-width: 0;
        p{
            margin: 0;
        };
        
        .rider-name {
            font-weight: 500;
            color: #303133;
        }
        
        .rider-details {
            font-size: 12px;
            color: #909399;
            display: flex;
            gap: 12px;
            padding: 0;
            
            .rider-phone, .rider-vehicle {
                flex-shrink: 0;
            }
        }
    }
    
    .rider-rating {
        flex-shrink: 0;
    }
}

:deep(.el-select-dropdown__item) {
    height: auto !important;
    padding: 8px 20px !important;
    line-height: 1.4 !important;
}

.rider-info-section {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #EBEEF5;
    
    h4 {
        margin: 0 0 12px 0;
        color: #303133;
        font-size: 14px;
        font-weight: 600;
    }
}

.rider-details-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8fbff;
    border: 1px solid #e3f2fd;
    border-radius: 8px;
    
    .rider-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        background: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        
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
            font-size: 14px;
            font-weight: 500;
        }
    }
    
    .rider-info-content {
        flex: 1;
        
        .rider-name {
            font-weight: 600;
            color: #303133;
            margin-bottom: 4px;
            font-size: 15px;
        }
        
        .rider-contact,
        .rider-vehicle,
        .rider-rating {
            font-size: 12px;
            color: #606266;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .rider-rating {
            color: #f39c12;
            font-weight: 500;
        }
    }
}
</style>
