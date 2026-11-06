import { ref } from 'vue'

// Module-level state makes the loader available to the Inertia bootstrap and
// any component that needs to coordinate with a genuine page transition.
const isLoading = ref(false)

export function useLoader() {
    const start = (): void => {
        isLoading.value = true
    }

    const finish = (): void => {
        isLoading.value = false
    }

    return { isLoading, start, finish }
}
