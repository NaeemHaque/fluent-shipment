<template>
    <transition name="fade" mode="out-in" appear>
        <div class="fluent-shipment-wrapper">
            <div class="page-header">
               <h3>All Shipments</h3>
            </div>
            <div class="fluent_shipment_card">
                <div class="table_wrap">
                    <el-table
                        :data="shipments"
                        style="width:100%;margin-top:10px;color:var(--el-color-info-dark-2);"
                        class="fluent_shipment_table"
                    >
                        <el-table-column prop="id" label="ID" width="80" />
                        <el-table-column prop="order_id" label="Order ID" />
                        <el-table-column prop="order_source" label="Order Source" />
                        <el-table-column prop="tracking_number" label="Tracking Number" />
                        <el-table-column prop="current_status" label="Current Status" />
                        <el-table-column prop="delivery_address" label="Delivery Address" />
                        <el-table-column prop="estimated_delivery" label="Estimated Delivery" />
                        <el-table-column prop="delivered_at" label="Delivered At" />
                        <el-table-column prop="customer_id" label="Customer ID" />
                    </el-table>
                </div>
            </div>
        </div>
    </transition>
</template>

<script type="text/javascript">
export default {
    name: 'Shipments',
    data() {
        return {
            loading: false,
            shipments: [],
            pagination: {
                page: 1,
                limit: 10,
                total: 0
            }
        }
    },
    mounted() {
        this.fetchShipments();
    },
    methods: {
        fetchShipments() {
            this.loading = true;
            this.$get('shipments', this.pagination)
                .then(res => {
                    this.shipments = res.shipments;
                    // this.pagination = res.data.pagination;
                })
                .catch(err => {
                    this.$notifyError(err.message);
                })
                .finally(() => {
                    this.loading = false;
                });
        }
    }
};
</script>
