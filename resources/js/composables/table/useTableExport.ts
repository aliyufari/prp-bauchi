export type TableExportColumn<T> = {
  key: keyof T | string
  label: string
  value?: (row: T) => unknown
}

export type TableExportFormat = 'csv' | 'xlsx'

function escapeCsv(value: unknown): string {
  const text = value == null ? '' : String(value)
  return /[",\r\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text
}

function xmlEscape(value: unknown): string {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;')
}

function columnName(index: number): string {
  let name = ''
  let n = index + 1
  while (n > 0) {
    const remainder = (n - 1) % 26
    name = String.fromCharCode(65 + remainder) + name
    n = Math.floor((n - 1) / 26)
  }
  return name
}

function crc32(bytes: Uint8Array): number {
  let crc = -1
  for (const byte of bytes) {
    crc ^= byte
    for (let bit = 0; bit < 8; bit++) {
      crc = (crc >>> 1) ^ (-(crc & 1) & 0xedb88320)
    }
  }
  return (crc ^ -1) >>> 0
}

function zipStored(files: Array<{ name: string; content: string }>): Blob {
  const encoder = new TextEncoder()
  const localParts: Uint8Array[] = []
  const centralParts: Uint8Array[] = []
  let offset = 0

  for (const file of files) {
    const name = encoder.encode(file.name)
    const data = encoder.encode(file.content)
    const crc = crc32(data)

    const local = new Uint8Array(30 + name.length + data.length)
    const lv = new DataView(local.buffer)
    lv.setUint32(0, 0x04034b50, true)
    lv.setUint16(4, 20, true)
    lv.setUint32(14, crc, true)
    lv.setUint32(18, data.length, true)
    lv.setUint32(22, data.length, true)
    lv.setUint16(26, name.length, true)
    local.set(name, 30)
    local.set(data, 30 + name.length)
    localParts.push(local)

    const central = new Uint8Array(46 + name.length)
    const cv = new DataView(central.buffer)
    cv.setUint32(0, 0x02014b50, true)
    cv.setUint16(4, 20, true)
    cv.setUint16(6, 20, true)
    cv.setUint32(16, crc, true)
    cv.setUint32(20, data.length, true)
    cv.setUint32(24, data.length, true)
    cv.setUint16(28, name.length, true)
    cv.setUint32(42, offset, true)
    central.set(name, 46)
    centralParts.push(central)
    offset += local.length
  }

  const centralSize = centralParts.reduce((sum, part) => sum + part.length, 0)
  const end = new Uint8Array(22)
  const ev = new DataView(end.buffer)
  ev.setUint32(0, 0x06054b50, true)
  ev.setUint16(8, files.length, true)
  ev.setUint16(10, files.length, true)
  ev.setUint32(12, centralSize, true)
  ev.setUint32(16, offset, true)

  return new Blob([...localParts, ...centralParts, end], {
    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  })
}

function download(blob: Blob, filename: string): void {
  const url = URL.createObjectURL(blob)
  const anchor = document.createElement('a')
  anchor.href = url
  anchor.download = filename
  anchor.style.display = 'none'
  document.body.appendChild(anchor)
  anchor.click()
  anchor.remove()
  URL.revokeObjectURL(url)
}

export function useTableExport<T extends object>() {
  function normalizeColumns(rows: T[], columns?: TableExportColumn<T>[]): TableExportColumn<T>[] {
    return columns?.length
      ? columns
      : Object.keys((rows[0] ?? {}) as unknown as Record<string, unknown>)
          .map((key): TableExportColumn<T> => ({ key, label: key }))
  }

  function exportCsv(
    rows: T[],
    columns?: TableExportColumn<T>[],
    filename = 'table-export',
  ): void {
    const selected = normalizeColumns(rows, columns)
    const lines = [
      selected.map((column) => escapeCsv(column.label)).join(','),
      ...rows.map((row) =>
        selected
          .map((column) =>
            escapeCsv(column.value ? column.value(row) : (row as unknown as Record<string, unknown>)[String(column.key)]),
          )
          .join(','),
      ),
    ]
    download(
      new Blob(['\ufeff', lines.join('\r\n')], { type: 'text/csv;charset=utf-8' }),
      `${filename}.csv`,
    )
  }

  function exportXlsx(
    rows: T[],
    columns?: TableExportColumn<T>[],
    filename = 'table-export',
  ): void {
    const selected = normalizeColumns(rows, columns)
    const allRows = [
      selected.map((column) => column.label),
      ...rows.map((row) =>
        selected.map((column) =>
          column.value ? column.value(row) : (row as unknown as Record<string, unknown>)[String(column.key)],
        ),
      ),
    ]

    const sheetRows = allRows.map((row, rowIndex) => {
      const cells = row.map((value, columnIndex) => {
        const ref = `${columnName(columnIndex)}${rowIndex + 1}`
        if (typeof value === 'number' && Number.isFinite(value)) {
          return `<c r="${ref}"><v>${value}</v></c>`
        }
        return `<c r="${ref}" t="inlineStr"><is><t xml:space="preserve">${xmlEscape(value)}</t></is></c>`
      }).join('')
      return `<row r="${rowIndex + 1}">${cells}</row>`
    }).join('')

    const sheet = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>${sheetRows}</sheetData></worksheet>`

    const files = [
      { name: '[Content_Types].xml', content: `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>` },
      { name: '_rels/.rels', content: `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>` },
      { name: 'xl/workbook.xml', content: `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Export" sheetId="1" r:id="rId1"/></sheets></workbook>` },
      { name: 'xl/_rels/workbook.xml.rels', content: `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>` },
      { name: 'xl/worksheets/sheet1.xml', content: sheet },
    ]

    download(zipStored(files), `${filename}.xlsx`)
  }

  function exportData(
    rows: T[],
    columns: TableExportColumn<T>[] | undefined,
    format: TableExportFormat,
    filename = 'table-export',
  ): void {
    if (format === 'xlsx') exportXlsx(rows, columns, filename)
    else exportCsv(rows, columns, filename)
  }

  return { exportCsv, exportXlsx, exportData }
}
