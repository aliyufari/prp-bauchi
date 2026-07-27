export interface PollingUnit {
  id: string
  name: string
  code: string | null
  address: string | null
  registered_voters: number

  ward_id: string | null
  ward: string | null

  agents_deployed: number

  created_at: string
}

export interface PollingUnitStatistics {
  total: number
  total_registered_voters: number
  wards_covered: number
  unassigned: number
}

export interface WardOption {
  id: string
  name: string
}

export interface PollingUnitFilterOptions {
  wards: WardOption[]
}