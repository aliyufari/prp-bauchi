export type SelectValue = string | number

export interface SelectOption {
  label: string
  value: SelectValue
  description?: string
  disabled?: boolean
  group?: string
}
