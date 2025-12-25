<template>
    <span v-if="errorMessage" class="el-form-item__error">
        {{ errorMessage }}
    </span>
</template>

<script>
export default {
    name: 'Error',
    props: ['name', 'field'],
    computed: {
        validationErrors() {
            const config =  this.$root.$.appContext.config;
            return config.globalProperties.$validationErrors;
        },
        errorMessage() {
            // If field is a string key
            if (typeof this.field === 'string') {
                const err = this.validationErrors[this.field];
                return err ? Object.values(err)[0] : null;
            }

            // If field is an object (already contains errors)
            if (typeof this.field === 'object') {
                return Object.values(this.field)[0] ?? null;
            }

            if (this.name) {
                const err = this.validationErrors[this.name];
                return err ? Object.values(err)[0] : null;
            }

            return null;
        }
    }
};
</script>
