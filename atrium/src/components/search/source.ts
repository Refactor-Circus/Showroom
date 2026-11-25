import { integrationData, useAtriumIntegration } from '@refactor-circus/atrium'
import type { AtriumSearchResult, AtriumSearchSourceResolver } from '@refactor-circus/atrium'
import type { components } from '../../../../client/src/schema'

/*
 * Showroom's ⌘K source, keeping its v1 key `showroom` (AC 62, D-030). It asks
 * `showroom.search.index` for at most `search.per_source` records and links
 * each to its type's detail page, grouped by type as v1 grouped them. A 403
 * (the user may list none of the searched types) rejects, and the search
 * drops the source.
 */

export type ShowroomSearchType = components['schemas']['SearchType']

/**
 * The layer path of a search result's detail page: a product and an owner
 * by id (Showroom's default lookup key), every other type by code.
 */
export function showroomResultPath(type: ShowroomSearchType, id: string, code: string): string {
  const segment = (value: string) => encodeURIComponent(value)

  switch (type) {
    case 'products':
      return `/showroom/products/${segment(id)}`
    case 'owners':
      return `/showroom/owners/${segment(id)}`
    case 'attribute_groups':
      return `/showroom/attribute-groups/${segment(code)}`
    default:
      return `/showroom/${type}/${segment(code)}`
  }
}

/** `showroom`: `showroom.search.index`, each result linking to its type's detail page. */
export const searchShowroom: AtriumSearchSourceResolver = async (q, { limit }) => {
  const { client } = useAtriumIntegration('showroom')

  if (!client) {
    return []
  }

  const i18n = useAtriumI18n()
  const { data } = integrationData(await client.GET('/search', { params: { query: { q, limit } } }))

  return data.map((record): AtriumSearchResult => ({
    title: record.label,
    subtitle: record.subtitle,
    icon: null,
    group: i18n.t(`showroom.search.groups.${record.type}`),
    link: { path: showroomResultPath(record.type, record.id, record.code), external: false }
  }))
}
