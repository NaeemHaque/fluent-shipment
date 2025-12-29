<template>
    <div class="pagination-wrapper">
        <div class="pagination-left">
            <el-pagination
                v-model:current-page="pagination.current_page"
                class="fluent-shipment-pagination fluent-shipment-pagination-sizes"
                :background="false"
                layout="total, sizes"
                :page-sizes="pageSizes"
                :page-size="pagination.per_page"
                :total="pagination.total"
                @size-change="changeSize"
            />
        </div>

        <div class="pagination-right">
            <el-pagination
                v-model:current-page="pagination.current_page"
                class="fluent-shipment-pagination fluent-shipment-pagination-pages"
                :background="false"
                layout="prev, pager, next"
                :hide-on-single-page="hideOnSingle"
                :page-size="pagination.per_page"
                :total="pagination.total"
                @current-change="changePage"
            />
        </div>
    </div>
</template>

<script type="text/babel" setup>

import {computed} from 'vue';

const props = defineProps({

    pagination: {
        required: true,
        type: Object
    },

    extraSizes: {
        required: false,
        type: Array,
        default: () => ([])
    },

    hideOnSingle: {
        required: false,
        type: Boolean,
        default: () => true
    }
});

const emit = defineEmits(['update:pagination', 'per_page_change']);

const pagination = computed({
    get: () => props.pagination,
    set: (value) => emit('update:pagination', value)
});

const pageSizes = computed(() => {
    const sizes = [];

    if (props.pagination.per_page < 10) {
        sizes.push(props.pagination.per_page);
    }

    const defaults = [
        10,
        20,
        50,
        80,
        100,
        120,
        150
    ];

    return [...sizes, ...defaults, ...props.extraSizes];
});

const changePage = (page) => {
    const updatedPagination = { ...props.pagination, current_page: page };
    emit('update:pagination', updatedPagination);
};

const changeSize = (size) => {
    const updatedPagination = { ...props.pagination, per_page: size, current_page: 1 };
    emit('update:pagination', updatedPagination);
    emit('per_page_change', size);
};

</script>

<style lang="scss">
.pagination-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.pagination-left {
    display: flex;
    align-items: center;
}

.pagination-right {
    display: flex;
    align-items: center;
}

.el-pagination {
    padding: 0;
    margin: 0;
    gap: 5px;

    .el-pagination__sizes {
        .el-select .el-select__wrapper {
            border-radius: 8px;
            border: 1px solid #E1E4EA;
            background: var(--fluentshipment-primary-bg);
            box-shadow: 0 1px 2px 0 rgba(10, 13, 20, 0.03);
        }
    }

    button {
        border-radius: 8px;
        color: var(--fluentshipment-secondary-text);
        background: var(--fluentshipment-primary-bg);
        font-size: 14px;
        margin: 0;
    }

    ul {
        display: flex;
        align-items: center;

        li {
            background: none;
            border-radius: 8px;
            font-weight: 400;
            color: var(--fluentshipment-secondary-text);
            font-size: 14px;
            margin: 0;

            &.is-active {
                background: var(--el-color-primary);
                font-weight: 400;
                color: var(--el-button-color);
            }
        }
    }
}

// Optional: Add some spacing between elements
.fluent-shipment-pagination-sizes {
    margin-right: 10px;
}

.fluent-shipment-pagination-pages {
    margin-left: 10px;
}
</style>
