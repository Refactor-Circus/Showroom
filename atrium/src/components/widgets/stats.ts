import { integrationData } from '@refactor-circus/atrium'
import { type AtriumIntegrationQuery, useIntegrationQuery } from '@refactor-circus/atrium'
import type { operations } from '../../../../client/src/schema'

/*
 * What the four Showroom widgets share (AC 60, D-029): one
 * `showroom.stats.show` request per dashboard load, however many of them are
 * placed, and the layer paths they link to.
 */

/** What `showroom.stats.show` answers under `data`. */
export type ShowroomStats = operations['showroom.stats.show']['responses'][200]['content']['application/json']['data']

export type ShowroomProductStatus = keyof ShowroomStats['status']['counts']

/** Every product status, in workflow order. */
export const PRODUCT_STATUSES: readonly ShowroomProductStatus[] = ['draft', 'in_review', 'approved', 'archived']

type BadgeColor = 'primary' | 'success' | 'info' | 'neutral'

/** A product status's colour, as v1's `Badges::forStatus()` gave it. */
export function statusColor(status: ShowroomProductStatus): BadgeColor {
  switch (status) {
    case 'in_review':
      return 'info'
    case 'approved':
      return 'success'
    case 'draft':
      return 'primary'
    default:
      return 'neutral'
  }
}

/** `showroom.stats.show`, shared by every Showroom widget on the dashboard. */
export function useShowroomStats(): AtriumIntegrationQuery<ShowroomStats> {
  return useIntegrationQuery('showroom', 'showroom.stats.show', async client => integrationData(await client.GET('/stats')).data)
}

/** The layer path of a product's page, by its id. */
export function productPath(id: string): string {
  return `/showroom/products/${encodeURIComponent(id)}`
}

/** The layer path of a product model's page, by its code. */
export function productModelPath(code: string): string {
  return `/showroom/product-models/${encodeURIComponent(code)}`
}

/** A timestamp as the user's locale shows it, or null. */
export function formatDate(value: string | null | undefined): string | null {
  if (!value) {
    return null
  }

  const date = new Date(value)

  return Number.isNaN(date.getTime()) ? value : date.toLocaleString()
}
