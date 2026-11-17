import type { LocationOption } from '@/types/member'

export interface PartyAgent {
  id: string
  first_name: string
  middle_name: string | null
  last_name: string
  email: string
  avatar_url: string | null
  status: 'active' | 'inactive'
  agent_no: string | null
  phone: string | null
  gender: string | null
  occupation: string | null
  education_status: string | null
  agent_type: string | null
  agent_type_label: string | null
  state_id: string | null
  zone_id: string | null
  lga_id: string | null
  ward_id: string | null
  pu_id: string | null
  state: string | null
  zone: string | null
  lga: string | null
  ward: string | null
  pu: string | null
  deployed: boolean
  created_at: string
}

export interface PartyAgentStatistics {
  total: number
  active: number
  deployed: number
  unassigned: number
}

export interface PartyAgentTypeOption {
  value: string
  label: string
}

export interface PartyAgentFilterOptions {
  wards: LocationOption[]
  statuses: { value: string; label: string }[]
  types: PartyAgentTypeOption[]
}
