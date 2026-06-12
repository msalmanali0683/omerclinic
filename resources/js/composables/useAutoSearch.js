import { onBeforeUnmount, unref, watch } from 'vue';
import { useDebouncedCallback } from '@/composables/useDebouncedCallback';

export const SEARCH_DEBOUNCE_MS = 350;

function resolveQueryGetter(querySource) {
  if (typeof querySource === 'function') {
    return querySource;
  }

  return () => unref(querySource);
}

/**
 * Debounced search while typing. Skips API calls until query meets minLength (empty query always runs).
 */
export function useAutoSearch(querySource, runSearch, options = {}) {
  const delay = options.delay ?? SEARCH_DEBOUNCE_MS;
  const minLength = options.minLength ?? 0;
  const getQuery = resolveQueryGetter(querySource);

  function shouldSearch() {
    const query = String(getQuery() ?? '').trim();
    return query.length === 0 || query.length >= minLength;
  }

  const { debounced, cancel, flush: flushNow } = useDebouncedCallback(() => {
    if (!shouldSearch()) {
      return;
    }
    runSearch();
  }, delay);

  const stop = watch(getQuery, debounced);

  onBeforeUnmount(() => {
    cancel();
    stop();
  });

  function flush() {
    if (!shouldSearch()) {
      return;
    }
    flushNow();
  }

  return { cancel, flush, debounced };
}
