import app from '@/bootstrap';
import router from '@/router';
import config from '@/bootstrap/config';
import { ElLoading } from 'element-plus';

import 'element-plus/theme-chalk/el-loading.css';
import 'element-plus/theme-chalk/el-message-box.css';
import 'element-plus/theme-chalk/el-notification.css';


if (typeof __webpack_public_path__ !== 'undefined') {
	if (config && config.asset_url) {
		__webpack_public_path__ = config.asset_url;
	}
}

app.vue.use(ElLoading);

app.vue.use(router(app.vue, config.routes));

app.vue.mount('#fluent-framework-app');

window.fluentFrameworkAdmin = {};
