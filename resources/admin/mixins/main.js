import { capitalize } from 'vue';
import dayjs from 'dayjs';
import Rest from '@/utils/http/Rest.js';
import Storage from '@/utils/Storage';
import { ElNotification, ElMessageBox } from 'element-plus';

const config = window.fluentFrameworkAdmin;

const convertToText = function (obj) {
    const result = [];

    if (obj === null || obj === undefined) {
        return '';
    }

    if (
        typeof obj === 'string' ||
        typeof obj === 'number' ||
        typeof obj === 'boolean'
    ) {
        result.push(String(obj));
    } else if (Array.isArray(obj)) {
        obj.forEach(item => result.push(convertToText(item)));
    } else if (typeof obj === 'object') {
        Object.values(obj).forEach(value => result.push(convertToText(value)));
    }

    return result.join('<br />');
};

const titleCase = function (str) {
    return str
        .replace(/([a-z])([A-Z])/g, '$1 $2')
        .replace(/[_\-]+/g, ' ')
        .replace(/\b\w/g, char => char.toUpperCase())
        .replace(/\s+/g, ' ')
        .trim();
};

const notify = function (type, title, message, position = 'top-right') {
    ElNotification({ type, title, message, position, offset: 20 });
}

export default {
    data() {
        return {
            storage: Storage,
        }
    },
    methods: {
        $get: Rest.get,
        $post: Rest.post,
        $put: Rest.put,
        $patch: Rest.patch,
        $del: Rest.delete,
        $alert:ElMessageBox.alert,
        $confirm:ElMessageBox.confirm,
        $capitalize: capitalize,
        $convertToText: convertToText,
        $titleCase: titleCase,
        $download: (route) => {
            let baseUrl = '';

            try {
                new URL(route);
            } catch {
                baseUrl = config.rest.url;
            }

            if (route.startsWith('/') && baseUrl.endsWith('/')) {
                route = route.substring(1);
            }

            window.location.href = baseUrl + route;
        },
        $formatNumber(amount, hideEmpty = false) {
            if (!amount && hideEmpty) {
                return '';
            }

            if (!amount) {
                amount = '0';
            }

            return new Intl.NumberFormat('en-US').format(amount)
        },
        $formatCurrency(value, locale = 'en-US', currency = 'USD') {
            if (value == null) return '';
            
            return new Intl.NumberFormat(
                locale, { style: 'currency', currency }
            ).format(value);
        },
        $defaultDate(format = 'DD-MM-YYYY') {
            return dayjs().format(format);
        },
        $formatDate(date, format = 'DD-MM-YYYY') {
            return dayjs(date).format(format);
        },
        $changeTitle(title) {
            document.querySelector(
                'head title'
            ).textContent = title + ' - ' + titleCase(config.slug);
        },
        $handleError(e) {
            if (e.status === 422) {
                return this.$root?.$validationErrors
                    ? this.$root.$handleValidationError(e)
                    : null;
            }

            let type = 'error';
            
            let errorMessage = '';

            if (typeof e === 'string') {
                errorMessage = e;
            } else if ('message' in e) {
                errorMessage = e.message;
            } else {
                if (e.isAborted) {
                    type = 'warning';
                    errorMessage = e.message || 'Request aborted.';
                } else if (e.status === 401) {
                    type = 'warning';
                    errorMessage = e.message || 'Resource not found.';
                }
            }

            notify(type, 'Error', errorMessage || 'Something went wrong');
        },
        $url(url) {
            return config.rest.url.replace(
                /\/+$/, ''
            ) + '/' + url.replace(/^\/+/, '');
        },
        $notify(message, type = 'info', position = 'top-right') {
            const title = type === 'info' ? 'Notification' : type;
            notify(type, capitalize(title), message, position);
        },
        $notifySuccess(message, position = 'top-right') {
            notify('success', 'Success', message, position);
        },
        $notifyError(message, position = 'top-right') {
            notify('error', 'Error', message, position);
        },
        $notifyWarning(message, position = 'top-right') {
            notify('warning', 'Warning', message, position);
        },
        $getStatusColumnClassName({ row, column }) {
            if (column.label === 'Status') {
                return 'capitalize';
            }
        },
    }
};
