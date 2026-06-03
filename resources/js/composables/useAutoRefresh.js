import { onMounted, onUnmounted } from 'vue';

/**
 * Poll a callback while the component is mounted.
 * Pauses when the browser tab is hidden; refreshes immediately when tab becomes visible.
 */
export function useAutoRefresh(callback, intervalMs = 15000) {
    let timer = null;

    function tick() {
        if (document.visibilityState !== 'visible') {
            return;
        }

        callback();
    }

    function onVisibilityChange() {
        if (document.visibilityState === 'visible') {
            callback();
        }
    }

    onMounted(() => {
        timer = setInterval(tick, intervalMs);
        document.addEventListener('visibilitychange', onVisibilityChange);
    });

    onUnmounted(() => {
        if (timer) {
            clearInterval(timer);
        }

        document.removeEventListener('visibilitychange', onVisibilityChange);
    });
}
