export default {
    install(app, { alpha = 0.3, lighten = 0.9, cssVar = '--el-bg-color' } = {}) {

        function getOverlayColor() {
            const color = app.config.globalProperties.$getCssVar(cssVar);
            
            if (!color) return '';

            const rgb = color.replace('#', '')
                .match(/.{1,2}/g)?.map(x => parseInt(x, 16));
            
            if (!rgb || rgb.length < 3) return '';

            const adjusted = rgb.map(c => 
                Math.min(
                    255, Math.max(
                        0, Math.round(c + (255 - c) * (1 - lighten))
                    )
                )
            );

            return `rgba(${adjusted.join(',')}, ${alpha})`;
        }

        function applyOverlay(mask) {
            if (!mask) return;

            const color = getOverlayColor();

            if (!color) return;
            
            requestAnimationFrame(() => {
                mask.style.backgroundColor = color;
            });
        }

        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node instanceof HTMLElement && node.classList.contains('el-loading-mask')) {
                        applyOverlay(node);
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }
};
