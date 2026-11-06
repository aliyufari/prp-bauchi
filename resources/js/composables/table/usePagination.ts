import { ref } from 'vue'

/** Inertia list pages in this project use 15 as their backend pagination default. */
export function usePagination(initialPerPage = 15) {
  const page = ref(1)
  const perPage = ref(initialPerPage)
  return { page, perPage }
}
