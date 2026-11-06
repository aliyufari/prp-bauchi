import { ref } from 'vue'

export function useFiltering() {
  const search = ref('')
  const filters = ref<Record<string, string>>({})
  return { search, filters }
}
