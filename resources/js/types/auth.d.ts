export type LocationKey = 'state' | 'zone' | 'lga' | 'ward' | 'pu'

export interface LockedLocation { id: string; name: string }

export interface Jurisdiction {
  level: LocationKey | null          // null = no access (or unrestricted, see flag)
  id: string | null
  name: string | null                // "Bauchi North"
  unrestricted: boolean              // super_admin / admin
  locked: Partial<Record<LocationKey, LockedLocation>> // levels at/above the user's, fixed
}

export interface AuthPayload {
  user: { id: string; first_name: string; last_name: string; email: string; avatar_url: string | null }
  roles: string[]
  permissions: string[]
  jurisdiction: Jurisdiction
  navigation: import('./navigation').NavGroup[]
  layout: 'app' | 'portal'
  home: string                       // where this user lands after login
}