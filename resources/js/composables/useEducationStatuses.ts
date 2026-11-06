import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export interface EducationStatusOption {
  value: string
  label: string
}

export function useEducationStatuses() {
  const page = usePage<{ educationStatuses?: EducationStatusOption[] }>()
  return computed(() => page.props.educationStatuses ?? [])
}
