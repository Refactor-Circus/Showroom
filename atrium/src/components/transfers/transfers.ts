import type { components, paths } from '../../../types/impex-schema'

/*
 * What the Showroom transfers screen shares (AC 54, 71; D-035, D-042): the
 * Impex flow and run payloads it reads, how it picks the flows under the
 * screen's prefix and merges their runs into the newest, and the import and
 * export forms it sends to Showroom.
 */

/** A flow `impex.flows.index` lists. */
export type TransferFlow = paths['/flows']['get']['responses'][200]['content']['application/json']['data'][number]

/** A run `impex.runs.index` lists (`RunResource`, without its owners and steps). */
export type TransferRun = Omit<components['schemas']['RunResource'], 'owners' | 'steps'>

/** How many runs the screen shows, as v1's `latest()->limit(20)`. */
export const RECENT_RUNS = 20

/** The flow prefix when the screen sends none: Atrium's shipped default (D-042). */
export const DEFAULT_FLOW_PREFIX = 'showroom:'

/** The import modes `showroom.imports.store` takes, upsert first as v1 offered them. */
export const IMPORT_MODES = ['upsert', 'create', 'update'] as const

export type ImportMode = (typeof IMPORT_MODES)[number]

/** The export formats `showroom.exports.store` takes, JSON Lines first. */
export const EXPORT_FORMATS = ['jsonl', 'csv'] as const

export type ExportFormat = (typeof EXPORT_FORMATS)[number]

/** The flow prefix of the screen's `options` (D-042), else the default. */
export function flowPrefix(options: Record<string, unknown> | null | undefined): string {
  const prefix = options?.flow_prefix

  return typeof prefix === 'string' ? prefix : DEFAULT_FLOW_PREFIX
}

/** The slugs of the flows whose name starts with `prefix`, in Impex's order. */
export function flowsUnder(flows: readonly TransferFlow[], prefix: string): string[] {
  return flows.filter(flow => flow.slug.startsWith(prefix)).map(flow => flow.slug)
}

function createdAt(run: TransferRun): number {
  const time = run.created_at ? new Date(run.created_at).getTime() : Number.NaN

  return Number.isNaN(time) ? 0 : time
}

/** The runs of every flow merged, newest first by `created_at`, at most `limit` of them. */
export function newestRuns(lists: readonly (readonly TransferRun[])[], limit = RECENT_RUNS): TransferRun[] {
  return lists.flat().sort((a, b) => createdAt(b) - createdAt(a)).slice(0, limit)
}

/** A failed run's message, from Impex's `error` payload. */
export function runError(run: TransferRun): string | null {
  const message = run.error?.message

  return typeof message === 'string' && message !== '' ? message : null
}

/** The badge colour of a run status. */
export function runStatusColor(status: TransferRun['status']): 'success' | 'error' | 'info' | 'warning' | 'neutral' {
  switch (status) {
    case 'completed':
      return 'success'
    case 'failed':
      return 'error'
    case 'running':
    case 'pending':
    case 'waiting':
      return 'info'
    case 'rolling_back':
      return 'warning'
    default:
      return 'neutral'
  }
}

/** The export form: the format, the optional family, category tree and channel, and published copies or working ones. */
export interface ExportForm {
  format: ExportFormat
  family: string | null
  category: string | null
  scope: string | null
  published: boolean
}

/** What an export sends of `form`: the format and published switch, and each filter that is picked. */
export function exportBody(form: ExportForm): { format: ExportFormat, published: boolean, family?: string, category?: string, scope?: string } {
  return {
    format: form.format,
    ...(form.family ? { family: form.family } : {}),
    ...(form.category ? { category: form.category } : {}),
    ...(form.scope ? { scope: form.scope } : {}),
    published: form.published
  }
}
