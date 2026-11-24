import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import type { AtriumRegistry } from '@refactor-circus/atrium'
import type { AtriumSearchSource } from '@refactor-circus/atrium'
import Showroom from '../src/atrium'
import { showroomResultPath } from '../src/components/search/source'
import { withSetup } from '@refactor-circus/atrium/testing'
import { integrationEntry, integrationShell, mockPackageApi, reply, resetPackageApis, useShell, type PackageBody } from '@refactor-circus/atrium/testing'
import fixture from '@refactor-circus/atrium/fixtures/integrations/showroom.json'
import { useAtrium, useAtriumSearch } from '@refactor-circus/atrium'
import { mockAtriumApi } from '@refactor-circus/atrium/testing'

/*
 * Showroom's ⌘K source (AC 62): `showroom` asks `showroom.search.index` for
 * at most `search.per_source` records and links each to its type's detail
 * page: products and owners by id, every other type by code.
 */

const { api } = vi.hoisted(() => ({
  api: vi.fn<(path: string, options?: unknown) => Promise<unknown>>()
}))

mockAtriumApi(api)

const sources = fixture.search_sources as AtriumSearchSource[]

const records = {
  data: [
    { type: 'products', id: '01JPRODUCT00000000000000A1', code: 'SKU-001', label: 'SKU-001', subtitle: null },
    { type: 'assets', id: '01JASSET000000000000000001', code: 'hero-shot', label: 'Hero shot', subtitle: 'hero.jpg' },
    { type: 'categories', id: '01JCATEGORY000000000000001', code: 'shirts', label: 'Shirts', subtitle: 'shirts' },
    { type: 'owners', id: '01JOWNER000000000000000001', code: 'acme', label: 'Acme', subtitle: 'acme · brand' },
    { type: 'families', id: '01JFAMILY00000000000000001', code: 'apparel', label: 'Apparel', subtitle: 'apparel' },
    { type: 'attributes', id: '01JATTRIBUTE00000000000001', code: 'size', label: 'Size', subtitle: 'size · select' },
    { type: 'attribute_groups', id: '01JGROUP000000000000000001', code: 'sizing', label: 'Sizing', subtitle: 'sizing' }
  ]
} satisfies PackageBody<'showroom', 'GET /search'>

describe('the showroom search source', () => {
  let atrium: AtriumRegistry

  beforeEach(async () => {
    api.mockReset()
    api.mockResolvedValue({ data: [] })

    atrium = await withSetup(() => useAtrium())
    atrium.searchSources.clear()
    for (const [key, source] of Object.entries(Showroom.searchSources ?? {})) {
      atrium.searchSource(key, source)
    }

    await useShell(integrationShell([integrationEntry('showroom')], { search: { min_length: 2, sources, per_source: 7 } }))
  })

  afterEach(() => {
    atrium.searchSources.clear()
    resetPackageApis()
  })

  it('is the one source the fixture declares', () => {
    expect(sources.map(source => [source.key, source.integration])).toEqual([['showroom', 'showroom']])
  })

  it('asks showroom.search.index with the term and search.per_source', async () => {
    const calls = mockPackageApi('showroom', { 'GET /search': records })

    const search = await withSetup(() => useAtriumSearch())
    await search.search('sh')

    expect(calls.map(call => [`${call.method} ${call.route}`, call.query])).toEqual([
      ['GET /search', { q: 'sh', limit: '7' }]
    ])
  })

  it('links each result to its type\'s detail page, grouped by type', async () => {
    mockPackageApi('showroom', { 'GET /search': records })

    const search = await withSetup(() => useAtriumSearch())
    await search.search('sh')

    expect(search.results.value.map(result => [result.group, result.title, result.subtitle, result.link])).toEqual([
      ['Products', 'SKU-001', null, { path: '/showroom/products/01JPRODUCT00000000000000A1', external: false }],
      ['Assets', 'Hero shot', 'hero.jpg', { path: '/showroom/assets/hero-shot', external: false }],
      ['Categories', 'Shirts', 'shirts', { path: '/showroom/categories/shirts', external: false }],
      ['Owners', 'Acme', 'acme · brand', { path: '/showroom/owners/01JOWNER000000000000000001', external: false }],
      ['Families', 'Apparel', 'apparel', { path: '/showroom/families/apparel', external: false }],
      ['Attributes', 'Size', 'size · select', { path: '/showroom/attributes/size', external: false }],
      ['Attribute groups', 'Sizing', 'sizing', { path: '/showroom/attribute-groups/sizing', external: false }]
    ])

    expect(search.groups.value.map(group => group.label)).toEqual(['Products', 'Assets', 'Categories', 'Owners', 'Families', 'Attributes', 'Attribute groups'])
  })

  it('links every type to a declared screen path', () => {
    const declared = fixture.screens.map(screen => screen.path)

    for (const record of records.data) {
      const path = showroomResultPath(record.type, record.id, record.code)
      const template = path.replace(/\/[^/]+$/, '/:')

      expect(declared.some(candidate => candidate.replace(/\/:[A-Za-z]+$/, '/:') === template), `${record.type}: ${path}`).toBe(true)
    }
  })

  it('encodes a code that is not URL-safe', () => {
    expect(showroomResultPath('categories', 'x', 'men/shirts')).toBe('/showroom/categories/men%2Fshirts')
  })

  it('is dropped when the user may search none of the types (403)', async () => {
    mockPackageApi('showroom', { 'GET /search': reply(403) })

    const search = await withSetup(() => useAtriumSearch())
    await search.search('sh')

    expect(search.results.value).toEqual([])
  })

  it('asks nothing while the showroom integration is inactive', async () => {
    const calls = mockPackageApi('showroom', { 'GET /search': records })
    await useShell(integrationShell([integrationEntry('showroom', { active: false })], { search: { min_length: 2, sources, per_source: 7 } }))

    const search = await withSetup(() => useAtriumSearch())
    await search.search('sh')

    expect(calls).toEqual([])
  })
})
