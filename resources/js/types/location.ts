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
  /** Optional ancestor ids; copied onto the record when the API provides them. */
  state_id?: string | number | null
  zone_id?: string | number | null
  lga_id?: string | number | null
  ward_id?: string | number | null
}

export type LocationSelectOption = SelectOption & {
  description?: string
}
