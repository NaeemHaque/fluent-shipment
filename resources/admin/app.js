import app from '@/bootstrap';
import { capitalize } from 'vue';
import { ElNotification } from 'element-plus';

const globals = app.vue.config.globalProperties;

window.addEventListener('offline', () => {
	ElNotification({
		type: 'warning',
		position: 'bottom-right',
		message: 'You are currently offline.'
	});
});

window.addEventListener('online', () => {
	ElNotification({
		type: 'success',
		position: 'bottom-right',
		message: 'Your connection has been restored.'
	});
});

jQuery(document).on('heartbeat-tick', (event, data) => {
	const slug = globals.appVars.slug;
    if (data[slug]) {
    	if (typeof wpApiSettings !== 'undefined') {
            wpApiSettings.nonce = data[slug];
        }
		globals.appVars.rest.nonce = data[slug];
    }
});
