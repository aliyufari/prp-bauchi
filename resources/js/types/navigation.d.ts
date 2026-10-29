export interface NavItem {
  label: string
  href: string
  icon: string
  exact?: boolean
  featured?: boolean   // the Dashboard link: slightly larger and bolder
  ready: boolean       // false = module not built yet, shown as "Soon"
  badge?: number | null
}
export interface NavGroup { group: string | null; items: NavItem[] }