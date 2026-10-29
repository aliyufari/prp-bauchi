export interface PartyVotes {
  [party: string]: number
}

export interface Result {
  id: string
  pu_id: string
  pu: string | null
  pu_code: string | null
  ward: string | null
  lga: string | null
  zone: string | null
  state: string | null

  office: string | null
  office_label: string | null
  election_date: string | null

  registered_voters: number
  accredited_voters: number
  rejected_votes: number
  party_votes: PartyVotes
  total_valid_votes: number

  leading_party: string | null
  leading_votes: number

  status: 'pending' | 'verified' | 'disputed'
  status_label: string | null
  result_sheet_url: string | null
  remarks: string | null

  uploaded_by: string
  uploaded_by_name: string | null
  verified_by_name: string | null
  verified_at: string | null

  can_edit: boolean
  can_verify: boolean

  created_at: string
}

export interface ResultStatistics {
  total: number
  verified: number
  pending: number
  disputed: number
}

export interface OfficeOption { value: string; label: string }
export interface PartyOption { value: string; label: string }
export interface LocationOption { id: string; name: string }

export interface ResultFilterOptions {
  offices: OfficeOption[]
  statuses: { value: string; label: string }[]
  states: LocationOption[]
}