@ -1,98 +0,0 @@
<template>
    <el-pagination
        class="fluent-pagination"
        :background="false"
        layout="total, sizes, prev, pager, next"
        @current-change="changePage"
        @size-change="changeSize"
        :hide-on-single-page="hide_on_single"
        :current-page.sync="current_page"
        :page-sizes="page_sizes"
        :page-size="per_page"
        :total="total"
    />
</template>

<script type="text/babel">
export default {
    name: 'Pagination',
    props: {
        pagination: {
            required: true,
            type: Object
        },
        extra_sizes: {
            required: false,
            type: Array,
            default() {
                return [];
            }
        },
        hide_on_single: {
            required: false,
            type: Boolean,
            default() {
                return false;
            }
        }
    },
    computed: {
        current_page: {
            get() {
                return +(
                    this.pagination.page ??
                    this.pagination.current_page ??
                    1
                );
            },
            set(value) {
                this.pagination.current_page = value;

                if ('page' in this.pagination) {
                    this.pagination.page = value;
                }
            }
        },
        per_page: {
            get() {
                return +(
                    this.pagination.limit ??
                    this.pagination.per_page ??
                    10
                );
            },
            set(value) {
                this.pagination.per_page = value;

                if ('limit' in this.pagination) {
                    this.pagination.limit = value;
                }
            }
        },
        total() {
            return +this.pagination.total;
        },
        page_sizes() {
            const sizes = [];
            if (this.per_page < 10) sizes.push(this.per_page);
            const defaults = [10, 20, 50, 80, 100, 120, 150];
            return [...sizes, ...defaults, ...(this.extra_sizes || [])];
        }
    },
    methods: {
        changePage(page) {
            this.current_page = page;
            this.$emit('fetch');
        },
        changeSize(size) {
            this.per_page = size;

            this.current_page = 1;

            this.$emit('per_page_change', size);

            this.$emit('fetch');
        }
    }
}
</script>
