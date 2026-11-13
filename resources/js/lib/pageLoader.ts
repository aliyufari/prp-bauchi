import { router } from '@inertiajs/vue3'
import { useLoader } from '@/composables/useLoader'

/**
 * Wires the global page loader to Inertia.
 *
 * The overlay is ONLY for real page navigations (sidebar links, `router.visit`,
 * `router.get` to another page). It is never shown for:
 *   - form submits / deletes / bulk actions (anything that is not a GET)
 *   - table search, filter, sort and pagination (`preserveState` GETs)
 *   - partial reloads (`only` / `except`, `router.reload()`)
 *   - prefetches and background (`async`) visits
 *   - visits that opt out with the `X-Loader-Scope` header
 */

/** Wait this long before showing the overlay so quick navigations never flash it. */
const SHOW_DELAY_MS = 150
/** Never leave the overlay up forever if a `finish` event is somehow missed. */
const FAILSAFE_MS = 30_000

type VisitLike = {
    method?: string
    preserveState?: boolean | string | ((page: unknown) => boolean)
    only?: string[]
    except?: string[]
    async?: boolean
    prefetch?: boolean
    headers?: Record<string, string>
}

/** Pure function of the visit so it gives the same answer on `start` and `finish`. */
export function isPageNavigation(visit: VisitLike | undefined): boolean {
    if (!visit) return false
    if ((visit.method ?? 'get').toLowerCase() !== 'get') return false
    if (visit.preserveState) return false
    if (visit.only?.length || visit.except?.length) return false
    if (visit.async || visit.prefetch) return false

    const headers = visit.headers ?? {}
    const scope = headers['X-Loader-Scope'] ?? headers['x-loader-scope']
    if (scope) return false
    if ((headers['X-Datatable'] ?? headers['x-datatable']) === 'true') return false

    return true
}

export function setupPageLoader(): void {
    const loader = useLoader()
    let showTimer: ReturnType<typeof setTimeout> | null = null
    let failsafeTimer: ReturnType<typeof setTimeout> | null = null
    let active = 0
    let visible = false

    const clearTimers = () => {
        if (showTimer) clearTimeout(showTimer)
        if (failsafeTimer) clearTimeout(failsafeTimer)
        showTimer = failsafeTimer = null
    }

    const hide = () => {
        clearTimers()
        if (visible) loader.finish()
        visible = false
        active = 0
    }

    // `start` (not `before`) fires only when the request really begins, so a
    // visit cancelled in `before` can never leave the overlay stuck.
    router.on('start', (event) => {
        if (!isPageNavigation(event.detail.visit as VisitLike)) return
        active += 1
        if (visible || showTimer) return

        showTimer = setTimeout(() => {
            showTimer = null
            if (active === 0) return
            visible = true
            loader.start()
            failsafeTimer = setTimeout(hide, FAILSAFE_MS)
        }, SHOW_DELAY_MS)
    })

    // `finish` is guaranteed for success, validation errors, cancellation,
    // interruption and failures, so it is the single cleanup path.
    router.on('finish', (event) => {
        if (!isPageNavigation(event.detail.visit as VisitLike)) return
        active = Math.max(0, active - 1)
        if (active === 0) hide()
    })

    // Restoring from the back/forward cache must never resurrect the overlay.
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            hide()
            loader.reset()
        }
    })
}
