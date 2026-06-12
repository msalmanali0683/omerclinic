import { onBeforeUnmount } from 'vue';

export function useDebouncedCallback(callback, delay = 350) {
  let timer = null;

  function cancel() {
    if (timer !== null) {
      clearTimeout(timer);
      timer = null;
    }
  }

  function debounced(...args) {
    cancel();
    timer = setTimeout(() => {
      timer = null;
      callback(...args);
    }, delay);
  }

  function flush(...args) {
    cancel();
    callback(...args);
  }

  onBeforeUnmount(cancel);

  return { debounced, cancel, flush };
}
