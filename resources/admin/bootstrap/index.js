import Application from './Application';
import cssVarPlugin from './plugins/cssVar';
import LoadingOverlayPlugin from './plugins/loadingOverlay';
import VueApplication from "@/components/Application";

export default window.FLUENTAPP = Application.create(
	VueApplication, [
		cssVarPlugin,
		[
			LoadingOverlayPlugin, {
				alpha: 0.3,
				lighten: 0.9,
				cssVar: '--el-bg-color'
			}
		],
	]
);
