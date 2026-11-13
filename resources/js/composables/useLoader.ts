import { computed, ref } from 'vue'

/**
 * Global page loader state.
 *
 * Several navigations can overlap (a new visit interrupts the previous one, and
 * Inertia still fires `finish` for the interrupted visit). A counter keeps the
 * overlay up until every tracked navigation has settled, instead of a boolean
 * that the first `finish` would switch off too early.
 */
const pending = ref(0)
const isLoading = computed(() => pending.value > 0)

export function useLoader() {
    const start = (): void => {
        pending.value += 1
    }

    const finish = (): void => {
        pending.value = Math.max(0, pending.value - 1)
    }

    /** Hard reset - used by the failsafe and when the page is restored from bfcache. */
    const reset = (): void => {
        pending.value = 0
    }

    return { isLoading, start, finish, reset }
}
