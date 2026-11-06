import type { SelectOption } from '@/components/ui/form/types'

export type LocationLevel = 'state' | 'zone' | 'lga' | 'ward' | 'pu'
export type LocationId = string | number | null
export type LocationField = `${LocationLevel}_id`
export type LocationModel = Partial<Record<LocationField, LocationId>>

export interface LocationApiOption {
  id: string | number
  name: string
  code?: string | null
  registered_voters?: number | null
}

export type LocationSelectOption = SelectOption & {
  description?: string
}
