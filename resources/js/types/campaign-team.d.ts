export interface CampaignTeam {
  id: string
  name: string
  description: string | null
  status: 'active' | 'inactive'

  aspirant_id: string
  aspirant_name: string | null
  aspirant_office: string | null

  team_lead_id: string | null
  team_lead_name: string | null

  member_count: number
  can_manage: boolean

  created_at: string
}

export interface CampaignTeamMember {
  id: string
  user_id: string
  first_name: string
  last_name: string
  email: string
  avatar_url?: string | null
  phone: string | null
  role_in_team: string
  status: 'active' | 'inactive'
  joined_date: string | null
  campaign_team_id: string
  created_at: string
}

export interface CampaignTeamStatistics {
  total: number
  active: number
  total_members: number
  unassigned_lead: number
}

export interface AspirantOption {
  id: string
  name: string
  office: string | null
}

export interface UserOption {
  id: string
  name: string
}

export interface CampaignTeamFilterOptions {
  aspirants: AspirantOption[]
  statuses: { value: string; label: string }[]
}