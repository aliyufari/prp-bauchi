import { reactive } from 'vue'

export interface AuthPanelLine { text: string; accent?: boolean }
export interface AuthPanel { badge: string; lines: AuthPanelLine[]; text: string; steps?: string[] }

const DEFAULT: AuthPanel = {
  badge: 'Management System',
  lines: [{ text: 'Justice,' }, { text: 'Unity', accent: true }, { text: '& Progress.' }],
  text: "The central platform for managing PRP Bauchi State's members, campaigns, elections, and financial operations.",
  steps: undefined,
}

const state = reactive<AuthPanel>({ ...DEFAULT })

/** Call from a page's setup to set the brand panel copy. Anything omitted resets to default. */
export function useAuthPanel(panel?: Partial<AuthPanel>) {
  if (panel) Object.assign(state, DEFAULT, panel)

  return state
}